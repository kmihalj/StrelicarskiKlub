<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('polaznici_skole', function (Blueprint $table) {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_croatian_ci';
            $table->id();
            $table->string('Ime');
            $table->string('Prezime');
            $table->date('datum_rodjenja')->nullable();
            $table->char('oib', 11)->unique();
            $table->string('br_telefona')->nullable();
            $table->string('email')->nullable();
            $table->enum('spol', ['M', 'Ž'])->nullable();
            $table->integer('clan_od')->nullable();
            $table->boolean('u_skoli')->default(true);
            $table->unsignedBigInteger('prebacen_u_clana_id')->nullable();
            $table->timestamp('prebacen_at')->nullable();
            $table->timestamps();

            $table->foreign('prebacen_u_clana_id')->references('id')->on('clanovis')->nullOnDelete();
            $table->index(['u_skoli', 'Prezime', 'Ime']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('polaznici_skole');
    }
};

