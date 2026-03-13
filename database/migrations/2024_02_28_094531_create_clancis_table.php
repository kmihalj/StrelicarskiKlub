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
        Schema::create('clancis', function (Blueprint $table) {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_croatian_ci';
            $table->id();
            $table->timestamps();
            $table->enum('vrsta', ['Članak', 'Obavijest', 'O nama', 'O streličarstvu']);
            $table->text('naslov');
            $table->date('datum');
            $table->longText('sadrzaj')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clancis');
    }
};
