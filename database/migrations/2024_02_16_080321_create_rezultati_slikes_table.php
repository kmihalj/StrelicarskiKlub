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
        Schema::create('rezultati_slikes', function (Blueprint $table) {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_croatian_ci';
            $table->id();

            // turnir
            $table->unsignedBigInteger('turnir_id');
            $table->foreign('turnir_id')->references('id')->on('turniris')->onDelete('restrict');

            $table->enum('vrsta', array(['slika', 'video']));
            $table->string('link');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rezultati_slikes');
    }
};
