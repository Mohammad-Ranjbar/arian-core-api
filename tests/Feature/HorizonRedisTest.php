<?php

namespace Tests\Feature;

use App\Jobs\ExportJob;
use App\Jobs\SmsJob;
use App\Models\Job;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Queue\WorkerOptions;
use Illuminate\Redis\RedisManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Laravel\Horizon\Horizon;
use Symfony\Component\Process\Exception\ProcessSignaledException;
use Symfony\Component\Process\Process;
use Tests\Fixtures\QueueProbeJob;
use Tests\Fixtures\TimeoutExportProbeJob;
use Tests\TestCase;

class HorizonRedisTest extends TestCase
{
    private ?Process $redisServer = null;

    private ?Process $horizon = null;

    private int $port;

    private string $prefix;

    protected function setUp(): void
    {
        parent::setUp();

        if (! is_executable('/usr/local/bin/redis-server') && ! is_executable('/usr/bin/redis-server')) {
            $this->markTestSkipped('A local redis-server binary is required for the isolated integration test.');
        }

        $listener = stream_socket_server('tcp://127.0.0.1:0');
        $this->port = (int) substr(strrchr(stream_socket_get_name($listener, false), ':'), 1);
        fclose($listener);
        $this->prefix = 'queue-test-'.Str::uuid().':';
        $binary = is_executable('/usr/local/bin/redis-server') ? '/usr/local/bin/redis-server' : '/usr/bin/redis-server';
        $this->redisServer = new Process([$binary, '--bind', '127.0.0.1', '--port', (string) $this->port, '--save', '', '--appendonly', 'no']);
        $this->redisServer->start();
        $this->await(fn () => str_contains($this->redisServer->getOutput(), 'Ready to accept connections'));

        config([
            'database.redis.client' => 'predis',
            'database.redis.options.prefix' => $this->prefix,
            'database.redis.default' => ['host' => '127.0.0.1', 'port' => $this->port, 'database' => 0],
            'database.redis.cache' => ['host' => '127.0.0.1', 'port' => $this->port, 'database' => 1],
            'horizon.prefix' => $this->prefix.'horizon:',
            'queue.default' => 'redis',
            'queue.failed.driver' => 'null',
        ]);
        Horizon::use('default');
        // RedisManager snapshots its configuration when constructed; purge alone
        // reconnects using that old snapshot, so replace it before resolving queues.
        Redis::swap(new RedisManager($this->app, 'predis', config('database.redis')));
        $this->assertSame(0, (int) Redis::dbsize());
    }

    protected function tearDown(): void
    {
        if ($this->horizon?->isRunning()) {
            $this->horizon->signal(SIGTERM);
            $this->horizon->wait();
        }
        $this->redisServer?->stop(2);

        parent::tearDown();
    }

    public function test_sms_worker_consumes_sms_while_an_export_is_reserved(): void
    {
        Http::preventStrayRequests();
        Http::fake(['*' => Http::response(['result' => 'ok'])]);
        dispatch(new ExportJob((string) Str::uuid()));
        $queue = Queue::connection('redis');
        $export = $queue->pop('exports');
        $this->assertNotNull($export);

        $expiry = Redis::zscore('queues:exports:reserved', $export->getReservedJob());
        $this->assertEqualsWithDelta(time() + 1860, (float) $expiry, 2);

        dispatch(new SmsJob('09120000000', 'test'));
        $this->app['queue.worker']->runNextJob('redis', 'sms', new WorkerOptions);

        Http::assertSentCount(1);
        $this->assertSame(0, $queue->size('sms'));
        $this->assertSame(1, $queue->size('exports'));
        $export->delete();
    }

    public function test_real_horizon_runs_queues_independently_and_finishes_active_job_on_sigint(): void
    {
        $this->horizon = new Process([PHP_BINARY, base_path('artisan'), 'horizon', '--environment=testing'], base_path(), $this->childEnvironment());
        $this->horizon->setTimeout(30);
        $this->horizon->start();

        dispatch((new QueueProbeJob('long', 8))->onQueue('exports'));
        $this->await(fn () => Redis::get('long:started') === '1');
        dispatch((new QueueProbeJob('fast'))->onQueue('sms'));
        $this->await(fn () => Redis::get('fast:finished') === '1');
        $this->assertNull(Redis::get('long:finished'));

        $this->horizon->signal(SIGINT);
        $this->assertSame(0, $this->horizon->wait(), $this->horizon->getErrorOutput());
        $this->assertSame('1', Redis::get('long:finished'));
    }

    public function test_worker_timeout_marks_custom_job_failed_and_removes_the_reservation(): void
    {
        $database = tempnam(sys_get_temp_dir(), 'queue-timeout-');
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => $database]);
        DB::purge('sqlite');
        Schema::create('custom_jobs', function (Blueprint $table): void {
            $table->uuid('uuid')->primary();
            $table->string('type');
            $table->string('status')->default('pending');
            $table->json('result')->nullable();
            $table->timestamps();
        });

        // --once uses runNextJob() without the daemon's SIGALRM timeout handler.
        $worker = new Process([PHP_BINARY, base_path('artisan'), 'queue:work', 'redis', '--queue=exports', '--stop-when-empty', '--sleep=0', '--tries=1'], base_path(), array_merge($this->childEnvironment(), ['DB_DATABASE' => $database]));
        $worker->setTimeout(15);

        try {
            $record = Job::create(['type' => 'timeout-test']);
            dispatch(new TimeoutExportProbeJob($record->uuid));
            try {
                $worker->run();
                $this->fail('The worker must terminate after the timeout.');
            } catch (ProcessSignaledException $exception) {
                // Laravel's timeout handler records the failure before killing itself.
                $this->assertSame(SIGKILL, $exception->getSignal());
            }
            $this->assertSame('failed', $record->fresh()->status);
            $this->assertArrayHasKey('message', $record->fresh()->result);
            $this->assertSame(0, Queue::connection('redis')->size('exports'));
        } finally {
            $worker->stop(2);
            DB::disconnect('sqlite');
            unlink($database);
        }
    }

    private function childEnvironment(): array
    {
        return [
            'APP_ENV' => 'testing',
            'APP_CONFIG_CACHE' => sys_get_temp_dir().'/'.$this->prefix.'missing-config.php',
            'QUEUE_CONNECTION' => 'redis',
            'REDIS_CLIENT' => 'predis',
            'REDIS_URL' => false,
            'REDIS_HOST' => '127.0.0.1',
            'REDIS_PORT' => (string) $this->port,
            'REDIS_USERNAME' => '',
            'REDIS_PASSWORD' => '',
            'REDIS_DB' => '0',
            'REDIS_CACHE_DB' => '1',
            'REDIS_PREFIX' => $this->prefix,
            'HORIZON_PREFIX' => $this->prefix.'horizon:',
            'CACHE_DRIVER' => 'array',
            'LOG_CHANNEL' => 'stderr',
            'QUEUE_FAILED_DRIVER' => 'null',
            'DB_CONNECTION' => 'sqlite',
            'DB_DATABASE' => ':memory:',
            'SENTRY_LARAVEL_DSN' => '',
        ];
    }

    private function await(callable $condition): void
    {
        $deadline = microtime(true) + 15;
        while (! $condition()) {
            if (microtime(true) >= $deadline) {
                $this->fail('Timed out waiting for isolated Redis/Horizon: '.($this->horizon?->getOutput() ?? '').($this->horizon?->getErrorOutput() ?? ''));
            }
            usleep(50000);
        }
    }
}
