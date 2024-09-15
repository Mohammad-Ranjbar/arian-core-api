<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class GeneralProductItem extends Model
{
    protected $fillable = [
        'title',
        'code',
        'priority',
        'height'
    ];

    protected $with = ['carModels'];

    public function carModels(): BelongsToMany
    {
        return $this->BelongsToMany(CarModel::class);
    }
}
