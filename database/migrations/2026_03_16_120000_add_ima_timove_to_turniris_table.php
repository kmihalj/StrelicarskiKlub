<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('turniris', 'ima_timove')) {
            Schema::table('turniris', function (Blueprint $table) {
                $table->boolean('ima_timove')->default(false)->after('eliminacije');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('turniris', 'ima_timove')) {
            Schema::table('turniris', function (Blueprint $table) {
                $table->dropColumn('ima_timove');
            });
        }
    }
};
