<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('membership_payment_option_prices')) {
            Schema::create('membership_payment_option_prices', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('membership_payment_option_id');
                $table->decimal('amount', 10, 2)->default(0);
                $table->date('valid_from');
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();

                $table->foreign('membership_payment_option_id', 'mppo_prices_option_fk')
                    ->references('id')
                    ->on('membership_payment_options')
                    ->cascadeOnDelete();
                $table->foreign('created_by', 'mppo_prices_user_fk')
                    ->references('id')
                    ->on('users')
                    ->nullOnDelete();
                $table->unique(['membership_payment_option_id', 'valid_from'], 'mppo_prices_unique');
            });
        }

        if (!Schema::hasTable('membership_payment_options')) {
            return;
        }

        $options = DB::table('membership_payment_options')->get(['id']);
        foreach ($options as $option) {
            $exists = DB::table('membership_payment_option_prices')
                ->where('membership_payment_option_id', $option->id)
                ->exists();

            if ($exists) {
                continue;
            }

            DB::table('membership_payment_option_prices')->insert([
                'membership_payment_option_id' => $option->id,
                'amount' => 0,
                'valid_from' => now()->toDateString(),
                'created_by' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('membership_payment_option_prices');
    }
};
