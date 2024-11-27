<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class YearMap extends Model
{
    use HasFactory;

    protected $fillable = [
        'year',
        'chr'
    ];
}
