<?php

namespace App\Http\Controllers;

use App\Models\Salon;
use App\Services\Erp\ErpDisplayBoard;
use Illuminate\Http\Request;

class DisplayBoardController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, Salon $salon)
    {
        $data = ErpDisplayBoard::getData();
        // TODO
        // Add a column to production line: location_code

        return response()->json([
            [
                'title' => 'خط تولید جک',
                'planned' => 100,
                'expectation' => 20,
                'percentage' => -20,
                'deviation' => 3
            ],
            [
                'title' => 'خط تولید شک',
                'planned' => 20,
                'expectation' => 20,
                'percentage' => -20,
                'deviation' => 3
            ],
        ]);
    }
}
