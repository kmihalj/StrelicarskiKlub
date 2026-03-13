<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('clan_payment_profiles')) {
            return;
        }

        Schema::create('clan_payment_profiles', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('clan_id');
            $table->unsignedBigInteger('membership_payment_option_id')->nullable();
            $table->date('start_date')->nullable();
            $table->decimal('opening_debt_amount', 10, 2)->default(0);
            $table->text('opening_debt_note')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->foreign('clan_id', 'cpp_clan_fk')->references('id')->on('clanovis')->cascadeOnDelete();
            $table->foreign('membership_payment_option_id', 'cpp_option_fk')->references('id')->on('membership_payment_options')->nullOnDelete();
            $table->foreign('created_by', 'cpp_created_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by', 'cpp_updated_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->unique('clan_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clan_payment_profiles');
    }
};
