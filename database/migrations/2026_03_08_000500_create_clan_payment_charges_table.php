<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('clan_payment_charges')) {
            return;
        }

        Schema::create('clan_payment_charges', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('clan_id');
            $table->unsignedBigInteger('clan_payment_profile_id')->nullable();
            $table->unsignedBigInteger('membership_payment_option_id')->nullable();

            $table->string('source', 32)->default('manual');
            $table->string('period_key', 64)->nullable();
            $table->date('period_start')->nullable();
            $table->date('period_end')->nullable();
            $table->date('due_date')->nullable();

            $table->string('title', 191);
            $table->text('description')->nullable();
            $table->decimal('amount', 10, 2);
            $table->char('currency', 3)->default('EUR');
            $table->string('status', 24)->default('open');
            $table->date('paid_at')->nullable();
            $table->unsignedBigInteger('confirmed_by')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('clan_id', 'cpc_clan_fk')->references('id')->on('clanovis')->cascadeOnDelete();
            $table->foreign('clan_payment_profile_id', 'cpc_profile_fk')->references('id')->on('clan_payment_profiles')->nullOnDelete();
            $table->foreign('membership_payment_option_id', 'cpc_option_fk')->references('id')->on('membership_payment_options')->nullOnDelete();
            $table->foreign('confirmed_by', 'cpc_confirmed_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('created_by', 'cpc_created_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by', 'cpc_updated_fk')->references('id')->on('users')->nullOnDelete();
            $table->index(['clan_id', 'status']);
            $table->index(['clan_id', 'source']);
            $table->index(['due_date']);
            $table->unique(['clan_id', 'source', 'period_key'], 'clan_payment_period_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clan_payment_charges');
    }
};
