<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Station extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * @var string[]
     */
    protected $fillable = [
        'title',
        'code',
        'work_group_center_id'
    ];

    /**
     * @return BelongsTo
     */
    public function workGroupCenter(): BelongsTo
    {
        return $this->belongsTo(WorkGroupCenter::class);
    }

    /**
     * @return BelongsToMany
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withPivot('data');
    }

    public function sendErp(): MorphOne
    {
        return $this->morphOne(SendErp::class, 'model');
    }
}
