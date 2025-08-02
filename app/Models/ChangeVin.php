<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChangeVin extends Model
{
    use HasFactory;

    protected $fillable = [
        'vin',
        'old_vin',
        'production_order_number',
        'data',
    ];
}
