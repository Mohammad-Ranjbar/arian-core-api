<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property mixed $id
 */
class IncidentSource extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * @var string[]
     */
    protected $fillable = [
        'element_id', 'incident_id', 'vin', 'car_model_id', 'model_type', 'model_id',
    ];

    public static function resourceRelations(): array
    {
        return [
            'carModel' => fn ($query) => $query->without('locations')->select(['id', 'title']),
            'element:id,title',
            'incident:id,title',
            'selections:id,incident_source_id,model_type,model_id,user_id,created_at',
            'selections.model' => fn (MorphTo $relation) => $relation->morphWith([
                WorkGroupCenter::class => ['ProductionLine:id,title'],
            ]),
            'selections.user:id,name',
        ];
    }

    public function carModel(): BelongsTo
    {
        return $this->belongsTo(CarModel::class);
    }

    public function element(): BelongsTo
    {
        return $this->belongsTo(Element::class);
    }

    public function incident(): BelongsTo
    {
        return $this->belongsTo(Incident::class);
    }

    public function selections(): HasMany
    {
        return $this->hasMany(IncidentSourceSelection::class)->orderBy('id');
    }

    public function model(): MorphTo
    {
        return $this->morphTo();
    }
}
