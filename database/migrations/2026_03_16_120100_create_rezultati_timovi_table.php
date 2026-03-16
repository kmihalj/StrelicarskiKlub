<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rezultati_timovi', function (Blueprint $table) {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_croatian_ci';
            $table->id();
            $table->unsignedBigInteger('turnir_id');
            $table->unsignedBigInteger('stil_id')->nullable();
            $table->unsignedBigInteger('kategorija_id')->nullable();
            $table->string('naziv', 120);
            $table->integer('plasman');
            $table->integer('rezultat')->default(0);
            $table->timestamps();

            $table->foreign('turnir_id')->references('id')->on('turniris')->onDelete('cascade');
            $table->foreign('stil_id')->references('id')->on('stilovis')->nullOnDelete();
            $table->foreign('kategorija_id')->references('id')->on('kategorijes')->nullOnDelete();
            $table->index(['turnir_id', 'plasman']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rezultati_timovi');
    }
};

