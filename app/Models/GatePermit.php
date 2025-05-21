<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class GatePermit extends Model
{

    use HasFactory, SoftDeletes;

    /**
     * @var string[]
     */
    protected $fillable = [
        'model_type', 'model_id', 'type', 'location_id', 'owner_id', 'owner_type', 'exit_type', 'item_type', 'source_id'
    ];

    public function billOfLading(): Attribute
    {
        return Attribute::get(function () {
            return GatePermit::query()->join('bill_of_lading_item', function ($join) {
                $join->on('gate_permits.model_id', '=', 'bill_of_lading_item.model_id')
                    ->join('bill_of_ladings', 'bill_of_lading_item.bill_of_lading_id', '=', 'bill_of_ladings.id');
            })
                ->select( 'bill_of_ladings.*')
                ->latest('bill_of_ladings.created_at')
                ->first();
        });
    }


    /**
     * @return MorphTo
     */
    public function model(): MorphTo
    {
        return $this->morphTo();
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'source_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function owner(): MorphTo
    {
        return $this->morphTo();
    }

}
