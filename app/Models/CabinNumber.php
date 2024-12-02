<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CabinNumber extends Model
{
    use HasFactory;

    /**
     * @var string[]
     */
    protected $fillable = [
        'serial_1_chr',
        'serial_2_chr',
        'color_chr',
        'year_chr',
        'body_option_chr',
        'counter',
        'used',
        'user_id',
        'car_model_id'
    ];

    /**
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo
     */
    public function carModel(): BelongsTo
    {
        return $this->belongsTo(CarModel::class);
    }
}
