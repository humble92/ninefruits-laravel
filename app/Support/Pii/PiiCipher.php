<?php

namespace App\Support\Pii;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Encryption\Encrypter;
use RuntimeException;

/**
 * Encrypts personal data with the dedicated PII key. Previous keys are accepted for
 * decryption so that data can be re-encrypted gradually (pii:reencrypt).
 */
class PiiCipher
{
    private ?Encrypter $encrypter = null;

    private ?Encrypter $currentKeyOnly = null;

    public function encrypt(string $value): string
    {
        return $this->encrypter()->encryptString($value);
    }

    public function decrypt(string $payload): string
    {
        return $this->encrypter()->decryptString($payload);
    }

    public function isEncryptedWithCurrentKey(string $payload): bool
    {
        try {
            $this->currentKeyOnly ??= new Encrypter(self::parseKey((string) config('pii.key')), (string) config('pii.cipher'));
            $this->currentKeyOnly->decryptString($payload);

            return true;
        } catch (DecryptException) {
            return false;
        }
    }

    public static function parseKey(string $key): string
    {
        if ($key === '') {
            throw new RuntimeException('PII_ENCRYPTION_KEY is not set. Generate one with php artisan pii:generate-key.');
        }

        return str_starts_with($key, 'base64:') ? (string) base64_decode(substr($key, 7), true) : $key;
    }

    private function encrypter(): Encrypter
    {
        return $this->encrypter ??= (new Encrypter(self::parseKey((string) config('pii.key')), (string) config('pii.cipher')))
            ->previousKeys(array_map(self::parseKey(...), (array) config('pii.previous_keys')));
    }
}
