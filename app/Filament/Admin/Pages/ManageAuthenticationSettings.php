<?php

namespace App\Filament\Admin\Pages;

use App\Domain\Security\Enums\Permission;
use App\Domain\Security\Enums\SsoLinkMode;
use App\Domain\Security\Enums\StudentNumberLogin;
use App\Domain\Security\Settings\AuthenticationSettings;
use BackedEnum;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Crypt;
use Spatie\LaravelSettings\Settings;

/**
 * Sign-in methods (docs/porting-plan.md section 7.1). Client secrets are stored encrypted
 * and never sent back to the browser; leaving the field empty keeps the stored secret.
 */
class ManageAuthenticationSettings extends SettingsPage
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedKey;

    protected static ?string $slug = 'settings/authentication';

    protected static function settingsClass(): string
    {
        return AuthenticationSettings::class;
    }

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->can(Permission::AuthenticationSettingsManage->value);
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.settings.authentication.title');
    }

    public function getTitle(): string
    {
        return __('admin.settings.authentication.title');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('admin.settings.authentication.password_section'))->columns(2)->schema([
                Select::make('student_number_login')->label(__('admin.settings.authentication.student_number_login'))
                    ->options(collect(StudentNumberLogin::cases())->mapWithKeys(fn (StudentNumberLogin $case) => [$case->value => $case->label()]))
                    ->required(),
                Toggle::make('password_login_for_sso_users')->label(__('admin.settings.authentication.password_login_for_sso_users'))
                    ->helperText(__('admin.settings.authentication.password_login_for_sso_users_help')),
                TextInput::make('lockout_threshold')->label(__('admin.settings.authentication.lockout_threshold'))
                    ->helperText(__('admin.settings.authentication.lockout_threshold_help'))->integer()->minValue(0)->maxValue(100)->required(),
                TextInput::make('lockout_minutes')->label(__('admin.settings.authentication.lockout_minutes'))->integer()->minValue(1)->maxValue(1440)->required(),
            ]),
            Section::make(__('admin.settings.authentication.sso_section'))->schema([
                Select::make('sso_link_mode')->label(__('admin.settings.authentication.sso_link_mode'))
                    ->options(collect(SsoLinkMode::cases())->mapWithKeys(fn (SsoLinkMode $case) => [$case->value => $case->label()]))
                    ->required(),
                Repeater::make('sso_providers')->label(__('admin.settings.authentication.sso_providers'))
                    ->columns(2)
                    ->defaultItems(0)
                    ->schema([
                        TextInput::make('key')->label(__('admin.settings.authentication.provider_key'))->required()->regex('/^[a-z][a-z0-9-]*$/')->maxLength(32),
                        TextInput::make('label')->label(__('admin.settings.authentication.provider_label'))->required()->maxLength(64),
                        TextInput::make('issuer')->label(__('admin.settings.authentication.issuer'))->url()->required()->columnSpanFull(),
                        TextInput::make('client_id')->label(__('admin.settings.authentication.client_id'))->required(),
                        TextInput::make('client_secret')->label(__('admin.settings.authentication.client_secret'))->password()
                            ->helperText(__('admin.settings.authentication.client_secret_help')),
                        TextInput::make('scopes')->label(__('admin.settings.authentication.scopes'))->default('openid email profile'),
                        Toggle::make('trusts_mfa')->label(__('admin.settings.authentication.trusts_mfa'))->helperText(__('admin.settings.authentication.trusts_mfa_help')),
                        Toggle::make('is_enabled')->label(__('reference.fields.is_active'))->default(true),
                    ]),
                TextInput::make('redirect_uri_hint')->label(__('admin.settings.authentication.redirect_uri'))
                    ->default(url('/auth/sso/{key}/callback'))->disabled()->dehydrated(false),
            ]),
        ]);
    }

    protected function formDataFromSettings(Settings $settings): array
    {
        $data = parent::formDataFromSettings($settings);
        $data['sso_providers'] = array_map(fn (array $provider) => [...$provider, 'client_secret' => null], $data['sso_providers']);

        return $data;
    }

    protected function settingsDataFromForm(array $state): array
    {
        /** @var AuthenticationSettings $current */
        $current = $this->settings();
        $storedSecrets = collect($current->sso_providers)->mapWithKeys(fn (array $provider) => [$provider['key'] => $provider['client_secret'] ?? null]);

        return [
            'student_number_login' => StudentNumberLogin::from($state['student_number_login']),
            'password_login_for_sso_users' => (bool) $state['password_login_for_sso_users'],
            'sso_link_mode' => SsoLinkMode::from($state['sso_link_mode']),
            'lockout_threshold' => (int) $state['lockout_threshold'],
            'lockout_minutes' => (int) $state['lockout_minutes'],
            'sso_providers' => array_values(array_map(fn (array $provider) => [
                'key' => $provider['key'],
                'label' => $provider['label'],
                'issuer' => rtrim($provider['issuer'], '/'),
                'client_id' => $provider['client_id'],
                'client_secret' => filled($provider['client_secret'] ?? null) ? Crypt::encryptString($provider['client_secret']) : $storedSecrets->get($provider['key']),
                'scopes' => $provider['scopes'] ?? 'openid email profile',
                'trusts_mfa' => (bool) ($provider['trusts_mfa'] ?? false),
                'is_enabled' => (bool) ($provider['is_enabled'] ?? true),
            ], (array) ($state['sso_providers'] ?? []))),
        ];
    }
}
