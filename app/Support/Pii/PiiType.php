<?php

namespace App\Support\Pii;

enum PiiType: string
{
    case NationalId = 'national_id';
    case PassportNumber = 'passport_number';
    case PhoneNumber = 'phone_number';
    case Email = 'email';
    case Generic = 'generic';

    /**
     * Normalization before hashing, so that "900101-1234567" and "9001011234567" match.
     */
    public function normalize(string $value): string
    {
        return match ($this) {
            self::NationalId, self::PassportNumber, self::PhoneNumber => strtoupper((string) preg_replace('/[^A-Za-z0-9]/', '', $value)),
            self::Email => mb_strtolower(trim($value)),
            self::Generic => trim($value),
        };
    }

    public function mask(string $value): string
    {
        if ($this === self::Email && str_contains($value, '@')) {
            [$local, $domain] = explode('@', $value, 2);

            return mb_substr($local, 0, 1).str_repeat('*', max(mb_strlen($local) - 1, 1)).'@'.$domain;
        }

        $visible = 4;
        $length = mb_strlen($value);

        if ($length <= $visible) {
            return str_repeat('*', $length);
        }

        return str_repeat('*', $length - $visible).mb_substr($value, -$visible);
    }
}
