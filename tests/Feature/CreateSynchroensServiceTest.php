<?php

namespace Tests\Feature;

use App\Jobs\SendToRestErpJob;
use App\Models\RestErpJob;
use App\Services\Erp\RestApi\CreateSynchroensService;
use App\Services\Erp\RestApi\RestErpExecutionMode;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\CreatesRestErpTables;
use Tests\TestCase;

class CreateSynchroensServiceTest extends TestCase
{
    use CreatesRestErpTables;

    private const ERP_URL = 'http://172.16.0.154:7048/PreBC/api/arianCapital/product/v1.0/CreateSynchroens';

    protected function setUp(): void
    {
        parent::setUp();
        $this->createRestErpTables();
        config()->set('erp.auth', ['test-user', 'test-password', 'ntlm']);
        config()->set('rest_erp.base_url', 'http://172.16.0.154:7048/PreBC/api/arianCapital');
        Http::preventStrayRequests();
        Queue::fake();
    }

    public function test_it_posts_the_exact_sample_payload_with_shared_authentication(): void
    {
        Http::fake(function (Request $request, array $options) {
            $this->assertSame(['test-user', 'test-password', 'ntlm'], $options['auth']);

            return Http::response(['Doc' => 'P-10-00932'], 201);
        });

        $result = resolve(CreateSynchroensService::class)->send(...$this->arguments())->sync();

        $this->assertTrue($result['success']);
        $this->assertSame(201, $result['http_status']);
        $this->assertSame(['Doc' => 'P-10-00932'], $result['data']);
        Http::assertSentCount(1);
        Http::assertSent(fn (Request $request) => $request->url() === self::ERP_URL
            && $request->method() === 'POST'
            && $request->hasHeader('Content-Type', 'application/json')
            && $request->data() === [
                'CrossReference' => 'SX5G-2804-0056',
                'Doc' => 'P-10-00932',
                'LocationCode' => 'LMRLP0100',
                'NewLocationCode' => 'LMRIL0011',
                'VariantCode' => '213',
                'Number' => '1',
                'BatchNo' => '123',
            ]);
        $this->assertSame('ارسال سنکرون قطعات به ERP', RestErpJob::query()->sole()->section);
        Queue::assertNothingPushed();
    }

    public function test_queue_explicitly_uses_parallel_and_dispatch_can_override_the_default(): void
    {
        $service = resolve(CreateSynchroensService::class);
        $queued = $service->queue(...$this->arguments());
        $override = $service->dispatch(...array_merge($this->arguments(), ['mode' => RestErpExecutionMode::Parallel]));

        $this->assertSame('unique', config('rest_erp.endpoints.CreateSynchroens.mode'));
        $this->assertSame(202, $queued['http_status']);
        $this->assertSame(202, $override['http_status']);
        $this->assertSame(['parallel', 'parallel'], RestErpJob::query()->pluck('queue')->all());
        Queue::assertPushed(SendToRestErpJob::class, 2);
        Http::assertNothingSent();
    }

    #[DataProvider('invalidInputs')]
    public function test_invalid_payloads_are_rejected_with_persian_messages(array $overrides): void
    {
        $result = resolve(CreateSynchroensService::class)->dispatch(...array_replace($this->arguments(), $overrides));

        $this->assertFalse($result['success']);
        $this->assertSame(422, $result['http_status']);
        $this->assertSame('VALIDATION_ERROR', $result['error']['code']);
        $this->assertMatchesRegularExpression('/[\x{0600}-\x{06FF}]/u', $result['error']['message']);
        $this->assertSame('rejected', RestErpJob::query()->sole()->status);
        Http::assertNothingSent();
        Queue::assertNothingPushed();
    }

    public static function invalidInputs(): array
    {
        return [
            'empty reference' => [['crossReference' => '']],
            'empty document' => [['doc' => '']],
            'empty source' => [['locationCode' => '']],
            'empty destination' => [['newLocationCode' => '']],
            'zero count' => [['number' => 0]],
            'negative count' => [['number' => -1]],
            'fractional count' => [['number' => '1.5']],
            'empty batch' => [['batchNo' => '']],
        ];
    }

    public function test_it_preserves_comma_separated_values_and_empty_variant_codes(): void
    {
        Http::fake(['*' => Http::response([], 200)]);
        $result = resolve(CreateSynchroensService::class)->send(...array_replace($this->arguments(), [
            'crossReference' => 'REF-1,REF-2', 'variantCode' => ',213', 'number' => 2,
        ]))->sync();

        $this->assertTrue($result['success']);
        Http::assertSent(fn (Request $request) => $request['CrossReference'] === 'REF-1,REF-2'
            && $request['VariantCode'] === ',213' && $request['Number'] === '2');
    }

    private function arguments(): array
    {
        return [
            'crossReference' => 'SX5G-2804-0056',
            'doc' => 'P-10-00932',
            'locationCode' => 'LMRLP0100',
            'newLocationCode' => 'LMRIL0011',
            'variantCode' => '213',
            'number' => 1,
            'batchNo' => '123',
        ];
    }
}
