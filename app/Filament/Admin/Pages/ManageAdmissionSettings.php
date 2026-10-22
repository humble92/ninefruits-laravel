<?php

namespace App\Filament\Admin\Pages;

use App\Domain\Admission\Settings\AdmissionSettings;
use App\Domain\Security\Enums\Permission;
use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class ManageAdmissionSettings extends SettingsPage
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $slug = 'settings/admissions';

    protected static function settingsClass(): string
    {
        return AdmissionSettings::class;
    }

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->can(Permission::AdmissionCyclesManage->value);
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.settings.admissions.title');
    }

    public function getTitle(): string
    {
        return __('admin.settings.admissions.title');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->schema([
                Toggle::make('applicant_registration_enabled')->label(__('admin.settings.admissions.applicant_registration_enabled'))
                    ->helperText(__('admin.settings.admissions.applicant_registration_enabled_help')),
                TextInput::make('draft_expiry_days')->label(__('admin.settings.admissions.draft_expiry_days'))
                    ->helperText(__('admin.settings.admissions.draft_expiry_days_help'))->integer()->minValue(1)->maxValue(730)->required(),
                TextInput::make('max_document_size_mb')->label(__('admin.settings.admissions.max_document_size_mb'))
                    ->integer()->minValue(1)->maxValue(50)->required(),
            ]),
        ]);
    }

    protected function settingsDataFromForm(array $state): array
    {
        return [
            'applicant_registration_enabled' => (bool) $state['applicant_registration_enabled'],
            'draft_expiry_days' => (int) $state['draft_expiry_days'],
            'max_document_size_mb' => (int) $state['max_document_size_mb'],
        ];
    }
}
