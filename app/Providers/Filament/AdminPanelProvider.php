<?php

namespace App\Providers\Filament;

use App\Domain\Institution\Settings\InstitutionSettings;
use App\Filament\Admin\NavigationGroup;
use App\Filament\Admin\Support\TranslatableFormData;
use App\Http\Middleware\EnforceMfaPolicy;
use App\Http\Middleware\SetDatabaseSessionContext;
use App\Http\Middleware\SetLocale;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Schemas\Schema;
use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentTimezone;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

/**
 * Staff administration panel. There is no panel login: unauthenticated users are sent to
 * the shared Fortify login, and two-factor enrollment happens in the portal
 * (docs/decisions/0001-shared-authentication.md).
 */
class AdminPanelProvider extends PanelProvider
{
    public function boot(): void
    {
        // Times are shown and entered in the institution time zone (stored in UTC), and
        // dates use ISO formats, readable the same way in every language.
        FilamentTimezone::set(fn () => app(InstitutionSettings::class)->timezone);

        Table::configureUsing(fn (Table $table) => $table
            ->defaultDateDisplayFormat('Y-m-d')
            ->defaultDateTimeDisplayFormat('Y-m-d H:i'));
        Schema::configureUsing(fn (Schema $schema) => $schema
            ->defaultDateDisplayFormat('Y-m-d')
            ->defaultDateTimeDisplayFormat('Y-m-d H:i'));

        // Modal edit and view actions show every translation of translatable attributes.
        EditAction::configureUsing(fn (EditAction $action) => $action->mutateRecordDataUsing(
            fn (array $data, Model $record) => TranslatableFormData::fill($data, $record),
        ));
        ViewAction::configureUsing(fn (ViewAction $action) => $action->mutateRecordDataUsing(
            fn (array $data, Model $record) => TranslatableFormData::fill($data, $record),
        ));
    }

    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->path('admin')
            ->authGuard('web')
            ->brandName(fn () => app(InstitutionSettings::class)->name[app()->getLocale()]
                ?? app(InstitutionSettings::class)->name[config('app.fallback_locale')]
                ?? config('app.name'))
            ->colors([
                'primary' => Color::Indigo,
            ])
            ->navigationGroups(NavigationGroup::forPanel())
            ->userMenuItems([
                Action::make('security')
                    ->label(fn () => __('security.mfa.title'))
                    ->icon(Heroicon::OutlinedShieldCheck)
                    ->url(fn () => route('portal.security')),
                Action::make('portal')
                    ->label(fn () => __('admin.navigation.portal'))
                    ->icon(Heroicon::OutlinedHome)
                    ->url(fn () => route('portal.dashboard')),
            ])
            ->discoverResources(in: app_path('Filament/Admin/Resources'), for: 'App\Filament\Admin\Resources')
            ->discoverPages(in: app_path('Filament/Admin/Pages'), for: 'App\Filament\Admin\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->widgets([
                AccountWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                SetDatabaseSessionContext::class,
                SetLocale::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
                EnforceMfaPolicy::class,
            ], isPersistent: true);
    }
}
