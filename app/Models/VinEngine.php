<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VinEngine extends Model
{
    use HasFactory;

    protected $fillable = ['vin','engine_no','production_order_number'];


}
