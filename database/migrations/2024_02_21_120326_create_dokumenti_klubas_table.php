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
        Schema::create('dokumenti_klubas', function (Blueprint $table) {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_croatian_ci';
            $table->id();
            //klub
            $table->unsignedBigInteger('klub_id');
            $table->foreign('klub_id')->references('id')->on('klubs')->onDelete('restrict');

            $table->text('opis');
            $table->text('link_text');
            $table->boolean('javno')->default(false);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dokumenti_klubas');
    }
};
