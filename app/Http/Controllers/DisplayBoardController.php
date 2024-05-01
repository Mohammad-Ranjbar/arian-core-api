<?php

namespace App\Http\Controllers;

use App\Models\Salon;
use App\Services\Erp\ErpDisplayBoard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Octane\Exceptions\DdException;

class DisplayBoardController extends Controller
{

    /**
     * @param Request $request
     * @param Salon $salon
     * @return JsonResponse
     */
    public function __invoke(Request $request, Salon $salon): JsonResponse
    {
        $data = ErpDisplayBoard::getData();


        return response()->json([
            [
                'type' => 'production-board',
                'duration' => 8000,
                'data' => [
                    'title' => 'خط تولید جک',
                    'planned' => 100,
                    'expectation' => 20,
                    'percentage' => -20,
                    'deviation' => 3
                ]
            ],
            [
                'type' => 'production-board',
                'duration' => 8000,
                'data' => [
                    'title' => 'خط تولید شک',
                    'planned' => 20,
                    'expectation' => 20,
                    'percentage' => -20,
                    'deviation' => 3
                ]
            ],
            [
                'type' => 'image-board',
                'duration' => 5000,
                'data' => [
                    'url' => 'http://172.16.0.87/_next/image?url=%2FArianPars.png&w=96&q=75'
                ]
            ],
            [
                'type' => 'text-board',
                'duration' => 9000,
                'data' => [
                    'text' => 'متن دلخواه
                    خط دوم متن
                    به نظرم وسط تابلو باشه'
                ]
            ],
        ]);
    }
}
