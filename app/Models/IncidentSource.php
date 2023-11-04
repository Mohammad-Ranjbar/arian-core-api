<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class IncidentSource extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'element_id',
        'incident_id',
        'vin',
        'car_model_id',
        'model_type',
        'model_id'
    ];
}
