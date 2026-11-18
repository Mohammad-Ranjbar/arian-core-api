<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\EngineNumberLabelPrinter;
use App\Services\RawPrinterService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class EnterEnginePrintLabelTest extends TestCase
{
    private RawPrinterService $printer;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'cache.default' => 'array',
            'printers.engine_label.ip' => '192.168.147.193',
        ]);
        DB::purge('sqlite');

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
        });
        Schema::create('enter_engines', function (Blueprint $table): void {
            $table->id();
            $table->string('engine_no');
        });
        (require database_path('migrations/2023_10_29_103259_create_permission_tables.php'))->up();
        DB::table('users')->insert(['id' => 1, 'name' => 'Print operator']);
        DB::table('enter_engines')->insert(['id' => 7, 'engine_no' => '00123ABC456789']);
        $user = User::findOrFail(1);
        $user->givePermissionTo(Permission::create(['name' => 'enter-engines.print', 'guard_name' => 'web']));
        $this->actingAs($user);

        $this->printer = new class extends RawPrinterService
        {
            public array $sent = [];

            public bool $fail = false;

            public function sendData(string $ip, string $data): void
            {
                $this->sent[] = ['ip' => $ip, 'data' => $data];

                if ($this->fail) {
                    throw new RuntimeException('Simulated printer failure');
                }
            }
        };
        $this->app->instance(RawPrinterService::class, $this->printer);
    }

    public function test_sends_one_100_by_20_mm_tspl_label(): void
    {
        $this->postJson('/api/enter-engines/7/print', ['printer_ip' => '127.0.0.1', 'engine_no' => 'OTHER'])
            ->assertOk()
            ->assertJsonPath('data.id', 7)
            ->assertJsonPath('data.engine_no', '00123ABC456789')
            ->assertJsonPath('data.printer_ip', '192.168.147.193')
            ->assertJsonPath('data.label_size_mm', [100, 20]);

        $this->assertCount(1, $this->printer->sent);
        $sent = $this->printer->sent[0];
        $this->assertSame('192.168.147.193', $sent['ip']);
        $this->assertStringStartsWith("SIZE 100 mm,20 mm\r\nGAP 2 mm,0 mm\r\nDIRECTION 1\r\nCLS\r\nBITMAP 0,0,100,160,0,", $sent['data']);
        $this->assertStringEndsWith("\r\nPRINT 1,1\r\n", $sent['data']);
        $this->assertSame(app(EngineNumberLabelPrinter::class)->renderTspl('00123ABC456789'), $sent['data']);
        $this->assertDatabaseHas('enter_engines', ['id' => 7, 'engine_no' => '00123ABC456789']);
    }

    public function test_printer_failure_returns_502(): void
    {
        $this->printer->fail = true;

        $this->postJson('/api/enter-engines/7/print')->assertStatus(502);

        $this->assertCount(1, $this->printer->sent);
    }

    public function test_unknown_id_returns_404_without_printing(): void
    {
        $this->postJson('/api/enter-engines/999/print')->assertNotFound();

        $this->assertSame([], $this->printer->sent);
    }

    public function test_print_permission_is_required(): void
    {
        $this->app['auth']->user()->revokePermissionTo('enter-engines.print');

        $this->postJson('/api/enter-engines/7/print')->assertForbidden();

        $this->assertSame([], $this->printer->sent);
    }

    public function test_authentication_is_required(): void
    {
        $this->app['auth']->forgetGuards();

        $this->postJson('/api/enter-engines/7/print')->assertUnauthorized();

        $this->assertSame([], $this->printer->sent);
    }

    public static function invalidEngineNumbers(): array
    {
        return [
            'empty' => [''],
            'blank' => ['   '],
            'unsupported characters' => ['موتور۱۲۳'],
            'control characters' => ["ABC\n123"],
            'too wide for label' => [str_repeat('A', 70)],
        ];
    }

    #[DataProvider('invalidEngineNumbers')]
    public function test_invalid_engine_numbers_are_rejected_without_printing(string $engineNumber): void
    {
        DB::table('enter_engines')->where('id', 7)->update(['engine_no' => $engineNumber]);

        $this->postJson('/api/enter-engines/7/print')->assertUnprocessable()->assertJsonValidationErrors('engine_no');

        $this->assertSame([], $this->printer->sent);
    }

    public function test_printer_address_can_be_configured_on_the_server(): void
    {
        config(['printers.engine_label.ip' => '192.0.2.10']);

        $this->postJson('/api/enter-engines/7/print')->assertOk()->assertJsonPath('data.printer_ip', '192.0.2.10');

        $this->assertSame('192.0.2.10', $this->printer->sent[0]['ip']);
    }

    public static function invalidPrinterConfigurations(): array
    {
        return [
            'missing config file or stale cache' => [[]],
            'null address' => [['engine_label' => ['ip' => null]]],
            'empty address' => [['engine_label' => ['ip' => '']]],
            'blank address' => [['engine_label' => ['ip' => '   ']]],
            'boolean address' => [['engine_label' => ['ip' => false]]],
            'array address' => [['engine_label' => ['ip' => []]]],
        ];
    }

    #[DataProvider('invalidPrinterConfigurations')]
    public function test_missing_or_invalid_printer_configuration_returns_502_without_printing(array $configuration): void
    {
        config(['printers' => $configuration]);

        $this->postJson('/api/enter-engines/7/print')
            ->assertStatus(502)
            ->assertJsonPath('message', 'ارسال لیبل به چاپگر انجام نشد؛ تنظیمات، ارتباط و وضعیت چاپگر را بررسی کنید.');

        $this->assertSame([], $this->printer->sent);
    }

    public function test_missing_printer_configuration_is_reported_before_rendering_the_label(): void
    {
        config(['printers' => []]);
        $service = $this->getMockBuilder(EngineNumberLabelPrinter::class)
            ->setConstructorArgs([$this->printer])
            ->onlyMethods(['renderTspl'])
            ->getMock();
        $service->expects($this->never())->method('renderTspl');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('آدرس چاپگر لیبل موتور در printers.engine_label.ip تنظیم نشده یا معتبر نیست؛ ENGINE_LABEL_PRINTER_IP و کش تنظیمات را بررسی کنید.');

        $service->print('00123ABC456789');
    }
}
