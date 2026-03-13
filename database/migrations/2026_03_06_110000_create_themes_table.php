<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('themes', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->string('description')->nullable();
            $table->boolean('is_active')->default(false)->index();
            $table->json('colors');
            $table->string('logo_path')->nullable();
            $table->string('favicon_path')->nullable();
            $table->timestamps();
        });

        $defaultColors = [
            'body_bg' => '#e9ecef',
            'body_text' => '#212529',
            'primary' => '#0d6efd',
            'secondary' => '#6c757d',
            'success' => '#198754',
            'danger' => '#dc3545',
            'warning' => '#ffc107',
            'info' => '#0dcaf0',
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

        $darkColors = [
            'body_bg' => '#111317',
            'body_text' => '#e9ecef',
            'primary' => '#4f8dff',
            'secondary' => '#8f98a3',
            'success' => '#31b56e',
            'danger' => '#ff6b6b',
            'warning' => '#ffd166',
            'info' => '#4cc9f0',
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

        DB::table('themes')->insert([
            [
                'name' => 'SKDubrava Tema',
                'slug' => 'skdubrava-tema',
                'description' => 'Zadana tema koja odgovara postojećem izgledu.',
                'is_active' => true,
                'colors' => json_encode($defaultColors, JSON_UNESCAPED_UNICODE),
                'logo_path' => 'slike/logo.png',
                'favicon_path' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'SKDubrava Tamna',
                'slug' => 'skdubrava-tamna',
                'description' => 'Tamna varijanta zadane teme.',
                'is_active' => false,
                'colors' => json_encode($darkColors, JSON_UNESCAPED_UNICODE),
                'logo_path' => 'slike/logo.png',
                'favicon_path' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('themes');
    }
};
