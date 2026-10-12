<?php

namespace App\Services\Erp\RestApi;

use Illuminate\Http\Request;

readonly class RestErpRequestContext
{
    public function __construct(private RestErpLogSanitizer $sanitizer) {}

    public function capture(?int $userId): array
    {
        $request = app()->bound('request') ? request() : null;
        $http = $request instanceof Request && ! app()->runningInConsole();
        // HTTP tests/embedded requests also carry an explicit network peer.
        $http = $http || ($request instanceof Request && $request->server('REMOTE_ADDR') !== null);

        $context = ['source' => $http ? 'http' : 'console', 'user_id' => $userId];
        if (! $http) {
            return ['request_context' => $context];
        }

        $headers = [];
        foreach (config('rest_erp.request_headers', []) as $name) {
            if ($request->headers->has($name)) {
                $headers[$name] = $request->headers->get($name);
            }
        }
        $ip = $this->ip($request->ip());
        $peer = $this->ip($request->server('REMOTE_ADDR'));
        $computer = $this->sanitizer->text($request->header('X-Client-Computer-Name'), 128);
        $agent = $this->sanitizer->text($request->userAgent(), 4096);
        $context += [
            'method' => $request->method(),
            'path' => $request->path(),
            'route_name' => $request->route()?->getName(),
            'route_uri' => $request->route()?->uri(),
            'scheme' => $request->getScheme(),
            'host' => $request->getHost(),
            'port' => $request->getPort(),
            'client_ip' => $ip,
            'peer_ip' => $peer,
            'client_ips' => array_values(array_filter(array_map($this->ip(...), $request->ips()))),
            'headers' => $headers,
            'query' => $request->query(),
            'input' => $request->input(),
            'client_computer_name' => $computer,
            'computer_name_source' => $computer ? 'unverified_client_header' : null,
            'client_request_id' => $request->header('X-Request-ID'),
            'client_timezone' => $request->header('X-Client-Timezone'),
            'client_metadata_trust' => 'unverified',
            'http_protocol' => $request->server('SERVER_PROTOCOL'),
            'origin' => $this->origin($request->header('Origin')),
            'referer_origin' => $this->origin($request->header('Referer')),
            'files' => $this->files($request->allFiles()),
        ];

        return [
            'client_ip' => $ip,
            'peer_ip' => $peer,
            'user_agent' => $agent,
            'client_computer_name' => $computer,
            'request_context' => $this->sanitizer->sanitize($context),
        ];
    }

    private function ip(?string $value): ?string
    {
        return filter_var($value, FILTER_VALIDATE_IP) ? $value : null;
    }

    private function origin(?string $url): ?string
    {
        $parts = $url ? parse_url($url) : null;
        if (! is_array($parts) || ! in_array($parts['scheme'] ?? null, ['http', 'https'], true) || ! isset($parts['host'])) {
            return null;
        }

        return $this->sanitizer->text($parts['scheme'].'://'.$parts['host'].(isset($parts['port']) ? ':'.$parts['port'] : ''), 512);
    }

    private function files(array $files, int $depth = 0): array
    {
        if ($depth > 3) {
            return ['_truncated' => true];
        }
        $result = [];
        foreach (array_slice($files, 0, 50, true) as $key => $file) {
            $result[$key] = is_array($file) ? $this->files($file, $depth + 1) : [
                'client_name' => $file->getClientOriginalName(),
                'client_mime_type' => $file->getClientMimeType(),
                'size' => $file->getSize(),
                'upload_error' => $file->getError(),
            ];
        }

        return $result;
    }
}
