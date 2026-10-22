<?php

namespace App\Filament\Admin\Resources\People\Schemas;

use App\Models\Person\Person;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PersonInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('people.sections.name'))->columns(3)->schema([
                ImageEntry::make('photo')->label(__('people.fields.photo'))
                    ->state(fn (Person $record) => $record->hasMedia(Person::PHOTO_COLLECTION) ? route('people.photo', $record) : null)
                    ->height(120)->placeholder('-'),
                TextEntry::make('display_name')->label(__('people.fields.name'))->state(fn (Person $record) => $record->displayName()),
                TextEntry::make('romanized_name')->label(__('people.fields.romanized_name'))->state(fn (Person $record) => $record->romanizedName()),
            ]),
            Section::make(__('people.sections.personal'))->columns(3)->schema([
                TextEntry::make('birth_date')->label(__('people.fields.birth_date'))->date()->placeholder('-'),
                TextEntry::make('gender')->label(__('people.fields.gender'))->formatStateUsing(fn (Person $record) => $record->gender?->label())->placeholder('-'),
                TextEntry::make('nationality.name')->label(__('people.fields.nationality'))->placeholder('-'),
                TextEntry::make('ethnicity.name')->label(__('people.fields.ethnicity'))->placeholder('-'),
                TextEntry::make('national_id_masked')->label(__('people.fields.national_id'))
                    ->state(fn (Person $record) => $record->maskedPii('national_id'))->placeholder('-'),
                TextEntry::make('passport_number_masked')->label(__('people.fields.passport_number'))
                    ->state(fn (Person $record) => $record->maskedPii('passport_number'))->placeholder('-'),
            ]),
            Section::make(__('people.sections.contact'))->columns(3)->schema([
                TextEntry::make('email')->label(__('people.fields.contact_email'))->placeholder('-'),
                TextEntry::make('phone')->label(__('people.fields.phone'))->placeholder('-'),
                TextEntry::make('address')->label(__('people.fields.address'))->placeholder('-'),
            ]),
            Section::make(__('people.sections.account'))->columns(3)->schema([
                TextEntry::make('user.email')->label(__('people.fields.login_email'))->placeholder(__('people.fields.no_account')),
                TextEntry::make('user.last_login_at')->label(__('admin.users.fields.last_login_at'))->dateTime()->placeholder('-'),
                TextEntry::make('mergedInto.family_name')->label(__('people.fields.merged_into'))
                    ->state(fn (Person $record) => $record->mergedInto?->displayName())
                    ->visible(fn (Person $record) => $record->merged_into_person_id !== null),
            ]),
        ]);
    }
}
