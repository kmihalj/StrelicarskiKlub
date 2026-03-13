<?php

namespace Database\Seeders;

use App\Models\SiteSetting;
use App\Models\Theme;
use App\Services\ThemeService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class DefaultThemeSeeder extends Seeder
{
    public function run(): void
    {
        if (!Schema::hasTable('themes')) {
            return;
        }

        $supportsThemeKey = Schema::hasColumn('themes', 'theme_key');
        $supportsVariant = Schema::hasColumn('themes', 'variant');

        if ($supportsThemeKey) {
            Theme::query()->where('theme_key', 'skdubrava')->delete();
        } else {
            Theme::query()->where('name', 'like', 'SKDubrava%')->delete();
        }

        $siteSetting = null;
        if (Schema::hasTable('site_settings')) {
            $siteSetting = SiteSetting::query()->first();
            if ($siteSetting === null) {
                $siteSetting = SiteSetting::query()->create([
                    'theme_mode_policy' => ThemeService::MODE_AUTO,
                ]);
            }
        }

        $logoPath = null;
        $logoDarkPath = null;
        $faviconPath = null;

        $sourceLogo = __DIR__ . '/assets/archery-target.png';
        $sourceFavicon = __DIR__ . '/assets/archery-target-favicon.png';

        if (is_file($sourceLogo)) {
            Storage::disk('public')->put('site-assets/logo.png', (string)file_get_contents($sourceLogo));
            Storage::disk('public')->put('site-assets/logo_dark.png', (string)file_get_contents($sourceLogo));
            $logoPath = 'site-assets/logo.png';
            $logoDarkPath = 'site-assets/logo_dark.png';
        }

        if (is_file($sourceFavicon)) {
            Storage::disk('public')->put('site-assets/favicon.png', (string)file_get_contents($sourceFavicon));
            $faviconPath = 'site-assets/favicon.png';
        }

        if ($siteSetting !== null) {
            if (Schema::hasColumn('site_settings', 'theme_mode_policy')) {
                $siteSetting->theme_mode_policy = ThemeService::MODE_AUTO;
            }
            if ($logoPath !== null && Schema::hasColumn('site_settings', 'logo_path')) {
                $siteSetting->logo_path = $logoPath;
            }
            if ($logoDarkPath !== null && Schema::hasColumn('site_settings', 'logo_dark_path')) {
                $siteSetting->logo_dark_path = $logoDarkPath;
            }
            if ($faviconPath !== null && Schema::hasColumn('site_settings', 'favicon_path')) {
                $siteSetting->favicon_path = $faviconPath;
            }
            $siteSetting->save();
        }

        if ($logoPath !== null && Schema::hasColumn('themes', 'logo_path')) {
            Theme::query()->update(['logo_path' => $logoPath]);
        }
        if ($faviconPath !== null && Schema::hasColumn('themes', 'favicon_path')) {
            Theme::query()->update(['favicon_path' => $faviconPath]);
        }

        Theme::query()->update(['is_active' => false]);

        $greenTheme = null;
        if ($supportsThemeKey && $supportsVariant) {
            $greenTheme = Theme::query()
                ->where('theme_key', 'zelena')
                ->where('variant', 'light')
                ->first();
        }

        if ($greenTheme === null) {
            $greenTheme = Theme::query()->where('name', 'Zelena')->orderBy('id')->first();
        }

        if ($greenTheme === null) {
            $greenTheme = Theme::query()->orderBy('id')->first();
        }

        if ($greenTheme !== null) {
            $greenTheme->is_active = true;
            $greenTheme->save();
        }
    }
}
