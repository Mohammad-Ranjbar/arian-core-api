<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Alternative extends Model
{
    protected $fillable = [
        'alternative_item_id',
        'item_no',
        'document_number',
        'counter',
        'production_order_number',
        'status',
        'send_at',
        'failed_at',
        'success_at',
        'data',
    ];

    protected $casts = [
        'data' => 'array',
        'send_at' => 'datetime',
        'failed_at' => 'datetime',
        'success_at' => 'datetime',
    ];


}
