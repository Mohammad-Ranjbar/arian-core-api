<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Driver extends Model
{
    use HasFactory;

    /**
     * @var string[]
     */
    protected $fillable = [
        'name',
        'national_code',
        'phone',
        'bank_information'
    ];

    /**
     * @return HasOne
     */
    public function car(): HasOne
    {
        return $this->hasOne(Car::class);
    }
}
