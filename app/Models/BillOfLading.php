<?php

namespace App\Models;

use App\Services\CreateAutoGatePermitService;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
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
    public function createAutoGatePermit($items, $itemType, $batch = false): void
    {
        resolve(CreateAutoGatePermitService::class)->create($this, $items, $itemType, $batch);
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
