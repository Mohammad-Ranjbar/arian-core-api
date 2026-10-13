<?php

namespace App\Services\Erp\RestApi;

use Throwable;

class RestErpLogSanitizer
{
    public function sanitize(mixed $value): mixed
    {
        $limit = max(128, (int) config('rest_erp.log_byte_limit', 65536));
        for ($pass = 0; $pass < 8; $pass++) {
            $items = (int) config('rest_erp.log_item_limit', 500);
            $bytes = intdiv($limit, 2 ** $pass);
            $safe = $this->walk($value, 0, $items, $bytes);
            // Budget the serialized representation too, including JSON escaping of Unicode/quotes.
            if (strlen(json_encode($safe, JSON_THROW_ON_ERROR)) <= $limit) {
                return $safe;
            }
        }

        return '[TRUNCATED]';
    }

    public function text(?string $value, int $limit = 4096): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = mb_convert_encoding($value, 'UTF-8', 'UTF-8');
        $value = preg_replace('/[\x00-\x1F\x7F]/u', '', $value);
        $value = preg_replace('/\b(?:Bearer|Basic)\s+[^\s,;]+/i', '[REDACTED]', $value);
        $value = preg_replace('/\beyJ[A-Za-z0-9_-]+\.[A-Za-z0-9_-]+\.[A-Za-z0-9_-]+\b/', '[REDACTED]', $value);
        $value = preg_replace('/\b(password|passwd|secret|token|api[_-]?key|authorization)\s*[:=]\s*[^\s,;&]+/i', '$1=[REDACTED]', $value);
        $value = preg_replace('~\b([a-z][a-z0-9+.-]*)://[^/\s:@]+:[^@\s/]+@~i', '$1://[REDACTED]@', $value);

        if (strlen($value) > $limit) {
            $marker = '[TRUNCATED]';

            return mb_strcut($value, 0, max(0, $limit - strlen($marker)), 'UTF-8').substr($marker, 0, max(0, $limit));
        }

        return $value;
    }

    public function errorText(string $value, int $limit = 4096): string
    {
        $connection = config('database.connections.'.config('database.default'), []);
        $credentials = array_filter([
            config('erp.auth.0'), config('erp.auth.1'),
            $connection['password'] ?? null,
        ], fn ($credential) => is_string($credential) && $credential !== '');
        $value = str_replace($credentials, '[REDACTED]', $value);
        $value = preg_replace('/("(?:password|passwd|secret|token|api[_-]?key|authorization|cookie|set-cookie)"\s*:\s*)"(?:\\\\.|[^"\\\\])*"/i', '$1"[REDACTED]"', $value);
        $value = preg_replace('/\b(?:NTLM|Negotiate)\s+[A-Za-z0-9+\/]{20,}={0,2}/i', '[REDACTED]', $value);
        $value = preg_replace('/\b(cookie|set-cookie)\s*[:=]\s*[^\r\n]+/i', '$1=[REDACTED]', $value);

        return $this->text($value, $limit) ?? '';
    }

    public function exceptionText(Throwable $exception): string
    {
        $messages = [];
        for ($depth = 0; $exception !== null && $depth < 3; $depth++, $exception = $exception->getPrevious()) {
            $messages[] = $exception::class.': '.$exception->getMessage();
        }

        return $this->errorText(implode(' | ', $messages));
    }

    private function walk(mixed $value, int $depth, int &$items, int &$bytes): mixed
    {
        if ($items-- <= 0 || $bytes <= 0 || $depth > (int) config('rest_erp.log_depth_limit', 8)) {
            return '[TRUNCATED]';
        }

        if (is_array($value)) {
            $result = [];
            foreach ($value as $key => $item) {
                if ($items <= 0 || $bytes <= 0) {
                    $result['_truncated'] = true;
                    break;
                }
                $safeKey = is_string($key) ? $this->text($key, 128) : $key;
                $bytes -= strlen((string) $safeKey);
                $normalized = preg_replace('/[^a-z0-9]/', '', strtolower((string) $key));
                if (preg_match('/password|passwd|pwd|secret|token|authorization|authentication|credentials|cookie|apikey|sessionid|privatekey|connectionstring|signature|^auth$|^otp$|^pin$/', $normalized)) {
                    $result[$safeKey] = '[REDACTED]';
                } else {
                    $result[$safeKey] = $this->walk($item, $depth + 1, $items, $bytes);
                }
            }

            return $result;
        }

        if (is_string($value)) {
            $safe = $this->text($value, min($bytes, (int) config('rest_erp.log_string_limit', 4096)));
            $bytes -= strlen($safe);

            return $safe;
        }

        if (is_float($value) && ! is_finite($value)) {
            return '[INVALID_NUMBER]';
        }

        return is_scalar($value) || $value === null ? $value : '[UNSUPPORTED]';
    }
}
