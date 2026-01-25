<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EnterEngine extends Model
{
    use HasFactory;

    protected $fillable = [
        'engine_no',
        'production_line_id',
        'user_id',
        'make_code_id',
        'counter'
    ];

    public function productionLine(): BelongsTo
    {
        return $this->belongsTo(ProductionLine::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reserveVehicle(): BelongsTo
    {
        return $this->belongsTo(VinEngine::class, 'engine_no', 'engine_no');
    }


    public function vin(): Attribute
    {
        return Attribute::get(fn() => $this->reserveVehicle?->vin ?? '');
    }

    public function makeCode(): BelongsTo
    {
        return $this->belongsTo(MakeCode::class);
    }

    public static function computeSynchronousCounter(int $productionLineId):int
    {
      return  self::query()->where('production_line_id',$productionLineId)->max('counter') + 1 ;
    }
}
