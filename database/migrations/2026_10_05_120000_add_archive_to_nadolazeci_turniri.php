<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nadolazeci_turniri', function (Blueprint $table): void {
            $table->timestamp('archived_at')->nullable()->index();
            $table->foreignId('rezultatski_turnir_id')->nullable()
                ->constrained('turniris')->nullOnDelete();
        });

        // Postojeće prošle turnire veži samo uz rezultat s istim datumom, nazivom, mjestom i tipom.
        DB::table('nadolazeci_turniri')
            ->whereDate('datum', '<=', now()->toDateString())
            ->orderBy('id')
            ->chunkById(100, function ($turniri): void {
                foreach ($turniri as $turnir) {
                    $rezultat = DB::table('turniris')
                        ->whereDate('datum', $turnir->datum)
                        ->where('naziv', $turnir->naziv)
                        ->where('lokacija', $turnir->mjesto)
                        ->where('tipovi_turnira_id', $turnir->tipovi_turnira_id)
                        ->first(['id']);

                    if ($rezultat !== null) {
                        DB::table('nadolazeci_turniri')
                            ->where('id', $turnir->id)
                            ->update([
                                'archived_at' => now(),
                                'rezultatski_turnir_id' => $rezultat->id,
                            ]);
                    }
                }
            });
    }

    public function down(): void
    {
        Schema::table('nadolazeci_turniri', function (Blueprint $table): void {
            $table->dropForeign(['rezultatski_turnir_id']);
            $table->dropColumn(['archived_at', 'rezultatski_turnir_id']);
        });
    }
};
