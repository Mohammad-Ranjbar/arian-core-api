<?php

namespace Tests\Feature;

use App\Http\Controllers\PDIController;
use App\Http\Requests\StorePDIRequest;
use App\Models\CarModel;
use App\Models\PDI;
use App\Models\Vehicle;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\CreatesRestErpTables;
use Tests\TestCase;

class PdiMileageTest extends TestCase
{
    use CreatesRestErpTables;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createRestErpTables();
        Schema::create('p_d_i_s', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('vin');
            $table->string('pdi_result')->nullable();
            $table->timestamps();
        });
        (require database_path('migrations/2026_11_21_000000_add_mileage_to_p_d_i_s_table.php'))->up();
        config()->set('rest_erp.base_url', 'http://example.test');
        config()->set('erp.auth', ['test-user', 'test-password', 'ntlm']);
        Http::fake(['*' => Http::response([], 201)]);
    }

    private function vehicle(): Vehicle
    {
        $vehicle = new Vehicle();
        $vehicle->vin = 'N5RF42CG5RP000020';
        $vehicle->setRelation('carModel', new CarModel(['production' => 'imported']));

        return $vehicle;
    }

    private function pdiRequest(array $data, Vehicle $vehicle): StorePDIRequest
    {
        $request = StorePDIRequest::create('/', 'POST', $data);
        $route = new Route('POST', '/', []);
        $route->bind($request);
        $route->setParameter('vehicle', $vehicle);
        $request->setRouteResolver(fn () => $route);
        $request->setContainer(app())->setRedirector(app('redirect'));
        $request->validateResolved();

        return $request;
    }

    public function test_optional_mileage_is_saved_and_returned_as_a_string(): void
    {
        $vehicle = $this->vehicle();
        $response = app(PDIController::class)->store($vehicle, $this->pdiRequest([
            'pdi_result' => 'G1',
            'mileage' => 123.5,
        ], $vehicle));

        $this->assertSame('123.5', $response->resource->mileage);
        $this->assertSame('123.5', DB::table('p_d_i_s')->value('mileage'));
        $response->resource->setRelation('vehicle', $this->vehicle());
        $this->assertSame('123.5', $response->toArray(Request::create('/'))['mileage']);

        $withoutMileage = app(PDIController::class)->store($vehicle, $this->pdiRequest([
            'pdi_result' => 'G1',
        ], $vehicle));
        $this->assertNull($withoutMileage->resource->mileage);
    }

    public static function invalidMileage(): array
    {
        return [
            'zero' => [0, 'کارکرد خودرو باید بزرگ‌تر از صفر باشد.'],
            'negative' => [-1, 'کارکرد خودرو باید بزرگ‌تر از صفر باشد.'],
            'non-numeric' => ['abc', 'کارکرد خودرو باید عدد باشد.'],
            'too long' => [str_repeat('1', 256), 'کارکرد خودرو نباید بیشتر از ۲۵۵ نویسه باشد.'],
        ];
    }

    #[DataProvider('invalidMileage')]
    public function test_invalid_mileage_is_rejected(mixed $mileage, string $message): void
    {
        try {
            $vehicle = $this->vehicle();
            app(PDIController::class)->store($vehicle, $this->pdiRequest(['mileage' => $mileage], $vehicle));
            $this->fail('Expected a validation error.');
        } catch (ValidationException $exception) {
            $this->assertSame([$message], $exception->errors()['mileage']);
            $this->assertSame(0, PDI::query()->count());
            Http::assertNothingSent();
        }
    }

    public function test_persian_messages_and_imported_vehicle_fields(): void
    {
        $vehicle = $this->vehicle();

        try {
            $this->pdiRequest(['model_year' => str_repeat('a', 256), 'engine_no' => 123], $vehicle);
            $this->fail('Expected a validation error.');
        } catch (ValidationException $exception) {
            $this->assertSame('سال مدل نباید بیشتر از 255 نویسه باشد.', $exception->errors()['model_year'][0]);
            $this->assertSame('شماره موتور باید به صورت متن وارد شود.', $exception->errors()['engine_no'][0]);
        }

        $vehicle->carModel->production = 'local';
        $request = $this->pdiRequest(['model_year' => '1405', 'pdi_result' => 'G1'], $vehicle);
        $this->assertSame(['pdi_result' => 'G1'], $request->validated());
    }
}
