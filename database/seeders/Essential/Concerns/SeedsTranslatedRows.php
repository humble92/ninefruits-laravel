<?php

namespace Database\Seeders\Essential\Concerns;

use App\Models\Contracts\Translatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;

/**
 * Idempotent seeding of translatable rows: create missing rows, and for existing rows only
 * add translations for languages that have none yet. Values administrators edited stay.
 */
trait SeedsTranslatedRows
{
    /**
     * @return list<string>
     */
    protected function installedLocales(): array
    {
        return collect(File::directories(lang_path()))->map(fn (string $path) => basename($path))->sort()->values()->all();
    }

    /**
     * @template TModel of Model&Translatable
     *
     * @param  class-string<TModel>  $modelClass
     * @param  array<string, mixed>  $keys
     * @param  array<string, array<string, string>>  $translations  attribute => [locale => text]
     * @param  array<string, mixed>  $attributes
     * @return TModel
     */
    protected function seedRow(string $modelClass, array $keys, array $translations, array $attributes = []): Model
    {
        $model = $modelClass::query()->where($keys)->first();

        if ($model === null) {
            return $modelClass::query()->create([...$keys, ...$attributes, ...$translations]);
        }

        foreach ($translations as $attribute => $texts) {
            $existing = $model->translationsFor($attribute);
            $missing = array_diff_key($texts, $existing);

            if ($missing !== []) {
                $model->setAttribute($attribute, [...$existing, ...$missing]);
            }
        }

        if ($model->isDirty()) {
            $model->save();
        }

        return $model;
    }
}
