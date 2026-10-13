<?php

namespace App\Services\Erp\RestApi;

use App\Models\BillOfLading;
use App\Models\GatePermit;
use App\Models\RestErpJob;
use Illuminate\Database\Eloquent\Collection;

class RestErpJobLinksService
{
    public function forGatePermit(GatePermit $permit): Collection
    {
        $direct = RestErpJob::query()->where('model_type', $permit->getMorphClass())->where('model_id', $permit->getKey());

        // Group jobs can reference another permit in the batch; each print carries its exact parent ID.
        return RestErpJob::query()->where(function ($query) use ($direct) {
            $query->whereIn('id', (clone $direct)->select('id'))
                ->orWhereIn('id', (clone $direct)->whereNotNull('depends_on_id')->select('depends_on_id'));
        })->latest('id')->get();
    }

    public function forBillOfLading(BillOfLading $bill): Collection
    {
        // Use recorded job IDs, not VIN matching, which can attach a previous bill's requests.
        $ids = $bill->actionLogs->where('action', 'rest_erp_exit_requested')
            ->pluck('data.rest_erp_job_id')->filter()->unique()->values()->all();

        $jobs = RestErpJob::query()->where(function ($query) use ($ids) {
            $query->whereIn('id', $ids)->orWhereIn('depends_on_id', $ids);
        })->latest('id')->get();

        $permitIds = $jobs->where('model_type', (new GatePermit)->getMorphClass())->pluck('model_id')->unique()->all();
        $approvals = RestErpJob::query()->where('model_type', (new GatePermit)->getMorphClass())
            ->whereIn('model_id', $permitIds)->whereIn('endpoint', ['ApproveExitZones', 'ApproveReturnZones'])->get();

        return $jobs->merge($approvals)->unique('id')->sortByDesc('id')->values();
    }
}
