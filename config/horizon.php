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
        'redis:default' => 60,
        'redis:sms' => 10,
        'redis:printing' => 30,
        'redis:exports' => 120,
        'redis:imports' => 120,
        'redis:unique' => 60,
        'redis:parallel' => 60,
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

    'memory_limit' => 128,

    'defaults' => [
        'supervisor-default' => [
            'connection' => 'redis',
            'queue' => ['default'],
            'balance' => 'auto',
            'autoScalingStrategy' => 'time',
            'maxProcesses' => 1,
            'minProcesses' => 1,
            'maxTime' => 3600,
            'maxJobs' => 500,
            'memory' => 512,
            'tries' => 1,
            // Jobs without an explicit timeout may run for at most ten minutes.
            'timeout' => 600,
            'sleep' => 1,
            'balanceMaxShift' => 1,
            'balanceCooldown' => 3,
            'nice' => 0,
        ],
        'supervisor-sms' => [
            'connection' => 'redis',
            'queue' => ['sms'],
            'balance' => 'simple',
            'maxProcesses' => 1,
            'minProcesses' => 1,
            'maxTime' => 3600,
            'maxJobs' => 500,
            'memory' => 128,
            'tries' => 1,
            'timeout' => 60,
            'sleep' => 1,
            'nice' => 0,
        ],
        'supervisor-printing' => [
            'connection' => 'redis',
            'queue' => ['printing'],
            // Preserve print order and avoid simultaneous writes to the printers.
            'balance' => 'simple',
            'maxProcesses' => 1,
            'minProcesses' => 1,
            'maxTime' => 3600,
            'maxJobs' => 100,
            'memory' => 256,
            'tries' => 1,
            'timeout' => 120,
            'sleep' => 1,
            'nice' => 0,
        ],
        'supervisor-exports' => [
            'connection' => 'redis',
            'queue' => ['exports'],
            'balance' => 'auto',
            'autoScalingStrategy' => 'time',
            'maxProcesses' => 1,
            'minProcesses' => 1,
            'maxTime' => 3600,
            'maxJobs' => 25,
            'memory' => 1024,
            'tries' => 1,
            'timeout' => 630,
            'sleep' => 1,
            'balanceMaxShift' => 1,
            'balanceCooldown' => 3,
            'nice' => 0,
        ],
        'supervisor-imports' => [
            'connection' => 'redis',
            'queue' => ['imports'],
            'balance' => 'auto',
            'autoScalingStrategy' => 'time',
            'maxProcesses' => 1,
            'minProcesses' => 1,
            'maxTime' => 3600,
            'maxJobs' => 25,
            'memory' => 1024,
            'tries' => 1,
            'timeout' => 630,
            'sleep' => 1,
            'balanceMaxShift' => 2,
            'balanceCooldown' => 1,
            'nice' => 0,
        ],
        'supervisor-unique' => [
            'connection' => 'redis',
            'queue' => ['unique'],
            'balance' => 'simple',
            'maxProcesses' => 1,
            'minProcesses' => 1,
            'maxTime' => 3600,
            'maxJobs' => 500,
            'memory' => 256,
            'tries' => 1,
            'timeout' => 330,
            'sleep' => 1,
            'nice' => 0,
        ],
        'supervisor-parallel' => [
            'connection' => 'redis',
            'queue' => ['parallel'],
            'balance' => 'simple',
            'maxProcesses' => 1,
            'minProcesses' => 1,
            'maxTime' => 3600,
            'maxJobs' => 500,
            'memory' => 256,
            'tries' => 1,
            'timeout' => 330,
            'sleep' => 1,
            'nice' => 0,
        ],
    ],

    'environments' => [
        'production' => [
            'supervisor-default' => [
                'minProcesses' => 4,
                'maxProcesses' => 12,
                'balanceMaxShift' => 4,
                'balanceCooldown' => 1,
            ],
            'supervisor-sms' => [
                'maxProcesses' => 4,
            ],
            'supervisor-exports' => [
                'minProcesses' => 2,
                'maxProcesses' => 4,
                'balanceMaxShift' => 2,
                'balanceCooldown' => 1,
            ],
            'supervisor-imports' => [
                'minProcesses' => 2,
                'maxProcesses' => 4,
            ],
            'supervisor-parallel' => [
                'maxProcesses' => 12,
            ],
        ],
        'local' => [],
        'staging' => [],
        'testing' => [],
        '*' => [],
    ],
];
