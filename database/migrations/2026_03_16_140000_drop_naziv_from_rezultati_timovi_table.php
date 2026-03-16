<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('rezultati_timovi', 'naziv')) {
            Schema::table('rezultati_timovi', function (Blueprint $table) {
                $table->dropColumn('naziv');
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasColumn('rezultati_timovi', 'naziv')) {
            Schema::table('rezultati_timovi', function (Blueprint $table) {
                $table->string('naziv', 120)->default('Tim');
            });
        }
    }
};

