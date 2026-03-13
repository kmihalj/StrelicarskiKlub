<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('polaznici_skole_dolasci', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('polaznik_skole_id');
            $table->unsignedTinyInteger('redni_broj');
            $table->date('datum')->nullable();
            $table->timestamps();

            $table->foreign('polaznik_skole_id')->references('id')->on('polaznici_skole')->cascadeOnDelete();
            $table->unique(['polaznik_skole_id', 'redni_broj']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('polaznici_skole_dolasci');
    }
};

