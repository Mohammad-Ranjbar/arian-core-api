<?php

use Illuminate\Support\Str;

return [


    'domain' => env('HORIZON_DOMAIN'),


    'path' => env('HORIZON_PATH', 'horizon'),


    'use' => 'default',


    'prefix' => env(
        'HORIZON_PREFIX',
        Str::slug(env('APP_NAME', 'laravel'), '_').'_horizon:'
    ),


    'middleware' => ['web'],

    'waits' => [
        'redis:default' => 6000,
    ],


    'trim' => [
        'recent' => 60,
        'pending' => 60,
        'completed' => 60,
        'recent_failed' => 10080,
        'failed' => 10080,
        'monitored' => 10080,
    ],


    'silenced' => [
        // App\Jobs\ExampleJob::class,
    ],


    'metrics' => [
        'trim_snapshots' => [
            'job' => 24,
            'queue' => 24,
        ],
    ],

    'fast_termination' => false,


    'memory_limit' => 20000,


    'defaults' => [
        'supervisor-1' => [
            'connection' => 'redis',
            'queue' => ['default','unique','parallel'],
            'balance' => 'auto',
            'autoScalingStrategy' => 'time',
            'maxProcesses' => 5,
            'maxTime' => 0,
            'maxJobs' => 5,
            'memory' => 8192,
            'tries' => 2,
            'timeout' => 1000000,
            'nice' => 0,
        ],
    ],

    'environments' => [
        'production' => [
            'supervisor-1' => [
                'maxProcesses' => 5,
                'balanceMaxShift' => 2,
                'balanceCooldown' => 4,
            ],
        ],

        'local' => [
            'supervisor-1' => [
                'maxProcesses' => 5,
            ],
        ],
    ],
];
