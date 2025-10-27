<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Screw extends Model
{
    use HasFactory;

    protected $fillable = [
        'title', 'min', 'max', 'torque_measuring_equipment_id', 'salon_id'
    ];

    public function carModels(): BelongsToMany
    {
        return $this->belongsToMany(CarModel::class);
    }

    public function salon(): BelongsTo
    {
        return $this->belongsTo(Salon::class);
    }

    public function torqueMeasuringEquipment(): BelongsTo
    {
        return $this->belongsTo(TorqueMeasuringEquipment::class);
    }
}
