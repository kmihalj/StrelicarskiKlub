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
        Schema::create('clanovis', function (Blueprint $table) {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_croatian_ci';
            $table->id();
            $table->string('Ime');
            $table->string('Prezime');
            $table->string('slika_link')->nullable();
            $table->date('datum_rodjenja');
            $table->string('br_telefona')->nullable();
            $table->string('email')->nullable();
            $table->integer('clan_od')->nullable();
            $table->boolean('aktivan')->default(true);
            $table->enum('spol', array(['M', 'Ž']));
            $table->char('oib', 11)->unique();
            $table->string('broj_licence')->nullable();
            $table->date('lijecnicki_do')->nullable();
            $table->string('lijecnicki_dokument')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clanovis');
    }
};
