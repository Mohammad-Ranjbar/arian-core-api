<?php

namespace Tests\Feature;

use App\Events\JobProcessEvent;
use App\Exports\TrackingExitDoorReportExport;
use App\Models\Location;
use App\Models\Station;
use App\Models\Tracking;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Excel as ExcelWriter;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class TrackingReportsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');
        Event::fake([JobProcessEvent::class]);

        Schema::create('production_tracking', function (Blueprint $table): void {
            $table->id();
            $table->string('vin')->index();
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('type');
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('vehicles', function (Blueprint $table): void {
            $table->string('vin')->primary();
            $table->unsignedBigInteger('car_model_id')->nullable();
            $table->unsignedBigInteger('make_code_id')->nullable();
            $table->softDeletes();
        });
        foreach (['car_models', 'make_codes'] as $name) {
            Schema::create($name, function (Blueprint $table): void {
                $table->id();
                $table->string('title');
                $table->string('reference');
            });
        }
        Schema::create('car_model_locations', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('car_model_id');
        });
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->softDeletes();
        });
        foreach (['locations', 'stations'] as $name) {
            Schema::create($name, function (Blueprint $table): void {
                $table->id();
                $table->string('title');
                $table->softDeletes();
            });
            DB::table($name)->insert(['id' => 1, 'title' => 'مکان آزمایشی']);
        }

        DB::table('car_models')->insert(['id' => 1, 'title' => 'مدل آزمایشی', 'reference' => '000123']);
        DB::table('make_codes')->insert(['id' => 1, 'title' => 'برند آزمایشی', 'reference' => '000045']);
        DB::table('users')->insert(['id' => 1, 'name' => 'کاربر آزمایشی']);
        DB::table('vehicles')->insert([
            ['vin' => '00000000000000123', 'car_model_id' => 1, 'make_code_id' => 1],
            ['vin' => 'VIN-2', 'car_model_id' => null, 'make_code_id' => null],
        ]);
    }

    public function test_general_report_eager_loads_nested_relations_without_a_redundant_count(): void
    {
        $this->tracking();
        $this->tracking();
        DB::enableQueryLog();
        DB::flushQueryLog();

        $rows = Tracking::getReportData([]);
        $queries = DB::getQueryLog();

        $this->assertCount(2, $rows);
        $this->assertTrue($rows->first()->relationLoaded('model'));
        $this->assertTrue($rows->first()->relationLoaded('user'));
        $this->assertTrue($rows->first()->vehicle->relationLoaded('carModel'));
        $this->assertTrue($rows->first()->vehicle->relationLoaded('makeCode'));
        $this->assertSame('مدل آزمایشی', $rows->first()->vehicle->carModel->title);
        $this->assertSame('000045', $rows->last()->vehicle->makeCode->reference);
        $this->assertCount(count($queries), DB::getQueryLog());
        foreach ($queries as $query) {
            $this->assertStringNotContainsString('count(', strtolower($query['query']));
        }
        DB::disableQueryLog();
    }

    public function test_general_report_preserves_filters_and_pagination(): void
    {
        $this->tracking(['model_type' => Station::class]);
        $id = $this->tracking(['model_type' => Station::class, 'created_at' => '2026-11-01 11:00:00']);
        $this->tracking(['vin' => 'VIN-2', 'model_type' => Station::class]);
        $this->tracking();

        $rows = Tracking::getReportData([
            'model_type' => 'stations', 'model_id' => [1], 'make_code_id' => 1,
            'user_id' => 1, 'type' => 'exit',
            'from' => '2026-11-01 09:00:00', 'to' => '2026-11-01 12:00:00',
            'vin' => '00000000000000123', 'paginate' => true, 'per_page' => 1,
        ]);

        $this->assertSame(2, $rows->total());
        $this->assertSame($id, $rows->first()->id);
    }

    public function test_general_report_preserves_scalar_model_id_and_ignores_unsupported_filters(): void
    {
        $first = $this->tracking(['model_type' => Station::class]);
        $second = $this->tracking(['model_type' => Station::class]);
        $this->tracking(['model_type' => Station::class, 'model_id' => 2]);
        $filters = ['model_type' => 'stations', 'model_id' => 1, 'car_model_id' => 999];
        $originalFilters = $filters;

        $rows = Tracking::getReportData($filters);

        $this->assertInstanceOf(Collection::class, $rows);
        $this->assertEqualsCanonicalizing([$first, $second], $rows->pluck('id')->all());
        $this->assertSame($originalFilters, $filters);
    }

    public function test_exit_report_selects_latest_timestamp_then_highest_id_per_vin(): void
    {
        $this->tracking(['created_at' => '2026-11-01 11:00:00']);
        $latest = $this->tracking(['created_at' => '2026-11-01 11:00:00']);
        $this->tracking(['created_at' => '2026-11-01 09:00:00']);
        $secondVin = $this->tracking(['vin' => 'VIN-2']);
        $this->tracking(['created_at' => '2026-11-01 12:00:00', 'model_type' => Station::class]);
        $this->tracking(['created_at' => '2026-11-01 13:00:00', 'deleted_at' => '2026-11-01 14:00:00']);

        DB::enableQueryLog();
        DB::flushQueryLog();
        $rows = Tracking::getReportExitDoorData([]);

        $this->assertSame([$latest, $secondVin], $rows->pluck('id')->all());
        $this->assertCount(5, DB::getQueryLog());
        $this->assertTrue($rows->first()->vehicle->relationLoaded('carModel'));
        $this->assertTrue($rows->first()->vehicle->relationLoaded('makeCode'));
        $this->assertFalse($rows->first()->vehicle->carModel->relationLoaded('locations'));
        $this->assertSame('کاربر آزمایشی', $rows->first()->user->name);
        $this->assertSame('000123', $rows->first()->vehicle->carModel->reference);
        $this->assertCount(5, DB::getQueryLog());
        DB::disableQueryLog();
    }

    public function test_exit_report_deduplicates_after_applying_all_filters(): void
    {
        $expected = $this->tracking();
        $this->tracking(['created_at' => '2026-11-01 11:00:00']);
        $this->tracking(['type' => 'enter']);
        $this->tracking(['user_id' => 2]);
        $this->tracking(['vin' => 'VIN-2']);

        $rows = Tracking::getReportExitDoorData([
            'from' => '2026-11-01 10:00:00', 'to' => '2026-11-01 10:00:00',
            'type' => 'exit', 'user_id' => 1, 'vin' => '00000000000000123',
            'make_code_id' => 1, 'car_model_id' => 1,
        ]);

        $this->assertSame([$expected], $rows->pluck('id')->all());
        $this->assertCount(0, Tracking::getReportExitDoorData(['car_model_id' => 2]));
        $this->assertCount(0, Tracking::getReportExitDoorData(['make_code_id' => 2]));
    }

    public function test_exit_report_keeps_vins_with_missing_timestamps(): void
    {
        $dated = $this->tracking();
        $this->tracking(['created_at' => null]);
        $this->tracking(['vin' => 'VIN-2', 'created_at' => null]);
        $undated = $this->tracking(['vin' => 'VIN-2', 'created_at' => null]);

        $rows = Tracking::getReportExitDoorData([]);

        $this->assertSame([$dated, $undated], $rows->pluck('id')->all());
    }

    public function test_exit_report_exports_titles_references_filters_and_text_identifiers(): void
    {
        $this->tracking();
        $latest = $this->tracking(['created_at' => '2026-11-01 11:00:00']);
        $this->tracking(['vin' => 'VIN-2', 'user_id' => null]);
        DB::table('make_codes')->where('id', 1)->update(['title' => '=1+1']);
        $rows = Tracking::getReportExitDoorData([]);

        $this->withWorkbook($rows, function ($sheet) use ($latest): void {
            $this->assertSame('گزارش تردد درب خروج', $sheet->getTitle());
            $this->assertSame('عنوان مدل خودرو', $sheet->getCell('C1')->getValue());
            $this->assertSame('کد مرجع برند', $sheet->getCell('F1')->getValue());
            $this->assertSame($latest, $sheet->getCell('A2')->getValue());
            $this->assertSame('00000000000000123', $sheet->getCell('B2')->getValue());
            $this->assertSame(DataType::TYPE_STRING, $sheet->getCell('B2')->getDataType());
            $this->assertTrue($sheet->getCell('B2')->getIgnoredErrors()->getNumberStoredAsText());
            $this->assertSame('مدل آزمایشی', $sheet->getCell('C2')->getValue());
            $this->assertSame('000123', $sheet->getCell('D2')->getValue());
            $this->assertSame('=1+1', $sheet->getCell('E2')->getValue());
            $this->assertSame(DataType::TYPE_STRING, $sheet->getCell('E2')->getDataType());
            $this->assertSame('000045', $sheet->getCell('F2')->getValue());
            $this->assertSame('خروج', $sheet->getCell('G2')->getValue());
            $this->assertSame('کاربر آزمایشی', $sheet->getCell('H2')->getValue());
            $this->assertSame(jdate('2026-11-01 11:00:00')->format('Y/m/d H:i:s'), $sheet->getCell('I2')->getValue());
            $this->assertSame('VIN-2', $sheet->getCell('B3')->getValue());
            foreach (['C3', 'D3', 'E3', 'F3', 'H3'] as $cell) {
                $this->assertEmpty($sheet->getCell($cell)->getValue());
            }
            $this->assertSame(3, $sheet->getHighestDataRow());
            $this->assertTrue($sheet->getRightToLeft());
            $this->assertSame('C2', $sheet->getFreezePane());
            $this->assertSame('A1:I3', $sheet->getAutoFilter()->getRange());
            $this->assertTrue($sheet->getStyle('A1')->getFont()->getBold());
            $this->assertEquals(30, $sheet->getRowDimension(2)->getRowHeight());
            $this->assertSame('1F4E78', $sheet->getStyle('A1')->getFill()->getStartColor()->getRGB());
            $this->assertCount(1, $sheet->getStyle('A2:I3')->getConditionalStyles());
        });

        Event::assertDispatched(JobProcessEvent::class, fn ($event) => $event->data['done'] === 2
            && $event->data['success'] === 2 && $event->data['total'] === 2);
    }

    public function test_empty_exit_report_still_exports_filterable_headers(): void
    {
        $this->withWorkbook(Tracking::getReportExitDoorData([]), function ($sheet): void {
            $this->assertSame(1, $sheet->getHighestDataRow());
            $this->assertSame('A1:I1', $sheet->getAutoFilter()->getRange());
            $this->assertSame('زمان ثبت', $sheet->getCell('I1')->getValue());
        });
    }

    private function tracking(array $attributes = []): int
    {
        return DB::table('production_tracking')->insertGetId(array_merge([
            'vin' => '00000000000000123', 'model_type' => Location::class, 'model_id' => 1,
            'type' => 'exit', 'user_id' => 1, 'created_at' => '2026-11-01 10:00:00',
            'updated_at' => '2026-11-01 10:00:00',
        ], $attributes));
    }

    private function withWorkbook($rows, callable $assertions): void
    {
        $binary = Excel::raw(new TrackingExitDoorReportExport($rows, [
            'id' => 'tracking-report-test', 'user_id' => 1, 'total' => $rows->count(),
            'done' => 0, 'success' => 0, 'error' => 0,
        ]), ExcelWriter::XLSX);
        $path = tempnam(sys_get_temp_dir(), 'tracking-report-');
        file_put_contents($path, $binary);

        try {
            // PhpSpreadsheet 1.30's reader resets this option from printOptions;
            // inspect the saved sheet view to verify what Excel actually receives.
            $zip = new \ZipArchive;
            $this->assertTrue($zip->open($path));
            $xml = new \SimpleXMLElement($zip->getFromName('xl/worksheets/sheet1.xml'));
            $this->assertSame('false', (string) $xml->sheetViews->sheetView['showGridLines']);
            $zip->close();

            Cell::setValueBinder(new DefaultValueBinder);
            $workbook = IOFactory::load($path);
            $assertions($workbook->getActiveSheet());
        } finally {
            if (isset($workbook)) {
                $workbook->disconnectWorksheets();
            }
            unlink($path);
        }
    }
}
