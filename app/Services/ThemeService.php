<?php

namespace App\Services;

use App\Models\SiteSetting;
use App\Models\Theme;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ThemeService
{
    public const MODE_AUTO = 'auto';
    public const MODE_LIGHT = 'light';
    public const MODE_DARK = 'dark';

    public const MODE_COOKIE_PREFERENCE = 'theme_mode_preference';
    public const MODE_COOKIE_RESOLVED = 'theme_mode_resolved';

    public function siteThemeModePolicy(): string
    {
        if (!Schema::hasTable('site_settings') || !Schema::hasColumn('site_settings', 'theme_mode_policy')) {
            return self::MODE_AUTO;
        }

        $policy = SiteSetting::query()->value('theme_mode_policy');

        return $this->normalizeModePreference(is_string($policy) ? $policy : null);
    }

    public function isThemeModeForced(): bool
    {
        return in_array($this->siteThemeModePolicy(), [self::MODE_LIGHT, self::MODE_DARK], true);
    }

    public function getDefaultColors(): array
    {
        return [
            'body_bg' => '#e9ecef',
            'body_text' => '#212529',
            'primary' => '#0d6efd',
            'secondary' => '#6c757d',
            'success' => '#198754',
            'danger' => '#dc3545',
            'warning' => '#ffc107',
            'info' => '#0dcaf0',
            'link' => '#0d6efd',
            'light' => '#f8f9fa',
            'dark' => '#212529',
            'secondary_subtle' => '#e2e3e5',
            'dark_subtle' => '#ced4da',
            'nav_solid_bg' => '#000000',
            'nav_gradient_start' => '#ff0000',
            'nav_gradient_mid' => '#ffd700',
            'nav_gradient_end' => '#07c818',
            'nav_item_border' => '#eaeaea',
            'nav_item_text' => '#ffffff',
            'nav_item_hover_text' => '#000000',
            'nav_item_hover_bg' => '#62cc46',
            'nav_dropdown_bg' => '#e0f144',
            'nav_dropdown_text' => '#000000',
            'nav_dropdown_hover_bg' => '#62cc46',
            'nav_dropdown_hover_text' => '#ffffff',
        ];
    }

    public function getDarkColors(): array
    {
        return [
            'body_bg' => '#111317',
            'body_text' => '#e9ecef',
            'primary' => '#4f8dff',
            'secondary' => '#8f98a3',
            'success' => '#31b56e',
            'danger' => '#ff6b6b',
            'warning' => '#ffd166',
            'info' => '#4cc9f0',
            'link' => '#8fc3ff',
            'light' => '#f1f3f5',
            'dark' => '#1f242b',
            'secondary_subtle' => '#1f252c',
            'dark_subtle' => '#2a2f36',
            'nav_solid_bg' => '#111317',
            'nav_gradient_start' => '#111317',
            'nav_gradient_mid' => '#273444',
            'nav_gradient_end' => '#1f3b2a',
            'nav_item_border' => '#3a3f46',
            'nav_item_text' => '#f8f9fa',
            'nav_item_hover_text' => '#ffffff',
            'nav_item_hover_bg' => '#2b5f3f',
            'nav_dropdown_bg' => '#1f252c',
            'nav_dropdown_text' => '#e9ecef',
            'nav_dropdown_hover_bg' => '#2b5f3f',
            'nav_dropdown_hover_text' => '#ffffff',
        ];
    }

    public function getColorKeys(): array
    {
        return array_keys($this->getDefaultColors());
    }

    public function getEditableColorKeys(): array
    {
        return [
            'body_bg',
            'body_text',
            'primary',
            'secondary',
            'success',
            'danger',
            'warning',
            'info',
            'link',
            'light',
            'dark',
            'secondary_subtle',
            'dark_subtle',
            'nav_solid_bg',
            'nav_gradient_start',
            'nav_gradient_mid',
            'nav_gradient_end',
            'nav_item_border',
            'nav_item_text',
            'nav_item_hover_bg',
            'nav_dropdown_bg',
            'nav_dropdown_hover_bg',
        ];
    }

    public function modePreferenceCookieName(): string
    {
        return self::MODE_COOKIE_PREFERENCE;
    }

    public function resolvedModeCookieName(): string
    {
        return self::MODE_COOKIE_RESOLVED;
    }

    public function normalizeModePreference(?string $value): string
    {
        $normalized = Str::lower(trim((string)$value));
        if (in_array($normalized, [self::MODE_LIGHT, self::MODE_DARK], true)) {
            return $normalized;
        }

        return self::MODE_AUTO;
    }

    public function normalizeResolvedMode(?string $value): string
    {
        $normalized = Str::lower(trim((string)$value));

        return $normalized === self::MODE_DARK ? self::MODE_DARK : self::MODE_LIGHT;
    }

    public function currentModePreference(): string
    {
        if (Auth::check()) {
            $userPreference = Auth::user()?->theme_mode_preference;
            if (is_string($userPreference) && $userPreference !== '') {
                return $this->normalizeModePreference($userPreference);
            }
        }

        return self::MODE_AUTO;
    }

    public function resolveMode(string $modePreference): string
    {
        $normalizedPreference = $this->normalizeModePreference($modePreference);
        if (in_array($normalizedPreference, [self::MODE_LIGHT, self::MODE_DARK], true)) {
            return $normalizedPreference;
        }

        return $this->normalizeResolvedMode($this->readCookie(self::MODE_COOKIE_RESOLVED));
    }

    public function getActiveTheme(): ?Theme
    {
        if (!Schema::hasTable('themes')) {
            return null;
        }

        return Theme::active()->first() ?? Theme::query()->orderBy('id')->first();
    }

    public function normalizeColors(?array $colors): array
    {
        $defaults = $this->getDefaultColors();
        $colors = $colors ?? [];

        foreach ($defaults as $key => $fallback) {
            $defaults[$key] = $this->normalizeHexColor((string)($colors[$key] ?? $fallback), $fallback);
        }

        return $defaults;
    }

    public function isValidHexColor(?string $value): bool
    {
        return is_string($value) && preg_match('/^#[0-9a-fA-F]{6}$/', trim($value)) === 1;
    }

    public function contrastColor(string $backgroundHex, string $dark = '#111111', string $light = '#ffffff'): string
    {
        [$r, $g, $b] = $this->hexToRgb($backgroundHex);
        $luminance = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;

        return $luminance > 0.58 ? $dark : $light;
    }

    public function cssVariables(array $colors, string $modeResolved = self::MODE_LIGHT): array
    {
        $isDarkMode = $this->normalizeResolvedMode($modeResolved) === self::MODE_DARK;
        $themeBodyBackground = $isDarkMode
            ? $colors['body_bg']
            : $colors['secondary_subtle'];
        $bootstrapBodyBackground = $isDarkMode ? $colors['body_bg'] : '#f8fafc';
        $linkColor = $this->normalizeHexColor(
            (string)($colors['link'] ?? ($isDarkMode ? '#8fc3ff' : '#0d6efd')),
            $isDarkMode ? '#8fc3ff' : '#0d6efd'
        );
        $linkHoverColor = $this->shiftHexColor($linkColor, $isDarkMode ? 24 : -24);

        return [
            '--theme-body-bg' => $themeBodyBackground,
            '--theme-body-color' => $colors['body_text'],
            '--theme-nav-solid-bg' => $colors['nav_solid_bg'],
            '--theme-nav-gradient-start' => $colors['nav_gradient_start'],
            '--theme-nav-gradient-mid' => $colors['nav_gradient_mid'],
            '--theme-nav-gradient-end' => $colors['nav_gradient_end'],
            '--theme-nav-item-border' => $colors['nav_item_border'],
            '--theme-nav-item-text' => $colors['nav_item_text'],
            '--theme-nav-item-hover-bg' => $colors['nav_item_hover_bg'],
            '--theme-nav-item-hover-text' => $colors['nav_item_hover_text'],
            '--theme-nav-dropdown-bg' => $colors['nav_dropdown_bg'],
            '--theme-nav-dropdown-text' => $colors['nav_dropdown_text'],
            '--theme-nav-dropdown-hover-bg' => $colors['nav_dropdown_hover_bg'],
            '--theme-nav-dropdown-hover-text' => $colors['nav_dropdown_hover_text'],
            '--theme-link-color' => $linkColor,
            '--theme-link-hover-color' => $linkHoverColor,

            '--bs-primary' => $colors['primary'],
            '--bs-primary-rgb' => $this->rgbCsv($colors['primary']),
            '--bs-secondary' => $colors['secondary'],
            '--bs-secondary-rgb' => $this->rgbCsv($colors['secondary']),
            '--bs-success' => $colors['success'],
            '--bs-success-rgb' => $this->rgbCsv($colors['success']),
            '--bs-danger' => $colors['danger'],
            '--bs-danger-rgb' => $this->rgbCsv($colors['danger']),
            '--bs-warning' => $colors['warning'],
            '--bs-warning-rgb' => $this->rgbCsv($colors['warning']),
            '--bs-info' => $colors['info'],
            '--bs-info-rgb' => $this->rgbCsv($colors['info']),
            '--bs-light' => $colors['light'],
            '--bs-light-rgb' => $this->rgbCsv($colors['light']),
            '--bs-dark' => $colors['dark'],
            '--bs-dark-rgb' => $this->rgbCsv($colors['dark']),
            '--bs-link-color' => $linkColor,
            '--bs-link-color-rgb' => $this->rgbCsv($linkColor),
            '--bs-link-hover-color' => $linkHoverColor,
            '--bs-link-hover-color-rgb' => $this->rgbCsv($linkHoverColor),
            '--bs-body-bg' => $bootstrapBodyBackground,
            '--bs-body-color' => $colors['body_text'],
            '--bs-secondary-bg-subtle' => $colors['secondary_subtle'],
            '--bs-dark-bg-subtle' => $colors['dark_subtle'],

            '--theme-on-primary' => $this->contrastColor($colors['primary']),
            '--theme-on-secondary' => $this->contrastColor($colors['secondary']),
            '--theme-on-success' => $this->contrastColor($colors['success']),
            '--theme-on-danger' => $this->contrastColor($colors['danger']),
            '--theme-on-warning' => $this->contrastColor($colors['warning']),
            '--theme-on-info' => $this->contrastColor($colors['info']),
            '--theme-on-light' => $this->contrastColor($colors['light']),
            '--theme-on-dark' => $this->contrastColor($colors['dark']),
        ];
    }

    public function sharedThemeData(): array
    {
        $activeTheme = $this->getActiveTheme();

        $siteModePolicy = $this->siteThemeModePolicy();
        $isThemeModeForced = in_array($siteModePolicy, [self::MODE_LIGHT, self::MODE_DARK], true);

        if ($isThemeModeForced) {
            $modePreference = $siteModePolicy;
            $modeResolved = $siteModePolicy;
        } else {
            $modePreference = $this->currentModePreference();
            $modeResolved = $this->resolveMode($modePreference);
        }

        $themeForMode = $this->resolveThemeVariant($activeTheme, $modeResolved) ?? $activeTheme;
        $colors = $this->normalizeColors($themeForMode?->colors);
        $cssVars = $this->cssVariables($colors, $modeResolved);
        $isDarkTheme = $this->isDarkTheme($colors);

        $siteAssets = $this->siteAssetPaths();
        $themeFallbackAssets = $this->themeAssetFallbackPaths($activeTheme, $themeForMode);

        $logoLightPath = $siteAssets['logo_path'] ?? $themeFallbackAssets['logo_light'] ?? 'slike/logo.png';
        $logoDarkPath = $siteAssets['logo_dark_path'] ?? $themeFallbackAssets['logo_dark'] ?? $logoLightPath;
        $faviconPath = $siteAssets['favicon_path'] ?? $themeFallbackAssets['favicon'];
        if ($faviconPath === null && Storage::disk('public')->exists('site-assets/favicon.png')) {
            $faviconPath = 'site-assets/favicon.png';
        }
        $this->syncLegacyIcoFromFaviconPng($faviconPath);
        $defaultFavicon = $this->defaultFaviconFallback();

        $resolvedLogoPath = $modeResolved === self::MODE_DARK
            ? ($logoDarkPath ?: $logoLightPath)
            : $logoLightPath;

        $logoUrl = $this->assetUrlFromPath($resolvedLogoPath, asset('storage/slike/logo.png'));
        $faviconUrl = $this->assetUrlFromPath($faviconPath, $defaultFavicon['url']);
        $faviconType = $faviconPath !== null
            ? $this->faviconMimeType($faviconPath)
            : $defaultFavicon['type'];
        $faviconVersion = $this->faviconVersion($faviconPath);

        return [
            'activeTheme' => $themeForMode,
            'activeThemeBase' => $activeTheme,
            'activeThemeColors' => $colors,
            'activeThemeCssVars' => $cssVars,
            'activeThemeIsDark' => $isDarkTheme,
            'activeThemeLogoUrl' => $logoUrl,
            'activeThemeFaviconUrl' => $faviconUrl,
            'activeThemeFaviconType' => $faviconType,
            'activeThemeFaviconVersion' => $faviconVersion,
            'themeModePreference' => $modePreference,
            'themeModeResolved' => $modeResolved,
            'themeModeForced' => $isThemeModeForced,
            'siteThemeModePolicy' => $siteModePolicy,
            'themeModePreferenceCookieName' => $this->modePreferenceCookieName(),
            'themeModeResolvedCookieName' => $this->resolvedModeCookieName(),
        ];
    }

    private function siteAssetPaths(): array
    {
        if (!Schema::hasTable('site_settings')) {
            return [
                'logo_path' => null,
                'logo_dark_path' => null,
                'favicon_path' => null,
            ];
        }

        $siteSettings = SiteSetting::query()->first();
        if ($siteSettings === null) {
            return [
                'logo_path' => null,
                'logo_dark_path' => null,
                'favicon_path' => null,
            ];
        }

        $logoPath = Schema::hasColumn('site_settings', 'logo_path')
            ? $this->sanitizeAssetPath($siteSettings->logo_path)
            : null;
        $logoDarkPath = Schema::hasColumn('site_settings', 'logo_dark_path')
            ? $this->sanitizeAssetPath($siteSettings->logo_dark_path)
            : null;
        $faviconPath = Schema::hasColumn('site_settings', 'favicon_path')
            ? $this->sanitizeAssetPath($siteSettings->favicon_path)
            : null;

        return [
            'logo_path' => $logoPath,
            'logo_dark_path' => $logoDarkPath,
            'favicon_path' => $faviconPath,
        ];
    }

    private function themeAssetFallbackPaths(?Theme $activeTheme, ?Theme $themeForMode): array
    {
        $logoLight = $this->sanitizeAssetPath($themeForMode?->logo_path)
            ?? $this->sanitizeAssetPath($activeTheme?->logo_path);
        $logoDark = null;
        $favicon = $this->sanitizeAssetPath($themeForMode?->favicon_path)
            ?? $this->sanitizeAssetPath($activeTheme?->favicon_path);

        if ($this->supportsVariants() && $activeTheme !== null && !empty($activeTheme->theme_key)) {
            $familyThemes = Theme::query()
                ->where('theme_key', $activeTheme->theme_key)
                ->orderByRaw("CASE WHEN variant = 'light' THEN 0 ELSE 1 END")
                ->get(['variant', 'logo_path', 'favicon_path']);

            $lightTheme = $familyThemes->first(fn (Theme $theme): bool => $theme->variant === 'light');
            $darkTheme = $familyThemes->first(fn (Theme $theme): bool => $theme->variant === 'dark');

            $logoLight = $this->sanitizeAssetPath($lightTheme?->logo_path)
                ?? $logoLight
                ?? $this->sanitizeAssetPath($darkTheme?->logo_path);
            $logoDark = $this->sanitizeAssetPath($darkTheme?->logo_path);
            $favicon = $this->sanitizeAssetPath($lightTheme?->favicon_path)
                ?? $this->sanitizeAssetPath($darkTheme?->favicon_path)
                ?? $favicon;
        }

        return [
            'logo_light' => $logoLight,
            'logo_dark' => $logoDark,
            'favicon' => $favicon,
        ];
    }

    private function assetUrlFromPath(?string $path, string $defaultUrl): string
    {
        $normalizedPath = $this->sanitizeAssetPath($path);
        if ($normalizedPath === null) {
            return $defaultUrl;
        }

        if (str_starts_with($normalizedPath, 'http')) {
            return $normalizedPath;
        }

        return asset('storage/' . ltrim($normalizedPath, '/'));
    }

    private function faviconMimeType(?string $path): string
    {
        $normalizedPath = strtolower((string)$this->sanitizeAssetPath($path));
        if (str_ends_with($normalizedPath, '.png')) {
            return 'image/png';
        }

        if (str_ends_with($normalizedPath, '.svg')) {
            return 'image/svg+xml';
        }

        if (str_ends_with($normalizedPath, '.webp')) {
            return 'image/webp';
        }

        if (str_ends_with($normalizedPath, '.jpg') || str_ends_with($normalizedPath, '.jpeg')) {
            return 'image/jpeg';
        }

        return 'image/x-icon';
    }

    private function defaultFaviconFallback(): array
    {
        if (Storage::disk('public')->exists('site-assets/favicon.png')) {
            return [
                'url' => asset('storage/site-assets/favicon.png'),
                'type' => 'image/png',
            ];
        }

        if (is_file(public_path('favicon.png'))) {
            return [
                'url' => asset('favicon.png'),
                'type' => 'image/png',
            ];
        }

        return [
            'url' => asset('favicon.ico'),
            'type' => 'image/x-icon',
        ];
    }

    private function faviconVersion(?string $path): int
    {
        $normalizedPath = $this->sanitizeAssetPath($path);
        if ($normalizedPath !== null && !str_starts_with($normalizedPath, 'http')) {
            if (Storage::disk('public')->exists($normalizedPath)) {
                return (int)Storage::disk('public')->lastModified($normalizedPath);
            }
        }

        if (Storage::disk('public')->exists('site-assets/favicon.png')) {
            return (int)Storage::disk('public')->lastModified('site-assets/favicon.png');
        }

        if (is_file(public_path('favicon.png'))) {
            return (int)@filemtime(public_path('favicon.png'));
        }

        if (is_file(public_path('favicon.ico'))) {
            return (int)@filemtime(public_path('favicon.ico'));
        }

        return time();
    }

    private function syncLegacyIcoFromFaviconPng(?string $faviconPath): void
    {
        try {
            $source = $this->resolveFaviconPngSource($faviconPath);
            if ($source === null) {
                return;
            }

            $icoPath = public_path('favicon.ico');
            $sourceModified = $source['modified'];
            $currentModified = is_file($icoPath) ? (int)@filemtime($icoPath) : 0;
            if ($currentModified >= $sourceModified && $sourceModified > 0) {
                return;
            }

            $icoBinary = $this->wrapPngAsIco($source['bytes'], 64);
            @file_put_contents($icoPath, $icoBinary, LOCK_EX);

            if ($sourceModified > 0 && is_file($icoPath)) {
                @touch($icoPath, $sourceModified);
            }
        } catch (\Throwable) {
            // Best effort: favicon.ico sync must not break page rendering.
        }
    }

    private function resolveFaviconPngSource(?string $faviconPath): ?array
    {
        $storage = Storage::disk('public');
        $normalizedPath = $this->sanitizeAssetPath($faviconPath);

        if ($normalizedPath !== null
            && !str_starts_with($normalizedPath, 'http')
            && str_ends_with(strtolower($normalizedPath), '.png')
            && $storage->exists($normalizedPath)
        ) {
            $bytes = (string)$storage->get($normalizedPath);
            if ($this->isPngBinary($bytes)) {
                return [
                    'bytes' => $bytes,
                    'modified' => (int)$storage->lastModified($normalizedPath),
                ];
            }
        }

        if ($storage->exists('site-assets/favicon.png')) {
            $bytes = (string)$storage->get('site-assets/favicon.png');
            if ($this->isPngBinary($bytes)) {
                return [
                    'bytes' => $bytes,
                    'modified' => (int)$storage->lastModified('site-assets/favicon.png'),
                ];
            }
        }

        $publicPng = public_path('favicon.png');
        if (is_file($publicPng)) {
            $bytes = (string)@file_get_contents($publicPng);
            if ($this->isPngBinary($bytes)) {
                return [
                    'bytes' => $bytes,
                    'modified' => (int)@filemtime($publicPng),
                ];
            }
        }

        return null;
    }

    private function isPngBinary(string $content): bool
    {
        return strncmp($content, "\x89PNG\r\n\x1a\n", 8) === 0;
    }

    private function wrapPngAsIco(string $pngBinary, int $size = 64): string
    {
        $sizeByte = ($size >= 256) ? 0 : max(min($size, 255), 1);
        $iconDir = pack('vvv', 0, 1, 1);
        $iconEntry = pack(
            'CCCCvvVV',
            $sizeByte,
            $sizeByte,
            0,
            0,
            1,
            32,
            strlen($pngBinary),
            6 + 16
        );

        return $iconDir . $iconEntry . $pngBinary;
    }

    private function sanitizeAssetPath(?string $path): ?string
    {
        if (!is_string($path)) {
            return null;
        }

        $cleaned = trim($path);

        return $cleaned === '' ? null : $cleaned;
    }

    private function resolveThemeVariant(?Theme $activeTheme, string $resolvedMode): ?Theme
    {
        if ($activeTheme === null || !$this->supportsVariants()) {
            return $activeTheme;
        }

        $themeKey = $this->themeKeyFromTheme($activeTheme);
        if ($themeKey === '') {
            return $activeTheme;
        }

        $variant = Theme::query()
            ->where('theme_key', $themeKey)
            ->where('variant', $resolvedMode)
            ->first();

        return $variant ?? $activeTheme;
    }

    private function themeKeyFromTheme(Theme $theme): string
    {
        $key = trim((string)($theme->theme_key ?? ''));
        if ($key !== '') {
            return $key;
        }

        $slug = Str::lower((string)$theme->slug);
        $slug = preg_replace('/-(light|dark|svijetla|tamna)$/', '', $slug);

        return trim((string)$slug);
    }

    private function supportsVariants(): bool
    {
        return Schema::hasTable('themes')
            && Schema::hasColumn('themes', 'theme_key')
            && Schema::hasColumn('themes', 'variant');
    }

    private function readCookie(string $name): ?string
    {
        if (!app()->bound('request')) {
            $cookieValue = $_COOKIE[$name] ?? null;

            return is_string($cookieValue) ? $cookieValue : null;
        }

        $request = app('request');
        if (!method_exists($request, 'cookie')) {
            $cookieValue = $_COOKIE[$name] ?? null;

            return is_string($cookieValue) ? $cookieValue : null;
        }

        $value = $request->cookie($name);
        if (is_string($value)) {
            return $value;
        }

        $cookieValue = $_COOKIE[$name] ?? null;

        return is_string($cookieValue) ? $cookieValue : null;
    }

    private function isDarkTheme(array $colors): bool
    {
        [$r, $g, $b] = $this->hexToRgb($colors['body_bg'] ?? '#ffffff');
        $luminance = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;

        return $luminance < 0.45;
    }

    private function rgbCsv(string $hexColor): string
    {
        [$r, $g, $b] = $this->hexToRgb($hexColor);

        return $r . ', ' . $g . ', ' . $b;
    }

    private function hexToRgb(string $hexColor): array
    {
        $hex = ltrim($hexColor, '#');

        return [
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2)),
        ];
    }

    private function normalizeHexColor(string $color, string $fallback): string
    {
        $candidate = trim($color);
        if (!$this->isValidHexColor($candidate)) {
            return strtolower($fallback);
        }

        return strtolower($candidate);
    }

    private function shiftHexColor(string $hexColor, int $delta): string
    {
        [$r, $g, $b] = $this->hexToRgb($hexColor);

        $r = max(0, min(255, $r + $delta));
        $g = max(0, min(255, $g + $delta));
        $b = max(0, min(255, $b + $delta));

        return sprintf('#%02x%02x%02x', $r, $g, $b);
    }
}
