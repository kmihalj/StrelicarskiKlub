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
        Schema::create('clanovi_funkcijes', function (Blueprint $table) {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_croatian_ci';
            $table->id();
            //klub
            $table->unsignedBigInteger('klub_id');
            $table->foreign('klub_id')->references('id')->on('klubs')->onDelete('restrict');
            // clan
            $table->unsignedBigInteger('clan_id');
            $table->foreign('clan_id')->references('id')->on('clanovis')->onDelete('restrict');

            $table->enum('funkcija', array(['Predsjednik kluba', 'Upravni odbor', 'Nadzorni odbor', 'Arbitražno vijeće', 'Tajnik', 'Likvidator', 'Trener']));
            $table->unsignedSmallInteger('redniBroj')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clanovi_funkcijes');
    }
};
