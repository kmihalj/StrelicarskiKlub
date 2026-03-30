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
        Schema::table('rezultati_opcis', function (Blueprint $table) {
            $table->boolean('bez_eliminacija')->default(false)->after('plasman_nakon_eliminacija');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rezultati_opcis', function (Blueprint $table) {
            $table->dropColumn('bez_eliminacija');
        });
    }
};
