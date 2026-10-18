<?php

namespace Tests\Feature;

use App\Http\Middleware\Authenticate;
use App\Http\Middleware\CheckActiveUserMiddleware;
use App\Models\BillOfLading;
use App\Models\Container;
use App\Models\Vehicle;
use Illuminate\Auth\Middleware\Authorize;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\Concerns\CreatesShortageAuditTables;
use Tests\TestCase;

class BillOfLadingDeleteTest extends TestCase
{
    use CreatesShortageAuditTables;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        config()->set('database.connections.sqlite.foreign_key_constraints', true);
        DB::purge('sqlite');
        $this->createShortageAuditTablesAndAuthenticate();

        Schema::create('bill_of_ladings', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->string('code');
            $table->string('status');
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('containers', function (Blueprint $table) {
            $table->id();
            $table->string('code');
            $table->boolean('on_shipping')->default(true);
            $table->timestamps();
        });
        Schema::create('vehicles', function (Blueprint $table) {
            $table->string('vin')->primary();
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('bill_of_lading_item', function (Blueprint $table) {
            $table->foreignId('bill_of_lading_id')->constrained()->restrictOnDelete();
            $table->string('model_id');
            $table->string('model_type');
            $table->string('type')->nullable();
        });
        DB::table('containers')->insert(['id' => 1, 'code' => 'CONTAINER-1']);
        DB::table('vehicles')->insert(['vin' => 'VIN-1']);
        $this->withoutMiddleware([Authenticate::class, Authorize::class, CheckActiveUserMiddleware::class]);
    }

    private function bill(string $status = 'new'): BillOfLading
    {
        $bill = BillOfLading::query()->create(['user_id' => 1, 'code' => 'BOL', 'status' => $status]);
        $bill->actionLogs()->create(['user_id' => 1, 'action' => 'new']);

        return $bill;
    }

    private function item(BillOfLading $bill, string $modelType, string $modelId, ?string $type): void
    {
        DB::table('bill_of_lading_item')->insert([
            'bill_of_lading_id' => $bill->id, 'model_type' => $modelType, 'model_id' => $modelId, 'type' => $type,
        ]);
    }

    public static function items(): array
    {
        return [
            'container' => [Container::class, '1', 'containers'],
            'vehicle' => [Vehicle::class, 'VIN-1', 'vehicles'],
            'body' => [Vehicle::class, 'VIN-1', 'bodies'],
            'missing vehicle' => [Vehicle::class, 'MISSING-VIN', 'vehicles'],
            'missing type' => [Vehicle::class, 'VIN-1', null],
            'unrecognized model' => ['LegacyModel', '1', 'legacy'],
        ];
    }

    #[DataProvider('items')]
    public function test_deletion_removes_all_pivot_rows_even_when_type_cannot_be_resolved(string $modelType, string $modelId, ?string $type): void
    {
        $bill = $this->bill();
        $this->item($bill, $modelType, $modelId, $type);
        $other = $this->bill();
        $this->item($other, Vehicle::class, 'VIN-1', 'vehicles');

        $this->deleteJson('/api/bill-of-ladings/'.$bill->id)->assertOk()
            ->assertExactJson(['message' => 'حذف با موفقیت انجام گردید']);

        $this->assertDatabaseMissing('bill_of_ladings', ['id' => $bill->id]);
        $this->assertDatabaseMissing('bill_of_lading_item', ['bill_of_lading_id' => $bill->id]);
        $this->assertDatabaseMissing('action_logs', ['model_type' => BillOfLading::class, 'model_id' => (string) $bill->id]);
        $this->assertDatabaseHas('bill_of_ladings', ['id' => $other->id]);
        $this->assertDatabaseHas('bill_of_lading_item', ['bill_of_lading_id' => $other->id]);
        $this->assertDatabaseCount('action_logs', 1);
        $this->assertDatabaseCount('containers', 1);
        $this->assertDatabaseCount('vehicles', 1);
        if ($modelType === Container::class) {
            $this->assertDatabaseHas('containers', ['id' => 1, 'on_shipping' => false]);
        }
    }

    public function test_deletion_cleans_mixed_items_and_resets_container_shipping(): void
    {
        $bill = $this->bill();
        $this->item($bill, Container::class, '1', 'containers');
        $this->item($bill, Vehicle::class, 'VIN-1', 'bodies');
        $this->item($bill, 'LegacyModel', '99', null);

        $this->deleteJson('/api/bill-of-ladings/'.$bill->id)->assertOk();

        $this->assertDatabaseCount('bill_of_lading_item', 0);
        $this->assertDatabaseHas('containers', ['id' => 1, 'on_shipping' => false]);
    }

    public function test_non_new_bill_cannot_be_deleted(): void
    {
        $bill = $this->bill('discharge');
        $this->item($bill, Container::class, '1', 'containers');

        $this->deleteJson('/api/bill-of-ladings/'.$bill->id)->assertStatus(400);

        $this->assertDatabaseHas('bill_of_ladings', ['id' => $bill->id]);
        $this->assertDatabaseCount('bill_of_lading_item', 1);
        $this->assertDatabaseCount('action_logs', 1);
        $this->assertDatabaseHas('containers', ['id' => 1, 'on_shipping' => true]);
    }

    public function test_failed_parent_deletion_rolls_back_items_logs_and_container_shipping(): void
    {
        $bill = $this->bill();
        $this->item($bill, Container::class, '1', 'containers');
        Event::listen('eloquent.forceDeleting: '.BillOfLading::class, function () {
            throw new RuntimeException('Deletion failed');
        });
        $this->withoutExceptionHandling();

        try {
            $this->deleteJson('/api/bill-of-ladings/'.$bill->id);
            $this->fail('Expected deletion failure.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Deletion failed', $exception->getMessage());
        }

        $this->assertDatabaseHas('bill_of_ladings', ['id' => $bill->id]);
        $this->assertDatabaseCount('bill_of_lading_item', 1);
        $this->assertDatabaseCount('action_logs', 1);
        $this->assertDatabaseHas('containers', ['id' => 1, 'on_shipping' => true]);
        $this->assertSame(0, DB::transactionLevel());
    }
}
