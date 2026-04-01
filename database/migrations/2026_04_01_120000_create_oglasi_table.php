<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('oglasi')) {
            return;
        }

        Schema::create('oglasi', function (Blueprint $table): void {
            $table->id();
            $table->timestamps();
            $table->unsignedBigInteger('clan_id');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->string('naslov', 191);
            $table->text('opis');
            $table->decimal('cijena', 10, 2);
            $table->string('kontakt_telefon', 64);
            $table->string('kontakt_email', 191);
            $table->boolean('is_active')->default(true);
            $table->timestamp('deactivated_at')->nullable();

            $table->foreign('clan_id', 'oglasi_clan_fk')->references('id')->on('clanovis')->cascadeOnDelete();
            $table->foreign('created_by', 'oglasi_created_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by', 'oglasi_updated_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->index(['is_active', 'created_at'], 'oglasi_active_created_idx');
            $table->index(['clan_id', 'is_active'], 'oglasi_clan_active_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('oglasi');
    }
};
