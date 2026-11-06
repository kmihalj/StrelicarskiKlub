<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nadolazeci_turniri', function (Blueprint $table): void {
            $table->json('kotizacija_razredi')->nullable();
        });
        Schema::table('prijave_turnira', function (Blueprint $table): void {
            $table->unique(['nadolazeci_turnir_id', 'clan_id', 'stil_id'], 'pt_turnir_clan_stil_unique');
            $table->dropUnique('pt_turnir_clan_unique');
        });
    }

    public function down(): void
    {
        // Ograničenje sprečava gubitak prijava ako je član prijavljen u više stilova.
        Schema::table('prijave_turnira', function (Blueprint $table): void {
            $table->unique(['nadolazeci_turnir_id', 'clan_id'], 'pt_turnir_clan_unique');
            $table->dropUnique('pt_turnir_clan_stil_unique');
        });
        Schema::table('nadolazeci_turniri', function (Blueprint $table): void {
            $table->dropColumn('kotizacija_razredi');
        });
    }
};
