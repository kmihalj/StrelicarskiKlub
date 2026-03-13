<?php

namespace App\Http\Controllers;

use App\Services\ThemeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class UserThemePreferenceController extends Controller
{
    public function __construct(private readonly ThemeService $themeService)
    {
    }

    public function update(Request $request): RedirectResponse
    {
        $siteModePolicy = $this->themeService->siteThemeModePolicy();
        $isForced = in_array($siteModePolicy, [ThemeService::MODE_LIGHT, ThemeService::MODE_DARK], true);

        if ($isForced) {
            $response = redirect()->back()->with('error', 'Prikaz teme je trenutno postavljen globalno od strane administratora.');
            $response->withCookie(cookie()->forever($this->themeService->modePreferenceCookieName(), $siteModePolicy));
            $response->withCookie(cookie()->forever($this->themeService->resolvedModeCookieName(), $siteModePolicy));

            return $response;
        }

        $validated = $request->validate([
            'theme_mode_preference' => 'required|in:auto,light,dark',
        ], [
            'theme_mode_preference.in' => 'Odaberi: automatski, svijetla ili tamna tema.',
        ]);

        $modePreference = $this->themeService->normalizeModePreference($validated['theme_mode_preference']);

        $user = $request->user();
        if ($user !== null) {
            $user->theme_mode_preference = $modePreference;
            $user->save();
        }

        $resolvedMode = $modePreference === ThemeService::MODE_AUTO
            ? $this->themeService->normalizeResolvedMode($request->cookie($this->themeService->resolvedModeCookieName()))
            : $modePreference;

        $response = redirect()->back()->with('success', 'Postavka prikaza je spremljena.');

        $response->withCookie(cookie()->forever($this->themeService->modePreferenceCookieName(), $modePreference));
        $response->withCookie(cookie()->forever($this->themeService->resolvedModeCookieName(), $resolvedMode));

        return $response;
    }
}
