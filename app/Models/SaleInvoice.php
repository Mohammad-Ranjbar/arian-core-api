<?php

namespace App\Models;

use App\Jobs\MoveInvitationToSaleOnlineApplicationJob;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
    protected $casts    = [
        'data' => 'json'
    ];

    public function saleCompany(): BelongsTo
    {
        return $this->belongsTo(SaleCompany::class);
    }

    public function sendToOnline(): void
    {
        MoveInvitationToSaleOnlineApplicationJob::dispatch($this->getAttribute('id'));
    }
}
