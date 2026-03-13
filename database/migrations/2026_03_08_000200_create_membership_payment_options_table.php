<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('membership_payment_options')) {
            Schema::create('membership_payment_options', function (Blueprint $table): void {
                $table->id();
                $table->string('key', 64)->unique();
                $table->string('name', 191);
                $table->text('description')->nullable();
                $table->string('period_type', 32);
                $table->string('period_anchor', 32)->nullable();
                $table->boolean('is_enabled')->default(false);
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }

    }

    public function down(): void
    {
        Schema::dropIfExists('membership_payment_options');
    }
};
