<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('site_settings')) {
            return;
        }

        Schema::table('site_settings', function (Blueprint $table): void {
            if (!Schema::hasColumn('site_settings', 'logo_path')) {
                $table->string('logo_path')->nullable()->after('theme_mode_policy');
            }
            if (!Schema::hasColumn('site_settings', 'logo_dark_path')) {
                $table->string('logo_dark_path')->nullable()->after('logo_path');
            }
            if (!Schema::hasColumn('site_settings', 'favicon_path')) {
                $table->string('favicon_path')->nullable()->after('logo_dark_path');
            }
        });

        if (!DB::table('site_settings')->exists()) {
            DB::table('site_settings')->insert([
                'theme_mode_policy' => 'auto',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $siteSetting = DB::table('site_settings')->orderBy('id')->first();
        if ($siteSetting === null) {
            return;
        }

        $legacyAssets = $this->resolveLegacyThemeAssets();

        $lightSource = $this->normalizePath($siteSetting->logo_path ?? null) ?? $legacyAssets['logo_light'];
        $darkSource = $this->normalizePath($siteSetting->logo_dark_path ?? null) ?? $legacyAssets['logo_dark'];
        $faviconSource = $this->normalizePath($siteSetting->favicon_path ?? null) ?? $legacyAssets['favicon'];

        if ($darkSource !== null && $lightSource !== null && $darkSource === $lightSource) {
            $darkSource = null;
        }

        $logoPath = $this->moveAssetToSiteAssets($lightSource, 'logo');
        $logoDarkPath = $darkSource !== null ? $this->moveAssetToSiteAssets($darkSource, 'logo_dark') : null;
        $faviconPath = $this->moveAssetToSiteAssets($faviconSource, 'favicon');

        $logoPath = $logoPath ?? 'slike/logo.png';
        if ($logoDarkPath === $logoPath) {
            $logoDarkPath = null;
        }

        DB::table('site_settings')
            ->where('id', $siteSetting->id)
            ->update([
                'logo_path' => $logoPath,
                'logo_dark_path' => $logoDarkPath,
                'favicon_path' => $faviconPath,
                'updated_at' => now(),
            ]);

        $this->syncThemeAssetColumns($logoPath, $logoDarkPath, $faviconPath);
        $this->forceSkDubravaMenuTextWhite();
        $this->cleanupLegacyThemeAssetFolders();
    }

    public function down(): void
    {
        if (!Schema::hasTable('site_settings')) {
            return;
        }

        Schema::table('site_settings', function (Blueprint $table): void {
            if (Schema::hasColumn('site_settings', 'favicon_path')) {
                $table->dropColumn('favicon_path');
            }
            if (Schema::hasColumn('site_settings', 'logo_dark_path')) {
                $table->dropColumn('logo_dark_path');
            }
            if (Schema::hasColumn('site_settings', 'logo_path')) {
                $table->dropColumn('logo_path');
            }
        });
    }

    private function resolveLegacyThemeAssets(): array
    {
        if (!Schema::hasTable('themes')) {
            return [
                'logo_light' => null,
                'logo_dark' => null,
                'favicon' => null,
            ];
        }

        $hasVariant = Schema::hasColumn('themes', 'variant');

        $lightLogo = null;
        $darkLogo = null;
        $favicon = null;

        if ($hasVariant) {
            $lightLogo = $this->firstThemeAssetPath('logo_path', 'light');
            $darkLogo = $this->firstThemeAssetPath('logo_path', 'dark');
            $favicon = $this->firstThemeAssetPath('favicon_path', 'light')
                ?? $this->firstThemeAssetPath('favicon_path', 'dark');
        }

        $lightLogo = $lightLogo ?? $this->firstThemeAssetPath('logo_path');
        $favicon = $favicon ?? $this->firstThemeAssetPath('favicon_path');

        return [
            'logo_light' => $lightLogo,
            'logo_dark' => $darkLogo,
            'favicon' => $favicon,
        ];
    }

    private function firstThemeAssetPath(string $column, ?string $variant = null): ?string
    {
        if (!Schema::hasTable('themes') || !Schema::hasColumn('themes', $column)) {
            return null;
        }

        $query = DB::table('themes')
            ->whereNotNull($column)
            ->where($column, '!=', '');

        if ($variant !== null && Schema::hasColumn('themes', 'variant')) {
            $query->where('variant', $variant);
        }

        $path = $query
            ->orderByDesc('is_active')
            ->orderBy('id')
            ->value($column);

        return $this->normalizePath(is_string($path) ? $path : null);
    }

    private function moveAssetToSiteAssets(?string $sourcePath, string $baseName): ?string
    {
        $sourcePath = $this->normalizePath($sourcePath);
        if ($sourcePath === null || str_starts_with($sourcePath, 'http')) {
            return null;
        }

        $disk = Storage::disk('public');
        if (!$disk->exists($sourcePath)) {
            return null;
        }

        $extension = strtolower((string)pathinfo($sourcePath, PATHINFO_EXTENSION));
        if ($extension === '') {
            $extension = 'png';
        }

        $destinationPath = 'site-assets/' . $baseName . '.' . $extension;
        $this->cleanupSiteAssetVariants($baseName, $sourcePath === $destinationPath ? $destinationPath : null);

        if ($sourcePath !== $destinationPath) {
            $disk->copy($sourcePath, $destinationPath);

            if (str_starts_with($sourcePath, 'themes/')) {
                $disk->delete($sourcePath);
            }
        }

        return $destinationPath;
    }

    private function cleanupSiteAssetVariants(string $baseName, ?string $exceptPath = null): void
    {
        $disk = Storage::disk('public');
        $prefix = 'site-assets/' . $baseName . '.';

        foreach ($disk->files('site-assets') as $filePath) {
            if (!str_starts_with($filePath, $prefix)) {
                continue;
            }

            if ($exceptPath !== null && $filePath === $exceptPath) {
                continue;
            }

            $disk->delete($filePath);
        }
    }

    private function syncThemeAssetColumns(string $logoPath, ?string $logoDarkPath, ?string $faviconPath): void
    {
        if (!Schema::hasTable('themes')) {
            return;
        }

        $darkLogoPath = $logoDarkPath ?: $logoPath;

        if (Schema::hasColumn('themes', 'variant')) {
            DB::table('themes')
                ->where('variant', 'dark')
                ->update([
                    'logo_path' => $darkLogoPath,
                    'favicon_path' => $faviconPath,
                    'updated_at' => now(),
                ]);

            DB::table('themes')
                ->where('variant', '!=', 'dark')
                ->update([
                    'logo_path' => $logoPath,
                    'favicon_path' => $faviconPath,
                    'updated_at' => now(),
                ]);

            DB::table('themes')
                ->whereNull('variant')
                ->update([
                    'logo_path' => $logoPath,
                    'favicon_path' => $faviconPath,
                    'updated_at' => now(),
                ]);

            return;
        }

        DB::table('themes')->update([
            'logo_path' => $logoPath,
            'favicon_path' => $faviconPath,
            'updated_at' => now(),
        ]);
    }

    private function forceSkDubravaMenuTextWhite(): void
    {
        if (!Schema::hasTable('themes') || !Schema::hasColumn('themes', 'colors')) {
            return;
        }

        $query = DB::table('themes');
        if (Schema::hasColumn('themes', 'theme_key')) {
            $query->where('theme_key', 'skdubrava');
        } else {
            $query->where('name', 'SKDubrava');
        }

        $themes = $query->get(['id', 'colors']);
        foreach ($themes as $theme) {
            $colors = json_decode((string)$theme->colors, true);
            if (!is_array($colors)) {
                continue;
            }

            $colors['nav_item_text'] = '#ffffff';

            DB::table('themes')
                ->where('id', $theme->id)
                ->update([
                    'colors' => json_encode($colors, JSON_UNESCAPED_UNICODE),
                    'updated_at' => now(),
                ]);
        }
    }

    private function cleanupLegacyThemeAssetFolders(): void
    {
        $disk = Storage::disk('public');
        if (!$disk->exists('themes')) {
            return;
        }

        foreach ($disk->allFiles('themes') as $filePath) {
            $fileName = strtolower((string)basename($filePath));
            if (str_starts_with($fileName, 'logo_') || str_starts_with($fileName, 'favicon')) {
                $disk->delete($filePath);
            }
        }

        $directories = $disk->allDirectories('themes');
        usort($directories, static fn (string $a, string $b): int => strlen($b) <=> strlen($a));
        foreach ($directories as $directory) {
            if (count($disk->files($directory)) === 0 && count($disk->directories($directory)) === 0) {
                $disk->deleteDirectory($directory);
            }
        }

        if (count($disk->files('themes')) === 0 && count($disk->directories('themes')) === 0) {
            $disk->deleteDirectory('themes');
        }
    }

    private function normalizePath(?string $path): ?string
    {
        if (!is_string($path)) {
            return null;
        }

        $cleaned = trim($path);

        return $cleaned === '' ? null : $cleaned;
    }
};
