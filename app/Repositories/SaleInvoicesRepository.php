<?php

namespace App\Repositories;

use App\libs\BaseRepository;
use App\Models\SaleInvoices;

class SaleInvoicesRepository extends BaseRepository
{
    /**
     * @var array|array[]
     */
    protected array $rules = [
        'create' => [
            'sale_company_id' => 'required|integer|exists:sale_companies,id',
            'reference' => 'required|string|unique:sale_invoices,reference',
            'online_order_id' => 'nullable',
            'payment_status' => 'string|in:paid,pending',
            'erp_status' => 'string|in:submitted,pending',
            'data' => 'nullable',
            'type' => 'string',
            'value' => 'string',
        ], 'update' => [
            'sale_company_id' => 'required|integer|exists:sale_companies,id',
            'reference' => 'required|string|unique:sale_invoices,reference,',
            'online_order_id' => 'nullable',
            'payment_status' => 'string|in:paid,pending',
            'erp_status' => 'string|in:submitted,pending',
            'data' => 'nullable',
            'type' => 'string',
            'value' => 'string',
        ]
    ];

    /**
     * @param SaleInvoices $model
     */
    public function __construct(SaleInvoices $model)
    {
        $this->model = $model;
        if (request()->route()) {
            $this->rules['update']['reference'] .= request()->route()->parameter('saleInvoice');
        }
    }

}
