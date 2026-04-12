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
        'redis:default' => 60000,
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

    'memory_limit' => 16000,

    'defaults' => [
        'supervisor-default' => [
            'connection' => 'redis',
            'queue' => ['default'], // Only default queue
            'balance' => 'simple', // FIFO processing for sequential jobs
            'processes' => 10, // Single process = sequential processing
            'autoScalingStrategy' => 'time',
            'maxProcesses' => 10, // Must be 1 for sequential
            'minProcesses' => 5, // Keep always 1 process
            'maxTime' => 0,
            'maxJobs' => 5,
            'memory' => 16000,
            'tries' => 0,
            'timeout' => 10000000,
            'nice' => 0,
        ],
        'supervisor-unique' => [
            'connection' => 'redis',
            'queue' => ['unique'], // Only unique queue
            'balance' => 'simple', // FIFO processing
            'processes' => 1, // Single process
            'autoScalingStrategy' => 'time',
            'maxProcesses' => 1, // Must be 1
            'minProcesses' => 1,
            'maxTime' => 0,
            'maxJobs' => 0,
            'memory' => 16000,
            'tries' => 1,
            'timeout' => 10000000,
            'nice' => 0,
        ],
        'supervisor-parallel' => [
            'connection' => 'redis',
            'queue' => ['parallel'], // Only parallel queue
            'balance' => 'simple', // FIFO processing
            'processes' => 1, // Single process
            'autoScalingStrategy' => 'time',
            'maxProcesses' => 1, // Must be 1
            'minProcesses' => 1,
            'maxTime' => 0,
            'maxJobs' => 0,
            'memory' => 16000,
            'tries' => 1,
            'timeout' => 10000000,
            'nice' => 0,
        ],
    ],

    'environments' => [
        'production' => [
            'supervisor-default' => [
                'maxProcesses' => 1,
                'balanceMaxShift' => 1,
                'balanceCooldown' => 1,
                'processes' => 1,
            ],
            'supervisor-unique' => [
                'maxProcesses' => 1,
                'balanceMaxShift' => 1,
                'balanceCooldown' => 1,
                'processes' => 1,
            ],
            'supervisor-parallel' => [
                'maxProcesses' => 1,
                'balanceMaxShift' => 1,
                'balanceCooldown' => 1,
                'processes' => 1,
            ],
        ],

        'local' => [
            'supervisor-default' => [
                'maxProcesses' => 1,
                'processes' => 1,
            ],
            'supervisor-unique' => [
                'maxProcesses' => 1,
                'processes' => 1,
            ],
            'supervisor-parallel' => [
                'maxProcesses' => 1,
                'processes' => 1,
            ],
        ],

        'staging' => [
            'supervisor-default' => [
                'maxProcesses' => 1,
                'processes' => 1,
            ],
            'supervisor-unique' => [
                'maxProcesses' => 1,
                'processes' => 1,
            ],
            'supervisor-parallel' => [
                'maxProcesses' => 1,
                'processes' => 1,
            ],
        ],

        'testing' => [
            'supervisor-default' => [
                'maxProcesses' => 1,
                'processes' => 1,
            ],
            'supervisor-unique' => [
                'maxProcesses' => 1,
                'processes' => 1,
            ],
            'supervisor-parallel' => [
                'maxProcesses' => 1,
                'processes' => 1,
            ],
        ],
    ],
];
