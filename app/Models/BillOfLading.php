<?php

namespace App\Models;

use App\Services\Erp\ErpCreateExitRequest;
use App\Services\Erp\ErpReturnRequest;
use Exception;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class BillOfLading extends Model
{
    use HasFactory, SoftDeletes;


    const HEAD_CONTAINER = [
        'container_number',
        'shipping_company',
        'vehicle_title',
        'number_plates',
        'plate_area',
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
    const HEAD_VEHICLE = [
        'VIN',
        'shipping_company',
        'vehicle_title',
        'number_plates',
        'plate_area',
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
            $query = $query->withCount([
                'containers', // Count related containers
                'vehicles as painted_body_count' => function ($query) {
                    $query->whereIn('data->Item_Type', ['Painted Body', 'Raw Body']);
                },
                'vehicles as vehicle_count' => function ($query) {
                    $query->where('data->Item_Type', 'Vehicle');
                },
            ])
                ->when(request()->get('type') === 'containers', function ($query) {
                    $query->having('containers_count', '>', 0);
                })
                ->when(request()->get('type') === 'bodies', function ($query) {
                    $query->having('painted_body_count', '>', 0);
                })
                ->when(request()->get('type') === 'vehicles', function ($query) {
                    $query->having('vehicle_count', '>', 0);
                });
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
    public function actionLogs(): MorphMany
    {
        return $this->morphMany(ActionLog::class, 'model');
    }

    /**
     * @return MorphMany
     */
    public function permits(): MorphMany
    {
        return $this->morphMany(GatePermit::class, 'owner');
    }

    /**
     * @throws Exception
     */
    public function createAutoGatePermit($items): void
    {
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

        if ($type) {
            if (GatePermit::query()
                    ->where('model_type', Vehicle::class)
                    ->whereIn('model_id', $items)->count() < count($items)) {

                foreach ($items as $vin) {
                    if (GatePermit::query()
                        ->where('model_type', Vehicle::class)
                        ->where('model_id', $vin)->exists()) {
                        continue;
                    }

                    $exitType = 2;// by shaghayegh => consignment , 1

                    $gate = GatePermit::query()->create([
                        'model_type' => Vehicle::class,
                        'model_id' => $vin,
                        'location_id' => $this->getAttribute('location_destination_id'),
                        'owner_id' => Auth::id(),
                        'owner_type' => User::class,
                        'type' => $type,
                        'exit_type' => $exitType
                    ]);

                    if ($type == 'exit') {
                        $response = ErpCreateExitRequest::createExitRequest($vin, $exitType,
                            $gate->getAttribute('location')->code);

                        if ($response['status'] !== 'ok') {
                            throw new Exception($response['message']);
                        }
                    } else {
                        $response = ErpReturnRequest::returnRequest($vin, $gate->getAttribute('location')->code);

                        if ($response['status'] !== 'ok') {
                            throw new Exception($response['message']);
                        }
                    }
                }
            }
        }
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
                        'url' => url('/api/bill-of-lading/' . $this->getAttribute('id') . '/change-status/discharge'),

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
                if ($this->containers()->count()) {
                    return 'containers';
                }
                if ($this->vehicles()->where(function ($q) {
                    $q->where('data->Item_Type', 'Painted Body')->orWhere('data->Item_Type', 'Raw Body');
                })->count()) {
                    return 'bodies';
                }
                if ($this->vehicles()->where('data->Item_Type', 'Vehicle')->count()) {
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
            'bill_of_lading_item'
        );
    }

    public function vehicles(): MorphToMany
    {
        return $this->morphedByMany(
            Vehicle::class,
            'model',
            'bill_of_lading_item'
        );
    }

    public function canReturn(): bool
    {
        return $this->actionLogs()->where('action','discharge')->exists();
    }

}
