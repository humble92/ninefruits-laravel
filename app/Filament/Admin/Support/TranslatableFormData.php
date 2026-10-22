<?php

namespace App\Filament\Admin\Support;

use App\Models\Contracts\Translatable;
use Illuminate\Database\Eloquent\Model;

/**
 * Filament fills forms from attributesToArray(), which returns translatable attributes in
 * the current locale only. This puts every translation back so the per-locale inputs of
 * TranslatableFields are filled.
 */
final class TranslatableFormData
{
    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function fill(array $data, Model $record): array
    {
        if (! $record instanceof Translatable) {
            return $data;
        }

        foreach ($record->getTranslatableAttributes() as $attribute) {
            $data[$attribute] = $record->translationsFor($attribute);
        }

        return $data;
    }
}
