<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('polaznici_skole_dolasci', function (Blueprint $table): void {
            $table->unique(['polaznik_skole_id', 'datum'], 'polaznik_skole_dolazak_po_danu');
        });

        Schema::table('polaznici_skole', function (Blueprint $table): void {
            $table->char('qr_dolazak_token', 32)->nullable()->unique();
        });
    }

    public function down(): void
    {
        Schema::table('polaznici_skole', function (Blueprint $table): void {
            $table->dropUnique(['qr_dolazak_token']);
            $table->dropColumn('qr_dolazak_token');
        });

        Schema::table('polaznici_skole_dolasci', function (Blueprint $table): void {
            $table->dropUnique('polaznik_skole_dolazak_po_danu');
        });
    }
};
