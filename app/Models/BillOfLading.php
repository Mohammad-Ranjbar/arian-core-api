<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class BillOfLading extends Model
{
    use HasFactory;

    /**
     * @var string[]
     */
    protected $fillable = [
        'user_id', 'transit_source_id', 'transit_destination_id', 'code', 'request_no',
        'status', 'priority', 'cottage',
        'weight', 'batch', 'description', 'car_id','type'
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
    public function transitSource(): BelongsTo
    {
        return $this->belongsTo(TransitSource::class);
    }

    /**
     * @return BelongsTo
     */
    public function transitDestination(): BelongsTo
    {
        return $this->belongsTo(TransitDestination::class);
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

}
