<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use InvalidArgumentException;
use JsonException;

class ReadableRestErpParameters implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?array
    {
        return self::decode($value);
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): string
    {
        if (! is_array($value)) {
            throw new InvalidArgumentException('پارامترهای درخواست بیزینس سنترال باید به‌صورت آرایه ارسال شوند.');
        }

        return json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public static function decode(mixed $value): ?array
    {
        if (! is_string($value)) {
            return is_array($value) ? $value : null;
        }
        try {
            $decoded = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            // Read records written by the previous encrypted:array cast without changing them.
            try {
                $decoded = json_decode(Crypt::decryptString($value), true, 512, JSON_THROW_ON_ERROR);
            } catch (DecryptException|JsonException) {
                return null;
            }
        }

        return is_array($decoded) ? $decoded : null;
    }
}
