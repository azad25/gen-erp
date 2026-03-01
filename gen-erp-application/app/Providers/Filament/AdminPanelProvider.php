<?php

namespace App\Providers\Filament;

use App\Http\Middleware\EnsureActiveCompany;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentColor;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Filament\View\PanelsRenderHook;
use Filament\Support\Facades\FilamentView;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('app')
            ->path('admin')
            ->login(\App\Filament\Pages\Auth\Login::class)
            ->registration(\App\Filament\Pages\Auth\Register::class)
            ->passwordReset()
            ->emailVerification()
            ->profile()
            ->colors([
                'primary'   => Color::hex('#0F766E'),
                'secondary' => Color::hex('#115E59'),
                'info'      => Color::hex('#14B8A6'),
                'success'   => Color::hex('#16A34A'),
                'warning'   => Color::hex('#CA8A04'),
                'danger'    => Color::hex('#B91C1C'),
                'gray'      => Color::Slate,
            ])
            ->font('Inter', provider: \Filament\FontProviders\LocalFontProvider::class)
            ->favicon(asset('images/favicon.png'))
            ->brandLogo(fn () => view('filament.logo'))
            ->defaultThemeMode(\Filament\Enums\ThemeMode::Light)
            ->brandLogoHeight('3rem')
            ->sidebarCollapsibleOnDesktop()
            ->sidebarWidth('280px')
            ->collapsedSidebarWidth('80px')
            ->globalSearchKeyBindings(['command+k', 'ctrl+k'])
            ->databaseNotifications()
            ->databaseNotificationsPolling('30s')
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->navigationGroups([
                NavigationGroup::make(__('Dashboard'))
                    ->icon('heroicon-o-home')
                    ->collapsed(false),
                NavigationGroup::make(__('Sales'))
                    ->icon('heroicon-o-shopping-cart')
                    ->collapsed(false),
                NavigationGroup::make(__('Purchases'))
                    ->icon('heroicon-o-shopping-bag')
                    ->collapsed(true),
                NavigationGroup::make(__('Inventory'))
                    ->icon('heroicon-o-cube')
                    ->collapsed(true),
                NavigationGroup::make(__('HR & Payroll'))
                    ->icon('heroicon-o-users')
                    ->collapsed(true),
                NavigationGroup::make(__('Accounts'))
                    ->icon('heroicon-o-banknotes')
                    ->collapsed(true),
                NavigationGroup::make(__('Reports'))
                    ->icon('heroicon-o-chart-bar')
                    ->collapsed(true),
                NavigationGroup::make(__('Settings'))
                    ->icon('heroicon-o-cog-6-tooth')
                    ->collapsed(true),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                \App\Filament\Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                // Widgets handled in Dashboard
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
                EnsureActiveCompany::class,
            ]);
    }

    public function boot(): void
    {
        // Register custom brand colors using Filament's color system
        FilamentColor::register([
            'primary' => [
                50 => '#f0fdfa',
                100 => '#ccfbf1',
                200 => '#99f6e4',
                300 => '#5eead4',
                400 => '#2dd4bf',
                500 => '#14b8a6', // primary-light
                600 => '#0f766e', // primary (Deep Teal)
                700 => '#0d5f5a',
                800 => '#115e59', // primary-dark
                900 => '#134e4a',
                950 => '#042f2e',
            ],
            'success' => [
                50 => '#f0fdf4',
                100 => '#dcfce7',
                200 => '#bbf7d0',
                300 => '#86efac',
                400 => '#4ade80',
                500 => '#22c55e',
                600 => '#16a34a', // success (Forest Green)
                700 => '#15803d',
                800 => '#166534',
                900 => '#14532d',
                950 => '#052e16',
            ],
            'warning' => Color::Amber,
            'danger' => Color::Rose,
            'info' => Color::Cyan,
            'gray' => Color::Slate,
        ]);

        // Global Component Styling for Modern SaaS Aesthetic
        \Filament\Tables\Table::configureUsing(function (\Filament\Tables\Table $table): void {
            $table
                ->paginationPageOptions([10, 25, 50, 100])
                ->defaultPaginationPageOption(25)
                ->extremePaginationLinks()
                ->persistSearchInSession()
                ->persistSortInSession()
                ->persistFiltersInSession()
                ->persistColumnSearchesInSession()
                ->deferLoading();
        });

        \Filament\Tables\Actions\Action::configureUsing(function (\Filament\Tables\Actions\Action $action): void {
            $action->button(); // Default all table actions to pill buttons (styled via CSS)
        });

        \Filament\Pages\Actions\Action::configureUsing(function (\Filament\Pages\Actions\Action $action): void {
            $action->button();
        });

        // Register Company Switcher in the Sidebar
        FilamentView::registerRenderHook(
            PanelsRenderHook::SIDEBAR_NAV_START,
            fn (): \Illuminate\Contracts\View\View => view('filament.widgets.company-switcher')
        );

        // Register Language Switcher in Topbar
        FilamentView::registerRenderHook(
            PanelsRenderHook::GLOBAL_SEARCH_BEFORE,
            fn (): \Illuminate\Contracts\View\View => view('filament.widgets.language-switcher')
        );
    }
}
