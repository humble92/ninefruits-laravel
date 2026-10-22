<?php

namespace App\Models\Contracts;

/**
 * Models with translatable jsonb attributes (implemented by HasTranslatableAttributes).
 */
interface Translatable
{
    /**
     * @return array<int, string>
     */
    public function getTranslatableAttributes(): array;

    /**
     * @return array<string, string>
     */
    public function translationsFor(string $attribute): array;
}
