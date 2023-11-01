<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tracking extends Model
{
    use HasFactory;
    protected $table = 'production_tracking';

    /**
     * @var string[]
     */
    protected $fillable =[
        'model_type',
        'model_id',
        'user_id',
        'type',
        'vin',
    ];
}
