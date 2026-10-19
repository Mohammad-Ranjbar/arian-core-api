<?php

namespace App\Services;

use App\Models\BillOfLading;
use App\Models\GatePermit;
use App\Models\Location;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\Erp\RestApi\ExitPrintsService;
use App\Services\Erp\RestApi\ExitRequestsService;
use App\Services\Erp\RestApi\GroupExitsService;
use App\Services\Erp\RestApi\GroupReturnsService;
use App\Services\Erp\RestApi\ReturnRequestsService;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Throwable;

class CreateAutoGatePermitService
{
    public function __construct(
        private ExitRequestsService $exitRequests,
        private ReturnRequestsService $returnRequests,
        private GroupExitsService $groupExits,
        private GroupReturnsService $groupReturns,
        private ExitPrintsService $exitPrints,
    ) {}

    /**
     * @param  string|string[]  $items  VIN or VINs to create permits for.
     *
     * @throws Throwable
     */
    public function create(BillOfLading $billOfLading, string|array $items, $itemType, bool $batch = false): void
    {
        $batch = is_array($items) && $batch;
        $items = is_array($items) ? array_values(array_unique($items)) : [$items];
        $type = $this->permitType($billOfLading);
        if ($type === null || $items === []) {
            return;
        }
        $actorId = Auth::id() ?? $billOfLading->getAttribute('user_id');
        $returnResults = [];
        if ($type === 'enter' && ! $batch) {
            $destination = (string) Location::query()->findOrFail($billOfLading->location_destination_id)->code;
            foreach ($items as $vin) {
                if (GatePermit::query()->where('model_type', Vehicle::class)->where('model_id', $vin)->exists()) {
                    continue;
                }
                $result = $this->returnRequests->sendOnce($vin, $destination, (string) $itemType, $actorId);
                $this->jobId($result);
                $returnResults[$vin] = $result;
            }
        }

        DB::transaction(function () use ($billOfLading, $items, $itemType, $batch, $type, $actorId, $returnResults) {
            $permits = [];
            foreach ($items as $vin) {
                if (GatePermit::query()->where('model_type', Vehicle::class)->where('model_id', $vin)->exists()) {
                    continue;
                }

                $permit = GatePermit::query()->create([
                    'model_type' => Vehicle::class,
                    'model_id' => $vin,
                    'location_id' => $billOfLading->getAttribute('location_destination_id'),
                    'owner_id' => $actorId,
                    'owner_type' => User::class,
                    'type' => $type,
                    'exit_type' => 2,
                    'item_type' => $itemType,
                    'source_id' => $billOfLading->getAttribute('location_source_id'),
                ]);
                $permits[] = $permit;

                if (! $batch) {
                    $this->queueSingle($billOfLading, $permit, $itemType, $actorId, $returnResults[$vin] ?? null);
                }
            }

            if ($batch && $permits !== []) {
                $this->queueBatch($billOfLading, $permits, $itemType, $actorId);
            }
        });
    }

    private function permitType(BillOfLading $billOfLading): ?string
    {
        if ($this->hasAutoPermit($billOfLading->getAttribute('location_source_id'), 'autoExitPermit')) {
            return 'exit';
        }

        if ($this->hasAutoPermit($billOfLading->getAttribute('location_destination_id'), 'autoEnterPermit')) {
            return 'enter';
        }

        return null;
    }

    private function hasAutoPermit($locationId, string $attribute): bool
    {
        return Location::query()->whereKey($locationId)->whereHas('attributes', fn ($query) => $query
            ->where('attribute', $attribute)
            ->whereJsonContains('location_attributes.value', true))->exists();
    }

    private function queueSingle(BillOfLading $billOfLading, GatePermit $permit, $itemType, int $actorId, ?array $returnResult): void
    {
        $destination = (string) $permit->getAttribute('location')->code;
        $result = $permit->type === 'exit'
            ? $this->exitRequests->queue($permit->model_id, 2, $destination, (string) $itemType, model: $permit, userId: $actorId)
            : $returnResult;

        $jobId = $this->jobId($result);
        $this->recordRequest($billOfLading, $permit->type, $jobId, $actorId);
    }

    /** @param GatePermit[] $permits */
    private function queueBatch(BillOfLading $billOfLading, array $permits, $itemType, int $actorId): void
    {
        $permit = $permits[0];
        $vins = implode(',', array_map(fn (GatePermit $gate) => $gate->model_id, $permits));
        $destination = (string) $permit->getAttribute('location')->code;
        $result = $permit->type === 'exit'
            ? $this->groupExits->queue($vins, 2, $destination, count($permits), (string) $itemType, model: $permit, userId: $actorId)
            : $this->groupReturns->queue($vins, $destination, count($permits), (string) $itemType, model: $permit, userId: $actorId);

        $jobId = $this->jobId($result);
        $this->recordRequest($billOfLading, $permit->type, $jobId, $actorId);

        if ($permit->type === 'exit') {
            foreach ($permits as $gate) {
                $this->jobId($this->exitPrints->queue(
                    $gate->model_id, (string) $itemType, model: $gate,
                    userId: $actorId, dependsOnId: $jobId
                ));
            }
        }
    }

    private function jobId(array $result): int
    {
        if (! $result['success']) {
            throw new Exception($result['error']['message']);
        }

        return $result['data']['rest_erp_job_id'];
    }

    private function recordRequest(BillOfLading $billOfLading, string $type, int $jobId, int $actorId): void
    {
        if ($billOfLading->exists) {
            $billOfLading->actionLogs()->create([
                'user_id' => $actorId,
                'action' => $type === 'exit' ? 'rest_erp_exit_requested' : 'rest_erp_return_requested',
                'data' => ['rest_erp_job_id' => $jobId],
            ]);
            $billOfLading->unsetRelation('actionLogs');
        }
    }
}
