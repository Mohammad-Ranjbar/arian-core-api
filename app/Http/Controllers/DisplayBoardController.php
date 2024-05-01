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
                'type'     => 'production-board',
                'duration' => 8000,
                'data'     => [
                    'title'       => 'خط تولید جک',
                    'planned'     => 100,
                    'expectation' => 20,
                    'percentage'  => -20,
                    'deviation'   => 3,
                    'line_status' => true,
                    'description' => ''
                ]
            ],
            [
                'type'     => 'production-board',
                'duration' => 8000,
                'data'     => [
                    'title'       => 'خط تولید شک',
                    'planned'     => 20,
                    'expectation' => 20,
                    'percentage'  => -20,
                    'deviation'   => 3,
                    'line_status' => false,
                    'description' => 'کسری قطعه بدنه'
                ]
            ],
            [
                'type'     => 'image-board',
                'duration' => 5000,
                'data'     => [
                    'url' => 'http://172.16.0.87/_next/image?url=%2FArianPars.png&w=96&q=75'
                ]
            ],
            [
                'type'     => 'text-board',
                'duration' => 9000,
                'data'     => [
                    'text' => 'متن دلخواه
                    خط دوم متن
                    به نظرم وسط تابلو باشه'
                ]
            ],
        ]);
    }
}
