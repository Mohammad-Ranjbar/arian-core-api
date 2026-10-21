<?php

namespace Tests\Feature;

use App\Services\Erp\RestApi\Servers\Server;
use Illuminate\Support\Env;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use Tests\TestCase;

class ServerConfigurationTest extends TestCase
{
    public function test_one_environment_base_url_configures_get_and_post_requests(): void
    {
        $values = [
            'ERP_REST_BASE_URL' => 'https://erp-live.example.test/BC/api/arianCapital/',
        ];
        $repository = Env::getRepository();
        $original = [];
        foreach ($values as $key => $value) {
            $original[$key] = $repository->get($key);
            $repository->set($key, $value);
        }

        try {
            $config = require base_path('config/rest_erp.php');
            config()->set('rest_erp', $config);
            config()->set('erp.auth', ['test-user', 'test-password', 'ntlm']);
            Http::preventStrayRequests();
            Http::fake([
                $values['ERP_REST_BASE_URL'].'product/v1.0/ExitPrints' => Http::response(['accepted' => true]),
                $values['ERP_REST_BASE_URL'].'product/v1.0/productionBoardEntries' => Http::response(['value' => []]),
            ]);

            $server = resolve(Server::class);
            $this->assertTrue($server->post('ExitPrints', [])['success']);
            $this->assertTrue($server->get('productionBoardEntries')['success']);
            Http::assertSentCount(2);
        } finally {
            foreach ($original as $key => $value) {
                if ($value === null) {
                    $repository->clear($key);
                } else {
                    $repository->set($key, $value);
                }
            }
        }
    }

    public function test_configured_url_still_requires_a_host(): void
    {
        config()->set('rest_erp.base_url', 'https:/BC/api/arianCapital/product/v1.0');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('آدرس پایه بیزینس سنترال در تنظیمات معتبر نیست.');

        resolve(Server::class)->urlFor('ExitPrints');
    }
}
