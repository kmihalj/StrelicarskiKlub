<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('clan_payment_profiles')) {
            return;
        }

        Schema::table('clan_payment_profiles', function (Blueprint $table): void {
            if (!Schema::hasColumn('clan_payment_profiles', 'membership_amount_override')) {
                $table->decimal('membership_amount_override', 10, 2)->nullable()->after('start_date');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('clan_payment_profiles')) {
            return;
        }

        Schema::table('clan_payment_profiles', function (Blueprint $table): void {
            if (Schema::hasColumn('clan_payment_profiles', 'membership_amount_override')) {
                $table->dropColumn('membership_amount_override');
            }
        });
    }
};

