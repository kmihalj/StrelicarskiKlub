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
        Schema::create('clan_lijecnicki_pregledi', function (Blueprint $table) {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_croatian_ci';
            $table->id();

            $table->unsignedBigInteger('clan_id');
            $table->foreign('clan_id')->references('id')->on('clanovis')->onDelete('cascade');

            $table->date('vrijedi_do');
            $table->string('putanja')->nullable();
            $table->string('originalni_naziv')->nullable();
            $table->text('napomena')->nullable();
            $table->boolean('legacy_import')->default(false);

            $table->unsignedBigInteger('created_by')->nullable();
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');

            $table->timestamps();
            $table->index(['clan_id', 'vrijedi_do']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clan_lijecnicki_pregledi');
    }
};
