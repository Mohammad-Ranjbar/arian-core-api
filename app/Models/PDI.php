<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PDI extends Model
{
    use HasFactory;

    protected $table = 'p_d_i_s';

    protected $fillable = [
        'user_id',
        'vin',
        'pdi_result',
        'pdi_result_description',
        'pdi_result_description_2',
        'model_year',
        'engine_no',
        'police_inspection_date'
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
