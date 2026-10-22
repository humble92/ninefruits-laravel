<?php

namespace App\Filament\Admin\Resources\People\Schemas;

use App\Domain\Institution\Services\SupportedLocales;
use App\Domain\Person\Enums\Gender;
use App\Models\Institution\Country;
use App\Models\Institution\LookupValue;
use App\Models\Person\Person;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PersonForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components(self::components());
    }

    /**
     * Shared with the "register student" form for new people.
     *
     * @return list<Section>
     */
    public static function components(string $prefix = ''): array
    {
        $locales = app(SupportedLocales::class);

        return [
            Section::make(__('people.sections.name'))->columns(2)->schema([
                TextInput::make($prefix.'family_name')->label(__('people.fields.family_name'))->helperText(__('people.help.romanized'))->required()->maxLength(255),
                TextInput::make($prefix.'given_name')->label(__('people.fields.given_name'))->maxLength(255),
                Fieldset::make(__('people.fields.name_variants'))->columnSpanFull()->schema(array_map(
                    fn (string $locale) => TextInput::make($prefix."name_variants.{$locale}")->label($locales->label($locale))->maxLength(255),
                    array_values(array_diff($locales->all(), ['en'])),
                )),
            ]),
            Section::make(__('people.sections.personal'))->columns(2)->schema([
                DatePicker::make($prefix.'birth_date')->label(__('people.fields.birth_date'))->maxDate(now()),
                Select::make($prefix.'gender')->label(__('people.fields.gender'))
                    ->options(collect(Gender::cases())->mapWithKeys(fn (Gender $gender) => [$gender->value => $gender->label()])),
                Select::make($prefix.'nationality_country_id')->label(__('people.fields.nationality'))
                    ->options(fn () => Country::query()->where('is_active', true)->get()->sortBy('name')->mapWithKeys(fn (Country $country) => [$country->id => $country->name]))
                    ->searchable(),
                Select::make($prefix.'ethnicity_id')->label(__('people.fields.ethnicity'))
                    ->options(fn () => LookupValue::query()->ofType('ethnicity')->where('is_active', true)->orderBy('sort_order')->get()->mapWithKeys(fn (LookupValue $value) => [$value->id => $value->name])),
                TextInput::make($prefix.'national_id_input')->label(__('people.fields.national_id'))
                    ->helperText(fn (?Person $record) => $record?->national_id_encrypted !== null ? __('people.help.national_id_keep') : __('people.help.encrypted'))
                    ->password()->revealable()->maxLength(64)->dehydrated(fn (?string $state) => filled($state)),
                TextInput::make($prefix.'passport_number_input')->label(__('people.fields.passport_number'))
                    ->helperText(fn (?Person $record) => $record?->passport_number_encrypted !== null ? __('people.help.national_id_keep') : __('people.help.encrypted'))
                    ->password()->revealable()->maxLength(64)->dehydrated(fn (?string $state) => filled($state)),
            ]),
            Section::make(__('people.sections.contact'))->columns(2)->schema([
                TextInput::make($prefix.'email')->label(__('people.fields.contact_email'))->email()->maxLength(255),
                TextInput::make($prefix.'phone')->label(__('people.fields.phone'))->tel()->maxLength(32),
                Textarea::make($prefix.'address')->label(__('people.fields.address'))->columnSpanFull(),
                TextInput::make($prefix.'postal_code')->label(__('people.fields.postal_code'))->maxLength(16),
            ]),
            Section::make(__('people.sections.photo'))->schema([
                SpatieMediaLibraryFileUpload::make($prefix.'photo')->label(__('people.fields.photo'))
                    ->collection(Person::PHOTO_COLLECTION)
                    ->disk((string) config('media-library.disk_name'))
                    ->visibility('private')
                    ->image()
                    ->maxSize(2048),
            ])->visible($prefix === ''),
        ];
    }

    /**
     * Turns form state into model attributes: empty name variants are dropped and the
     * identity numbers are only replaced when something was typed.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function toAttributes(array $data): array
    {
        $data['name_variants'] = array_filter((array) ($data['name_variants'] ?? []), fn (?string $value) => filled($value)) ?: null;

        foreach (['national_id', 'passport_number'] as $field) {
            if (filled($data["{$field}_input"] ?? null)) {
                $data["{$field}_encrypted"] = $data["{$field}_input"];
            }

            unset($data["{$field}_input"]);
        }

        unset($data['photo']);

        return $data;
    }
}
