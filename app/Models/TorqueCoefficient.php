<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TorqueCoefficient extends Model
{
    use HasFactory;

    protected $fillable = [
        'low_1',
        'low_2',
        'low_3',
        'high_1',
        'high_2',
        'high_3',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
