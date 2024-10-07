<?php

namespace App\Http\Controllers;

use App\Models\ActionLog;
use App\Models\ProductionLine;
use App\Models\Tracking;
use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

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

        $qcActionLogs = ActionLog::query()
            ->with(['model','model.carModel'])
            ->where('action', 'update_qc_status')
            ->where('created_at', '>=', $startTime)
            ->where('created_at', '<', $endTime)
            ->where('model_type', Vehicle::class)
            ->get()->groupBy('model.car_model_id');


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
            [
                $qcActionLogs->map(function ($carModelActionLogs) {
                    $row = $carModelActionLogs->first();
                    return [
                        'title' => print_r($row->model,1),
                        'type'  => 'table',
                        'heads' => [
                            'کد کیفی',
                            'تعداد',
                        ],
                        'items' => $carModelActionLogs->groupBy('data')->map(function ($actions) use($row){
                            return [
                                (string)$row->data,
                                $actions->count()
                            ];
                        })->values()
                    ];
                })->values()
            ]
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
