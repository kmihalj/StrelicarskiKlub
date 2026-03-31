<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('nadolazeci_turniri')) {
            return;
        }

        Schema::table('nadolazeci_turniri', function (Blueprint $table): void {
            if (! Schema::hasColumn('nadolazeci_turniri', 'datum_do')) {
                $table->date('datum_do')->nullable()->after('datum');
            }

            if (! Schema::hasColumn('nadolazeci_turniri', 'napomena')) {
                $table->string('napomena', 500)->nullable()->after('mjesto');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('nadolazeci_turniri')) {
            return;
        }

        Schema::table('nadolazeci_turniri', function (Blueprint $table): void {
            if (Schema::hasColumn('nadolazeci_turniri', 'datum_do')) {
                $table->dropColumn('datum_do');
            }

            if (Schema::hasColumn('nadolazeci_turniri', 'napomena')) {
                $table->dropColumn('napomena');
            }
        });
    }
};

