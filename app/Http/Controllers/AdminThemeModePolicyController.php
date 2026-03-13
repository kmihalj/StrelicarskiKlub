<?php

namespace App\Http\Controllers;

use App\Models\SiteSetting;
use App\Services\ThemeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class AdminThemeModePolicyController extends Controller
{
    public function __construct(private readonly ThemeService $themeService)
    {
    }

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

        $response->withCookie(cookie()->forever($this->themeService->modePreferenceCookieName(), $policy));
        $response->withCookie(cookie()->forever($this->themeService->resolvedModeCookieName(), $resolvedMode));

        return $response;
    }
}
