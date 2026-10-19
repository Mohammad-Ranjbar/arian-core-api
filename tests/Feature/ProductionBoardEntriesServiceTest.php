<?php

namespace Tests\Feature;

use App\Http\Controllers\DisplayBoardController;
use App\Models\ProductionLine;
use App\Models\Salon;
use App\Services\Erp\RestApi\ProductionBoardEntriesService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ProductionBoardEntriesServiceTest extends TestCase
{
    private const ERP_URL = 'http://172.16.0.154:7048/PreBC/api/arianCapital/production/v1.0/productionBoardEntries';

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('rest_erp.production_base_url', dirname(self::ERP_URL));
        config()->set('erp.auth', ['test-user', 'test-password', 'ntlm']);
        Http::preventStrayRequests();
    }

    public function test_controller_reads_entries_using_get_and_preserves_the_board_response(): void
    {
        Carbon::setTestNow('2026-10-19 10:00:00');
        try {
            $entry = [
                'entryNo' => 1, 'locationCode' => 'LMRLP0100', 'workShiftCode' => 'SHIFT1',
                'date' => '2026-10-19', 'startingTime' => '07:15:00', 'endingTime' => '13:00:00',
                'qtyPlanned' => 0, 'dayOutput' => 12, 'shiftOutput' => 5,
                'timeBreaks' => '[{"inShiftStartTime":"07:15:00","inShiftEndTime":"13:00:00"}]',
            ];
            Http::fake(function (Request $request, array $options) use ($entry) {
                $this->assertSame(['test-user', 'test-password', 'ntlm'], $options['auth']);

                return Http::response(['value' => [$entry, array_replace($entry, ['locationCode' => 'OTHER'])]]);
            });
            $line = Mockery::mock(ProductionLine::class)->makePartial();
            $line->fill(['title' => 'Line 1', 'location' => 'LMRLP0100', 'shift' => 'SHIFT1']);
            $line->shouldReceive('isStarted')->once()->andReturn(true);
            $line->shouldReceive('trackingCount')->once()->with('enter', date('Y-m-d ').'07:15:00', date('Y-m-d ').'13:00:00')->andReturn(15);
            $relation = Mockery::mock(HasMany::class);
            $relation->shouldReceive('where')->once()->with('show_in_board', true)->andReturnSelf();
            $relation->shouldReceive('get')->once()->andReturn(new Collection([$line]));
            $salon = Mockery::mock(Salon::class)->makePartial();
            $salon->shouldReceive('productionLines')->once()->andReturn($relation);

            $response = resolve(DisplayBoardController::class)(\Illuminate\Http\Request::create('/'), $salon);

            $this->assertSame(200, $response->status());
            $this->assertSame([[
                'type' => 'production-board', 'duration' => 10000,
                'data' => [
                    'title' => 'Line 1', 'planned' => 0, 'expectation' => 0, 'percentage' => 0,
                    'deviation' => -12, 'line_status' => true, 'description' => '', 'in' => 15, 'out' => 12,
                ],
            ]], $response->getData(true));
            Http::assertSentCount(1);
            Http::assertSent(fn (Request $request) => $request->url() === self::ERP_URL
                && $request->method() === 'GET' && $request->body() === ''
                && $request->hasHeader('Accept', 'application/json'));
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_empty_value_is_a_successful_empty_result(): void
    {
        Http::fake([self::ERP_URL => Http::response(['value' => []])]);

        $result = resolve(ProductionBoardEntriesService::class)->get();

        $this->assertTrue($result['success']);
        $this->assertSame([], $result['data']);
    }

    public static function failures(): array
    {
        return [
            'missing value' => [[], 200, 502],
            'invalid value' => [['value' => 'invalid'], 200, 502],
            'invalid entry' => [['value' => [null]], 200, 502],
            'authentication' => [[], 401, 502],
            'server error' => [[], 500, 502],
            'ERP error' => [['error' => ['code' => 'Application_DialogException', 'message' => 'Rejected.']], 400, 422],
            'connection' => [null, 0, 503],
        ];
    }

    #[DataProvider('failures')]
    public function test_controller_handles_provider_failures_before_querying_lines(?array $body, int $status, int $expectedStatus): void
    {
        Http::fake(function () use ($body, $status) {
            if ($status === 0) {
                throw new ConnectionException('Connection timed out.');
            }

            return Http::response($body, $status);
        });
        $salon = Mockery::mock(Salon::class)->makePartial();
        $salon->shouldNotReceive('productionLines');

        $response = resolve(DisplayBoardController::class)(\Illuminate\Http\Request::create('/'), $salon);

        $this->assertSame($expectedStatus, $response->status());
        $this->assertNotEmpty($response->getData(true)['message']);
    }
}
