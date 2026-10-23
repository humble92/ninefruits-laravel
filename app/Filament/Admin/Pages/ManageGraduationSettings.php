<?php

namespace App\Filament\Admin\Pages;

use App\Domain\Graduation\Settings\GraduationSettings;
use App\Domain\Security\Enums\Permission;
use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class ManageGraduationSettings extends SettingsPage
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAcademicCap;

    protected static ?string $slug = 'settings/graduation';

    protected static function settingsClass(): string
    {
        return GraduationSettings::class;
    }

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->can(Permission::GraduationProcess->value);
    }

    public static function getNavigationLabel(): string
    {
        return __('graduation.settings.title');
    }

    public function getTitle(): string
    {
        return __('graduation.settings.title');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->schema([
                TextInput::make('degree_number_pattern')->label(__('graduation.settings.degree_number_pattern'))->required()->maxLength(64)
                    ->regex('/^[^{}]*(\{(PROGRAM|YYYY|YY)\}[^{}]*)*\{SEQ:\d{1,2}\}[^{}]*$/')->helperText(__('graduation.settings.degree_number_pattern_help')),
                TextInput::make('minimum_cumulative_gpa')->label(__('graduation.settings.minimum_cumulative_gpa'))->numeric()->minValue(0)->maxValue(5)->step(0.01)
                    ->helperText(__('graduation.settings.minimum_cumulative_gpa_help')),
            ]),
        ]);
    }

    protected function settingsDataFromForm(array $state): array
    {
        return [
            'degree_number_pattern' => (string) $state['degree_number_pattern'],
            'minimum_cumulative_gpa' => filled($state['minimum_cumulative_gpa'] ?? null) ? (float) $state['minimum_cumulative_gpa'] : null,
        ];
    }
}
