<?php

namespace App\Models;

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
        'user_id', 'transit_source_id', 'transit_destination_id', 'code', 'request_no',
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

    /**
     * @return MorphMany
     */
    public function actionLogs(): MorphMany
    {
        return $this->morphMany(ActionLog::class, 'model');
    }

    public function permits():MorphMany
    {
        return $this->morphMany(GatePermit::class,'owner');
    }


}
