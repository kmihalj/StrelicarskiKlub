<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rezultati_tim_clanovi', function (Blueprint $table) {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_croatian_ci';
            $table->id();
            $table->unsignedBigInteger('rezultati_tim_id');
            $table->unsignedBigInteger('rezultat_opci_id');
            $table->integer('redni_broj')->nullable();
            $table->timestamps();

            $table->foreign('rezultati_tim_id')->references('id')->on('rezultati_timovi')->onDelete('cascade');
            $table->foreign('rezultat_opci_id')->references('id')->on('rezultati_opcis')->onDelete('cascade');
            $table->unique(['rezultati_tim_id', 'rezultat_opci_id'], 'rez_tim_clanovi_unique');
            $table->index(['rezultat_opci_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rezultati_tim_clanovi');
    }
};

