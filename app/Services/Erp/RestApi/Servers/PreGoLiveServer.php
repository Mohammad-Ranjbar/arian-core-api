<?php

namespace App\Services\Erp\RestApi\Servers;

use App\Services\Erp\RestApi\RestErpLogSanitizer;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class PreGoLiveServer
{
    private const array ENDPOINTS = [
        'qcStatuses' => 'qcStatuses',
        'CreateTransferZones' => 'CreateTransferZones',
        'ItemReclassJournals' => 'ItemReclassJournals',
        'ApproveExitZones' => 'ApproveExitZones',
        'ApproveReturnZones' => 'ApproveReturnZones',
        'ExitRequests' => 'ExitRequests',
        'GroupExits' => 'GroupExits',
        'ExitPrints' => 'ExitPrints',
    ];

    public function urlFor(string $endpoint): string
    {
        if (! array_key_exists($endpoint, self::ENDPOINTS)) {
            throw new InvalidArgumentException('Unsupported ERP endpoint.');
        }

        $baseUrl = config('rest_erp.base_url');
        $parts = is_string($baseUrl) ? parse_url($baseUrl) : false;
        if (! is_array($parts)
            || ! in_array($parts['scheme'] ?? null, ['http', 'https'], true)
            || ! in_array($parts['host'] ?? null, config('rest_erp.allowed_hosts', []), true)
            || isset($parts['user']) || isset($parts['pass'])
            || isset($parts['query']) || isset($parts['fragment'])) {
            throw new InvalidArgumentException('Invalid ERP base URL configuration.');
        }

        return rtrim($baseUrl, '/').'/'.self::ENDPOINTS[$endpoint];
    }

    public function post(string $endpoint, array $data, ?callable $onResponse = null, ?string $requestId = null): array
    {
        $url = $this->urlFor($endpoint);

        $username = config('erp.auth.0');
        $password = config('erp.auth.1');

        if (
            ! extension_loaded('curl')
            || ! is_string($username)
            || $username === ''
            || ! is_string($password)
            || $password === ''
        ) {
            Log::error('ERP NTLM configuration is incomplete');

            return $this->failure(
                'ERP_CONFIGURATION_ERROR',
                'تنظیمات ارتباط با سامانه ERP کامل نیست.',
                503
            );
        }

        try {
            $response = Http::acceptJson()
                ->asJson()
                ->withHeaders($requestId ? ['X-Request-ID' => $requestId] : [])
                ->withOptions([
                    'auth' => [$username, $password, 'ntlm'],
                ])
                ->connectTimeout(5)
                ->timeout(15)
                ->withoutRedirecting()
                ->post(
                    $url,
                    $data
                );
        } catch (ConnectionException $exception) {
            Log::warning('Could not connect to ERP', ['exception_class' => $exception::class]);

            return $this->failure(
                'ERP_CONNECTION_ERROR',
                'ارتباط با سامانه ERP برقرار نشد.',
                503
            );
        }

        $rawBody = $response->body();
        if ($onResponse) {
            $onResponse(['http_status' => $response->status()]);
        }

        if (in_array($response->status(), [401, 403], true)) {
            Log::warning('ERP NTLM authentication failed', [
                'http_status' => $response->status(),
            ]);

            return $this->failure(
                'ERP_AUTH_ERROR',
                'احراز هویت در سامانه ERP ناموفق بود.',
                502
            );
        }

        if (strlen($rawBody) > (int) config('rest_erp.max_response_bytes', 1048576)) {
            return $this->failure('ERP_RESPONSE_TOO_LARGE', 'حجم پاسخ ERP بیش از حد مجاز است.', 502);
        }

        $body = $response->json();
        $code = is_array($body) ? data_get($body, 'error.code') : null;
        $message = is_array($body) ? data_get($body, 'error.message') : null;

        if ($code === 'Application_DialogException' && is_string($message)) {
            return $this->failure($code, $message, 422);
        }

        if (
            ! $response->successful()
            || ! is_array($body)
            || array_key_exists('error', $body)
        ) {
            Log::warning('ERP returned an invalid response', [
                'http_status' => $response->status(),
                'erp_code' => is_string($code) ? resolve(RestErpLogSanitizer::class)->text($code, 255) : null,
            ]);

            return $this->failure(
                'ERP_ERROR',
                'خطا در پاسخ سامانه ERP.',
                502
            );
        }

        return [
            'success' => true,
            'data' => $body,
            'error' => null,
            'http_status' => $response->status(),
        ];
    }

    private function failure(
        string $code,
        string $message,
        int $httpStatus
    ): array {
        return [
            'success' => false,
            'data' => null,
            'error' => [
                'code' => $code,
                'message' => $message,
            ],
            'http_status' => $httpStatus,
        ];
    }
}
