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
            return;
        }

        Schema::table('site_settings', function (Blueprint $table): void {
            if (!Schema::hasColumn('site_settings', 'payment_tracking_enabled')) {
                $table->boolean('payment_tracking_enabled')->default(false)->after('theme_mode_policy');
            }
            if (!Schema::hasColumn('site_settings', 'payment_info_clanak_id')) {
                $table->unsignedBigInteger('payment_info_clanak_id')->nullable()->after('payment_tracking_enabled');
            }
        });

        if (!DB::table('site_settings')->exists()) {
            DB::table('site_settings')->insert([
                'theme_mode_policy' => 'auto',
                'payment_tracking_enabled' => false,
                'payment_info_clanak_id' => 33,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return;
        }

        DB::table('site_settings')
            ->whereNull('payment_info_clanak_id')
            ->update([
                'payment_info_clanak_id' => 33,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        if (!Schema::hasTable('site_settings')) {
            return;
        }

        Schema::table('site_settings', function (Blueprint $table): void {
            if (Schema::hasColumn('site_settings', 'payment_info_clanak_id')) {
                $table->dropColumn('payment_info_clanak_id');
            }
            if (Schema::hasColumn('site_settings', 'payment_tracking_enabled')) {
                $table->dropColumn('payment_tracking_enabled');
            }
        });
    }
};
