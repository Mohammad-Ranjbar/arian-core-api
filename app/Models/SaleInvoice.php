<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SaleInvoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'sale_company_id',
        'reference',
        'online_order_id',
        'payment_status',
        'erp_status',
        'data',
        "data->message",
        'type',
        'value'
    ];
    protected $casts = [
        'data' => 'json'
    ];
}
