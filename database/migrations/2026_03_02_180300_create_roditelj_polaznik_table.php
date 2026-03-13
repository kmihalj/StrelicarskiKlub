<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roditelj_polaznik', function (Blueprint $table) {
            $table->id();
            $table->foreignId('roditelj_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('polaznik_id')->constrained('polaznici_skole')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['roditelj_user_id', 'polaznik_id']);
            $table->index('polaznik_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roditelj_polaznik');
    }
};
