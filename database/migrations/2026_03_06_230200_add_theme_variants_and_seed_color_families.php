<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('themes')) {
            return;
        }

        $hasThemeKey = Schema::hasColumn('themes', 'theme_key');
        $hasVariant = Schema::hasColumn('themes', 'variant');

        Schema::table('themes', function (Blueprint $table) use ($hasThemeKey, $hasVariant): void {
            if (!$hasThemeKey) {
                $table->string('theme_key')->nullable()->after('slug');
                $table->index('theme_key');
            }
            if (!$hasVariant) {
                $table->string('variant', 10)->default('light')->after('theme_key');
            }
        });

        try {
            Schema::table('themes', function (Blueprint $table): void {
                $table->dropUnique('themes_name_unique');
            });
        } catch (\Throwable) {
            // best-effort: index may already be removed
        }

        $themes = DB::table('themes')->orderBy('id')->get();
        $usedPairs = [];
        $usedSlugs = [];

        foreach ($themes as $theme) {
            $colors = json_decode((string)$theme->colors, true);
            if (!is_array($colors)) {
                $colors = [];
            }

            $variant = $this->inferVariant((string)$theme->slug, $colors);
            [$normalizedName, $baseKey] = $this->normalizeThemeIdentity((string)$theme->name, (string)$theme->slug);

            $themeKey = $baseKey;
            $suffix = 2;
            while (isset($usedPairs[$themeKey . '|' . $variant])) {
                $themeKey = $baseKey . '-' . $suffix;
                $suffix++;
            }

            $slugBase = $themeKey . '-' . $variant;
            $slug = $slugBase;
            $slugSuffix = 2;
            while (isset($usedSlugs[$slug])) {
                $slug = $slugBase . '-' . $slugSuffix;
                $slugSuffix++;
            }

            DB::table('themes')
                ->where('id', $theme->id)
                ->update([
                    'name' => $normalizedName,
                    'slug' => $slug,
                    'theme_key' => $themeKey,
                    'variant' => $variant,
                ]);

            $usedPairs[$themeKey . '|' . $variant] = true;
            $usedSlugs[$slug] = true;
        }

        if (!DB::table('themes')->where('is_active', true)->exists()) {
            DB::table('themes')
                ->orderBy('id')
                ->limit(1)
                ->update(['is_active' => true]);
        }

        $fallbackLogo = DB::table('themes')->whereNotNull('logo_path')->value('logo_path') ?: 'slike/logo.png';
        $fallbackFavicon = DB::table('themes')->whereNotNull('favicon_path')->value('favicon_path');

        $presets = $this->presetFamilies();
        foreach ($presets as $family) {
            foreach (['light', 'dark'] as $variant) {
                $exists = DB::table('themes')
                    ->where('theme_key', $family['key'])
                    ->where('variant', $variant)
                    ->exists();

                if ($exists) {
                    continue;
                }

                DB::table('themes')->insert([
                    'name' => $family['name'],
                    'slug' => $this->uniqueSlug($family['key'] . '-' . $variant),
                    'theme_key' => $family['key'],
                    'variant' => $variant,
                    'description' => $family['description'][$variant],
                    'is_active' => false,
                    'colors' => json_encode($family['colors'][$variant], JSON_UNESCAPED_UNICODE),
                    'logo_path' => $fallbackLogo,
                    'favicon_path' => $fallbackFavicon,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        try {
            Schema::table('themes', function (Blueprint $table): void {
                $table->unique(['theme_key', 'variant'], 'themes_theme_key_variant_unique');
            });
        } catch (\Throwable) {
            // best-effort: index may already exist
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('themes')) {
            return;
        }

        try {
            Schema::table('themes', function (Blueprint $table): void {
                $table->dropUnique('themes_theme_key_variant_unique');
            });
        } catch (\Throwable) {
            // ignore
        }

        if (Schema::hasColumn('themes', 'variant')) {
            $themes = DB::table('themes')->orderBy('id')->get();
            $usedNames = [];

            foreach ($themes as $theme) {
                $name = (string)$theme->name;
                if (isset($usedNames[$name])) {
                    $suffix = $theme->variant === 'dark' ? ' Tamna' : ' Svijetla';
                    $name = trim($name . $suffix . ' ' . $theme->id);
                }
                $usedNames[$name] = true;

                DB::table('themes')->where('id', $theme->id)->update(['name' => $name]);
            }
        }

        Schema::table('themes', function (Blueprint $table): void {
            if (Schema::hasColumn('themes', 'variant')) {
                $table->dropColumn('variant');
            }
            if (Schema::hasColumn('themes', 'theme_key')) {
                try {
                    $table->dropIndex('themes_theme_key_index');
                } catch (\Throwable) {
                    // ignore
                }
                $table->dropColumn('theme_key');
            }
        });

        try {
            Schema::table('themes', function (Blueprint $table): void {
                $table->unique('name');
            });
        } catch (\Throwable) {
            // ignore
        }
    }

    private function inferVariant(string $slug, array $colors): string
    {
        $slugLower = Str::lower($slug);
        if (str_contains($slugLower, 'dark') || str_contains($slugLower, 'tamna')) {
            return 'dark';
        }
        if (str_contains($slugLower, 'light') || str_contains($slugLower, 'svijetla')) {
            return 'light';
        }

        $bodyBg = (string)($colors['body_bg'] ?? '#ffffff');
        $luminance = $this->hexLuminance($bodyBg);

        return $luminance < 0.45 ? 'dark' : 'light';
    }

    private function normalizeThemeIdentity(string $name, string $slug): array
    {
        $nameLower = Str::lower($name);
        $slugLower = Str::lower($slug);

        if (str_contains($nameLower, 'skdubrava') || str_contains($slugLower, 'skdubrava')) {
            return ['SKDubrava', 'skdubrava'];
        }

        $clean = trim((string)preg_replace('/\s+(tema|tamna|svijetla|light|dark)$/iu', '', $name));
        if ($clean === '') {
            $clean = $name !== '' ? $name : 'Tema';
        }

        $key = Str::slug($clean);
        if ($key === '') {
            $key = 'tema';
        }

        return [$clean, $key];
    }

    private function uniqueSlug(string $base): string
    {
        $slug = $base;
        $i = 2;
        while (DB::table('themes')->where('slug', $slug)->exists()) {
            $slug = $base . '-' . $i;
            $i++;
        }

        return $slug;
    }

    private function presetFamilies(): array
    {
        return [
            [
                'key' => 'narancasta',
                'name' => 'Naranđasta',
                'description' => [
                    'light' => 'Topla narančasta svijetla varijanta.',
                    'dark' => 'Topla narančasta tamna varijanta.',
                ],
                'colors' => [
                    'light' => $this->buildPalette([
                        'body_bg' => '#f7efe8',
                        'body_text' => '#2d2118',
                        'primary' => '#f97316',
                        'secondary' => '#ea580c',
                        'success' => '#16a34a',
                        'danger' => '#dc2626',
                        'warning' => '#f59e0b',
                        'info' => '#0ea5e9',
                        'light' => '#fff7ed',
                        'dark' => '#7c2d12',
                        'secondary_subtle' => '#f3dfcd',
                        'dark_subtle' => '#e8c7a9',
                        'nav_solid_bg' => '#7c2d12',
                        'nav_gradient_start' => '#ea580c',
                        'nav_gradient_mid' => '#f97316',
                        'nav_gradient_end' => '#fb923c',
                        'nav_item_border' => '#fed7aa',
                        'nav_item_hover_bg' => '#fdba74',
                        'nav_dropdown_bg' => '#ffedd5',
                        'nav_dropdown_hover_bg' => '#fdba74',
                    ]),
                    'dark' => $this->buildPalette([
                        'body_bg' => '#17110d',
                        'body_text' => '#f4ede7',
                        'primary' => '#fb923c',
                        'secondary' => '#f97316',
                        'success' => '#34d399',
                        'danger' => '#f87171',
                        'warning' => '#fbbf24',
                        'info' => '#38bdf8',
                        'light' => '#fef3e7',
                        'dark' => '#2b1c13',
                        'secondary_subtle' => '#2b2019',
                        'dark_subtle' => '#3a2a20',
                        'nav_solid_bg' => '#1f140f',
                        'nav_gradient_start' => '#2a1a12',
                        'nav_gradient_mid' => '#7c2d12',
                        'nav_gradient_end' => '#c2410c',
                        'nav_item_border' => '#7c4a2f',
                        'nav_item_hover_bg' => '#9a3412',
                        'nav_dropdown_bg' => '#2b2019',
                        'nav_dropdown_hover_bg' => '#9a3412',
                    ]),
                ],
            ],
            [
                'key' => 'plava',
                'name' => 'Plava',
                'description' => [
                    'light' => 'Plava svijetla varijanta.',
                    'dark' => 'Plava tamna varijanta.',
                ],
                'colors' => [
                    'light' => $this->buildPalette([
                        'body_bg' => '#ecf3ff',
                        'body_text' => '#10213d',
                        'primary' => '#2563eb',
                        'secondary' => '#1d4ed8',
                        'success' => '#16a34a',
                        'danger' => '#dc2626',
                        'warning' => '#f59e0b',
                        'info' => '#0ea5e9',
                        'light' => '#f8fbff',
                        'dark' => '#1e3a8a',
                        'secondary_subtle' => '#dbe8ff',
                        'dark_subtle' => '#bfd4ff',
                        'nav_solid_bg' => '#1e3a8a',
                        'nav_gradient_start' => '#1d4ed8',
                        'nav_gradient_mid' => '#2563eb',
                        'nav_gradient_end' => '#3b82f6',
                        'nav_item_border' => '#bfdbfe',
                        'nav_item_hover_bg' => '#60a5fa',
                        'nav_dropdown_bg' => '#dbeafe',
                        'nav_dropdown_hover_bg' => '#60a5fa',
                    ]),
                    'dark' => $this->buildPalette([
                        'body_bg' => '#0e1422',
                        'body_text' => '#e6eeff',
                        'primary' => '#60a5fa',
                        'secondary' => '#3b82f6',
                        'success' => '#34d399',
                        'danger' => '#f87171',
                        'warning' => '#fbbf24',
                        'info' => '#38bdf8',
                        'light' => '#eff6ff',
                        'dark' => '#111a2e',
                        'secondary_subtle' => '#1a2438',
                        'dark_subtle' => '#22314b',
                        'nav_solid_bg' => '#111a2e',
                        'nav_gradient_start' => '#15213a',
                        'nav_gradient_mid' => '#1d4ed8',
                        'nav_gradient_end' => '#2563eb',
                        'nav_item_border' => '#33527f',
                        'nav_item_hover_bg' => '#2563eb',
                        'nav_dropdown_bg' => '#1a2438',
                        'nav_dropdown_hover_bg' => '#2563eb',
                    ]),
                ],
            ],
            [
                'key' => 'zelena',
                'name' => 'Zelena',
                'description' => [
                    'light' => 'Zelena svijetla varijanta.',
                    'dark' => 'Zelena tamna varijanta.',
                ],
                'colors' => [
                    'light' => $this->buildPalette([
                        'body_bg' => '#edf8f0',
                        'body_text' => '#13251a',
                        'primary' => '#16a34a',
                        'secondary' => '#15803d',
                        'success' => '#16a34a',
                        'danger' => '#dc2626',
                        'warning' => '#f59e0b',
                        'info' => '#0ea5e9',
                        'light' => '#f5fff8',
                        'dark' => '#14532d',
                        'secondary_subtle' => '#d7f0df',
                        'dark_subtle' => '#bde6cb',
                        'nav_solid_bg' => '#14532d',
                        'nav_gradient_start' => '#15803d',
                        'nav_gradient_mid' => '#16a34a',
                        'nav_gradient_end' => '#22c55e',
                        'nav_item_border' => '#bbf7d0',
                        'nav_item_hover_bg' => '#4ade80',
                        'nav_dropdown_bg' => '#dcfce7',
                        'nav_dropdown_hover_bg' => '#4ade80',
                    ]),
                    'dark' => $this->buildPalette([
                        'body_bg' => '#0f1912',
                        'body_text' => '#e8f6ec',
                        'primary' => '#4ade80',
                        'secondary' => '#22c55e',
                        'success' => '#4ade80',
                        'danger' => '#f87171',
                        'warning' => '#fbbf24',
                        'info' => '#38bdf8',
                        'light' => '#ecfdf3',
                        'dark' => '#102417',
                        'secondary_subtle' => '#1a2a1f',
                        'dark_subtle' => '#223729',
                        'nav_solid_bg' => '#112217',
                        'nav_gradient_start' => '#173422',
                        'nav_gradient_mid' => '#166534',
                        'nav_gradient_end' => '#15803d',
                        'nav_item_border' => '#31523f',
                        'nav_item_hover_bg' => '#166534',
                        'nav_dropdown_bg' => '#1a2a1f',
                        'nav_dropdown_hover_bg' => '#166534',
                    ]),
                ],
            ],
            [
                'key' => 'crvena',
                'name' => 'Crvena',
                'description' => [
                    'light' => 'Crvena svijetla varijanta.',
                    'dark' => 'Crvena tamna varijanta.',
                ],
                'colors' => [
                    'light' => $this->buildPalette([
                        'body_bg' => '#fdf0f0',
                        'body_text' => '#331919',
                        'primary' => '#dc2626',
                        'secondary' => '#b91c1c',
                        'success' => '#16a34a',
                        'danger' => '#dc2626',
                        'warning' => '#f59e0b',
                        'info' => '#0ea5e9',
                        'light' => '#fff6f6',
                        'dark' => '#7f1d1d',
                        'secondary_subtle' => '#f8d6d6',
                        'dark_subtle' => '#f1bcbc',
                        'nav_solid_bg' => '#7f1d1d',
                        'nav_gradient_start' => '#b91c1c',
                        'nav_gradient_mid' => '#dc2626',
                        'nav_gradient_end' => '#ef4444',
                        'nav_item_border' => '#fecaca',
                        'nav_item_hover_bg' => '#f87171',
                        'nav_dropdown_bg' => '#fee2e2',
                        'nav_dropdown_hover_bg' => '#f87171',
                    ]),
                    'dark' => $this->buildPalette([
                        'body_bg' => '#1b0f10',
                        'body_text' => '#f8e8e9',
                        'primary' => '#f87171',
                        'secondary' => '#ef4444',
                        'success' => '#34d399',
                        'danger' => '#f87171',
                        'warning' => '#fbbf24',
                        'info' => '#38bdf8',
                        'light' => '#fef2f2',
                        'dark' => '#2b1215',
                        'secondary_subtle' => '#2d1a1c',
                        'dark_subtle' => '#3b2326',
                        'nav_solid_bg' => '#231316',
                        'nav_gradient_start' => '#3b1518',
                        'nav_gradient_mid' => '#991b1b',
                        'nav_gradient_end' => '#b91c1c',
                        'nav_item_border' => '#6b2c2f',
                        'nav_item_hover_bg' => '#b91c1c',
                        'nav_dropdown_bg' => '#2d1a1c',
                        'nav_dropdown_hover_bg' => '#b91c1c',
                    ]),
                ],
            ],
            [
                'key' => 'ljubicasta',
                'name' => 'Ljubičasta',
                'description' => [
                    'light' => 'Ljubičasta svijetla varijanta.',
                    'dark' => 'Ljubičasta tamna varijanta.',
                ],
                'colors' => [
                    'light' => $this->buildPalette([
                        'body_bg' => '#f3eefc',
                        'body_text' => '#251a38',
                        'primary' => '#7c3aed',
                        'secondary' => '#6d28d9',
                        'success' => '#16a34a',
                        'danger' => '#dc2626',
                        'warning' => '#f59e0b',
                        'info' => '#0ea5e9',
                        'light' => '#faf5ff',
                        'dark' => '#4c1d95',
                        'secondary_subtle' => '#e6dafb',
                        'dark_subtle' => '#d5c0f7',
                        'nav_solid_bg' => '#4c1d95',
                        'nav_gradient_start' => '#6d28d9',
                        'nav_gradient_mid' => '#7c3aed',
                        'nav_gradient_end' => '#8b5cf6',
                        'nav_item_border' => '#ddd6fe',
                        'nav_item_hover_bg' => '#a78bfa',
                        'nav_dropdown_bg' => '#ede9fe',
                        'nav_dropdown_hover_bg' => '#a78bfa',
                    ]),
                    'dark' => $this->buildPalette([
                        'body_bg' => '#151022',
                        'body_text' => '#eee9fb',
                        'primary' => '#a78bfa',
                        'secondary' => '#8b5cf6',
                        'success' => '#34d399',
                        'danger' => '#f87171',
                        'warning' => '#fbbf24',
                        'info' => '#38bdf8',
                        'light' => '#f5f3ff',
                        'dark' => '#1f1633',
                        'secondary_subtle' => '#221b37',
                        'dark_subtle' => '#2f2549',
                        'nav_solid_bg' => '#1e1730',
                        'nav_gradient_start' => '#2a1f45',
                        'nav_gradient_mid' => '#5b21b6',
                        'nav_gradient_end' => '#6d28d9',
                        'nav_item_border' => '#4a3a73',
                        'nav_item_hover_bg' => '#6d28d9',
                        'nav_dropdown_bg' => '#221b37',
                        'nav_dropdown_hover_bg' => '#6d28d9',
                    ]),
                ],
            ],
            [
                'key' => 'zuta',
                'name' => 'Žuta',
                'description' => [
                    'light' => 'Žuta svijetla varijanta.',
                    'dark' => 'Žuta tamna varijanta.',
                ],
                'colors' => [
                    'light' => $this->buildPalette([
                        'body_bg' => '#fbf8e8',
                        'body_text' => '#2d290f',
                        'primary' => '#eab308',
                        'secondary' => '#ca8a04',
                        'success' => '#16a34a',
                        'danger' => '#dc2626',
                        'warning' => '#f59e0b',
                        'info' => '#0ea5e9',
                        'light' => '#fffdf2',
                        'dark' => '#713f12',
                        'secondary_subtle' => '#f4e9ba',
                        'dark_subtle' => '#eadb93',
                        'nav_solid_bg' => '#713f12',
                        'nav_gradient_start' => '#ca8a04',
                        'nav_gradient_mid' => '#eab308',
                        'nav_gradient_end' => '#facc15',
                        'nav_item_border' => '#fde68a',
                        'nav_item_hover_bg' => '#facc15',
                        'nav_dropdown_bg' => '#fef3c7',
                        'nav_dropdown_hover_bg' => '#facc15',
                    ]),
                    'dark' => $this->buildPalette([
                        'body_bg' => '#17150a',
                        'body_text' => '#f6f2de',
                        'primary' => '#facc15',
                        'secondary' => '#eab308',
                        'success' => '#34d399',
                        'danger' => '#f87171',
                        'warning' => '#facc15',
                        'info' => '#38bdf8',
                        'light' => '#fefce8',
                        'dark' => '#2b250f',
                        'secondary_subtle' => '#2a2615',
                        'dark_subtle' => '#39331c',
                        'nav_solid_bg' => '#221d0f',
                        'nav_gradient_start' => '#3a3319',
                        'nav_gradient_mid' => '#a16207',
                        'nav_gradient_end' => '#ca8a04',
                        'nav_item_border' => '#6a5a29',
                        'nav_item_hover_bg' => '#ca8a04',
                        'nav_dropdown_bg' => '#2a2615',
                        'nav_dropdown_hover_bg' => '#ca8a04',
                    ]),
                ],
            ],
        ];
    }

    private function buildPalette(array $base): array
    {
        $palette = [
            'body_bg' => $base['body_bg'],
            'body_text' => $base['body_text'],
            'primary' => $base['primary'],
            'secondary' => $base['secondary'],
            'success' => $base['success'],
            'danger' => $base['danger'],
            'warning' => $base['warning'],
            'info' => $base['info'],
            'light' => $base['light'],
            'dark' => $base['dark'],
            'secondary_subtle' => $base['secondary_subtle'],
            'dark_subtle' => $base['dark_subtle'],
            'nav_solid_bg' => $base['nav_solid_bg'],
            'nav_gradient_start' => $base['nav_gradient_start'],
            'nav_gradient_mid' => $base['nav_gradient_mid'],
            'nav_gradient_end' => $base['nav_gradient_end'],
            'nav_item_border' => $base['nav_item_border'],
            'nav_item_hover_bg' => $base['nav_item_hover_bg'],
            'nav_dropdown_bg' => $base['nav_dropdown_bg'],
            'nav_dropdown_hover_bg' => $base['nav_dropdown_hover_bg'],
        ];

        $palette['nav_item_text'] = $this->contrastColor($palette['nav_gradient_mid']);
        $palette['nav_item_hover_text'] = $this->contrastColor($palette['nav_item_hover_bg']);
        $palette['nav_dropdown_text'] = $this->contrastColor($palette['nav_dropdown_bg']);
        $palette['nav_dropdown_hover_text'] = $this->contrastColor($palette['nav_dropdown_hover_bg']);

        return $palette;
    }

    private function contrastColor(string $hexColor, string $dark = '#111111', string $light = '#ffffff'): string
    {
        $luminance = $this->hexLuminance($hexColor);

        return $luminance > 0.58 ? $dark : $light;
    }

    private function hexLuminance(string $hexColor): float
    {
        $hex = ltrim($hexColor, '#');
        if (strlen($hex) !== 6) {
            return 1.0;
        }

        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        return (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;
    }
};
