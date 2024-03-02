<?php

namespace App\Services\Erp;

use App\Models\SendErp;
use Illuminate\Database\Eloquent\Relations\MorphOne;

trait SendErpTrait
{
    public function sendErp(): MorphOne
    {
        return $this->morphOne(SendErp::class, 'model');
    }
}