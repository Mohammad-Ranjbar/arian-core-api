<?php

namespace App\Models;

use App\Services\Erp\SendErpTrait;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Station extends Model
{
    use HasFactory, SoftDeletes,SendErpTrait;

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

    /**
     * @return bool
     */
    public function isEnterUpperLocation(): bool
    {
        return Tracking::query()
            ->where('model_type', ProductionLine::class)
            ->where('model_id', $this->getAttribute('workGroupCenter')->ProductionLine->id)
            ->where('type', 'enter')
            ->exists();
    }

    /**
     * @return Attribute
     */
    protected function upperLocation(): Attribute
    {
        return Attribute::make(
            get: fn() => [
                'is_upper_location' => true,
                'upper_id'          => $this->getAttribute('workGroupCenter')->ProductionLine->id,
                'upper_class'       => ProductionLine::class
            ],
        );
    }

}
