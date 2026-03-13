<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('clancis')) {
            return;
        }

        if (!Schema::hasColumn('clancis', 'menu')) {
            Schema::table('clancis', function (Blueprint $table): void {
                $table->boolean('menu')->default(false)->after('datum');
            });
        }

        if (!Schema::hasColumn('clancis', 'menu_naslov')) {
            Schema::table('clancis', function (Blueprint $table): void {
                $table->text('menu_naslov')->nullable()->after('menu');
            });
        }

        if (DB::getDriverName() === 'mysql' && Schema::hasColumn('clancis', 'vrsta')) {
            DB::statement("UPDATE `clancis` SET `vrsta` = 'Streličarstvo' WHERE `vrsta` = 'O streličarstvu'");
            DB::statement(
                "ALTER TABLE `clancis` MODIFY `vrsta` ENUM('Članak','Obavijest','O nama','O streličarstvu','Streličarstvo','Naslovnica') NOT NULL"
            );
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('clancis')) {
            return;
        }

        if (DB::getDriverName() === 'mysql' && Schema::hasColumn('clancis', 'vrsta')) {
            DB::statement("UPDATE `clancis` SET `vrsta` = 'O streličarstvu' WHERE `vrsta` IN ('Streličarstvo','Naslovnica')");
            DB::statement(
                "ALTER TABLE `clancis` MODIFY `vrsta` ENUM('Članak','Obavijest','O nama','O streličarstvu') NOT NULL"
            );
        }

        if (Schema::hasColumn('clancis', 'menu_naslov')) {
            Schema::table('clancis', function (Blueprint $table): void {
                $table->dropColumn('menu_naslov');
            });
        }

        if (Schema::hasColumn('clancis', 'menu')) {
            Schema::table('clancis', function (Blueprint $table): void {
                $table->dropColumn('menu');
            });
        }
    }
};

