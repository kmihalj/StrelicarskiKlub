<?php

namespace App\Providers;

use App\Models\Clanci;
use App\Services\ThemeService;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
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
            $menu = [];
            $menu['Obavijesti'] = Clanci::where('vrsta', 'Obavijest')
                ->where('menu', '1')
                ->orderByDesc('datum')
                ->get(['id', 'menu_naslov']);
            $menu['O nama'] = Clanci::where('vrsta', 'O nama')
                ->where('menu', '1')
                ->orderByDesc('datum')
                ->get(['id', 'menu_naslov']);
            $menu['Strelicarstvo'] = Clanci::where('vrsta', 'Streličarstvo')
                ->where('menu', '1')
                ->orderByDesc('datum')
                ->get(['id', 'menu_naslov']);

            $view->with('menu', $menu);
        });
    }
}
