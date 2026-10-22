<?php

namespace App\Filament\Admin\Imports\Concerns;

use App\Domain\Institution\Services\SupportedLocales;
use App\Domain\Person\Enums\Gender;
use App\Models\Person\Person;
use Carbon\CarbonImmutable;
use Filament\Actions\Imports\ImportColumn;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

/**
 * Person columns for importers whose record (student record, instructor) belongs to a
 * person. The importer keeps the person as the record's `person` relation and saves it first.
 *
 * Columns: family_name, given_name, name_{locale} (name as written in that language),
 * birth_date, gender, email, phone, national_id. A new row with a national_id that matches
 * an existing person is attached to that person instead of creating a duplicate.
 */
trait ImportsPersonColumns
{
    /**
     * @return list<ImportColumn>
     */
    protected static function personColumns(): array
    {
        $person = fn (Model $record): Person => $record->getRelation('person');

        return [
            ImportColumn::make('family_name')->requiredMappingForNewRecordsOnly()
                ->rules(fn (?Model $record) => $record?->exists ? ['nullable', 'max:255'] : ['required', 'max:255'])
                ->fillRecordUsing(function (Model $record, ?string $state) use ($person): void {
                    if (filled($state)) {
                        $person($record)->family_name = $state;
                    }
                }),
            ImportColumn::make('given_name')->rules(['nullable', 'max:255'])
                ->fillRecordUsing(fn (Model $record, ?string $state) => filled($state) ? $person($record)->given_name = $state : null),
            ...array_map(
                fn (string $locale) => ImportColumn::make("name_{$locale}")->label("name_{$locale}")->rules(['nullable', 'max:255'])
                    ->fillRecordUsing(function (Model $record, ?string $state) use ($person, $locale): void {
                        if (filled($state)) {
                            $person($record)->name_variants = [...($person($record)->name_variants ?? []), $locale => $state];
                        }
                    }),
                app(SupportedLocales::class)->all(),
            ),
            ImportColumn::make('birth_date')->rules(['nullable', 'date'])
                ->fillRecordUsing(fn (Model $record, ?string $state) => filled($state) ? $person($record)->birth_date = CarbonImmutable::parse($state) : null),
            ImportColumn::make('gender')->rules(['nullable', Rule::enum(Gender::class)])
                ->fillRecordUsing(fn (Model $record, ?string $state) => filled($state) ? $person($record)->gender = Gender::from($state) : null),
            ImportColumn::make('email')->rules(['nullable', 'email', 'max:255'])
                ->fillRecordUsing(fn (Model $record, ?string $state) => filled($state) ? $person($record)->email = $state : null),
            ImportColumn::make('phone')->rules(['nullable', 'max:32'])
                ->fillRecordUsing(fn (Model $record, ?string $state) => filled($state) ? $person($record)->phone = $state : null),
            ImportColumn::make('national_id')->rules(['nullable', 'max:64'])
                ->fillRecordUsing(fn (Model $record, ?string $state) => filled($state) ? $person($record)->national_id_encrypted = $state : null),
        ];
    }

    /**
     * The existing person for a new record: matched by national id when the row has one.
     */
    protected function personForNewRecord(): Person
    {
        $nationalId = $this->data['national_id'] ?? null;

        if (filled($nationalId)) {
            $existing = Person::query()->notMerged()->wherePii('national_id', (string) $nationalId)->first();

            if ($existing !== null) {
                return $existing;
            }
        }

        return new Person;
    }

    protected function savePerson(Model $record): void
    {
        /** @var Person $person */
        $person = $record->getRelation('person');
        $person->save();
        $record->setAttribute('person_id', $person->id);
    }
}
