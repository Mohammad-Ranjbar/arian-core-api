<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VinEngine extends Model
{
    use HasFactory;

    protected $fillable = ['vin','engine_no','production_order_number','cabin_no'];
    const  HEADING = [
        'vin',
        'engine_no',
        'cabin_no',
    ];

    public function getHeadings(): array
    {
        return self::HEADING;
    }

}
