<?php

namespace App\Http\Controllers;

use App\Models\SiteSetting;
use App\Services\ThemeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

/**
 * Admin kontroler za globalno zaključavanje prikaza teme (automatski, svijetla ili tamna) na cijelom siteu.
 */
class AdminThemeModePolicyController extends Controller
{
    /**
     * Učitava servis za rad s temama i načinima prikaza svijetla/tamna.
     */
    public function __construct(private readonly ThemeService $themeService)
    {
    }

    /**
     * Sprema globalnu politiku prikaza teme za sve korisnike i goste.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'theme_mode_policy' => 'required|in:auto,light,dark',
        ], [
            'theme_mode_policy.in' => 'Odaberi: automatski, forsiraj svijetlu ili forsiraj tamnu.',
        ]);

        if (!Schema::hasTable('site_settings')) {
            return redirect()
                ->back()
                ->with('error', 'Nedostaje tablica site_settings. Pokreni migracije.');
        }

        $policy = $this->themeService->normalizeModePreference($validated['theme_mode_policy']);

        SiteSetting::query()->updateOrCreate(
            ['id' => 1],
            ['theme_mode_policy' => $policy]
        );

        $resolvedMode = $policy === ThemeService::MODE_AUTO
            ? $this->themeService->normalizeResolvedMode($request->cookie($this->themeService->resolvedModeCookieName()))
            : $policy;

        $response = redirect()
            ->back()
            ->with('success', 'Globalna politika prikaza teme je spremljena.');

        $response->withCookie(
            $this->themeService->makeThemeModeCookie($this->themeService->modePreferenceCookieName(), $policy)
        );
        $response->withCookie(
            $this->themeService->makeThemeModeCookie($this->themeService->resolvedModeCookieName(), $resolvedMode)
        );

        return $response;
    }
}
