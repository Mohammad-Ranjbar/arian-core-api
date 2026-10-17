<?php

namespace App\Models;

// use App\Services\Erp\ErpReturnRequest;
use App\Services\Erp\RestApi\ExitPrintsService;
use App\Services\Erp\RestApi\ExitRequestsService;
use App\Services\Erp\RestApi\GroupExitsService;
use App\Services\Erp\RestApi\GroupReturnsService;
use App\Services\Erp\RestApi\ReturnRequestsService;
use Exception;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Throwable;

class BillOfLading extends Model
{
    use SoftDeletes;


    const array HEAD_CONTAINER = [
        'container_number',
        'shipping_company',
        'vehicle_title',
        'plate_area',
        '3_digit_plate',
        'char_plate',
        '2_digit_plate',
        'driver_name',
        'driver_mobile',
        'driver_national_code',
        'freight_number',
        'request_number',
        'cottage_number',
        'weight',
        'batch',
        'description',
    ];
    const array HEAD_VEHICLE = [
        'VIN',
        'shipping_company',
        'vehicle_title',
        'plate_area',
        '3_digit_plate',
        'char_plate',
        '2_digit_plate',
        'driver_name',
        'driver_mobile',
        'driver_national_code',
        'freight_number',
        'request_number',
        'cottage_number',
        'weight',
        'batch',
        'description',
    ];

    /**
     * @var string[]
     */
    protected $fillable = [
        'user_id', 'location_source_id', 'location_destination_id', 'code', 'request_no',
        'status', 'priority', 'cottage',
        'weight', 'batch', 'description', 'car_id', 'created_at'
    ];


    /**
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     */
    public static function getReportData($data)
    {
        $query = self::query();

        if (!empty($data['from'])) {
            $query = $query->where('created_at', '>=', $data['from']);
        }
        if (!empty($data['to'])) {
            $query = $query->where('created_at', '<=', $data['to']);
        }
        if (!empty($data['type'])) {
            if ($data['type'] == 'containers') {
                $query = $query->whereHas('containers', function ($q) {
                    $q->where('type', 'containers');
                });
            }
            if ($data['type'] == 'vehicles') {
                $query = $query->whereHas('vehicles', function ($q) {
                    $q->where('type', 'vehicles');
                });
            }
            if ($data['type'] == 'bodies') {
                $query = $query->whereHas('vehicles', function ($q) {
                    $q->where('type', 'bodies');
                });
            }

        }


        return $query->get();
    }

    /**
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo
     */
    public function locationSource(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'location_source_id');
    }

    /**
     * @return BelongsTo
     */
    public function locationDestination(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'location_destination_id');
    }

    /**
     * @return BelongsTo
     */
    public function car(): BelongsTo
    {
        return $this->belongsTo(Car::class);
    }

    /**
     * @return MorphMany
     */
    public function permits(): MorphMany
    {
        return $this->morphMany(GatePermit::class, 'owner');
    }


    /**
     * @throws Throwable
     */
    public function createAutoGatePermit($items, $itemType = null, $batch = false): void
    {
        DB::transaction(function () use ($items, $itemType, $batch) {
            $actorId = Auth::id() ?? $this->getAttribute('user_id');
            $exitIds = Location::query()->whereHas('attributes', function ($q) {
                $q->where('attribute', 'autoExitPermit')->where('value', 'true');
            })->get()->pluck('id')->all();
            $enterIds = Location::query()->whereHas('attributes', function ($q) {
                $q->where('attribute', 'autoEnterPermit')->where('value', 'true');
            })->get()->pluck('id')->all();
            $type = null;
            if (in_array($this->getAttribute('location_source_id'), $exitIds)) {
                $type = 'exit';
            } elseif (in_array($this->getAttribute('location_destination_id'), $enterIds)) {
                $type = 'enter';
            }
            dd($exitIds, $type, $batch);
            if ($type) {
                if (GatePermit::query()->where('model_type', Vehicle::class)->whereIn('model_id', $items)->count() < count($items))
                {

                    foreach ($items as $vin) {
                        if (GatePermit::query()
                            ->where('model_type', Vehicle::class)
                            ->where('model_id', $vin)->exists()) {
                            continue;
                        }

                        $exitType = 2;

                        $gate = GatePermit::query()->create([
                            'model_type' => Vehicle::class,
                            'model_id' => $vin,
                            'location_id' => $this->getAttribute('location_destination_id'),
                            'owner_id' => $actorId,
                            'owner_type' => User::class,
                            'type' => $type,
                            'exit_type' => $exitType,
                            'item_type' => $itemType,
                            'source_id' => $this->getAttribute('location_source_id')
                        ]);
                        dd($gate);
                        if ($type == 'exit') {
                            if (!$batch) {
                                // $response = ErpCreateExitRequest::createExitRequest($vin, $exitType,
                                //     $gate->getAttribute('location')->code, $itemType);
                                // if ($response['status'] !== 'ok') {
                                //     throw new Exception($response['message']);
                                // }
                                $result = resolve(ExitRequestsService::class)->queue(
                                    $vin, $exitType, (string)$gate->getAttribute('location')->code, (string)$itemType,
                                    model: $gate, userId: $actorId
                                );
                                if (!$result['success']) {
                                    throw new Exception($result['error']['message']);
                                }
                                $this->recordRestExitRequest($result['data']['rest_erp_job_id'], $actorId);
                            }

                        } else
                        {
                            if (!$batch) {
                                // $response = ErpReturnRequest::returnRequest($vin, $gate->getAttribute('location')->code, $itemType);
                                // if ($response['status'] !== 'ok') {
                                //     throw new Exception($response['message']);
                                // }
                                $result = resolve(ReturnRequestsService::class)->queue(
                                    $vin, (string)$gate->getAttribute('location')->code, (string)$itemType,
                                    model: $gate, userId: $actorId
                                );
                                if (!$result['success']) {
                                    throw new Exception($result['error']['message']);
                                }
                                $this->recordRestReturnRequest($result['data']['rest_erp_job_id'], $actorId);
                            }
                        }
                    }

                    if ($batch) {
                        if ($type == 'enter') {
                            // $response = ErpReturnRequest::returnBatchRequest($items, $gate->getAttribute('location')->code, $itemType);
                            // if ($response['status'] !== 'ok') {
                            //     throw new Exception($response['message']);
                            // }
                            $result = resolve(GroupReturnsService::class)->queue(
                                implode(',', $items), (string)$gate->getAttribute('location')->code,
                                count($items), (string)$itemType, model: $gate, userId: $actorId
                            );
                            if (!$result['success']) {
                                throw new Exception($result['error']['message']);
                            }
                            $this->recordRestReturnRequest($result['data']['rest_erp_job_id'], $actorId);
                        }
                        elseif ($type == 'exit') {
                            dd('is here');
                            // $response = ErpCreateExitRequest::createBatchExitRequest($items, $exitType,
                            //     $gate->getAttribute('location')->code, $itemType);
                            // if ($response['status'] !== 'ok') {
                            //     throw new Exception($response['message']);
                            // }
                            $result = resolve(GroupExitsService::class)->queue(
                                implode(',', $items), $exitType, (string)$gate->getAttribute('location')->code,
                                count($items), (string)$itemType, model: $gate, userId: $actorId
                            );
                            if (!$result['success']) {
                                throw new Exception($result['error']['message']);
                            }
                            $this->recordRestExitRequest($result['data']['rest_erp_job_id'], $actorId);
                            $permits = GatePermit::query()->where('model_type', Vehicle::class)->whereIn('model_id', $items)->get();
                            foreach ($permits as $permit) {
                                $print = resolve(ExitPrintsService::class)->queue(
                                    $permit->getAttribute('model_id'), (string)$itemType, model: $permit,
                                    userId: $actorId, dependsOnId: $result['data']['rest_erp_job_id']
                                );
                                if (!$print['success']) {
                                    throw new Exception($print['error']['message']);
                                }
                            }
                        }
                    }
                }
            }
        });
    }

    private function recordRestReturnRequest(int $jobId, int $actorId): void
    {
        if ($this->exists) {
            $this->actionLogs()->create([
                'user_id' => $actorId,
                'action' => 'rest_erp_return_requested',
                'data' => ['rest_erp_job_id' => $jobId],
            ]);
            $this->unsetRelation('actionLogs');
        }
    }

    private function recordRestExitRequest(int $jobId, int $actorId): void
    {
        if ($this->exists) {
            $this->actionLogs()->create([
                'user_id' => $actorId,
                'action' => 'rest_erp_exit_requested',
                'data' => ['rest_erp_job_id' => $jobId],
            ]);
            $this->unsetRelation('actionLogs');
        }
    }

    /**
     * @return MorphMany
     */
    public function actionLogs(): MorphMany
    {
        return $this->morphMany(ActionLog::class, 'model');
    }

    public function canReturn(): bool
    {
        return $this->actionLogs()->where('action', 'discharge')->exists() && !$this->actionLogs()->where('action', ActionLog::RETURN_CONTAINER_AT)->exists();
    }

    public function statusTime(string $status)
    {
        return $this->actionLogs()->where('action', $status)->first()?->getAttribute('created_at') ?? false;
    }

    /**
     * @return Attribute
     */
    protected function action(): Attribute
    {
        return Attribute::make(
            get: fn() => match ($this->getAttribute('status')) {
                'new' => [
                    [
                        'action' => 'temporary_receipt',
                        'permission' => 'bill-of-lading.change-status.temporary_receipt',
                        'url' => '/bill-of-lading/' . $this->getAttribute('id') . '/change-status/temporary_receipt',
                    ]
                ],
                'temporary_receipt' => [
                    [
                        'action' => 'enter',
                        'permission' => 'bill-of-lading.change-status.enter',
                        'url' => '/bill-of-lading/' . $this->getAttribute('id') . '/change-status/enter',
                    ],
                    [
                        'action' => 'notice',
                        'permission' => 'bill-of-lading.notice',
                        'url' => '/bill-of-lading/' . $this->getAttribute('id') . '/notice',
                    ]
                ],
                'enter' => [
                    [
                        'action' => 'discharge',
                        'permission' => 'bill-of-lading.change-status.discharge',
                        'url' => '/bill-of-lading/' . $this->getAttribute('id') . '/change-status/discharge',

                    ]
                ],
                default => []
            },
        );
    }

    /**
     * @return Attribute
     */
    protected function type(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->containers()->wherePivot('type', 'containers')->count()) {
                    return 'containers';
                }
                if ($this->vehicles()->wherePivot('type', 'bodies')->count()) {
                    return 'bodies';
                }
                if ($this->vehicles()->wherePivot('type', 'vehicles')->count()) {
                    return 'vehicles';
                }
                return 'unknown';
            }
        );
    }

    public function containers(): MorphToMany
    {
        return $this->morphedByMany(
            Container::class,
            'model',
            'bill_of_lading_item')->withPivot(['type']);
    }

    public function vehicles(): MorphToMany
    {
        return $this->morphedByMany(
            Vehicle::class,
            'model',
            'bill_of_lading_item'
        )->withPivot(['type']);
    }

}
