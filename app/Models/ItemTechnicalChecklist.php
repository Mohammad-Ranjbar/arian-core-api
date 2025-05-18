<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ItemTechnicalChecklist extends Model
{
    use HasFactory;

    protected $fillable = ['title'];

    protected $with = ['carModels'];

    public function carModels(): BelongsToMany
    {
        return $this->belongsToMany(CarModel::class);
    }
}
