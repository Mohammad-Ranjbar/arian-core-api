<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ColorMap extends Model
{
    use HasFactory;

    protected $fillable=[
        'title',
        'code',
        'color_code'
    ];

}
