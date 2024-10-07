<?php

namespace App\Http\Controllers;

use App\Models\ProductionLine;
use App\Models\Tracking;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $linesTracking = Tracking::query()
            ->with('model')
            ->where('created_at','>=',Carbon::today())
            ->where('created_at','<',Carbon::tomorrow())
            ->where('model_type', ProductionLine::class)
            ->get()->groupBy('model_id');
        if ($linesTracking) {
            return response()->json(
                [
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
                                    $lineTracking->where('type','enter')->count(),
                                    $lineTracking->where('type','exit')->count(),
                                ]
                            ]
                        ];
                    })->values()
                ]);
        }
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
