<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('polaznik_id')->nullable()->after('clan_id');
            $table->unique('polaznik_id');
            $table->foreign('polaznik_id')->references('id')->on('polaznici_skole')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['polaznik_id']);
            $table->dropUnique(['polaznik_id']);
            $table->dropColumn('polaznik_id');
        });
    }
};

