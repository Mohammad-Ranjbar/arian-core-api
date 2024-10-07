<?php

namespace App\Http\Controllers;

use App\Models\ActionLog;
use App\Models\ProductionLine;
use App\Models\Tracking;
use App\Models\Vehicle;
use Carbon\Carbon;
use Exception;
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
        $qcActionLogs = collect(DB::select($sql,[Vehicle::class,$startTime,$endTime]));

        $rows = [];
        foreach ($qcActionLogs->groupBy('title') as $title => $list){
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

        return response()->json(
            [
                $linesTracking->map(function ($lineTracking) {
                    return [
                        'title' => $lineTracking->first()->model->title.'(امروز)',
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
                })->values()
            ],
            [$rows]
        );

        return response()->json([
            [
                [
                    'title' => fake('fa_IR')->title,
                    'type'  => 'table',
                    'heads' => [
                        fake('fa_IR')->title,
                        fake('fa_IR')->title,
                        fake('fa_IR')->title
                    ],
                    'items' => [
                        [1, 2, 3],
                        [4, 5, 6],
                        [7, 8, 9]
                    ]
                ],
                [
                    'title' => fake('fa_IR')->title,
                    'type'  => 'table',
                    'heads' => [
                        fake('fa_IR')->title,
                        fake('fa_IR')->title,
                        fake('fa_IR')->title
                    ],
                    'items' => [
                        [1, 2, 3],
                        [4, 5, 6],
                        [7, 8, 9]
                    ]
                ],
                [
                    'title' => fake('fa_IR')->title,
                    'type'  => 'table',
                    'heads' => [
                        fake('fa_IR')->title,
                        fake('fa_IR')->title,
                        fake('fa_IR')->title
                    ],
                    'items' => [
                        [1, 2, 3],
                        [4, 5, 6],
                        [7, 8, 9]
                    ]
                ],
                [
                    'title' => fake('fa_IR')->title,
                    'type'  => 'table',
                    'heads' => [
                        fake('fa_IR')->title,
                        fake('fa_IR')->title,
                        fake('fa_IR')->title
                    ],
                    'items' => [
                        [1, 2, 3],
                        [4, 5, 6],
                        [7, 8, 9]
                    ]
                ],
            ],
            [
                [
                    'title' => fake('fa_IR')->title,
                    'type'  => 'table',
                    'heads' => [
                        fake('fa_IR')->title,
                        fake('fa_IR')->title,
                        fake('fa_IR')->title
                    ],
                    'items' => [
                        [1, 2, 3],
                        [4, 5, 6],
                        [7, 8, 9]
                    ]
                ],
                [
                    'title' => fake('fa_IR')->title,
                    'type'  => 'table',
                    'heads' => [
                        fake('fa_IR')->title,
                        fake('fa_IR')->title,
                        fake('fa_IR')->title
                    ],
                    'items' => [
                        [1, 2, 3],
                        [4, 5, 6],
                        [7, 8, 9]
                    ]
                ],
            ],
            [
                [
                    'title' => fake('fa_IR')->title,
                    'type'  => 'table',
                    'heads' => [
                        fake('fa_IR')->title,
                        fake('fa_IR')->title,
                        fake('fa_IR')->title
                    ],
                    'items' => [
                        [1, 2, 3],
                        [4, 5, 6],
                        [7, 8, 9]
                    ]
                ],
            ]
        ]);
    }
}
