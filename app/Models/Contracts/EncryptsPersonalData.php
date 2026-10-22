<?php

namespace App\Models\Contracts;

use App\Support\Pii\PiiType;

/**
 * Models with encrypted personal data columns (implemented with the HasEncryptedPii trait).
 */
interface EncryptsPersonalData
{
    /**
     * Attribute names (without the _encrypted suffix) and their type.
     *
     * @return array<string, PiiType>
     */
    public function encryptedPiiAttributes(): array;
}
