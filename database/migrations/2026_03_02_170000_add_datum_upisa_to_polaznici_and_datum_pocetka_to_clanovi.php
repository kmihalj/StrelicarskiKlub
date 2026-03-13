<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('polaznici_skole', function (Blueprint $table) {
            $table->date('datum_upisa')->nullable()->after('datum_rodjenja');
        });

        Schema::table('clanovis', function (Blueprint $table) {
            $table->date('datum_pocetka_clanstva')->nullable()->after('clan_od');
        });

        DB::statement("UPDATE polaznici_skole SET datum_upisa = STR_TO_DATE(CONCAT(clan_od, '-01-01'), '%Y-%m-%d') WHERE datum_upisa IS NULL AND clan_od IS NOT NULL");
    }

    public function down(): void
    {
        Schema::table('polaznici_skole', function (Blueprint $table) {
            $table->dropColumn('datum_upisa');
        });

        Schema::table('clanovis', function (Blueprint $table) {
            $table->dropColumn('datum_pocetka_clanstva');
        });
    }
};

