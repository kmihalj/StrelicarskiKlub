<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('membership_payment_options')) {
            return;
        }

        Schema::table('membership_payment_options', function (Blueprint $table): void {
            if (!Schema::hasColumn('membership_payment_options', 'collection_method')) {
                $table->string('collection_method', 16)->default('bank')->after('period_anchor');
            }
        });

        DB::table('membership_payment_options')
            ->whereNull('collection_method')
            ->update(['collection_method' => 'bank']);
    }

    public function down(): void
    {
        if (!Schema::hasTable('membership_payment_options')) {
            return;
        }

        Schema::table('membership_payment_options', function (Blueprint $table): void {
            if (Schema::hasColumn('membership_payment_options', 'collection_method')) {
                $table->dropColumn('collection_method');
            }
        });
    }
};
