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
    CONCAT(
        'https://sales.lamari.ir/api/check-customer-finance/',
        reference
    ) AS url,
    reference,
    JSON_EXTRACT(DATA, '$.message') AS message
FROM
    `sale_invoices`
WHERE
    `sale_company_id` = 3 AND `payment_status` = 'paid' AND `erp_status` = 'pending';
        ");

        return response()->json($list);
    }
}
