<?php

namespace App\Services\Erp\RestApi\Servers;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class PreGoLiveServer
{
    private const string BASE_URL =
        'http://172.16.0.154:7048/PreBC/api/arianCapital/product/v1.0';

    private const array ENDPOINTS = [
        'qcStatuses' => 'qcStatuses',
        'CreateTransferZones' => 'CreateTransferZones',
    ];

    public function post(string $endpoint, array $data): array
    {
        if (! array_key_exists($endpoint, self::ENDPOINTS)) {
            throw new InvalidArgumentException('Unsupported ERP endpoint.');
        }

        $username = config('erp.auth')[0];
        $password = config('erp.auth')[1];

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
                ->withOptions([
                    'auth' => [$username, $password, 'ntlm'],
                ])
                ->connectTimeout(5)
                ->timeout(15)
                ->withoutRedirecting()
                ->post(
                    self::BASE_URL.'/'.self::ENDPOINTS[$endpoint],
                    $data
                );
        } catch (ConnectionException $exception) {
            Log::warning('Could not connect to ERP. '.$exception->getMessage());

            return $this->failure(
                'ERP_CONNECTION_ERROR',
                'ارتباط با سامانه ERP برقرار نشد.',
                503
            );
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
                'erp_code' => is_string($code) ? $code : null,
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
            'http_status' => 201,
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
