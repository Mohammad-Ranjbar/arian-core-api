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
    ];

    public function urlFor(string $endpoint): string
    {
        if (! array_key_exists($endpoint, self::ENDPOINTS)) {
            throw new InvalidArgumentException('Unsupported ERP endpoint.');
        }

        $baseUrl = config($endpoint === 'productionBoardEntries' ? 'rest_erp.production_base_url' : 'rest_erp.base_url');
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
            $missing[] = 'PHP curl extension';
        }
        if (! is_string($username) || $username === '') {
            $missing[] = 'ERP username';
        }
        if (! is_string($password) || $password === '') {
            $missing[] = 'ERP password';
        }
        if ($missing !== []) {
            return $this->failure('ERP_CONFIGURATION_ERROR', 'Missing configuration: '.implode(', ', $missing), 503);
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
            $onResponse(['http_status' => $response->status()]);
        }

        if (strlen($rawBody) > (int) config('rest_erp.max_response_bytes', 1048576)) {
            return $this->failure('ERP_RESPONSE_TOO_LARGE',
                'ERP HTTP '.$response->status().': response size '.strlen($rawBody).' bytes exceeds the '.config('rest_erp.max_response_bytes', 1048576).' byte limit.', 502
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

        return 'ERP HTTP '.$response->status().': '.(trim($detail) !== '' ? $detail : 'Empty response body.');
    }

    private function failure(
        string $code,
        string $message,
        int $httpStatus
    ): array {
        $sanitizer = resolve(RestErpLogSanitizer::class);

        return [
            'success' => false,
            'data' => null,
            'error' => [
                'code' => $sanitizer->errorText($code, 255),
                'message' => $sanitizer->errorText($message),
            ],
            'http_status' => $httpStatus,
        ];
    }
}
