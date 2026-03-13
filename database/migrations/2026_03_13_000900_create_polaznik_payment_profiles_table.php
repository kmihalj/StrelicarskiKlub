<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('polaznik_payment_profiles')) {
            return;
        }

        Schema::create('polaznik_payment_profiles', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('polaznik_skole_id');
            $table->string('payment_mode', 20)->default('full');
            $table->decimal('tuition_amount', 10, 2)->default(0);
            $table->date('started_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->unique('polaznik_skole_id', 'polaznik_payment_profiles_polaznik_unique');
            $table->foreign('polaznik_skole_id', 'polaznik_payment_profiles_polaznik_fk')
                ->references('id')
                ->on('polaznici_skole')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('polaznik_payment_profiles');
    }
};

