<?php

namespace App\Models;

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
        'model_type', 'model_id', 'type', 'location_id', 'owner_id', 'owner_type', 'exit_type'
    ];


    /**
     * @return MorphTo
     */
    public function model(): MorphTo
    {
        return $this->morphTo();
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
