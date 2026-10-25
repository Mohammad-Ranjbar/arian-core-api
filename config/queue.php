<?php

return [

    'default' => env('QUEUE_CONNECTION', 'sync'),

    'connections' => [
        'rabbitmq' => [
            'driver' => 'rabbitmq',
            'host' => env('RABBITMQ_HOST', 'localhost'),
            'port' => env('RABBITMQ_PORT', 5672),
            'vhost' => env('RABBITMQ_VHOST', '/'),
            'queue' => env('RABBITMQ_QUEUE', 'default'),
            'exchange_declare' => env('RABBITMQ_EXCHANGE_DECLARE', true),
            'queue_declare_bind' => env('RABBITMQ_QUEUE_DECLARE_BIND', true),
            'queue_params' => [
                'passive' => env('RABBITMQ_QUEUE_PASSIVE', false),
                'durable' => env('RABBITMQ_QUEUE_DURABLE', true),
                'exclusive' => env('RABBITMQ_QUEUE_EXCLUSIVE', false),
                'auto_delete' => env('RABBITMQ_QUEUE_AUTODELETE', false),
            ],
            'exchange_params' => [
                'name' => env('RABBITMQ_EXCHANGE_NAME', null),
                'type' => env('RABBITMQ_EXCHANGE_TYPE', 'direct'),
                'passive' => env('RABBITMQ_EXCHANGE_PASSIVE', false),
                'durable' => env('RABBITMQ_EXCHANGE_DURABLE', true),
                'auto_delete' => env('RABBITMQ_EXCHANGE_AUTODELETE', false),
            ],
        ],

        'sync' => [
            'driver' => 'sync',
        ],

        'database' => [
            'driver' => 'database',
            'table' => 'jobs',
            'queue' => 'default',
            'retry_after' => 1860,
            'after_commit' => false,
        ],

        'beanstalkd' => [
            'driver' => 'beanstalkd',
            'host' => 'localhost',
            'queue' => 'default',
            'retry_after' => 1860,
            'block_for' => 0,
            'after_commit' => false,
        ],

        'sqs' => [
            'driver' => 'sqs',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'prefix' => env('SQS_PREFIX', 'https://sqs.us-east-1.amazonaws.com/your-account-id'),
            'queue' => env('SQS_QUEUE', 'default'),
            'suffix' => env('SQS_SUFFIX'),
            'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
            'after_commit' => false,
        ],

        'redis' => [
            'driver' => 'redis',
            'connection' => 'default',
            'queue' => env('REDIS_QUEUE', 'default'),
            // Must exceed the longest job (1800s) and Horizon timeout (1830s).
            'retry_after' => 1860,
            'block_for' => 5,
            'after_commit' => false,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Job Batching
    |--------------------------------------------------------------------------
    |
    | The following options configure the database and table that store job
    | batching information. These options can be updated to any database
    | connection and table which has been defined by your application.
    |
    */

    'batching' => [
        'database' => env('DB_CONNECTION', 'mysql'),
        'table' => 'job_batches',
    ],

    /*
    |--------------------------------------------------------------------------
    | Failed Queue Jobs
    |--------------------------------------------------------------------------
    |
    | These options configure the behavior of failed queue job logging so you
    | can control which database and table are used to store the jobs that
    | have failed. You may change them to any database / table you wish.
    |
    */

    'failed' => [
        'driver' => env('QUEUE_FAILED_DRIVER', 'database-uuids'),
        'database' => env('DB_CONNECTION', 'mysql'),
        'table' => 'failed_jobs',
    ],

];
