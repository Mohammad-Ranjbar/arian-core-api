<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class TorqueTestGroup extends Model
{
    use HasFactory;

    protected $fillable = ['title'];

    public function carModels(): BelongsToMany
    {
        return $this->belongsToMany(CarModel::class);
    }

    public function torqueMeasuringEquipments(): BelongsToMany
    {
        return $this->belongsToMany(TorqueMeasuringEquipment::class);
    }
}
