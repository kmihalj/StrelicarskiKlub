<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('prijave_turnira')) {
            return;
        }

        Schema::create('prijave_turnira', function (Blueprint $table): void {
            $table->id();
            $table->timestamps();
            $table->unsignedBigInteger('nadolazeci_turnir_id');
            $table->unsignedBigInteger('clan_id');
            $table->unsignedBigInteger('prijavio_user_id')->nullable();
            $table->unsignedBigInteger('kategorija_id');
            $table->unsignedBigInteger('stil_id');
            $table->boolean('sudjelujem_u_kupu')->default(false);
            $table->string('smjena')->nullable();
            $table->string('status', 24)->default('active');
            $table->text('napomena_admin')->nullable();
            $table->unsignedBigInteger('removed_by')->nullable();
            $table->timestamp('removed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->unsignedBigInteger('clan_payment_charge_id')->nullable();

            $table->foreign('nadolazeci_turnir_id', 'pt_turnir_fk')->references('id')->on('nadolazeci_turniri')->cascadeOnDelete();
            $table->foreign('clan_id', 'pt_clan_fk')->references('id')->on('clanovis')->cascadeOnDelete();
            $table->foreign('prijavio_user_id', 'pt_prijavio_user_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('kategorija_id', 'pt_kategorija_fk')->references('id')->on('kategorijes')->onDelete('restrict');
            $table->foreign('stil_id', 'pt_stil_fk')->references('id')->on('stilovis')->onDelete('restrict');
            $table->foreign('removed_by', 'pt_removed_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('clan_payment_charge_id', 'pt_charge_fk')->references('id')->on('clan_payment_charges')->nullOnDelete();
            $table->unique(['nadolazeci_turnir_id', 'clan_id'], 'pt_turnir_clan_unique');
            $table->index(['clan_id', 'status'], 'pt_clan_status_idx');
            $table->index(['nadolazeci_turnir_id', 'status'], 'pt_turnir_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prijave_turnira');
    }
};
