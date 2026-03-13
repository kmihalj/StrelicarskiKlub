<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('site_settings')) {
            Schema::create('site_settings', function (Blueprint $table): void {
                $table->id();
                $table->string('theme_mode_policy', 10)->default('auto');
                $table->timestamps();
            });
        }

        if (!Schema::hasColumn('site_settings', 'theme_mode_policy')) {
            Schema::table('site_settings', function (Blueprint $table): void {
                $table->string('theme_mode_policy', 10)->default('auto')->after('id');
            });
        }

        if (!DB::table('site_settings')->exists()) {
            DB::table('site_settings')->insert([
                'theme_mode_policy' => 'auto',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('site_settings');
    }
};
