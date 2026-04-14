<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('clancis')) {
            return;
        }

        if (! Schema::hasColumn('clancis', 'prikazi_na_naslovnici')) {
            Schema::table('clancis', function (Blueprint $table): void {
                $table->boolean('prikazi_na_naslovnici')->default(true)->after('galerija');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('clancis')) {
            return;
        }

        if (Schema::hasColumn('clancis', 'prikazi_na_naslovnici')) {
            Schema::table('clancis', function (Blueprint $table): void {
                $table->dropColumn('prikazi_na_naslovnici');
            });
        }
    }
};
