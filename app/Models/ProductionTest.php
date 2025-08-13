<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductionTest extends Model
{
    /**
     * @var string[]
     */
    protected $fillable = ['title','type'];

    protected $table = 'production_tests';

    /**
     * @return BelongsToMany
     */
    public function carModels(): BelongsToMany
    {
        return $this->belongsToMany(CarModel::class, 'production_test_car_model', 'production_test_id', 'car_model_id');
    }

    public function productionTestResults(): HasMany
    {
        return $this->hasMany(ProductionTestResult::class);
    }
}
