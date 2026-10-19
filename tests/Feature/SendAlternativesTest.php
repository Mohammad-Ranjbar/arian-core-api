<?php

namespace Tests\Feature;

use App\Http\Controllers\AlternativeController;
use App\Http\Requests\SendAlternativeRequest;
use App\Models\Alternative;
use App\Models\AlternativeVariant;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\Concerns\CreatesRestErpTables;
use Tests\TestCase;

class SendAlternativesTest extends TestCase
{
    use CreatesRestErpTables;

    private const ERP_URL = 'http://172.16.0.154:7048/PreBC/api/arianCapital/product/v1.0/CreateTransferZones';

    protected function setUp(): void
    {
        parent::setUp();
        $this->createRestErpTables();
        Schema::create('alternatives', fn (Blueprint $table) => $table->id());
        DB::table('alternatives')->insert(['id' => 77]);

        config()->set('erp.auth', ['test-user', 'test-password', 'ntlm']);
        Http::preventStrayRequests();
    }

    public function test_it_sends_alternative_data_and_records_erp_success(): void
    {
        $body = ['DocumentNo' => 'ALT-77'];
        Http::fake([self::ERP_URL => Http::response($body, 201)]);
        $alternative = $this->alternative();

        $response = $this->send($alternative);
        $this->assertNull($response, $response?->getContent() ?? '');

        Http::assertSentCount(1);
        Http::assertSent(fn (Request $request) => $request->url() === self::ERP_URL
            && $request->data() === [
                'ItemNoArray' => 'AC-90199752100001,AC-90199752100001',
                'ItemQtyArray' => '1.5,2',
                'NumberItems' => '2',
                'FromLocationType' => 'Central',
                'ToLacationType' => 'Production',
                'VariantArray' => 'V1,V2',
                'CrossRefArray' => ',',
                'MakeCode' => 'LAMARI',
                'DocumentNo' => 'ALT-77',
            ]);
        $this->assertSame('success', $alternative->status);
        $this->assertSame($body, $alternative->data);
        $this->assertNotNull($alternative->send_at);
        $this->assertNotNull($alternative->success_at);
        $this->assertNull($alternative->failed_at);
    }

    public function test_it_records_erp_failure_and_returns_its_http_status_and_message(): void
    {
        $error = [
            'code' => 'Application_DialogException',
            'message' => 'Insufficient inventory.',
        ];
        Http::fake([self::ERP_URL => Http::response(['error' => $error], 400)]);
        $alternative = $this->alternative();

        $response = $this->send($alternative);

        $this->assertSame(422, $response->getStatusCode());
        $error['message'] = "\u{2067}خطای بیزینس سنترال :: \u{2066}".$error['message']." \u{2069}\u{2069}";
        $this->assertSame(['message' => $error['message']], $response->getData(true));
        $this->assertSame('failed', $alternative->status);
        $this->assertSame($error, $alternative->data);
        $this->assertNotNull($alternative->failed_at);
        $this->assertNull($alternative->success_at);
    }

    public function test_an_alternative_without_variants_fails_validation_without_sending_to_erp(): void
    {
        Http::fake();
        $alternative = $this->alternative();
        $alternative->setRelation('variants', new Collection);

        $response = $this->send($alternative);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame('failed', $alternative->status);
        $this->assertSame('VALIDATION_ERROR', $alternative->data['code']);
        Http::assertNothingSent();
    }

    private function alternative(): Alternative
    {
        $alternative = Mockery::mock(Alternative::class)->makePartial();
        $alternative->setTable('alternatives');
        $alternative->setAttribute('id', 77);
        $alternative->exists = true;
        $alternative->shouldReceive('getMorphClass')->andReturn(Alternative::class);
        $alternative->fill([
            'item_no' => 'AC-90199752100001',
            'document_number' => 'ALT-77',
        ]);
        $alternative->setRelation('variants', new Collection([
            new AlternativeVariant(['variant_code' => 'V1', 'quantity' => '1.5']),
            new AlternativeVariant(['variant_code' => 'V2', 'quantity' => '2']),
        ]));
        $alternative->shouldReceive('update')->andReturnUsing(function (array $attributes) use ($alternative) {
            $alternative->fill($attributes);

            return true;
        });

        return $alternative;
    }

    private function send(Alternative $alternative): mixed
    {
        $request = Mockery::mock(SendAlternativeRequest::class)->makePartial();
        $request->shouldReceive('validated')->once()->andReturn(['make_code_reference' => 'LAMARI']);

        return resolve(AlternativeController::class)->sendAlternatives($alternative, $request);
    }
}
