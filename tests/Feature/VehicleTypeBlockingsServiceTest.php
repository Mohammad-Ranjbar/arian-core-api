<?php

namespace Tests\Feature;

use App\Http\Controllers\BlockItemController;
use App\Models\BlockItem;
use App\Models\BlockReason;
use App\Models\RestErpJob;
use App\Repositories\BlockItemRepository;
use App\Repositories\BlockReasonRepository;
use App\Services\Erp\RestApi\VehicleTypeBlockingsService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\CreatesRestErpTables;
use Tests\TestCase;

class VehicleTypeBlockingsServiceTest extends TestCase
{
    use CreatesRestErpTables;

    private const ERP_URL = 'http://172.16.0.154:7048/PreBC/api/arianCapital/product/v1.0/VehicleTypeBlockings';

    protected function setUp(): void
    {
        parent::setUp();
        $this->createRestErpTables();
        Schema::create('block_items', function (Blueprint $table) {
            $table->id();
            $table->string('vin');
            $table->unsignedBigInteger('blocked_by');
            $table->unsignedBigInteger('block_reason_id');
            $table->softDeletes();
            $table->timestamps();
        });
        config()->set('rest_erp.base_url', dirname(self::ERP_URL));
        config()->set('erp.auth', ['test-user', 'test-password', 'ntlm']);
        Http::preventStrayRequests();
        Queue::fake();
    }

    private function createBlock(string $blockType = 'cop', bool $success = true): BlockItem
    {
        $data = ['vin' => 'N5RL42DA5SP018986', 'blocked_by' => 43, 'block_reason_id' => 7];
        $repository = Mockery::mock(BlockItemRepository::class);
        if ($success) {
            $repository->shouldReceive('store')->once()->with($data)->andReturnUsing(function (array $data) {
                $this->assertSame('success', RestErpJob::query()->sole()->status);

                return BlockItem::query()->create($data);
            });
        } else {
            $repository->shouldNotReceive('store');
        }
        $reasons = Mockery::mock(BlockReasonRepository::class);
        $reasons->shouldReceive('findBy')->once()->with(7, 'id')
            ->andReturn(new BlockReason(['block_type' => $blockType]));

        return (new BlockItemController($repository, $reasons))->createBlock($data);
    }

    public function test_create_block_posts_synchronously_and_saves_only_after_success(): void
    {
        Http::fake(function (Request $request, array $options) {
            $this->assertSame(['test-user', 'test-password', 'ntlm'], $options['auth']);
            $this->assertDatabaseCount('block_items', 0);

            return Http::response(['accepted' => true], 201);
        });
        $record = $this->createBlock();
        $payload = ['BarcodeInfo' => 'N5RL42DA5SP018986', 'BlockType' => 'cop'];
        $job = RestErpJob::query()->sole();
        $this->assertSame($payload, $job->parameters);
        $this->assertSame('VehicleTypeBlockings', $job->endpoint);
        $this->assertSame(VehicleTypeBlockingsService::class, $job->service);
        $this->assertSame('مسدودسازی خودرو', $job->section);
        $this->assertNull($job->model_type);
        $this->assertNull($job->model_id);
        $this->assertDatabaseHas('block_items', ['id' => $record->id, 'vin' => $payload['BarcodeInfo']]);
        $this->assertSame(43, $job->user_id);
        $this->assertSame('sync', $job->execution_mode);
        Queue::assertNothingPushed();
        $this->assertDatabaseCount('erp_jobs', 0);

        Http::assertSentCount(1);
        Http::assertSent(fn (Request $request) => $request->url() === self::ERP_URL
            && $request->method() === 'POST' && $request->data() === $payload
            && $request->hasHeader('Content-Type', 'application/json'));
        $this->assertSame('success', $job->refresh()->status);
        $this->assertTrue($job->actionLogs()->where('action', 'rest_erp_success')->exists());
    }

    public function test_create_block_surfaces_rejected_payload_and_keeps_the_rejected_job(): void
    {
        try {
            $this->createBlock('', false);
            $this->fail('Expected invalid BlockType to be rejected.');
        } catch (\RuntimeException $exception) {
            $this->assertNotEmpty($exception->getMessage());
        }
        $this->assertSame('rejected', RestErpJob::query()->sole()->status);
        $this->assertDatabaseCount('block_items', 0);
        Queue::assertNothingPushed();
        Http::assertNothingSent();
    }

    public static function failures(): array
    {
        return [
            'business' => [['error' => ['code' => 'Application_DialogException', 'message' => 'Blocking rejected.']], 400, 'failed'],
            'authentication' => [[], 401, 'failed'],
            'server' => [[], 500, 'uncertain'],
            'connection' => [null, 0, 'uncertain'],
        ];
    }

    #[DataProvider('failures')]
    public function test_provider_failure_keeps_job_and_logs_without_creating_block(?array $body, int $status, string $jobStatus): void
    {
        Http::fake(function () use ($body, $status) {
            if ($status === 0) {
                throw new ConnectionException('Connection timed out.');
            }

            return Http::response($body, $status);
        });
        try {
            $this->createBlock('cop', false);
            $this->fail('Expected ERP failure to prevent local creation.');
        } catch (\RuntimeException $exception) {
            $this->assertNotEmpty($exception->getMessage());
        }
        $this->assertDatabaseCount('block_items', 0);
        Queue::assertNothingPushed();
        $job = RestErpJob::query()->sole();
        $this->assertSame($jobStatus, $job->refresh()->status);
        $this->assertNotEmpty($job->description);
        $this->assertTrue($job->actionLogs()->where('action', 'rest_erp_'.$jobStatus)->exists());
    }

    public function test_send_supports_synchronous_execution(): void
    {
        Http::fake([self::ERP_URL => Http::response([], 200)]);

        $result = resolve(VehicleTypeBlockingsService::class)->send('N5RL42DA5SP018986', 'cop');

        $this->assertTrue($result['success']);
        $this->assertSame('sync', RestErpJob::query()->sole()->execution_mode);
        Queue::assertNothingPushed();
    }
}
