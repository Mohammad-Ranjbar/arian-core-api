<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ErpProductItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'reference',
        'tracking_code',
        'title',
        'description',
        'type',
    ];
}
