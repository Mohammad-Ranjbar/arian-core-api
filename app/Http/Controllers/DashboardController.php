<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Models\ProductionLine;
use App\Models\SaleLocation;
use App\Models\Tracking;
use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $startTime = Carbon::today();
        $endTime = Carbon::tomorrow();

        $linesTracking = Tracking::query()
            ->with('model')
            ->where('created_at', '>=', $startTime)
            ->where('created_at', '<', $endTime)
            ->where('model_type', ProductionLine::class)
            ->get()->groupBy('model_id');

        $salesTracking = Tracking::query()
            ->with('model')
            ->where('created_at', '>=', $startTime)
            ->where('created_at', '<', $endTime)
            ->where('model_type', SaleLocation::class)
            ->get()->groupBy('model_id');

        $sql = "
                SELECT
                    car_models.title,
                    action_logs.`data` as `code`,
                    COUNT(action_logs.`data`) as `count`
                FROM
                    `action_logs`
                LEFT JOIN vehicles ON action_logs.model_id = vehicles.vin
                LEFT JOIN car_models ON vehicles.car_model_id = car_models.id
                WHERE
                    `action_logs`.`action` = 'update_qc_status' AND 
                    `action_logs`.`model_type` = ? AND
                    `action_logs`.`created_at` >= ? AND 
                    `action_logs`.`created_at` < ?
                GROUP BY
                    `action_logs`.`data`, car_models.id;        
        ";
        $qcActionLogs = collect(DB::select($sql, [Vehicle::class, $startTime, $endTime]));

        $rows = [];
        foreach ($qcActionLogs->groupBy('title') as $title => $list) {
            $rows[] = [
                'title' => $title,
                'type'  => 'table',
                'heads' => [
                    'کد کیفی',
                    'تعداد',
                ],
                'items' => $list->map(function ($row) {
                    return [
                        $row->code,
                        $row->count,
                    ];
                })->values()
            ];
        }


        $sql = "
SELECT
    car_models.title,
    production_tracking.type,
    COUNT(*) as count
FROM
    `production_tracking`
    LEFT JOIN
        vehicles 
    ON 
        production_tracking.vin = vehicles.vin
    LEFT JOIN 
        car_models
    ON 
        vehicles.car_model_id = car_models.id
WHERE
    production_tracking.`model_type` = ? and 
    production_tracking.created_at >= ? and 
    production_tracking.created_at < ?
GROUP BY 
    car_models.title, production_tracking.type  
        ";
        $gateTracking = collect(DB::select($sql, [Location::class, $startTime, $endTime]));

        return response()->json(
            [
                [
                    'title' => 'درب ورود و خروج',
                    'type' => 'table',
                    'heads' => [
                        'عنوان',
                        'نوع',
                        'تعداد',
                    ],
                    'items' => $gateTracking->map(function ($row) {
                        return array_values($row);
                    })
                ],
                $linesTracking->map(function ($lineTracking) {
                    return [
                        'title' => $lineTracking->first()->model->title,
                        'type'  => 'table',
                        'heads' => [
                            'ورود',
                            'خروج',
                        ],
                        'items' => [
                            [
                                $lineTracking->where('type', 'enter')->count(),
                                $lineTracking->where('type', 'exit')->count(),
                            ]
                        ]
                    ];
                })->values(),
                $salesTracking->map(function ($saleTracking) {
                    return [
                        'title' => $saleTracking->first()->model->title,
                        'type'  => 'table',
                        'heads' => [
                            'ورود',
                        ],
                        'items' => [
                            [
                                $saleTracking->where('type', 'enter')->count(),
                            ]
                        ]
                    ];
                })->values(),
                $rows
            ]
        );
    }
}
