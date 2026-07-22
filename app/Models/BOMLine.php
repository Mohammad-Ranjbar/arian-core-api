<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BOMLine extends Model
{
    protected $fillable = [
        'no',
        'type',
        'version',
        'data',
    ];

  protected $casts = [
      'data' => 'array',
  ];
}
