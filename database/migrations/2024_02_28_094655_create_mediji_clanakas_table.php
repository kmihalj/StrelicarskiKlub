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
        Schema::create('mediji_clanakas', function (Blueprint $table) {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_croatian_ci';
            $table->id();

            // clanak
            $table->unsignedBigInteger('clanak_id');
            $table->foreign('clanak_id')->references('id')->on('clancis')->onDelete('restrict');

            $table->enum('vrsta', array(['slika', 'video', 'dokument']));
            $table->string('link');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mediji_clanakas');
    }
};
