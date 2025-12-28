<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CarModelFile extends Model
{
    use HasFactory;

    protected $fillable = ['title', 'file_id', 'car_model_id'];

    public function file(): BelongsTo
    {
        return $this->belongsTo(File::class, 'file_id', 'uuid');
    }

    public function carModel(): BelongsTo
    {
        return $this->belongsTo(CarModel::class);
    }

    public function carModelFiles(): HasMany
    {
        return $this->hasMany(CarModelFile::class);
    }
}
