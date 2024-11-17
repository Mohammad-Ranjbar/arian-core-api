<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ErpServiceItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'reference',
        'title',
        'description',
        'location_title',
    ];
}
