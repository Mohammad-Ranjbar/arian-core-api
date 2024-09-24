<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ProductionTest extends Model
{
    /**
     * @var string[]
     */
    protected $fillable = ['title'];

    protected $table = 'production_tests';
    /**
     * @return BelongsToMany
     */
    public function carModels(): BelongsToMany
    {
        return $this->belongsToMany(CarModel::class,'production_test_car_model','production_test_id','car_model_id');
    }
}
