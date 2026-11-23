<?php

namespace App\Providers\Filament;

use App\Filament\Resources\Orders\OrderResource;
use App\Models\Restaurant;
use Filament\Facades\Filament;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->homeUrl(fn (): string => OrderResource::getUrl('index'))
            ->login()
            ->tenant(Restaurant::class, slugAttribute: 'slug', ownershipRelationship: 'restaurants')
            ->tenantMenu(false)
            ->brandName(fn (): string => Filament::getTenant() instanceof Restaurant ? Filament::getTenant()->name : config('app.name', 'Mesa7'))
            ->brandLogo(function (): ?HtmlString {
                $tenant = Filament::getTenant();

                if (! $tenant instanceof Restaurant || blank($tenant->logo_path)) {
                    return null;
                }

                return new HtmlString(view('filament.partials.tenant-brand', [
                    'name' => $tenant->name,
                    'logo' => Storage::disk('public')->url($tenant->logo_path),
                ])->render());
            })
            ->brandLogoHeight('3.25rem')
            ->colors([
                'primary' => Color::hex('#E36A3C'),
                'gray' => Color::hex('#1F1F1F'),
                'success' => Color::hex('#A6B29A'),
            ])
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->renderHook(PanelsRenderHook::TOPBAR_END, fn () => view('filament.partials.platform-link'))
            ->renderHook(PanelsRenderHook::GLOBAL_SEARCH_BEFORE, fn () => view('filament.partials.tenant-switcher-hook'))
            ->renderHook(PanelsRenderHook::STYLES_AFTER, fn () => view('filament.partials.tenant-theme'))
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
