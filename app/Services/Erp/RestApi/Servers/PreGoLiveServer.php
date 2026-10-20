<?php

namespace App\Services\Erp\RestApi\Servers;

use App\Services\Erp\RestApi\RestErpLogSanitizer;
use GuzzleHttp\Exception\RequestException as GuzzleRequestException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;

class PreGoLiveServer
{
    private const array ENDPOINTS = [
        'ProductionPostingBINs' => 'ProductionPostingBINs',
        'Trackings' => 'Trackings',
        'TransferVehicleProdZones' => 'TransferVehicleProdZones',
        'ImportVehicles' => 'ImportVehicles',
        'qcStatuses' => 'qcStatuses',
        'CreateTransferZones' => 'CreateTransferZones',
        'ItemReclassJournals' => 'ItemReclassJournals',
        'ApproveExitZones' => 'ApproveExitZones',
        'ApproveReturnZones' => 'ApproveReturnZones',
        'ExitRequests' => 'ExitRequests',
        'GroupExits' => 'GroupExits',
        'GroupReturns' => 'GroupReturns',
        'ReturnRequests' => 'ReturnRequests',
        'ExitPrints' => 'ExitPrints',
        'productionBoardEntries' => 'productionBoardEntries',
        'VehicleTypeBlockings' => 'VehicleTypeBlockings',
        'VehicleUnblocks' => 'VehicleUnblocks',
    ];

    public function urlFor(string $endpoint): string
    {
        if (! array_key_exists($endpoint, self::ENDPOINTS)) {
            throw new InvalidArgumentException('عملیات درخواستی بیزینس سنترال پشتیبانی نمی‌شود.');
        }

        $baseUrl = config($endpoint === 'productionBoardEntries' ? 'rest_erp.production_base_url' : 'rest_erp.base_url');
        $parts = is_string($baseUrl) ? parse_url($baseUrl) : false;
        if (! is_array($parts)
            || ! in_array($parts['scheme'] ?? null, ['http', 'https'], true)
            || ! in_array($parts['host'] ?? null, config('rest_erp.allowed_hosts', []), true)
            || isset($parts['user']) || isset($parts['pass'])
            || isset($parts['query']) || isset($parts['fragment'])) {
            throw new InvalidArgumentException('آدرس پایه بیزینس سنترال در تنظیمات معتبر نیست.');
        }

        return rtrim($baseUrl, '/').'/'.self::ENDPOINTS[$endpoint];
    }

    public function post(string $endpoint, array $data, ?callable $onResponse = null, ?string $requestId = null): array
    {
        return $this->request('POST', $endpoint, $data, $onResponse, $requestId);
    }

    public function get(string $endpoint, array $query = []): array
    {
        return $this->request('GET', $endpoint, $query);
    }

    private function request(string $method, string $endpoint, array $data, ?callable $onResponse = null, ?string $requestId = null): array
    {
        $url = $this->urlFor($endpoint);

        $username = config('erp.auth.0');
        $password = config('erp.auth.1');

        $missing = [];
        if (! extension_loaded('curl')) {
            $missing[] = 'افزونه cURL در PHP';
        }
        if (! is_string($username) || $username === '') {
            $missing[] = 'نام کاربری بیزینس سنترال';
        }
        if (! is_string($password) || $password === '') {
            $missing[] = 'رمز عبور بیزینس سنترال';
        }
        if ($missing !== []) {
            return $this->failure('ERP_CONFIGURATION_ERROR', 'تنظیمات لازم برای اتصال به بیزینس سنترال کامل نیست: '.implode('، ', $missing), 503);
        }

        try {
            $response = Http::acceptJson()
                ->asJson()
                ->withHeaders($requestId ? ['X-Request-ID' => $requestId] : [])
                ->withOptions([
                    'auth' => [$username, $password, 'ntlm'],
                ])
                ->connectTimeout(5)
                ->timeout(300)
                ->withoutRedirecting()
                ->send(
                    $method,
                    $url,
                    [$method === 'GET' ? 'query' : 'json' => $data]
                );
        } catch (ConnectionException $exception) {
            return $this->failure(
                'ERP_CONNECTION_ERROR',
                resolve(RestErpLogSanitizer::class)->exceptionText($exception),
                503
            );
        } catch (RequestException $exception) {
            // HTTP exceptions still carry the ERP response; parse it instead of losing its error.
            $response = $exception->response;
        } catch (GuzzleRequestException $exception) {
            if (! $exception->hasResponse()) {
                throw $exception;
            }
            $response = new Response($exception->getResponse());
        }

        $rawBody = $response->body();
        if ($onResponse) {
            $onResponse([
                'http_status' => $response->status(),
                'body' => strlen($rawBody) > (int) config('rest_erp.max_response_bytes', 1048576)
                    ? '[TRUNCATED]'
                    : resolve(RestErpLogSanitizer::class)->sanitize($response->json() ?? $rawBody),
            ]);
        }

        if (strlen($rawBody) > (int) config('rest_erp.max_response_bytes', 1048576)) {
            return $this->failure('ERP_RESPONSE_TOO_LARGE',
                'حجم پاسخ بیزینس سنترال ('.strlen($rawBody).' بایت) از حد مجاز '.config('rest_erp.max_response_bytes', 1048576).' بایت بیشتر است؛ کد وضعیت HTTP: '.$response->status().'.', 502
            );
        }

        $body = $response->json();
        $error = $this->erpError($body, $response->status());
        if ($error !== null) {
            return $error;
        }

        if (in_array($response->status(), [401, 403], true)) {
            return $this->failure('ERP_AUTH_ERROR', $this->responseErrorMessage($response), 502);
        }

        if (! $response->successful() || ! is_array($body)) {
            return $this->failure('ERP_ERROR', $this->responseErrorMessage($response), 502);
        }

        return [
            'success' => true,
            'data' => $body,
            'error' => null,
            'http_status' => $response->status(),
        ];
    }

    private function erpError(mixed $body, int $providerStatus): ?array
    {
        if (! is_array($body) || ! array_key_exists('error', $body)) {
            return null;
        }

        $code = data_get($body, 'error.code');
        $message = is_string($body['error']) ? $body['error'] : data_get($body, 'error.message');
        if (is_array($message)) {
            $message = $message['value'] ?? null;
        }
        $sanitizer = resolve(RestErpLogSanitizer::class);
        if (! is_string($message) || trim($message) === '') {
            $message = json_encode($sanitizer->sanitize($body['error']), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        }
        $code = is_string($code) && trim($code) !== '' ? $code : 'ERP_ERROR';

        return $this->failure($code, $message,
            $code === 'Application_DialogException' && ! in_array($providerStatus, [401, 403], true) ? 422 : 502
        );
    }

    private function responseErrorMessage(Response $response): string
    {
        $detail = $response->json();
        $sanitizer = resolve(RestErpLogSanitizer::class);
        if (is_array($detail)) {
            $detail = json_encode($sanitizer->sanitize($detail), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        } elseif (! is_string($detail)) {
            $detail = strip_tags($response->body());
        }

        return 'پاسخ بیزینس سنترال با کد وضعیت HTTP '.$response->status().' دریافت شد؛ جزئیات: '.(trim($detail) !== '' ? $detail : 'پاسخ بیزینس سنترال خالی است.');
    }

    private function failure(
        string $code,
        string $message,
        int $httpStatus
    ): array {
        $sanitizer = resolve(RestErpLogSanitizer::class);
        // Isolate the Persian label and auto-detect the detail direction without adding HTML.
        $prefix = "\u{2067}خطای بیزینس سنترال :: \u{2068}";
        // Keep a whitespace boundary so subsequent credential sanitization preserves the isolates.
        $suffix = " \u{2069}\u{2069}";
        $message = $prefix.$sanitizer->errorText($message, 4096 - strlen($prefix.$suffix)).$suffix;

        return [
            'success' => false,
            'data' => null,
            'error' => [
                'code' => $sanitizer->errorText($code, 255),
                'message' => $message,
            ],
            'http_status' => $httpStatus,
        ];
    }
}
