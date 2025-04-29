<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QcCode extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'item_type',
        'type',
    ];

}
