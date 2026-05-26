<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('clanovi_funkcijes')) {
            Schema::table('clanovi_funkcijes', function (Blueprint $table): void {
                if (! Schema::hasColumn('clanovi_funkcijes', 'kotizacija_primatelj')) {
                    $table->string('kotizacija_primatelj')->nullable()->after('redniBroj');
                }

                if (! Schema::hasColumn('clanovi_funkcijes', 'kotizacija_iban')) {
                    $table->string('kotizacija_iban', 34)->nullable()->after('kotizacija_primatelj');
                }
            });
        }

        if (Schema::hasTable('nadolazeci_turniri')) {
            Schema::table('nadolazeci_turniri', function (Blueprint $table): void {
                if (! Schema::hasColumn('nadolazeci_turniri', 'kotizacija_primatelj_funkcija_id')) {
                    $table->unsignedBigInteger('kotizacija_primatelj_funkcija_id')
                        ->nullable()
                        ->after('kotizacija_rok_uplate');

                    $table->foreign('kotizacija_primatelj_funkcija_id', 'nt_kotizacija_primatelj_fk')
                        ->references('id')
                        ->on('clanovi_funkcijes')
                        ->nullOnDelete();
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('nadolazeci_turniri') && Schema::hasColumn('nadolazeci_turniri', 'kotizacija_primatelj_funkcija_id')) {
            Schema::table('nadolazeci_turniri', function (Blueprint $table): void {
                $table->dropForeign('nt_kotizacija_primatelj_fk');
                $table->dropColumn('kotizacija_primatelj_funkcija_id');
            });
        }

        if (Schema::hasTable('clanovi_funkcijes')) {
            Schema::table('clanovi_funkcijes', function (Blueprint $table): void {
                if (Schema::hasColumn('clanovi_funkcijes', 'kotizacija_iban')) {
                    $table->dropColumn('kotizacija_iban');
                }

                if (Schema::hasColumn('clanovi_funkcijes', 'kotizacija_primatelj')) {
                    $table->dropColumn('kotizacija_primatelj');
                }
            });
        }
    }
};
