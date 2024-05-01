<?php

namespace App\Http\Controllers;

use App\Models\Salon;
use App\Services\Erp\ErpDisplayBoard;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DisplayBoardController extends Controller
{

    /**
     * @param  Request  $request
     * @param  Salon  $salon
     * @return JsonResponse
     */
    public function __invoke(Request $request, Salon $salon): JsonResponse
    {
        $data = ErpDisplayBoard::getData();

        $lines = $salon->productionLines()->get();
        $locations = $lines->pluck('location_code')->all();

        $list = collect($data['data'])->whereIn('Location', $locations);
        $result = $list->map(function ($item) use ($lines) {

            $now = Carbon::now();
            $workTime = 0;
            $maxTime = 0;
            $outs = $item->Day_Output;
            $planned = $item->Qty_Planned;

            if (!isset($item->Time)) {
                $s = Carbon::make($item->Starting_Time);
                $e = Carbon::make($item->Ending_Time);

                if ($now->greaterThan($e)) {
                    $workTime += $s->diffInSeconds($e);
                } else {
                    $workTime += $s->diffInSeconds($now);
                }

                $maxTime += $s->diffInSeconds($e);

            } else {
                foreach ($item->Time as $time) {
                    $s = Carbon::make($time->InShiftStartTime);
                    $e = Carbon::make($time->InShiftEndTime);
                    if ($now->greaterThan($s)) {
                        if ($now->greaterThan($e)) {
                            $workTime += $s->diffInSeconds($e);
                        } else {
                            $workTime += $now->diffInSeconds($s);
                        }
                    }
                    $maxTime += $s->diffInSeconds($e);
                }
            }


            if ($planned and $workTime) {
                $avg = floor($maxTime / $planned);
                $expectation = floor($workTime / $avg);
            } else {
                $expectation = 0;
            }
            $deviation = $expectation - $outs;

            $percentage = '0';
            if ($planned) {
                $percentage = round(($outs * 100) / $planned);
            }

            $line = $lines->where('location_code', $item->Location)->first();
            $lineStatus = $line->isStarted();
            return [
                'type'     => 'production-board',
                'duration' => 8000,
                'data'     => [
                    'title'       => $line->title,
                    'planned'     => $planned,
                    'expectation' => $expectation,
                    'percentage'  => $percentage,
                    'deviation'   => $deviation,
                    'line_status' => $lineStatus,
                    'description' => $lineStatus ? '' : $line->getLatestActivity()->stopReason->title,
                    'in'          => $line->trackingCount('enter', date('Y-m-d ').$item->Starting_Time,
                        date('Y-m-d ').$item->Ending_Time),
                    'out'         => $outs
                ]
            ];
        });

        dd($result);


        return response()->json([

            [
                'type'     => 'production-board',
                'duration' => 8000,
                'data'     => [
                    'title'       => 'خط تولید شک',
                    'planned'     => 20,
                    'expectation' => 20,
                    'percentage'  => -20,
                    'deviation'   => 3,
                    'line_status' => true,
                    'description' => '',
                    'in'          => 41,
                    'out'         => 88,
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
                    'description' => 'کسری قطعه بدنه',
                    'in'          => 41,
                    'out'         => 88,
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
