<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

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
    const HEAD_VEHICLE   = [
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
        'weight', 'batch', 'description', 'car_id','created_at'
    ];

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
     * @return Attribute
     */
    protected function action(): Attribute
    {
        return Attribute::make(
            get: fn() => match ($this->getAttribute('status')) {
                'new' => [
                    [
                        'action'     => 'temporary_receipt',
                        'permission' => 'bill-of-lading.change-status.temporary_receipt',
                        'url'        => '/bill-of-lading/'.$this->getAttribute('id').'/change-status/temporary_receipt',
                    ]
                ],
                'temporary_receipt' => [
                    [
                        'action'     => 'enter',
                        'permission' => 'bill-of-lading.change-status.enter',
                        'url'        => '/bill-of-lading/'.$this->getAttribute('id').'/change-status/enter',
                    ],
                    [
                        'action'     => 'notice',
                        'permission' => 'bill-of-lading.notice',
                        'url'        => '/bill-of-lading/'.$this->getAttribute('id').'/notice',
                    ]
                ],
                'enter' => [
                    [
                        'action'     => 'discharge',
                        'permission' => 'bill-of-lading.change-status.discharge',
                        'url'        => url('/api/bill-of-lading/'.$this->getAttribute('id').'/change-status/discharge'),

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
                if ($this->vehicles()->where(function($q){
                    $q->where('data->Item_Type','Painted Body')->orWhere('data->Item_Type','Raw Body');
                })->count()) {
                    return 'bodies';
                }
                if ($this->vehicles()->where('data->Item_Type','Vehicle')->count()) {
                    return 'vehicles';
                }
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

    public static function getReportData( $data)
    {
        $query = self::query();

        if (!empty($data['from'])) {
            $query = $query->where('created_at', '>=', $data['from']);
        }
        if (!empty($data['to'])) {
            $query = $query->where('created_at', '<=', $data['to']);
        }

        return $query->get();
    }

}
