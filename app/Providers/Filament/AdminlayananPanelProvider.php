<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationBuilder;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Assets\Css;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Vite;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminLayananPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('admin-layanan')
            ->path('internal/admin-layanan')
            ->authGuard('employee')
            ->colors([
                'primary' => Color::hex('#093c5d'),
            ])
            ->darkMode(false)
            ->maxContentWidth('full')
            ->brandName('')
            ->assets([
                Css::make('admin-layanan-custom', Vite::asset('resources/css/filament/admin-layanan/theme.css')),
            ])
            ->renderHook(
                PanelsRenderHook::SIDEBAR_NAV_START,
                fn () => view('filament.admin-layanan.partials.header-brand'),
            )
            ->renderHook(
                PanelsRenderHook::TOPBAR_START,
                fn () => view('filament.admin-layanan.partials.topbar-text'),
            )
            ->renderHook(
                PanelsRenderHook::PAGE_START,
                fn () => view('filament.admin-layanan.partials.hero-section'),
            )
            // Load ApexCharts CDN sekali di global HEAD
            ->renderHook(
                PanelsRenderHook::HEAD_START,
                fn () => '<script src="https://cdn.jsdelivr.net/npm/apexcharts@3.45.2/dist/apexcharts.min.js"></script>',
            )
            ->navigation(function (NavigationBuilder $builder): NavigationBuilder {
                return $builder->items([
                    NavigationItem::make('Dashboard')
                        ->icon('heroicon-o-home')
                        ->url(fn (): string => \App\Filament\AdminLayanan\Pages\Dashboard::getUrl())
                        ->isActiveWhen(fn () => request()->routeIs('filament.admin-layanan.pages.dashboard'))
                        ->sort(1),
                    NavigationItem::make('Akun Pelanggan')
                        ->icon('heroicon-o-users')
                        ->url(fn (): string => \App\Filament\AdminLayanan\Pages\AkunPelanggan::getUrl())
                        ->isActiveWhen(fn () => request()->routeIs('filament.admin-layanan.pages.akun-pelanggan'))
                        ->sort(2),
                    NavigationItem::make('Permohonan Layanan')
                        ->icon('heroicon-o-document-text')
                        ->url(fn (): string => \App\Filament\AdminLayanan\Pages\PermohonanLayanan::getUrl())
                        ->isActiveWhen(fn () => request()->routeIs('filament.admin-layanan.pages.permohonan-layanan*'))
                        ->sort(3),
                    NavigationItem::make('Pembayaran')
                        ->icon('heroicon-o-credit-card')
                        ->url(fn (): string => \App\Filament\AdminLayanan\Pages\Pembayaran::getUrl())
                        ->isActiveWhen(fn () => request()->routeIs('filament.admin-layanan.pages.pembayaran'))
                        ->sort(4),
                    NavigationItem::make('Monitoring')
                        ->icon('heroicon-o-map')
                        ->url(fn (): string => \App\Filament\AdminLayanan\Pages\Monitoring::getUrl())
                        ->isActiveWhen(fn () => request()->routeIs('filament.admin-layanan.pages.monitoring'))
                        ->sort(5),
                    NavigationItem::make('Laporan Unit')
                        ->icon('heroicon-o-chart-bar')
                        ->url('#')
                        ->sort(6),
                ]);
            })
            ->discoverResources(in: app_path('Filament/AdminLayanan/Resources'), for: 'App\\Filament\\AdminLayanan\\Resources')
            ->discoverPages(in: app_path('Filament/AdminLayanan/Pages'), for: 'App\\Filament\\AdminLayanan\\Pages')
            ->discoverWidgets(in: app_path('Filament/AdminLayanan/Widgets'), for: 'App\\Filament\\AdminLayanan\\Widgets')
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}