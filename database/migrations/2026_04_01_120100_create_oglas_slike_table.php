<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('oglas_slike')) {
            return;
        }

        Schema::create('oglas_slike', function (Blueprint $table): void {
            $table->id();
            $table->timestamps();
            $table->unsignedBigInteger('oglas_id');
            $table->string('putanja', 255);
            $table->unsignedTinyInteger('redni_broj')->default(0);

            $table->foreign('oglas_id', 'oglas_slike_oglas_fk')->references('id')->on('oglasi')->cascadeOnDelete();
            $table->index(['oglas_id', 'redni_broj'], 'oglas_slike_oglas_redni_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('oglas_slike');
    }
};
