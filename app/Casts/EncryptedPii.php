<?php

namespace App\Casts;

use App\Support\Pii\PiiCipher;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * Stores the attribute encrypted with the PII key (not APP_KEY).
 *
 * @implements CastsAttributes<string|null, string|null>
 */
class EncryptedPii implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        return $value === null ? null : app(PiiCipher::class)->decrypt((string) $value);
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        return $value === null || $value === '' ? null : app(PiiCipher::class)->encrypt((string) $value);
    }
}
