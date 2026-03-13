<?php

namespace App\Http\Controllers;

use App\Models\SiteSetting;
use App\Models\Theme;
use App\Services\ThemeService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ThemeController extends Controller
{
    public function __construct(private readonly ThemeService $themeService)
    {
    }

    public function index(Request $request): View
    {
        $themes = $this->orderedThemes()->get();

        $selectedThemeId = (int)$request->query('theme');
        $selectedTheme = $selectedThemeId > 0 ? $themes->firstWhere('id', $selectedThemeId) : null;

        if ($selectedTheme === null) {
            $sharedThemeData = $this->themeService->sharedThemeData();
            $currentTheme = $sharedThemeData['activeTheme'] ?? null;
            if ($currentTheme instanceof Theme) {
                $selectedTheme = $themes->firstWhere('id', $currentTheme->id);
            }
        }

        $selectedTheme = $selectedTheme ?? $themes->first();
        $assetPreview = $this->buildAssetPreview($selectedTheme);

        return view('admin.teme.index', [
            'themes' => $themes,
            'selectedTheme' => $selectedTheme,
            'editableColorKeys' => $this->themeService->getEditableColorKeys(),
            'selectedColors' => $this->themeService->normalizeColors($selectedTheme?->colors),
            'assetPreview' => $assetPreview,
            'variantLabels' => [
                'light' => 'Svijetla',
                'dark' => 'Tamna',
            ],
        ]);
    }

    public function activate(Theme $theme): RedirectResponse
    {
        Theme::query()->update(['is_active' => false]);
        $theme->is_active = true;
        $theme->save();

        return redirect()
            ->route('admin.teme.index', ['theme' => $theme->id])
            ->with('success', 'Tema je aktivirana.');
    }

    public function clone(Request $request, Theme $theme): RedirectResponse
    {
        $validated = $request->validate([
            'clone_name' => 'required|string|min:3|max:120',
        ], [
            'clone_name.required' => 'Naziv klona je obavezan.',
        ]);

        $familyThemes = $this->familyThemes($theme)->get();
        $familyThemeIds = $familyThemes->pluck('id')->all();

        $nameTaken = Theme::query()
            ->whereNotIn('id', $familyThemeIds)
            ->where('name', $validated['clone_name'])
            ->exists();

        if ($nameTaken) {
            throw ValidationException::withMessages([
                'clone_name' => 'Tema s tim nazivom već postoji.',
            ]);
        }

        $newThemeKey = $this->generateUniqueThemeKey($validated['clone_name']);

        $clonedThemes = collect();
        foreach ($familyThemes as $sourceTheme) {
            $clone = $sourceTheme->replicate(['is_active']);
            $clone->name = $validated['clone_name'];
            $clone->is_active = false;

            if ($this->supportsVariants()) {
                $variant = $this->themeVariant($sourceTheme);
                $clone->theme_key = $newThemeKey;
                $clone->variant = $variant;
                $clone->slug = $this->generateUniqueSlug($newThemeKey . '-' . $variant);
            } else {
                $clone->slug = $this->generateUniqueSlug($validated['clone_name']);
            }

            $clone->save();
            $clonedThemes->push($clone);
        }

        $selectedClone = $clonedThemes
            ->first(fn (Theme $cloned) => $this->themeVariant($cloned) === 'light')
            ?? $clonedThemes->first();

        return redirect()
            ->route('admin.teme.index', ['theme' => $selectedClone?->id])
            ->with('success', 'Tema je uspješno klonirana.');
    }

    public function update(Request $request, Theme $theme): RedirectResponse
    {
        $rules = [
            'name' => 'required|string|min:3|max:120',
            'description' => 'nullable|string|max:255',
            'logo' => 'nullable|file|mimes:jpg,jpeg,png,webp,svg|max:8192',
            'favicon_source' => 'nullable|file|mimes:jpg,jpeg,png,webp|max:8192',
        ];

        foreach ($this->themeService->getEditableColorKeys() as $key) {
            $rules[$key] = 'required|regex:/^#[0-9A-Fa-f]{6}$/';
        }

        $validated = $request->validate($rules, [
            'name.required' => 'Naziv teme je obavezan.',
            'logo.mimes' => 'Logo mora biti jpg, jpeg, png, webp ili svg.',
            'favicon_source.mimes' => 'Favicon izvor mora biti jpg, jpeg, png ili webp.',
        ]);

        if (!$this->hasSiteAssetColumns()) {
            return redirect()
                ->route('admin.teme.index', ['theme' => $theme->id])
                ->with('error', 'Nedostaju globalna polja za logo/favicon. Pokreni migracije.');
        }

        $siteSetting = SiteSetting::query()->first();
        if ($siteSetting === null) {
            $siteSetting = SiteSetting::query()->create([
                'theme_mode_policy' => ThemeService::MODE_AUTO,
            ]);
        }

        $familyThemes = $this->familyThemes($theme)->get();
        $familyThemeIds = $familyThemes->pluck('id')->all();
        $currentFamilyKey = $this->themeKey($theme);
        $currentVariant = $this->themeVariant($theme);

        $nameTaken = Theme::query()
            ->whereNotIn('id', $familyThemeIds)
            ->where('name', $validated['name'])
            ->exists();

        if ($nameTaken) {
            throw ValidationException::withMessages([
                'name' => 'Tema s tim nazivom već postoji.',
            ]);
        }

        $newThemeKey = $this->supportsVariants()
            ? $this->generateUniqueThemeKey($validated['name'], $currentFamilyKey)
            : $currentFamilyKey;

        $colors = $this->themeService->normalizeColors($request->only($this->themeService->getEditableColorKeys()));
        $colors['nav_item_hover_text'] = $this->themeService->contrastColor($colors['nav_item_hover_bg']);
        $colors['nav_dropdown_text'] = $this->themeService->contrastColor($colors['nav_dropdown_bg']);
        $colors['nav_dropdown_hover_text'] = $this->themeService->contrastColor($colors['nav_dropdown_hover_bg']);

        $siteLogoPath = $this->sanitizeAssetPath($siteSetting->logo_path);
        $siteLogoDarkPath = $this->sanitizeAssetPath($siteSetting->logo_dark_path);
        $siteFaviconPath = $this->sanitizeAssetPath($siteSetting->favicon_path);

        if ($request->hasFile('logo')) {
            $targetBaseName = $currentVariant === 'dark' ? 'logo_dark' : 'logo';
            $uploadedLogoPath = $this->storeSiteAsset($request->file('logo'), $targetBaseName);

            if ($currentVariant === 'dark') {
                $this->cleanupReplacedAsset($siteLogoDarkPath, $uploadedLogoPath);
                $siteLogoDarkPath = $uploadedLogoPath;
            } else {
                $this->cleanupReplacedAsset($siteLogoPath, $uploadedLogoPath);
                $siteLogoPath = $uploadedLogoPath;
            }
        }

        if ($request->hasFile('favicon_source')) {
            $uploadedFaviconPath = $this->generateFaviconFromSource($request->file('favicon_source'));
            if ($uploadedFaviconPath === null) {
                return redirect()
                    ->route('admin.teme.index', ['theme' => $theme->id])
                    ->with('error', 'Favicon nije moguće generirati iz odabrane slike.');
            }

            $this->cleanupReplacedAsset($siteFaviconPath, $uploadedFaviconPath);
            $siteFaviconPath = $uploadedFaviconPath;
        }

        foreach ($familyThemes as $familyTheme) {
            $familyTheme->name = $validated['name'];
            $familyTheme->description = $validated['description'] ?? null;

            if ($this->supportsVariants()) {
                $variant = $this->themeVariant($familyTheme);
                $familyTheme->theme_key = $newThemeKey;
                $familyTheme->slug = $this->generateUniqueSlug($newThemeKey . '-' . $variant, $familyTheme->id);
            } else {
                $familyTheme->slug = $this->generateUniqueSlug($validated['name'], $familyTheme->id);
            }

            if ((int)$familyTheme->id === (int)$theme->id) {
                $familyTheme->colors = $colors;
            }

            $familyTheme->save();
        }

        $siteLogoPath = $siteLogoPath ?: 'slike/logo.png';
        if ($siteLogoDarkPath === $siteLogoPath) {
            $siteLogoDarkPath = null;
        }

        $siteSetting->logo_path = $siteLogoPath;
        $siteSetting->logo_dark_path = $siteLogoDarkPath;
        $siteSetting->favicon_path = $siteFaviconPath;
        $siteSetting->save();

        $this->syncGlobalAssetPathsToThemes($siteLogoPath, $siteLogoDarkPath, $siteFaviconPath);

        return redirect()
            ->route('admin.teme.index', ['theme' => $theme->id])
            ->with('success', 'Tema je spremljena.');
    }

    private function orderedThemes()
    {
        $query = Theme::query()
            ->orderByDesc('is_active')
            ->orderBy('name');

        if ($this->supportsVariants()) {
            $query->orderByRaw("CASE WHEN variant = 'light' THEN 0 ELSE 1 END");
        }

        return $query->orderBy('id');
    }

    private function familyThemes(Theme $theme)
    {
        if ($this->supportsVariants()) {
            return Theme::query()->where('theme_key', $this->themeKey($theme));
        }

        return Theme::query()->whereKey($theme->id);
    }

    private function themeKey(Theme $theme): string
    {
        $themeKey = trim((string)($theme->theme_key ?? ''));
        if ($themeKey !== '') {
            return $themeKey;
        }

        $slug = trim((string)$theme->slug);

        return $slug !== '' ? $slug : ('tema-' . $theme->id);
    }

    private function themeVariant(Theme $theme): string
    {
        $variant = Str::lower(trim((string)($theme->variant ?? 'light')));

        return in_array($variant, ['light', 'dark'], true) ? $variant : 'light';
    }

    private function supportsVariants(): bool
    {
        return Schema::hasColumn('themes', 'theme_key')
            && Schema::hasColumn('themes', 'variant');
    }

    private function generateUniqueThemeKey(string $name, ?string $ignoreThemeKey = null): string
    {
        $base = Str::slug($name);
        if ($base === '') {
            $base = 'tema';
        }

        if (!$this->supportsVariants()) {
            return $base;
        }

        $themeKey = $base;
        $i = 2;
        while (Theme::query()
            ->when($ignoreThemeKey !== null, fn ($query) => $query->where('theme_key', '!=', $ignoreThemeKey))
            ->where('theme_key', $themeKey)
            ->exists()) {
            $themeKey = $base . '-' . $i;
            $i++;
        }

        return $themeKey;
    }

    private function generateUniqueSlug(string $preferred, ?int $ignoreThemeId = null): string
    {
        $base = Str::slug($preferred);
        if ($base === '') {
            $base = 'tema';
        }

        $slug = $base;
        $i = 2;
        while (Theme::query()
            ->where('slug', $slug)
            ->when($ignoreThemeId !== null, fn ($query) => $query->where('id', '!=', $ignoreThemeId))
            ->exists()) {
            $slug = $base . '-' . $i;
            $i++;
        }

        return $slug;
    }

    private function generateFaviconFromSource(UploadedFile $file): ?string
    {
        $imageContents = @file_get_contents($file->getRealPath());
        if ($imageContents === false) {
            return null;
        }

        $pngBinary = null;

        if (function_exists('imagecreatefromstring')) {
            $sourceImage = @imagecreatefromstring($imageContents);
            if ($sourceImage === false) {
                return null;
            }

            $size = 64;
            $faviconImage = imagecreatetruecolor($size, $size);
            imagealphablending($faviconImage, false);
            imagesavealpha($faviconImage, true);
            $transparent = imagecolorallocatealpha($faviconImage, 0, 0, 0, 127);
            imagefill($faviconImage, 0, 0, $transparent);

            $srcWidth = imagesx($sourceImage);
            $srcHeight = imagesy($sourceImage);
            $scale = min($size / max($srcWidth, 1), $size / max($srcHeight, 1));
            $dstWidth = max((int)round($srcWidth * $scale), 1);
            $dstHeight = max((int)round($srcHeight * $scale), 1);
            $dstX = (int)floor(($size - $dstWidth) / 2);
            $dstY = (int)floor(($size - $dstHeight) / 2);

            imagecopyresampled(
                $faviconImage,
                $sourceImage,
                $dstX,
                $dstY,
                0,
                0,
                $dstWidth,
                $dstHeight,
                $srcWidth,
                $srcHeight
            );

            ob_start();
            imagepng($faviconImage, null, 9);
            $pngBinary = (string)ob_get_clean();

            imagedestroy($sourceImage);
            imagedestroy($faviconImage);
        } elseif ($this->isPngBinary($imageContents)) {
            // If GD is unavailable, only raw PNG input can be reused safely.
            $pngBinary = $imageContents;
        }

        if ($pngBinary === null || $pngBinary === '') {
            return null;
        }

        $this->cleanupSiteAssetVariants('favicon');
        $storagePath = 'site-assets/favicon.png';
        Storage::disk('public')->put($storagePath, $pngBinary);
        Storage::disk('public')->put('site-assets/favicon.ico', $this->wrapPngAsIco($pngBinary, 64));
        $this->syncPublicFaviconIco($pngBinary);

        return $storagePath;
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

    private function syncPublicFaviconIco(string $pngBinary): void
    {
        $icoPath = public_path('favicon.ico');
        $icoBinary = $this->wrapPngAsIco($pngBinary, 64);
        @file_put_contents($icoPath, $icoBinary, LOCK_EX);
    }

    private function hasSiteAssetColumns(): bool
    {
        return Schema::hasTable('site_settings')
            && Schema::hasColumn('site_settings', 'logo_path')
            && Schema::hasColumn('site_settings', 'logo_dark_path')
            && Schema::hasColumn('site_settings', 'favicon_path');
    }

    private function currentSiteAssetPaths(): array
    {
        if (!$this->hasSiteAssetColumns()) {
            return [
                'logo_path' => null,
                'logo_dark_path' => null,
                'favicon_path' => null,
            ];
        }

        $siteSetting = SiteSetting::query()->first();

        return [
            'logo_path' => $this->sanitizeAssetPath($siteSetting?->logo_path),
            'logo_dark_path' => $this->sanitizeAssetPath($siteSetting?->logo_dark_path),
            'favicon_path' => $this->sanitizeAssetPath($siteSetting?->favicon_path),
        ];
    }

    private function storeSiteAsset(UploadedFile $file, string $baseName): string
    {
        $extension = strtolower((string)$file->extension());
        if ($extension === '') {
            $extension = 'png';
        }

        $this->cleanupSiteAssetVariants($baseName);

        $fileName = $baseName . '.' . $extension;

        return $file->storeAs('site-assets', $fileName, 'public');
    }

    private function cleanupSiteAssetVariants(string $baseName, ?string $exceptPath = null): void
    {
        $disk = Storage::disk('public');
        $prefix = 'site-assets/' . $baseName . '.';
        foreach ($disk->files('site-assets') as $existingPath) {
            if (!str_starts_with($existingPath, $prefix)) {
                continue;
            }

            if ($exceptPath !== null && $existingPath === $exceptPath) {
                continue;
            }

            $disk->delete($existingPath);
        }
    }

    private function cleanupReplacedAsset(?string $oldPath, ?string $newPath = null): void
    {
        $normalizedOldPath = $this->sanitizeAssetPath($oldPath);
        if ($normalizedOldPath === null || $normalizedOldPath === $newPath) {
            return;
        }

        if (str_starts_with($normalizedOldPath, 'http')) {
            return;
        }

        if ($normalizedOldPath === 'slike/logo.png') {
            return;
        }

        if (str_starts_with($normalizedOldPath, 'site-assets/') || str_starts_with($normalizedOldPath, 'themes/')) {
            Storage::disk('public')->delete($normalizedOldPath);
        }
    }

    private function syncGlobalAssetPathsToThemes(?string $logoPath, ?string $logoDarkPath, ?string $faviconPath): void
    {
        if (!Schema::hasTable('themes')) {
            return;
        }

        $lightLogo = $logoPath ?: 'slike/logo.png';
        $darkLogo = $logoDarkPath ?: $lightLogo;

        if ($this->supportsVariants()) {
            Theme::query()
                ->where('variant', 'dark')
                ->update([
                    'logo_path' => $darkLogo,
                    'favicon_path' => $faviconPath,
                ]);

            Theme::query()
                ->where('variant', '!=', 'dark')
                ->update([
                    'logo_path' => $lightLogo,
                    'favicon_path' => $faviconPath,
                ]);

            Theme::query()
                ->whereNull('variant')
                ->update([
                    'logo_path' => $lightLogo,
                    'favicon_path' => $faviconPath,
                ]);

            return;
        }

        Theme::query()->update([
            'logo_path' => $lightLogo,
            'favicon_path' => $faviconPath,
        ]);
    }

    private function buildAssetPreview(?Theme $selectedTheme): array
    {
        if ($selectedTheme === null) {
            return [];
        }

        $siteAssetPaths = $this->currentSiteAssetPaths();
        $lightLogoOwnPath = $this->existingAssetPath($siteAssetPaths['logo_path']);
        $darkLogoOwnPath = $this->existingAssetPath($siteAssetPaths['logo_dark_path']);
        $faviconOwnPath = $this->existingAssetPath($siteAssetPaths['favicon_path']);

        $lightLogoEffectivePath = $lightLogoOwnPath ?? 'slike/logo.png';
        $darkLogoEffectivePath = $darkLogoOwnPath ?? $lightLogoEffectivePath;
        $faviconEffectivePath = $faviconOwnPath;

        $selectedVariant = $this->themeVariant($selectedTheme);
        $selectedLogoEffectivePath = $selectedVariant === 'dark' ? $darkLogoEffectivePath : $lightLogoEffectivePath;
        $selectedLogoOwnPath = $selectedVariant === 'dark' ? $darkLogoOwnPath : $lightLogoOwnPath;

        return [
            'selectedVariant' => $selectedVariant,
            'selected' => [
                'logo_url' => $this->logoUrlFromPath($selectedLogoEffectivePath),
                'logo_path' => $selectedLogoEffectivePath,
                'logo_inherited' => $selectedLogoOwnPath === null,
                'favicon_url' => $this->faviconUrlFromPath($faviconEffectivePath),
                'favicon_path' => $faviconEffectivePath,
                'favicon_inherited' => $faviconOwnPath === null,
            ],
            'light' => [
                'logo_url' => $this->logoUrlFromPath($lightLogoEffectivePath),
                'logo_path' => $lightLogoEffectivePath,
                'logo_inherited' => $lightLogoOwnPath === null,
                'favicon_url' => $this->faviconUrlFromPath($faviconEffectivePath),
                'favicon_path' => $faviconEffectivePath,
                'favicon_inherited' => $faviconOwnPath === null,
            ],
            'dark' => [
                'logo_url' => $this->logoUrlFromPath($darkLogoEffectivePath),
                'logo_path' => $darkLogoEffectivePath,
                'logo_inherited' => $darkLogoOwnPath === null,
                'favicon_url' => $this->faviconUrlFromPath($faviconEffectivePath),
                'favicon_path' => $faviconEffectivePath,
                'favicon_inherited' => $faviconOwnPath === null,
            ],
            'favicon' => [
                'url' => $this->faviconUrlFromPath($faviconEffectivePath),
                'path' => $faviconEffectivePath ?? $this->defaultFaviconRelativePath(),
                'is_default' => $faviconOwnPath === null,
            ],
        ];
    }

    private function sanitizeAssetPath(?string $path): ?string
    {
        if (!is_string($path)) {
            return null;
        }

        $cleaned = trim($path);

        return $cleaned === '' ? null : $cleaned;
    }

    private function logoUrlFromPath(?string $path): string
    {
        $normalizedPath = $this->existingAssetPath($path) ?? 'slike/logo.png';

        if (str_starts_with($normalizedPath, 'http')) {
            return $normalizedPath;
        }

        return asset('storage/' . ltrim($normalizedPath, '/'));
    }

    private function faviconUrlFromPath(?string $path): string
    {
        $normalizedPath = $this->existingAssetPath($path);
        if ($normalizedPath === null) {
            return $this->defaultFaviconUrl();
        }

        if (str_starts_with($normalizedPath, 'http')) {
            return $normalizedPath;
        }

        return asset('storage/' . ltrim($normalizedPath, '/'));
    }

    private function defaultFaviconUrl(): string
    {
        if (Storage::disk('public')->exists('site-assets/favicon.png')) {
            return asset('storage/site-assets/favicon.png');
        }

        return is_file(public_path('favicon.png'))
            ? asset('favicon.png')
            : asset('favicon.ico');
    }

    private function defaultFaviconRelativePath(): string
    {
        if (Storage::disk('public')->exists('site-assets/favicon.png')) {
            return 'site-assets/favicon.png';
        }

        return is_file(public_path('favicon.png')) ? 'favicon.png' : 'favicon.ico';
    }

    private function existingAssetPath(?string $path): ?string
    {
        $normalizedPath = $this->sanitizeAssetPath($path);
        if ($normalizedPath === null) {
            return null;
        }

        if (str_starts_with($normalizedPath, 'http')) {
            return $normalizedPath;
        }

        return Storage::disk('public')->exists($normalizedPath) ? $normalizedPath : null;
    }
}
