<?php

namespace Tests\Feature;

use App\Services\Erp\RestApi\BasketsService;
use App\Services\Erp\RestApi\Servers\Server;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class BasketsServiceTest extends TestCase
{
    private const ERP_URL = 'http://172.16.0.154:7048/PreBC/api/arianCapital/product/v1.0/Baskets';

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('rest_erp.base_url', 'http://172.16.0.154:7048/PreBC/api/arianCapital');
        config()->set('erp.auth', ['test-user', 'test-password', 'ntlm']);
        Http::preventStrayRequests();
    }

    public function test_it_sends_the_exact_post_request_with_a_json_body_and_shared_authentication(): void
    {
        Http::fake(function (Request $request, array $options) {
            $this->assertSame(['test-user', 'test-password', 'ntlm'], $options['auth']);
            $this->assertSame(5, $options['connect_timeout']);
            $this->assertSame(300, $options['timeout']);
            $this->assertFalse($options['allow_redirects']);

            return Http::response([], 200);
        });

        $result = resolve(BasketsService::class)->post('BKJ1038520');

        $this->assertSame(['success' => true, 'data' => [], 'error' => null, 'http_status' => 200], $result);
        Http::assertSentCount(1);
        Http::assertSent(fn (Request $request) => $request->method() === 'POST'
            && $request->url() === self::ERP_URL
            && $request->hasHeader('Content-Type', 'application/json')
            && $request->hasHeader('Accept', 'application/json')
            && json_decode($request->body(), true) === ['BarcodeInfo' => 'BKJ1038520']);
    }

    public function test_existing_get_requests_can_still_use_query_parameters_without_a_body(): void
    {
        Http::fake(['*' => Http::response(['value' => []], 200)]);

        resolve(Server::class)->get('productionBoardEntries', ['locationCode' => 'LMRLP0100']);

        Http::assertSent(fn (Request $request) => $request->method() === 'GET'
            && $request->url() === dirname(self::ERP_URL).'/productionBoardEntries?locationCode=LMRLP0100'
            && $request->body() === '');
    }

    #[DataProvider('emptyBarcodes')]
    public function test_empty_barcodes_are_rejected_before_http(string $barcode): void
    {
        $result = resolve(BasketsService::class)->post($barcode);

        $this->assertFalse($result['success']);
        $this->assertSame('VALIDATION_ERROR', $result['error']['code']);
        $this->assertSame('وارد کردن بارکد سبد الزامی است.', $result['error']['message']);
        $this->assertSame(422, $result['http_status']);
        Http::assertNothingSent();
    }

    public static function emptyBarcodes(): array
    {
        return [[''], ['   ']];
    }

    public static function providerFailures(): array
    {
        return [
            'business error' => [['error' => ['code' => 'Application_DialogException', 'message' => 'Basket rejected.']], 400, 'Application_DialogException', 422],
            'authentication error' => [[], 401, 'ERP_AUTH_ERROR', 502],
            'authorization error' => [[], 403, 'ERP_AUTH_ERROR', 502],
            'server error' => [[], 500, 'ERP_ERROR', 502],
            'invalid json' => ['<html>Invalid response</html>', 200, 'ERP_ERROR', 502],
        ];
    }

    #[DataProvider('providerFailures')]
    public function test_provider_errors_use_the_shared_error_contract(mixed $body, int $status, string $code, int $httpStatus): void
    {
        Http::fake([self::ERP_URL => Http::response($body, $status)]);

        $result = resolve(BasketsService::class)->post('BKJ1038520');

        $this->assertFalse($result['success']);
        $this->assertSame($code, $result['error']['code']);
        $this->assertSame($httpStatus, $result['http_status']);
        $this->assertStringContainsString('خطای بیزینس سنترال ::', $result['error']['message']);
        Http::assertSentCount(1);
    }

    public function test_connection_errors_are_returned_without_exposing_credentials(): void
    {
        Http::fake(fn () => throw new ConnectionException('Connection timed out. password=wire-secret'));

        $result = resolve(BasketsService::class)->post('BKJ1038520');

        $this->assertFalse($result['success']);
        $this->assertSame('ERP_CONNECTION_ERROR', $result['error']['code']);
        $this->assertSame(503, $result['http_status']);
        $this->assertStringNotContainsString('wire-secret', $result['error']['message']);
    }
}
