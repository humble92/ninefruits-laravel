<?php

namespace App\Models\Concerns;

use Spatie\Translatable\HasTranslations;

/**
 * Translatable jsonb attributes ({"en": "...", "ko": "..."}).
 *
 * Models list the attributes in a public `$translatable` property. The current locale is
 * returned with fallback to the institution's fallback locale and then any available value,
 * the same order as the util.i18n() database function.
 */
trait HasTranslatableAttributes
{
    use HasTranslations;

    /**
     * @return array<string, string>
     */
    public function translationsFor(string $attribute): array
    {
        return $this->getTranslations($attribute);
    }
}
