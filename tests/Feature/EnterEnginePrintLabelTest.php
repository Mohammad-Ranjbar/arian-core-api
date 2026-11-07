<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\RawPrinterService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mpdf\Mpdf;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use setasign\Fpdi\PdfParser\StreamReader;
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
        $user->givePermissionTo(Permission::create(['name' => 'enter-engines.print-label', 'guard_name' => 'web']));
        $this->actingAs($user);

        $this->printer = new class extends RawPrinterService
        {
            public array $sent = [];

            public bool $fail = false;

            public function sendFile(string $ip, string $path): void
            {
                $this->sent[] = ['ip' => $ip, 'path' => $path, 'pdf' => file_get_contents($path)];

                if ($this->fail) {
                    throw new RuntimeException('Simulated printer failure');
                }
            }
        };
        $this->app->instance(RawPrinterService::class, $this->printer);
    }

    public function test_prints_the_selected_engine_as_one_100_by_20_mm_pdf_and_removes_the_temporary_file(): void
    {
        $this->postJson('/api/enter-engines/7/print-label', ['printer_ip' => '127.0.0.1', 'engine_no' => 'OTHER'])
            ->assertOk()
            ->assertJsonPath('data.id', 7)
            ->assertJsonPath('data.engine_no', '00123ABC456789')
            ->assertJsonPath('data.printer_ip', '192.168.147.193')
            ->assertJsonPath('data.label_size_mm', [100, 20]);

        $this->assertCount(1, $this->printer->sent);
        $sent = $this->printer->sent[0];
        $this->assertSame('192.168.147.193', $sent['ip']);
        $this->assertFileDoesNotExist($sent['path']);
        $this->assertStringStartsWith('%PDF-', $sent['pdf']);
        $reader = new Mpdf(['tempDir' => storage_path('app/mpdf_temp')]);
        $this->assertSame(1, $reader->setSourceFile(StreamReader::createByString($sent['pdf'])));
        $size = $reader->getTemplateSize($reader->importPage(1));
        $this->assertEqualsWithDelta(100, $size['width'], 0.01);
        $this->assertEqualsWithDelta(20, $size['height'], 0.01);
        $this->assertDatabaseHas('enter_engines', ['id' => 7, 'engine_no' => '00123ABC456789']);
    }

    public function test_printer_failure_returns_502_and_removes_the_temporary_file(): void
    {
        $this->printer->fail = true;

        $this->postJson('/api/enter-engines/7/print-label')->assertStatus(502);

        $this->assertCount(1, $this->printer->sent);
        $this->assertFileDoesNotExist($this->printer->sent[0]['path']);
    }

    public function test_unknown_id_returns_404_without_printing(): void
    {
        $this->postJson('/api/enter-engines/999/print-label')->assertNotFound();

        $this->assertSame([], $this->printer->sent);
    }

    public function test_print_permission_is_required(): void
    {
        $this->app['auth']->user()->revokePermissionTo('enter-engines.print-label');

        $this->postJson('/api/enter-engines/7/print-label')->assertForbidden();

        $this->assertSame([], $this->printer->sent);
    }

    public function test_authentication_is_required(): void
    {
        $this->app['auth']->forgetGuards();

        $this->postJson('/api/enter-engines/7/print-label')->assertUnauthorized();

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

        $this->postJson('/api/enter-engines/7/print-label')->assertUnprocessable()->assertJsonValidationErrors('engine_no');

        $this->assertSame([], $this->printer->sent);
    }

    public function test_printer_address_can_be_configured_on_the_server(): void
    {
        config(['printers.engine_label.ip' => '192.0.2.10']);

        $this->postJson('/api/enter-engines/7/print-label')->assertOk()->assertJsonPath('data.printer_ip', '192.0.2.10');

        $this->assertSame('192.0.2.10', $this->printer->sent[0]['ip']);
    }
}
