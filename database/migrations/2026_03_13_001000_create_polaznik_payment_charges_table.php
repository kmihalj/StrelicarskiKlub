<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('polaznik_payment_charges')) {
            return;
        }

        Schema::create('polaznik_payment_charges', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('polaznik_skole_id');
            $table->unsignedBigInteger('polaznik_payment_profile_id')->nullable();
            $table->string('source', 60)->default('tuition');
            $table->string('title');
            $table->text('description')->nullable();
            $table->decimal('amount', 10, 2)->default(0);
            $table->unsignedSmallInteger('due_training_count')->nullable();
            $table->string('status', 20)->default('open');
            $table->date('paid_at')->nullable();
            $table->json('metadata')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->index(['polaznik_skole_id', 'status'], 'polaznik_payment_charges_polaznik_status_idx');
            $table->index('source', 'polaznik_payment_charges_source_idx');

            $table->foreign('polaznik_skole_id', 'polaznik_payment_charges_polaznik_fk')
                ->references('id')
                ->on('polaznici_skole')
                ->cascadeOnDelete();
            $table->foreign('polaznik_payment_profile_id', 'polaznik_payment_charges_profile_fk')
                ->references('id')
                ->on('polaznik_payment_profiles')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('polaznik_payment_charges');
    }
};

