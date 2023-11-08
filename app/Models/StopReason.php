<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class StopReason extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * @var string[]
     */
    protected $fillable = [
        'title',
        'stop_fact_id'
    ];

    /**
     * @return BelongsTo
     */
    public function fact(): BelongsTo
    {
        return $this->belongsTo(StopFact::class,'stop_fact_id');
    }
}
