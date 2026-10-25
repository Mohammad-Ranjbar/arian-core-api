<?php

namespace Tests\Unit;

use App\Services\Erp\ErpWsdlClient;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Promise\FulfilledPromise;
use GuzzleHttp\Promise\RejectedPromise;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Mockery;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
class ErpWsdlTimeoutTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $container = new Container;
        $container->instance('config', new Repository(['erp' => ['auth' => ['user', 'password', 'ntlm']]]));
        Container::setInstance($container);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        Container::setInstance(null);
        parent::tearDown();
    }

    public function test_soap_request_has_bounded_timeouts_and_preserves_normalized_success(): void
    {
        $client = Mockery::mock('overload:GuzzleHttp\Client');
        $client->shouldReceive('sendAsync')->once()->withArgs(function ($request, $options): bool {
            $this->assertSame(5, $options['connect_timeout']);
            $this->assertSame(300, $options['timeout']);
            $this->assertSame(['user', 'password', 'ntlm'], $options['auth']);
            $this->assertStringContainsString('<VIN>TEST</VIN>', (string) $request->getBody());

            return true;
        })->andReturn(new FulfilledPromise(new Response(200, [], '<return>{"Status":"OK","Message":"done"}</return>')));

        $this->assertSame(['status' => 'ok', 'message' => 'done'], ErpWsdlClient::run('http://erp.test/Service', 'Send', ['VIN' => 'TEST']));
    }

    public function test_response_less_connection_failure_does_not_call_get_response_on_connect_exception(): void
    {
        $client = Mockery::mock('overload:GuzzleHttp\Client');
        $client->shouldReceive('sendAsync')->once()->andReturn(new RejectedPromise(new ConnectException('timeout', new Request('POST', 'http://erp.test/Service'))));

        $result = ErpWsdlClient::run('http://erp.test/Service', 'Send');
        $this->assertSame('error', $result['status']);
        $this->assertStringContainsString('پیش از ارسال مجدد', $result['message']);
    }

    public function test_soap_http_error_keeps_the_provider_fault_message(): void
    {
        $client = Mockery::mock('overload:GuzzleHttp\Client');
        $client->shouldReceive('sendAsync')->once()->andReturn(new RejectedPromise(new RequestException('HTTP error', new Request('POST', 'http://erp.test/Service'), new Response(500, [], '<fault>provider failure</fault>'))));

        $result = ErpWsdlClient::run('http://erp.test/Service', 'Send');
        $this->assertSame('error', $result['status']);
        $this->assertStringContainsString('provider failure', $result['message']);
    }
}
