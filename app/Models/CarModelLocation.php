<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CarModelLocation extends Model
{

    protected $fillable = ['title', 'code', 'car_model_id'];

    public function carModel(): BelongsTo
    {
        return $this->belongsTo(CarModel::class);
    }

}
