<?php

namespace App\Providers\Filament;

use Filament\Enums\ThemeMode;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentAsset;
use Filament\Widgets;
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
            ->id('admin')
            ->path('admin')
            ->login()
            ->brandName('🟣 Liefmart')
            ->favicon(asset('favicon.ico'))
            ->sidebarCollapsibleOnDesktop()
            ->darkMode(true)
            ->defaultThemeMode(ThemeMode::System)
            ->colors([
                'primary' => Color::Indigo,
            ])
            ->renderHook(
                'panels::body.start',
                fn (): string => '<style>
                    :root {
                        --sidebar-bg: #fafaff;
                        --main-bg: #ffffff;
                        --topbar-border: rgba(99, 102, 241, 0.12);
                    }
                    .dark {
                        --sidebar-bg: #1e1e2a;
                        --main-bg: #181825;
                        --topbar-border: rgba(99, 102, 241, 0.2);
                    }
                    .fi-sidebar {
                        border-right: 1px solid var(--topbar-border);
                        background-color: var(--sidebar-bg);
                    }
                    .fi-main {
                        background-color: var(--main-bg);
                    }
                    .fi-topbar {
                        border-bottom: 1px solid var(--topbar-border);
                    }
                </style>'
            )
            ->navigationGroups(['Transaksi', 'Master Data', 'Keuangan', 'Laporan'])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
                Widgets\FilamentInfoWidget::class,
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
                \App\Http\Middleware\EnsureDynamicPaths::class,
                \App\Http\Middleware\SetFilamentSession::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
