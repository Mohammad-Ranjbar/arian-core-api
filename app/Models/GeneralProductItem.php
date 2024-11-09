<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class GeneralProductItem extends Model
{
    /**
     * @var string[]
     */
    protected $fillable = [
        'title',
        'priority',
        'height',
        'type'
    ];

    /**
     * @var string[]
     */
    protected $with = ['carModels'];

    /**
     * @return BelongsToMany
     */
    public function carModels(): BelongsToMany
    {
        return $this->BelongsToMany(CarModel::class);
    }
}
