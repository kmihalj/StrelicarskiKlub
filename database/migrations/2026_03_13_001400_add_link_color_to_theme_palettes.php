<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('themes') || !Schema::hasColumn('themes', 'colors')) {
            return;
        }

        $themes = DB::table('themes')
            ->select(['id', 'variant', 'colors'])
            ->orderBy('id')
            ->get();

        foreach ($themes as $theme) {
            $colors = json_decode((string)$theme->colors, true);
            if (!is_array($colors)) {
                $colors = [];
            }

            $defaultLink = strtolower(((string)$theme->variant === 'dark') ? '#8fc3ff' : '#0d6efd');
            $linkColor = is_string($colors['link'] ?? null) ? strtolower(trim((string)$colors['link'])) : '';
            if (!preg_match('/^#[0-9a-f]{6}$/', $linkColor)) {
                $linkColor = $defaultLink;
            }

            if (($colors['link'] ?? null) === $linkColor) {
                continue;
            }

            $colors['link'] = $linkColor;

            DB::table('themes')
                ->where('id', $theme->id)
                ->update([
                    'colors' => json_encode($colors, JSON_UNESCAPED_UNICODE),
                    'updated_at' => now(),
                ]);
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('themes') || !Schema::hasColumn('themes', 'colors')) {
            return;
        }

        $themes = DB::table('themes')
            ->select(['id', 'colors'])
            ->orderBy('id')
            ->get();

        foreach ($themes as $theme) {
            $colors = json_decode((string)$theme->colors, true);
            if (!is_array($colors) || !array_key_exists('link', $colors)) {
                continue;
            }

            unset($colors['link']);

            DB::table('themes')
                ->where('id', $theme->id)
                ->update([
                    'colors' => json_encode($colors, JSON_UNESCAPED_UNICODE),
                    'updated_at' => now(),
                ]);
        }
    }
};

