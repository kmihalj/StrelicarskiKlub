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
        Schema::create('polja_za_tipove_turniras', function (Blueprint $table) {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_croatian_ci';
            $table->id();
            $table->string('naziv');
            $table->unsignedBigInteger('tipovi_turnira_id');
            $table->timestamps();

            $table->foreign('tipovi_turnira_id')->references('id')->on('tipovi_turniras')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('polja_za_tipove_turniras');
    }
};
