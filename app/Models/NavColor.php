<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NavColor extends Model
{
    protected $fillable = ['color', 'code', 'car_model_id'];

    public function carModel(): BelongsTo
    {
        return $this->belongsTo(CarModel::class);
    }
}
