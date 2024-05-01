<?php

namespace App\Http\Controllers;

use App\Models\Salon;
use App\Services\Erp\ErpDisplayBoard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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

        $lines = $salon->productionLines()->get();
        $locations = $lines->pluck('location_code')->all();

        $list = collect($data['data'])->whereIn('Location', $locations);
        $result = $list->map(function ($item) use ($lines) {
            $line = $lines->where('location_code', $item->Location)->first();
            $lineStatus = $line->isStarted();
            return [
                'type' => 'production-board',
                'duration' => 8000,
                'data' => [
                    'title' => $line->title,
                    'planned' => $item->Qty_Planned,
                    'expectation' => 20,
                    'percentage' => -20,
                    'deviation' => 3,
                    'line_status' => $lineStatus,
                    'description' => $lineStatus ? '' : $line->getLatestActivity()->stopReason->title,
                    'in' => $line->trackingCount('enter', date('Y-m-d ') . $item->Starting_Time, date('Y-m-d ') . $item->Ending_Time),
                    'out' => $item->Day_Output
                ]
            ];
        });

        dd($result);

        return response()->json([

            [
                'type' => 'production-board',
                'duration' => 8000,
                'data' => [
                    'title' => 'خط تولید شک',
                    'planned' => 20,
                    'expectation' => 20,
                    'percentage' => -20,
                    'deviation' => 3,
                    'line_status' => false,
                    'description' => 'کسری قطعه بدنه',
                    'in' => 41,
                    'out' => 88,
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
