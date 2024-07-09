<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SaleCompany extends Model
{
    use HasFactory;

    /**
     * @var string[]
     */
    protected $fillable = [
        'title',
        'folder_name',
        'site_url',
        'erp_url',
        'invitation',
        'contract',
        'erp_contract_url',
        'sync_order_url',
    ];


    /**
     * @return BelongsToMany
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(SaleInvoice::class);
    }
}
