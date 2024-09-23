<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductionTestResult extends Model
{
    protected $fillable = [
        'vin',
        'files',
        'description',
        'production_test_id',
        'user_id'
    ];
    protected $casts = ['files' => 'json'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function productionTest(): BelongsTo
    {
        return $this->belongsTo(ProductionTest::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class,'vin');
    }
}
