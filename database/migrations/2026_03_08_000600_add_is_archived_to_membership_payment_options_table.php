<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('membership_payment_options')) {
            return;
        }

        Schema::table('membership_payment_options', function (Blueprint $table): void {
            if (!Schema::hasColumn('membership_payment_options', 'is_archived')) {
                $table->boolean('is_archived')->default(false)->after('is_enabled');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('membership_payment_options')) {
            return;
        }

        Schema::table('membership_payment_options', function (Blueprint $table): void {
            if (Schema::hasColumn('membership_payment_options', 'is_archived')) {
                $table->dropColumn('is_archived');
            }
        });
    }
};
