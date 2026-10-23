<?php

namespace App\Filament\Admin\Pages;

use App\Domain\Certificate\Settings\CertificateSettings;
use App\Domain\Institution\Settings\InstitutionSettings;
use App\Domain\Security\Enums\Permission;
use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Spatie\LaravelSettings\Settings;

class ManageCertificateSettings extends SettingsPage
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentCheck;

    protected static ?string $slug = 'settings/certificates';

    protected static function settingsClass(): string
    {
        return CertificateSettings::class;
    }

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->can(Permission::CertificatesManage->value);
    }

    public static function getNavigationLabel(): string
    {
        return __('certificate.settings.title');
    }

    public function getTitle(): string
    {
        return __('certificate.settings.title');
    }

    public function form(Schema $schema): Schema
    {
        $signers = array_map(fn (string $locale) => TextInput::make("signer.{$locale}")->label(__('certificate.settings.signer', ['locale' => $locale]))->maxLength(160),
            app(InstitutionSettings::class)->supported_locales);

        return $schema->components([
            Section::make()->schema([
                TextInput::make('serial_pattern')->label(__('certificate.settings.serial_pattern'))->required()->maxLength(64)
                    ->regex('/^[^{}]*(\{(TYPE|YYYY|YY)\}[^{}]*)*\{SEQ:\d{1,2}\}[^{}]*$/')->helperText(__('certificate.settings.serial_pattern_help')),
                TextInput::make('verification_valid_days')->label(__('certificate.settings.verification_valid_days'))->integer()->minValue(0)->maxValue(3650)->required()
                    ->helperText(__('certificate.settings.verification_valid_days_help')),
            ]),
            Section::make(__('certificate.settings.signers'))->schema($signers)->description(__('certificate.settings.signers_help')),
        ]);
    }

    protected function formDataFromSettings(Settings $settings): array
    {
        return parent::formDataFromSettings($settings);
    }

    protected function settingsDataFromForm(array $state): array
    {
        return [
            'serial_pattern' => (string) $state['serial_pattern'],
            'verification_valid_days' => (int) $state['verification_valid_days'],
            'signer' => array_filter(array_map(fn ($value) => is_string($value) ? trim($value) : '', (array) ($state['signer'] ?? [])), fn (string $value) => $value !== ''),
        ];
    }
}
