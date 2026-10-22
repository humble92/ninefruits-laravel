<?php

namespace App\Filament\Admin\Pages;

use App\Domain\Institution\Services\SupportedLocales;
use App\Domain\Institution\Settings\InstitutionSettings;
use App\Domain\Security\Enums\Permission;
use App\Filament\Admin\Support\TranslatableFields;
use BackedEnum;
use DateTimeZone;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\File;

class ManageInstitutionSettings extends SettingsPage
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingLibrary;

    protected static ?string $slug = 'settings/institution';

    protected static function settingsClass(): string
    {
        return InstitutionSettings::class;
    }

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->can(Permission::InstitutionSettingsManage->value);
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.settings.institution.title');
    }

    public function getTitle(): string
    {
        return __('admin.settings.institution.title');
    }

    public function form(Schema $schema): Schema
    {
        $locales = app(SupportedLocales::class);
        $available = collect(File::directories(lang_path()))->map(fn (string $path) => basename($path))
            ->mapWithKeys(fn (string $locale) => [$locale => $locales->label($locale)]);

        return $schema->components([
            Section::make(__('admin.settings.institution.general'))->schema([
                TranslatableFields::make('name', __('admin.settings.institution.name')),
                Select::make('timezone')
                    ->label(__('admin.settings.institution.timezone'))
                    ->options(array_combine(DateTimeZone::listIdentifiers(), DateTimeZone::listIdentifiers()))
                    ->searchable()
                    ->required(),
            ]),
            Section::make(__('admin.settings.institution.languages'))
                ->description(__('admin.settings.institution.languages_help'))
                ->schema([
                    CheckboxList::make('supported_locales')
                        ->label(__('admin.settings.institution.supported_locales'))
                        ->options($available)
                        ->required(),
                    Select::make('default_locale')
                        ->label(__('admin.settings.institution.default_locale'))
                        ->options($available)
                        ->required(),
                ]),
        ]);
    }

    protected function settingsDataFromForm(array $state): array
    {
        $supported = array_values($state['supported_locales']);

        return [
            'name' => array_filter($state['name'], fn (?string $value) => filled($value)),
            'supported_locales' => $supported,
            'default_locale' => in_array($state['default_locale'], $supported, true) ? $state['default_locale'] : (string) config('app.fallback_locale'),
            'timezone' => $state['timezone'],
        ];
    }
}
