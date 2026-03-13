<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('treninzi_dvorana', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('clan_id')->constrained('clanovis')->cascadeOnDelete();
            $table->date('datum');
            $table->json('runda1')->nullable();
            $table->json('runda2')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'datum']);
            $table->index(['clan_id', 'datum']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('treninzi_dvorana');
    }
};
