<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PendingInvoiceController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        $list = DB::select("
SELECT DISTINCT
    sale_companies.title,
    sale_invoices.sale_company_id,
    sale_invoices.reference,
    JSON_EXTRACT(sale_invoices.data, '$.message') AS message
FROM
    `sale_invoices`
left join 
        sale_companies on sale_invoices.sale_company_id = sale_companies.id
WHERE
    `payment_status` = 'paid' AND `erp_status` = 'pending';
        ");

        return response()->json(array_map(function ($row) {
            if ($row->sale_company_id === 3) {
                $row->url = 'https://sales.lamari.ir/api/check-customer-finance/'.$row->reference;
            }
            if ($row->sale_company_id === 2) {
                $row->url = 'https://sales.arian-motor.com/api/check-customer-finance/'.$row->reference;
            }
            if ($row->sale_company_id === 1) {
                $row->url = 'https://sales.arian-diesel.com/api/check-customer-finance/'.$row->reference;
            }
            $row->reference .= ' '.$row->title;
            return $row;
        },$list));
    }
}
