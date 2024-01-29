<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Element extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * @var string[]
     */

    const  HEADING = [
        'title',
        'title_en',
        'code'
    ];
    /**
     * @var string[]
     */
    protected $fillable = [
        'title',
        'title_en',
        'code',
    ];
}
