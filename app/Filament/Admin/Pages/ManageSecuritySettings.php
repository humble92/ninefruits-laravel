<?php

namespace App\Filament\Admin\Pages;

use App\Domain\Security\Enums\MfaEnforcement;
use App\Domain\Security\Enums\Permission;
use App\Domain\Security\Settings\SecuritySettings;
use App\Models\Security\Role;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class ManageSecuritySettings extends SettingsPage
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static ?string $slug = 'settings/security';

    protected static function settingsClass(): string
    {
        return SecuritySettings::class;
    }

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->can(Permission::SecuritySettingsManage->value);
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.settings.security.title');
    }

    public function getTitle(): string
    {
        return __('admin.settings.security.title');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('admin.settings.security.mfa_section'))
                ->description(__('admin.settings.security.mfa_description'))
                ->schema([
                    Select::make('mfa_enforcement')
                        ->label(__('admin.settings.security.mfa_enforcement'))
                        ->options(collect(MfaEnforcement::cases())->mapWithKeys(fn (MfaEnforcement $case) => [$case->value => $case->label()]))
                        ->required()
                        ->live(),
                    Select::make('mfa_required_role_ids')
                        ->label(__('admin.settings.security.mfa_required_roles'))
                        ->multiple()
                        ->options(fn () => Role::query()->assignable()->get()->mapWithKeys(fn (Role $role) => [$role->id => $role->display_name]))
                        ->visible(fn (Get $get) => $get('mfa_enforcement') === MfaEnforcement::RequiredForRoles->value)
                        ->required(fn (Get $get) => $get('mfa_enforcement') === MfaEnforcement::RequiredForRoles->value),
                    TextInput::make('mfa_grace_period_days')
                        ->label(__('admin.settings.security.mfa_grace_period_days'))
                        ->helperText(__('admin.settings.security.mfa_grace_period_help'))
                        ->integer()->minValue(0)->maxValue(90)->required(),
                    TextInput::make('mfa_remember_device_days')
                        ->label(__('admin.settings.security.mfa_remember_device_days'))
                        ->helperText(__('admin.settings.security.mfa_remember_device_help'))
                        ->integer()->minValue(0)->maxValue(365)->required(),
                    Toggle::make('mfa_email_code_enabled')
                        ->label(__('admin.settings.security.mfa_email_code_enabled'))
                        ->helperText(__('admin.settings.security.mfa_email_code_help')),
                ]),
        ]);
    }

    protected function settingsDataFromForm(array $state): array
    {
        $enforcement = MfaEnforcement::from($state['mfa_enforcement']);

        return [
            'mfa_enforcement' => $enforcement,
            'mfa_required_role_ids' => $enforcement === MfaEnforcement::RequiredForRoles
                ? array_values(array_map('intval', $state['mfa_required_role_ids'] ?? []))
                : [],
            'mfa_grace_period_days' => (int) $state['mfa_grace_period_days'],
            'mfa_remember_device_days' => (int) $state['mfa_remember_device_days'],
            'mfa_email_code_enabled' => (bool) ($state['mfa_email_code_enabled'] ?? false),
        ];
    }
}
