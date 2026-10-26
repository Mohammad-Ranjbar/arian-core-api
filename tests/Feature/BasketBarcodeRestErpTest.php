<?php

namespace Tests\Feature;

use App\Http\Controllers\BasketController;
use App\Models\Basket;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Permission;
use Tests\Concerns\CreatesRestErpTables;
use Tests\TestCase;

class BasketBarcodeRestErpTest extends TestCase
{
    use CreatesRestErpTables;

    private const ERP_URL = 'http://172.16.0.154:7048/PreBC/api/arianCapital/product/v1.0/Baskets';

    protected function setUp(): void
    {
        parent::setUp();
        $this->createRestErpTables();
        (require database_path('migrations/2024_08_11_134401_alter_basket_table.php'))->up();
        Schema::create('stations', function (Blueprint $table) {
            $table->id();
            $table->softDeletes();
        });
        Schema::create('station_user', function (Blueprint $table) {
            $table->unsignedBigInteger('station_id');
            $table->unsignedBigInteger('user_id');
            $table->json('data')->nullable();
        });
        config()->set('rest_erp.base_url', 'http://172.16.0.154:7048/PreBC/api/arianCapital');
        config()->set('erp.auth', ['test-user', 'test-password', 'ntlm']);
        Auth::user()->givePermissionTo(Permission::create(['name' => 'baskets.store', 'guard_name' => 'web']));
        Http::preventStrayRequests();
        Queue::fake();
    }

    public function test_check_preserves_its_status_and_data_contract_with_the_complete_json_response(): void
    {
        // Synthetic response: preserve its structure without assuming provider-specific field names.
        $body = ['value' => [['description' => 'قطعات داخل سبد']]];
        Http::fake([self::ERP_URL => Http::response($body, 200)]);

        $result = resolve(BasketController::class)->checkBasketBarcode('BKJ1038520');

        $this->assertSame(['status' => 'ok', 'data' => $body], $result);
        Http::assertSentCount(1);
        Http::assertSent(fn (Request $request) => $request->method() === 'GET'
            && $request->url() === self::ERP_URL
            && json_decode($request->body(), true) === ['BarcodeInfo' => 'BKJ1038520']);
        $this->assertDatabaseCount('baskets', 0);
        $this->assertDatabaseCount('rest_erp_jobs', 0);
        Queue::assertNothingPushed();
    }

    public function test_store_waits_for_erp_success_and_saves_the_full_response_as_description(): void
    {
        $body = ['value' => [['description' => 'قطعات داخل سبد']]];
        Http::fake(function () use ($body) {
            $this->assertDatabaseCount('baskets', 0);

            return Http::response($body, 200);
        });

        $response = $this->postJson('/api/baskets', ['code' => 'BKJ1038520']);
        $this->assertSame(201, $response->status(), $response->json('message') ?? 'Unexpected response.');
        $response->assertJsonPath('data.code', 'BKJ1038520')
            ->assertJsonPath('data.description', $body);

        $basket = Basket::query()->sole();
        $this->assertSame(42, $basket->user_id);
        $this->assertSame('new', $basket->status);
        $this->assertSame($body, $basket->description);
        $this->assertDatabaseCount('rest_erp_jobs', 0);
        Http::assertSentCount(1);
        Queue::assertNothingPushed();
    }

    public static function failures(): array
    {
        return [
            'business error' => [['error' => ['code' => 'Application_DialogException', 'message' => 'Basket rejected.']], 400],
            'authentication error' => [[], 401],
            'server error' => [[], 500],
            'invalid json' => ['not-json', 200],
            'connection error' => [null, 0],
        ];
    }

    #[DataProvider('failures')]
    public function test_store_does_not_create_a_basket_when_erp_fails(mixed $body, int $status): void
    {
        Http::fake(function () use ($body, $status) {
            if ($status === 0) {
                throw new ConnectionException('Connection timed out.');
            }

            return Http::response($body, $status);
        });

        $response = $this->postJson('/api/baskets', ['code' => 'BKJ1038520'])->assertStatus(500);

        $this->assertStringContainsString('خطای بیزینس سنترال ::', $response->json('message'));
        $this->assertDatabaseCount('baskets', 0);
        $this->assertDatabaseCount('erp_jobs', 0);
        $this->assertDatabaseCount('rest_erp_jobs', 0);
        Queue::assertNothingPushed();
    }
}
