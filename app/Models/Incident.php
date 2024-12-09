<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Incident extends Model
{
    use HasFactory, SoftDeletes;

    const  HEADING = [
        'title',
        'title_en',
        'code',
    ];
    /**
     * @var string[]
     */
    protected $fillable = [
        'title',
        'title_en',
        'code',
        'weight'
    ];

    public function getHeadings(): array
    {
        return self::HEADING;
    }

}
