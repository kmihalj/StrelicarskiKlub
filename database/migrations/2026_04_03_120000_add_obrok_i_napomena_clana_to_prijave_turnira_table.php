<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('prijave_turnira')) {
            return;
        }

        Schema::table('prijave_turnira', function (Blueprint $table): void {
            if (! Schema::hasColumn('prijave_turnira', 'obrok')) {
                $table->string('obrok', 32)->nullable()->after('odabrani_dan');
            }

            if (! Schema::hasColumn('prijave_turnira', 'napomena_clana')) {
                $table->string('napomena_clana', 255)->nullable()->after('obrok');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('prijave_turnira')) {
            return;
        }

        Schema::table('prijave_turnira', function (Blueprint $table): void {
            if (Schema::hasColumn('prijave_turnira', 'napomena_clana')) {
                $table->dropColumn('napomena_clana');
            }

            if (Schema::hasColumn('prijave_turnira', 'obrok')) {
                $table->dropColumn('obrok');
            }
        });
    }
};

