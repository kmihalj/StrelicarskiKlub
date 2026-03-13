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
        Schema::table('users', function (Blueprint $table) {
            $table->char('oib', 11)->nullable()->after('email');
            $table->string('br_telefona')->nullable()->after('oib');
            $table->unsignedBigInteger('clan_id')->nullable()->after('rola');

            $table->unique('oib');
            $table->unique('br_telefona');
            $table->unique('clan_id');
            $table->foreign('clan_id')->references('id')->on('clanovis')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['clan_id']);
            $table->dropUnique(['oib']);
            $table->dropUnique(['br_telefona']);
            $table->dropUnique(['clan_id']);

            $table->dropColumn(['oib', 'br_telefona', 'clan_id']);
        });
    }
};
