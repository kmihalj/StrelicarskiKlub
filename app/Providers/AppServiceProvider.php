<?php

namespace App\Providers;

use App\Models\Clanci;
use App\Services\ThemeService;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Registrira globalne servise aplikacije (trenutno bez dodatnih bindova).
     */
    public function register(): void
    {
        //
    }

    /**
     * Inicijalizira globalne postavke prikaza (Bootstrap paginacija, tema i navigacijski meni).
     */
    public function boot(): void
    {
        Paginator::useBootstrap();

        $themeService = app(ThemeService::class);
        $sharedThemeData = null;

        // Theme data must be resolved when views are rendered (after request/session/auth),
        // otherwise user light/dark/auto preference can be ignored.
        View::composer('*', function ($view) use ($themeService, &$sharedThemeData): void {
            if ($sharedThemeData === null) {
                $sharedThemeData = $themeService->sharedThemeData();
            }

            $view->with($sharedThemeData);
        });

        View::composer('layouts.nav2', function ($view) {
            // U cache spremamo samo jednostavne podatke, bez serijaliziranih Eloquent objekata.
            $menu = Cache::remember('nav2_menu_items_v2', now()->addMinutes(10), function (): array {
                $stavke = Clanci::query()
                    ->where('menu', '1')
                    ->whereIn('vrsta', ['Obavijest', 'O nama', 'Streličarstvo'])
                    ->orderByDesc('datum')
                    ->get(['id', 'menu_naslov', 'vrsta'])
                    ->map(fn (Clanci $clanak): array => [
                        'id' => $clanak->id,
                        'menu_naslov' => $clanak->menu_naslov,
                        'vrsta' => $clanak->vrsta,
                    ]);

                return [
                    'Obavijesti' => $stavke->where('vrsta', 'Obavijest')->values()->all(),
                    'O nama' => $stavke->where('vrsta', 'O nama')->values()->all(),
                    'Strelicarstvo' => $stavke->where('vrsta', 'Streličarstvo')->values()->all(),
                ];
            });

            $view->with('menu', $menu);
        });
    }
}
