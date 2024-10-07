<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(): JsonResponse
    {
        if (env('APP_ENV') === 'production') {
            return response()->json([]);
        }
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
