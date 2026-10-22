<?php

namespace App\Support\Pii;

use RuntimeException;

/**
 * Keyed hash for exact match search on encrypted values (stored in *_hash columns).
 */
class PiiHasher
{
    public function hash(string $value, PiiType $type): string
    {
        $key = (string) config('pii.hash_key');

        if ($key === '') {
            throw new RuntimeException('PII_HASH_KEY is not set. Generate one with php artisan pii:generate-key.');
        }

        return hash_hmac('sha256', $type->value.':'.$type->normalize($value), PiiCipher::parseKey($key));
    }
}
