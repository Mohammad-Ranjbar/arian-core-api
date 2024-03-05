<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class BillOfLading extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * @var string[]
     */
    protected $fillable = [
        'user_id', 'location_source_id', 'location_destination_id', 'code', 'request_no',
        'status', 'priority', 'cottage',
        'weight', 'batch', 'description', 'car_id'
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
     * @return MorphToMany
     */
    public function containers(): MorphToMany
    {
        return $this->morphedByMany(Container::class, 'model', 'bill_of_lading_item');
    }

    /**
     * @return MorphToMany
     */
    public function vehicles(): MorphToMany
    {
        return $this->morphedByMany(Vehicle::class, 'model', 'bill_of_lading_item');
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

}
