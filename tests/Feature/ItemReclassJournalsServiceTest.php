<?php

namespace Tests\Feature;

use App\Http\Controllers\BasketController;
use App\Http\Requests\UpdateBasketBatchRequest;
use App\Services\Erp\RestApi\ItemReclassJournalsService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ItemReclassJournalsServiceTest extends TestCase
{
    private const ERP_URL = 'http://172.16.0.154:7048/PreBC/api/arianCapital/product/v1.0/ItemReclassJournals';

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('erp.auth', ['test-user', 'test-password', 'ntlm']);
        Http::preventStrayRequests();
    }

    public function test_it_posts_the_exact_sample_payload_with_shared_erp_settings(): void
    {
        $body = ['Number' => '1'];
        Http::fake(function (Request $request, array $options) use ($body) {
            $this->assertSame(['test-user', 'test-password', 'ntlm'], $options['auth']);
            $this->assertSame(5, $options['connect_timeout']);
            $this->assertSame(15, $options['timeout']);
            $this->assertFalse($options['allow_redirects']);

            return Http::response($body, 201);
        });

        $result = $this->send();

        $this->assertSame([
            'success' => true,
            'data' => $body,
            'error' => null,
            'http_status' => 201,
        ], $result);
        Http::assertSentCount(1);
        Http::assertSent(fn (Request $request) => $request->url() === self::ERP_URL
            && $request->method() === 'POST'
            && $request->hasHeader('Content-Type', 'application/json')
            && $request->hasHeader('Accept', 'application/json')
            && $request->data() === [
                'ArrayCrossReferenceNo' => 'BKJ1038538',
                'BatchNo' => 'test',
                'Number' => '1',
                'BatchName' => 'LAMARI',
            ]);
    }

    #[DataProvider('invalidPayloads')]
    public function test_invalid_input_does_not_reach_erp(array $overrides): void
    {
        Http::fake();

        $result = $this->send($overrides);

        $this->assertFalse($result['success']);
        $this->assertNull($result['data']);
        $this->assertSame('VALIDATION_ERROR', $result['error']['code']);
        $this->assertSame(422, $result['http_status']);
        Http::assertNothingSent();
    }

    public static function invalidPayloads(): array
    {
        return [
            'empty codes' => [['arrayCrossReferenceNo' => '']],
            'empty batch' => [['batchNo' => '']],
            'zero count' => [['number' => 0]],
            'fractional count' => [['number' => '1.5']],
            'empty batch name' => [['batchName' => '']],
        ];
    }

    #[DataProvider('errorResponses')]
    public function test_controller_returns_the_shared_erp_error($body, int $status, string $code, int $expectedStatus): void
    {
        Http::fake([self::ERP_URL => Http::response($body, $status)]);

        $response = resolve(BasketController::class)->sendBasketBatchBarcode(['BKJ1038538'], 'test', 1, 'LAMARI');

        $this->assertSame($expectedStatus, $response->getStatusCode());
        $this->assertSame($code, $response->getData(true)['error']['code']);
        $this->assertSame(['error'], array_keys($response->getData(true)));
        if ($code === 'Application_DialogException') {
            $this->assertSame('Insufficient inventory.', $response->getData(true)['error']['message']);
        }
        Http::assertSentCount(1);
    }

    public static function errorResponses(): array
    {
        return [
            'unauthorized' => [[], 401, 'ERP_AUTH_ERROR', 502],
            'forbidden' => [[], 403, 'ERP_AUTH_ERROR', 502],
            'business error' => [['error' => ['code' => 'Application_DialogException', 'message' => 'Insufficient inventory.']], 400, 'Application_DialogException', 422],
            'server error' => [[], 500, 'ERP_ERROR', 502],
            'unexpected error' => [['error' => ['code' => 'Unknown']], 200, 'ERP_ERROR', 502],
            'invalid json' => ['<html>failure</html>', 200, 'ERP_ERROR', 502],
        ];
    }

    public function test_controller_returns_connection_errors(): void
    {
        Http::fake(function () {
            throw new ConnectionException('Connection timed out.');
        });

        $response = resolve(BasketController::class)->sendBasketBatchBarcode(['BKJ1038538'], 'test', 1, 'LAMARI');

        $this->assertSame(503, $response->getStatusCode());
        $this->assertSame('ERP_CONNECTION_ERROR', $response->getData(true)['error']['code']);
    }

    #[DataProvider('batchResults')]
    public function test_batch_status_depends_on_erp_success_and_the_lock_is_released(bool $success): void
    {
        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        config()->set('cache.default', 'array');
        DB::purge('sqlite');
        Auth::shouldReceive('id')->andReturn(42);
        Schema::create('baskets', function (Blueprint $table) {
            $table->id();
            $table->string('code');
            $table->unsignedBigInteger('user_id');
            $table->string('status');
            $table->timestamps();
            $table->softDeletes();
        });
        DB::table('baskets')->insert([
            ['id' => 1, 'code' => 'BKJ1038538', 'user_id' => 42, 'status' => 'new'],
            ['id' => 2, 'code' => 'BKJ1038539', 'user_id' => 42, 'status' => 'new'],
            ['id' => 3, 'code' => 'OTHER', 'user_id' => 43, 'status' => 'new'],
        ]);
        $error = ['code' => 'Application_DialogException', 'message' => 'Rejected'];
        Http::fake([self::ERP_URL => Http::response($success ? ['Number' => '2'] : ['error' => $error], $success ? 201 : 400)]);
        $request = Mockery::mock(UpdateBasketBatchRequest::class);
        $request->shouldReceive('validated')->once()->andReturn(['batch' => 'test', 'batch_name' => 'LAMARI']);

        $response = resolve(BasketController::class)->batchUpdate($request);

        if ($success) {
            $this->assertTrue($response);
        } else {
            $this->assertSame(422, $response->getStatusCode());
            $this->assertSame(['error' => $error], $response->getData(true));
        }
        $this->assertSame($success ? 'submitted' : 'new', DB::table('baskets')->where('id', 1)->value('status'));
        $this->assertSame($success ? 'submitted' : 'new', DB::table('baskets')->where('id', 2)->value('status'));
        $this->assertSame('new', DB::table('baskets')->where('id', 3)->value('status'));
        Http::assertSentCount(1);
        Http::assertSent(fn (Request $request) => $request->data() === [
            'ArrayCrossReferenceNo' => 'BKJ1038538,BKJ1038539',
            'BatchNo' => 'Batch-test',
            'Number' => '2',
            'BatchName' => 'LAMARI',
        ]);
        $lock = Cache::lock('batch_update_process_42', 30);
        $this->assertTrue($lock->get());
        $lock->release();
    }

    public static function batchResults(): array
    {
        return ['success' => [true], 'rejection' => [false]];
    }

    private function send(array $overrides = []): array
    {
        return resolve(ItemReclassJournalsService::class)->send(...array_replace([
            'arrayCrossReferenceNo' => 'BKJ1038538',
            'batchNo' => 'test',
            'number' => 1,
            'batchName' => 'LAMARI',
        ], $overrides));
    }
}
