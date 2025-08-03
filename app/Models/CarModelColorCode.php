<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CarModelColorCode extends Model
{
    use HasFactory;

    protected $with = ['carModel'];
    protected $fillable = [
        'car_model_id',
        'color_code',
        'variant'
    ];

    public function carModel(): BelongsTo
    {
        return $this->belongsTo(CarModel::class);
    }
}
