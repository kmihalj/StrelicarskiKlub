<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('prijave_turnira')) {
            return;
        }

        Schema::table('prijave_turnira', function (Blueprint $table): void {
            if (! Schema::hasColumn('prijave_turnira', 'odabrani_dan')) {
                $table->date('odabrani_dan')->nullable()->after('smjena');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('prijave_turnira')) {
            return;
        }

        Schema::table('prijave_turnira', function (Blueprint $table): void {
            if (Schema::hasColumn('prijave_turnira', 'odabrani_dan')) {
                $table->dropColumn('odabrani_dan');
            }
        });
    }
};
