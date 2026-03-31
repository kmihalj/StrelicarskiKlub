<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('nadolazeci_turniri')) {
            return;
        }

        Schema::create('nadolazeci_turniri', function (Blueprint $table): void {
            $table->id();
            $table->timestamps();
            $table->string('naziv');
            $table->string('organizator')->nullable();
            $table->string('mjesto');
            $table->date('datum');
            $table->unsignedBigInteger('tipovi_turnira_id');
            $table->boolean('boduje_za_kup')->default(false);
            $table->boolean('ima_smjene')->default(false);
            $table->string('smjene_opis')->nullable();
            $table->date('prijave_otvorene_do')->nullable();
            $table->boolean('is_zakljucan')->default(false);
            $table->string('poziv_pdf_path')->nullable();
            $table->string('kotizacija_nacin', 16)->nullable();
            $table->decimal('kotizacija_iznos', 10, 2)->nullable();
            $table->date('kotizacija_rok_uplate')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->foreign('tipovi_turnira_id', 'nt_tip_turnira_fk')->references('id')->on('tipovi_turniras')->onDelete('restrict');
            $table->foreign('created_by', 'nt_created_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by', 'nt_updated_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->index('datum', 'nt_datum_idx');
            $table->index('prijave_otvorene_do', 'nt_prijave_otvorene_do_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nadolazeci_turniri');
    }
};
