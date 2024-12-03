<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
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

    public function cabinNumber(): Attribute
    {
        return  Attribute::make(
            get: fn () => 'R'.$this->getAttribute('serial_1_chr').
                $this->getAttribute('body_option_chr').
                $this->getAttribute('color_chr').
                $this->getAttribute('serial_2_chr').
                $this->getAttribute('year_chr').
                str_pad($this->getAttribute('counter'), 4, '0', STR_PAD_LEFT)
            ,
        );
    }

    public static function getReportData(array $data)
    {
        $query = self::query();
        if (!empty($data['from'])) {
            $query = $query->where('created_at', '>=', $data['from']);
        }
        if (!empty($data['to'])) {
            $query = $query->where('created_at', '<=', $data['to']);
        }
        return $query->get();
    }
}
