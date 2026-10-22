<?php

namespace App\Models\Concerns;

use Spatie\Translatable\HasTranslations;

/**
 * Translatable jsonb attributes ({"en": "...", "ko": "..."}).
 *
 * Models list the attributes in a public `$translatable` property. The current locale is
 * returned with fallback to the institution's fallback locale and then any available value,
 * the same order as the util.i18n() database function.
 *
 * Assigning an array replaces all translations (spatie merges them, so a cleared language
 * could never be removed). Empty values are dropped; nothing left stores NULL.
 */
trait HasTranslatableAttributes
{
    use HasTranslations {
        setAttribute as setTranslatableAttribute;
    }

    /**
     * @param  string  $key
     * @param  mixed  $value
     * @return mixed
     */
    public function setAttribute($key, $value)
    {
        if ($this->isTranslatableAttribute($key) && is_array($value) && ($value === [] || ! array_is_list($value))) {
            $translations = array_filter($value, fn (mixed $text) => $text !== null && $text !== '');

            $this->attributes[$key] = $translations === [] ? null : $this->asJson($translations);

            return $this;
        }

        return $this->setTranslatableAttribute($key, $value);
    }

    /**
     * @return array<string, string>
     */
    public function translationsFor(string $attribute): array
    {
        return $this->getTranslations($attribute);
    }
}
