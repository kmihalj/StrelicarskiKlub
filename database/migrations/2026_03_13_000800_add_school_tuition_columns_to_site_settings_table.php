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
            if (!Schema::hasColumn('site_settings', 'school_tuition_adult_amount')) {
                $table->decimal('school_tuition_adult_amount', 10, 2)->default(100.00)->after('payment_info_clanak_id');
            }
            if (!Schema::hasColumn('site_settings', 'school_tuition_minor_amount')) {
                $table->decimal('school_tuition_minor_amount', 10, 2)->default(70.00)->after('school_tuition_adult_amount');
            }
        });

        DB::table('site_settings')
            ->whereNull('school_tuition_adult_amount')
            ->update([
                'school_tuition_adult_amount' => 100.00,
                'updated_at' => now(),
            ]);

        DB::table('site_settings')
            ->whereNull('school_tuition_minor_amount')
            ->update([
                'school_tuition_minor_amount' => 70.00,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        if (!Schema::hasTable('site_settings')) {
            return;
        }

        Schema::table('site_settings', function (Blueprint $table): void {
            if (Schema::hasColumn('site_settings', 'school_tuition_minor_amount')) {
                $table->dropColumn('school_tuition_minor_amount');
            }
            if (Schema::hasColumn('site_settings', 'school_tuition_adult_amount')) {
                $table->dropColumn('school_tuition_adult_amount');
            }
        });
    }
};

