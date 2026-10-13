<?php

namespace Tests\Feature;

use App\Services\Erp\RestApi\ItemReclassJournalsService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\CreatesRestErpTables;
use Tests\TestCase;

class ItemReclassJournalsServiceTest extends TestCase
{
    use CreatesRestErpTables;

    private const ERP_URL = 'http://172.16.0.154:7048/PreBC/api/arianCapital/product/v1.0/ItemReclassJournals';

    protected function setUp(): void
    {
        parent::setUp();
        $this->createRestErpTables();

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
    public function test_it_returns_the_shared_erp_error($body, int $status, string $code, int $expectedStatus): void
    {
        Http::fake([self::ERP_URL => Http::response($body, $status)]);

        $result = $this->send();

        $this->assertFalse($result['success']);
        $this->assertSame($expectedStatus, $result['http_status']);
        $this->assertSame($code, $result['error']['code']);
        if ($code === 'Application_DialogException') {
            $this->assertSame('Insufficient inventory.', $result['error']['message']);
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
            'unexpected error' => [['error' => ['code' => 'Unknown']], 200, 'Unknown', 502],
            'invalid json' => ['<html>failure</html>', 200, 'ERP_ERROR', 502],
        ];
    }

    public function test_it_returns_connection_errors(): void
    {
        Http::fake(function () {
            throw new ConnectionException('Connection timed out.');
        });

        $result = $this->send();

        $this->assertFalse($result['success']);
        $this->assertSame(503, $result['http_status']);
        $this->assertSame('ERP_CONNECTION_ERROR', $result['error']['code']);
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
