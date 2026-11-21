<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PDI extends Model
{
    protected $table = 'p_d_i_s';
    const  array HEADING_POLICE = [
        'vin',
        'police_inspection_date',
    ];
    const  array HEADING_PDI = [
        'vin',
        'pdi_result',
        'pdi_result_description',
        'pdi_result_description_2',

    ];

    const  array HEADING_ENGINE = [
        'vin',
        'model_year',
        'engine_no',
    ];

    protected $fillable = [
        'user_id',
        'vin',
        'pdi_result',
        'pdi_result_description',
        'pdi_result_description_2',
        'mileage',
        'model_year',
        'engine_no',
        'police_inspection_date'
    ];

    protected $casts = [
        'mileage' => 'string',
    ];

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class, 'vin');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
