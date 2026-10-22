<?php

namespace App\Providers\Filament;

use App\Domain\Institution\Settings\InstitutionSettings;
use App\Filament\Admin\NavigationGroup;
use App\Http\Middleware\EnforceMfaPolicy;
use App\Http\Middleware\SetDatabaseSessionContext;
use App\Http\Middleware\SetLocale;
use Filament\Actions\Action;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
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
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
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
