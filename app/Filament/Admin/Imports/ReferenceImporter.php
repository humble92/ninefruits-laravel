<?php

namespace App\Filament\Admin\Imports;

use App\Domain\Institution\Services\SupportedLocales;
use Filament\Actions\Imports\Exceptions\RowImportFailedException;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Number;

/**
 * Base for reference data CSV importers, used by the admin screens and by
 * `php artisan reference:import` alike.
 *
 * - Rows are matched by code, so importing the same file again updates instead of
 *   duplicating.
 * - Translatable attributes have one column per supported locale ({attribute}_{locale});
 *   empty cells keep existing translations. The fallback locale is required for new rows.
 * - Each row is saved in a savepoint: a database error fails that row only (PostgreSQL
 *   would otherwise abort the whole chunk).
 */
abstract class ReferenceImporter extends Importer
{
    /**
     * @return list<ImportColumn>
     */
    protected static function translatableColumns(string $attribute, bool $required = true): array
    {
        $fallback = (string) config('app.fallback_locale');

        return array_map(
            fn (string $locale) => ImportColumn::make("{$attribute}_{$locale}")
                ->label("{$attribute}_{$locale}")
                ->rules(fn (?Model $record) => $required && $locale === $fallback && ! $record?->exists ? ['required', 'max:255'] : ['nullable', 'max:255'])
                ->fillRecordUsing(function (Model $record, ?string $state) use ($attribute, $locale): void {
                    if (filled($state) && method_exists($record, 'setTranslation')) {
                        $record->setTranslation($attribute, $locale, $state);
                    }
                }),
            app(SupportedLocales::class)->all(),
        );
    }

    public function saveRecord(): void
    {
        try {
            DB::transaction(fn () => parent::saveRecord());
        } catch (QueryException $exception) {
            throw new RowImportFailedException(__('reference.import.row_rejected', ['message' => strtok($exception->getPrevious()?->getMessage() ?? $exception->getMessage(), "\n")]));
        }
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = __('reference.import.completed', ['count' => Number::format($import->successful_rows)]);

        if ($failed = $import->getFailedRowsCount()) {
            $body .= ' '.__('reference.import.failed', ['count' => Number::format($failed)]);
        }

        return $body;
    }
}
