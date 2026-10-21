<?php

namespace Tests\Feature;

use App\Jobs\SendToRestErpJob;
use App\Services\Erp\RestApi\Servers\Server;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ServerErrorMessageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config()->set('erp.auth', ['test-user', 'test-password', 'ntlm']);
        Http::preventStrayRequests();
    }

    public static function failures(): array
    {
        return [
            'business' => [['error' => ['code' => 'Application_DialogException', 'message' => 'Location invalid.']], 400, 'Location invalid.'],
            'error in success response' => [['error' => 'Location invalid.'], 200, 'Location invalid.'],
            'authentication' => ['Access denied.', 401, 'پاسخ بیزینس سنترال با کد وضعیت HTTP 401 دریافت شد؛ جزئیات: Access denied.'],
            'invalid JSON' => ['<html>Failure.</html>', 200, 'پاسخ بیزینس سنترال با کد وضعیت HTTP 200 دریافت شد؛ جزئیات: Failure.'],
            'connection' => [null, 0, ConnectionException::class.': Connection timed out.'],
        ];
    }

    #[DataProvider('failures')]
    public function test_get_and_post_failures_isolate_the_persian_prefix_and_english_detail(mixed $body, int $status, string $detail): void
    {
        Http::fake(function () use ($body, $status) {
            if ($status === 0) {
                throw new ConnectionException('Connection timed out.');
            }

            return Http::response($body, $status);
        });

        $server = resolve(Server::class);
        foreach ([$server->get('productionBoardEntries'), $server->post('ExitPrints', [])] as $result) {
            $this->assertFalse($result['success']);
            $this->assertSame("\u{2067}خطای بیزینس سنترال :: \u{2068}{$detail} \u{2069}\u{2069}", $result['error']['message']);
        }
    }

    public function test_get_and_post_requests_allow_five_minutes_with_queue_time_to_finish(): void
    {
        Http::fake(function ($request, array $options) {
            $this->assertSame(300, $options['timeout']);
            $this->assertSame(5, $options['connect_timeout']);

            return Http::response([]);
        });
        $server = resolve(Server::class);
        $this->assertTrue($server->get('productionBoardEntries')['success']);
        $this->assertTrue($server->post('ProductionPostingBINs', [])['success']);
        $timeout = (new SendToRestErpJob('1'))->timeout;
        $this->assertGreaterThan(300, $timeout);
        foreach (['redis', 'database', 'beanstalkd'] as $connection) {
            $this->assertGreaterThan($timeout, config('queue.connections.'.$connection.'.retry_after'));
        }
    }

    public function test_long_errors_remain_bounded_and_keep_the_closing_direction_isolates(): void
    {
        Http::fake(['*' => Http::response(['error' => ['message' => str_repeat('Failure. ', 1000)]], 400)]);

        $message = resolve(Server::class)->post('ExitPrints', [])['error']['message'];

        $this->assertLessThanOrEqual(4096, strlen($message));
        $this->assertStringStartsWith("\u{2067}خطای بیزینس سنترال :: \u{2068}", $message);
        $this->assertStringEndsWith("[TRUNCATED] \u{2069}\u{2069}", $message);
    }

    public function test_successful_responses_keep_the_original_contract(): void
    {
        Http::fake(['*' => Http::response(['accepted' => true])]);

        $this->assertSame([
            'success' => true, 'data' => ['accepted' => true], 'error' => null, 'http_status' => 200,
        ], resolve(Server::class)->post('ExitPrints', []));
    }
}
