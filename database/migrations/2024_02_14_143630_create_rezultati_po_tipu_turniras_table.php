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
        Schema::create('rezultati_po_tipu_turniras', function (Blueprint $table) {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_croatian_ci';
            $table->id();
            // turnir
            $table->unsignedBigInteger('turnir_id');
            $table->foreign('turnir_id')->references('id')->on('turniris')->onDelete('restrict');
            // clan
            $table->unsignedBigInteger('clan_id');
            $table->foreign('clan_id')->references('id')->on('clanovis')->onDelete('restrict');
            // kategorija
            $table->unsignedBigInteger('kategorija_id');
            $table->foreign('kategorija_id')->references('id')->on('kategorijes')->onDelete('restrict');
            // stil
            $table->unsignedBigInteger('stil_id');
            $table->foreign('stil_id')->references('id')->on('stilovis')->onDelete('restrict');
            // polje_za_tip_turnira
            $table->unsignedBigInteger('polje_za_tipove_turnira_id');
            $table->foreign('polje_za_tipove_turnira_id')->references('id')->on('polja_za_tipove_turniras')->onDelete('restrict');
            // rezultat
            $table->integer('rezultat')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rezultati_po_tipu_turniras');
    }
};
