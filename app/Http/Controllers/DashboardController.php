<?php

namespace App\Http\Controllers;

use App\Models\ProductionLine;
use App\Models\Tracking;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(): JsonResponse
    {
        if (env('APP_ENV') === 'production') {
            return response()->json([]);
        }
        $linesTracking = Tracking::query()
            ->with('model')
            ->where('created_at','>=',Carbon::today())
            ->where('created_at','<=',Carbon::tomorrow())
            ->where('model_type',ProductionLine::class)
            ->get()->groupBy('model_id');
        dd($linesTracking);
        return response()->json([
            [
                [
                    'title' => fake('fa_IR')->title,
                    'type' => 'table',
                    'heads' => [
                        fake('fa_IR')->title,
                        fake('fa_IR')->title,
                        fake('fa_IR')->title
                    ],
                    'items' => [
                        [1,2,3],
                        [4,5,6],
                        [7,8,9]
                    ]
                ],
                [
                    'title' => fake('fa_IR')->title,
                    'type' => 'table',
                    'heads' => [
                        fake('fa_IR')->title,
                        fake('fa_IR')->title,
                        fake('fa_IR')->title
                    ],
                    'items' => [
                        [1,2,3],
                        [4,5,6],
                        [7,8,9]
                    ]
                ],
                [
                    'title' => fake('fa_IR')->title,
                    'type' => 'table',
                    'heads' => [
                        fake('fa_IR')->title,
                        fake('fa_IR')->title,
                        fake('fa_IR')->title
                    ],
                    'items' => [
                        [1,2,3],
                        [4,5,6],
                        [7,8,9]
                    ]
                ],
                [
                    'title' => fake('fa_IR')->title,
                    'type' => 'table',
                    'heads' => [
                        fake('fa_IR')->title,
                        fake('fa_IR')->title,
                        fake('fa_IR')->title
                    ],
                    'items' => [
                        [1,2,3],
                        [4,5,6],
                        [7,8,9]
                    ]
                ],
            ],
            [
                [
                    'title' => fake('fa_IR')->title,
                    'type' => 'table',
                    'heads' => [
                        fake('fa_IR')->title,
                        fake('fa_IR')->title,
                        fake('fa_IR')->title
                    ],
                    'items' => [
                        [1,2,3],
                        [4,5,6],
                        [7,8,9]
                    ]
                ],
                [
                    'title' => fake('fa_IR')->title,
                    'type' => 'table',
                    'heads' => [
                        fake('fa_IR')->title,
                        fake('fa_IR')->title,
                        fake('fa_IR')->title
                    ],
                    'items' => [
                        [1,2,3],
                        [4,5,6],
                        [7,8,9]
                    ]
                ],
            ],
            [
                [
                    'title' => fake('fa_IR')->title,
                    'type' => 'table',
                    'heads' => [
                        fake('fa_IR')->title,
                        fake('fa_IR')->title,
                        fake('fa_IR')->title
                    ],
                    'items' => [
                        [1,2,3],
                        [4,5,6],
                        [7,8,9]
                    ]
                ],
            ]
        ]);
    }
}
