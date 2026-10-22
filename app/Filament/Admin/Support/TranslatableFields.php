<?php

namespace App\Filament\Admin\Support;

use App\Domain\Institution\Services\SupportedLocales;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Fieldset;

/**
 * One input per supported locale for a translatable jsonb attribute. The fallback locale
 * value is required, matching the database rule that every translatable value has it.
 */
final class TranslatableFields
{
    public static function make(string $attribute, string $label, bool $required = true): Fieldset
    {
        $locales = app(SupportedLocales::class);
        $fallback = (string) config('app.fallback_locale');

        return Fieldset::make($label)
            ->schema(array_map(
                fn (string $locale) => TextInput::make("{$attribute}.{$locale}")
                    ->label($locales->label($locale))
                    ->required($required && $locale === $fallback)
                    ->maxLength(255),
                $locales->all(),
            ));
    }
}
