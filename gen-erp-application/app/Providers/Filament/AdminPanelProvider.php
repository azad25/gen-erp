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
                'primary' => [
                    50 => '#f0fdfa',
                    100 => '#ccfbf1',
                    200 => '#99f6e4',
                    300 => '#5eead4',
                    400 => '#2dd4bf',
                    500 => '#14b8a6',
                    600 => '#0f766e',
                    700 => '#0d5f5a',
                    800 => '#115e59',
                    900 => '#134e4a',
                    950 => '#042f2e',
                ],
                'gray' => Color::Slate,
                'success' => Color::Green,
                'warning' => Color::Amber,
                'danger' => Color::Red,
                'info' => Color::Cyan,
            ])
            ->font('Inter')
            ->favicon(asset('images/favicon.png'))
            ->brandName('GenERP BD')
            ->brandLogo(fn () => view('components.home.logo', ['attributes' => new \Illuminate\View\ComponentAttributeBag(['class' => 'h-8 w-8'])]))
            ->darkModeBrandLogo(fn () => view('components.home.logo', ['attributes' => new \Illuminate\View\ComponentAttributeBag(['class' => 'h-8 w-8'])]))
            ->brandLogoHeight('2rem')
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
                \App\Filament\Widgets\CompanySwitcher::class,
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
                500 => '#14b8a6',
                600 => '#0f766e',
                700 => '#0d5f5a',
                800 => '#115e59',
                900 => '#134e4a',
                950 => '#042f2e',
            ],
            'success' => Color::Green,
            'warning' => Color::Amber,
            'danger' => Color::Red,
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
    }
}
