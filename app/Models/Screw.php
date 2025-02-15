<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Screw extends Model
{
    use HasFactory;

    protected $fillable = [
        'title', 'min', 'max'
    ];

    public function carModels(): BelongsToMany
    {
        return $this->belongsToMany(CarModel::class);
    }
}
