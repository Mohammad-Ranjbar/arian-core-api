<?php

namespace Tests\Feature;

use App\Services\Erp\RestApi\CreateTransferZonesService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CreateTransferZonesServiceTest extends TestCase
{
    private const ERP_URL = 'http://172.16.0.154:7048/PreBC/api/arianCapital/product/v1.0/CreateTransferZones';

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('erp.auth', ['test-user', 'test-password', 'ntlm']);
        Http::preventStrayRequests();
    }

    public function test_it_posts_the_exact_sample_payload_as_json(): void
    {
        $body = ['DocumentNo' => '77'];
        Http::fake([self::ERP_URL => Http::response($body, 201)]);

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
                'ItemNoArray' => 'AC-90199752100001',
                'ItemQtyArray' => '1.5',
                'NumberItems' => '1',
                'FromLocationType' => 'Central',
                'ToLacationType' => 'Production',
                'VariantArray' => ' , ',
                'CrossRefArray' => ',',
                'MakeCode' => 'LAMARI',
                'DocumentNo' => '77',
            ]);
    }

    public function test_it_preserves_lists_decimal_precision_and_empty_optional_values(): void
    {
        Http::fake([self::ERP_URL => Http::response(['DocumentNo' => 'ALT-77'], 201)]);

        $result = $this->send([
            'itemNoArray' => 'AC-001,AC-002',
            'itemQtyArray' => '1.500,2.25',
            'numberItems' => 2,
            'variantArray' => '',
            'crossRefArray' => '',
            'documentNo' => 'ALT-77',
        ]);

        $this->assertTrue($result['success']);
        Http::assertSent(fn (Request $request) => $request['ItemNoArray'] === 'AC-001,AC-002'
            && $request['ItemQtyArray'] === '1.500,2.25'
            && $request['NumberItems'] === '2'
            && $request['VariantArray'] === ''
            && $request['CrossRefArray'] === ''
            && $request['DocumentNo'] === 'ALT-77');
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
            'empty items' => [['itemNoArray' => '']],
            'empty quantities' => [['itemQtyArray' => '']],
            'zero item count' => [['numberItems' => 0]],
            'fractional item count' => [['numberItems' => '1.5']],
            'empty source' => [['fromLocationType' => '']],
            'empty destination' => [['toLacationType' => '']],
            'empty make code' => [['makeCode' => '']],
            'empty document' => [['documentNo' => '']],
        ];
    }

    public function test_it_returns_erp_business_errors(): void
    {
        Http::fake([self::ERP_URL => Http::response([
            'error' => [
                'code' => 'Application_DialogException',
                'message' => 'Insufficient inventory.',
            ],
        ], 400)]);

        $result = $this->send();

        $this->assertFalse($result['success']);
        $this->assertSame(422, $result['http_status']);
        $this->assertSame([
            'code' => 'Application_DialogException',
            'message' => 'Insufficient inventory.',
        ], $result['error']);
    }

    public function test_it_returns_authentication_errors(): void
    {
        Http::fake([self::ERP_URL => Http::response([], 401)]);

        $result = $this->send();

        $this->assertFalse($result['success']);
        $this->assertSame('ERP_AUTH_ERROR', $result['error']['code']);
        $this->assertSame(502, $result['http_status']);
    }

    public function test_it_returns_connection_errors(): void
    {
        Http::fake(function () {
            throw new ConnectionException('Connection timed out.');
        });

        $result = $this->send();

        $this->assertFalse($result['success']);
        $this->assertSame('ERP_CONNECTION_ERROR', $result['error']['code']);
        $this->assertSame(503, $result['http_status']);
    }

    private function send(array $overrides = []): array
    {
        return resolve(CreateTransferZonesService::class)->send(...array_replace([
            'itemNoArray' => 'AC-90199752100001',
            'itemQtyArray' => '1.5',
            'numberItems' => '1',
            'fromLocationType' => 'Central',
            'toLacationType' => 'Production',
            'variantArray' => ' , ',
            'crossRefArray' => ',',
            'makeCode' => 'LAMARI',
            'documentNo' => '77',
        ], $overrides));
    }
}
