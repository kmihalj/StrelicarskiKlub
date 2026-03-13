<?php

namespace App\Console\Commands;

use App\Models\ClanLijecnickiPregled;
use App\Models\Clanovi;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class MigrirajLegacyLijecnicke extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clanovi:migriraj-legacy-lijecnicke {--dry-run : Samo ispis bez upisa u bazu i bez premještanja datoteka}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Prebacivanje legacy liječničkih podataka iz clanovis tablice u clan_lijecnicki_pregledi.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dryRun = (bool)$this->option('dry-run');
        $ukupno = 0;
        $migrirano = 0;
        $preskoceno = 0;
        $bezDatoteke = 0;

        $clanovi = Clanovi::whereNotNull('lijecnicki_do')
            ->whereNotNull('lijecnicki_dokument')
            ->orderBy('id')
            ->get();

        if ($clanovi->count() === 0) {
            $this->info('Nema legacy liječničkih zapisa za migraciju.');
            return self::SUCCESS;
        }

        foreach ($clanovi as $clan) {
            $ukupno++;

            $vecMigrirano = ClanLijecnickiPregled::where('clan_id', $clan->id)
                ->where('legacy_import', true)
                ->exists();

            if ($vecMigrirano) {
                $preskoceno++;
                $this->line("Preskočeno (već migrirano): clan_id={$clan->id}");
                continue;
            }

            $staraPutanja = 'public/lijecnicki_dokumenti/' . $clan->lijecnicki_dokument;
            $novaMapa = 'private/clanovi/' . $clan->id . '/lijecnicki';
            $novoImeDatoteke = 'legacy_' . $clan->lijecnicki_dokument;
            $novaPutanja = $novaMapa . '/' . $novoImeDatoteke;

            if (Storage::disk('local')->exists($novaPutanja)) {
                $pathInfo = pathinfo($clan->lijecnicki_dokument);
                $base = $pathInfo['filename'] ?? ('legacy_' . $clan->id);
                $ext = isset($pathInfo['extension']) ? '.' . $pathInfo['extension'] : '';
                $novoImeDatoteke = 'legacy_' . $base . '_' . date('Ymd_His') . $ext;
                $novaPutanja = $novaMapa . '/' . $novoImeDatoteke;
            }

            if (!$dryRun) {
                if (!Storage::disk('local')->exists($novaMapa)) {
                    Storage::disk('local')->makeDirectory($novaMapa);
                }

                if (Storage::disk('local')->exists($staraPutanja)) {
                    Storage::disk('local')->move($staraPutanja, $novaPutanja);
                } else {
                    $novaPutanja = null;
                    $bezDatoteke++;
                }

                $pregled = new ClanLijecnickiPregled();
                $pregled->clan_id = $clan->id;
                $pregled->vrijedi_do = $clan->lijecnicki_do;
                $pregled->putanja = $novaPutanja;
                $pregled->originalni_naziv = $clan->lijecnicki_dokument;
                $pregled->legacy_import = true;
                $pregled->created_by = null;
                $pregled->save();

                $clan->osvjeziLijecnickiDo();
            }

            if ($dryRun) {
                $this->line("DRY-RUN: clan_id={$clan->id}, {$staraPutanja} -> {$novaPutanja}");
            } else {
                $this->line("Migrirano: clan_id={$clan->id}, {$staraPutanja} -> " . ($novaPutanja ?? 'bez datoteke'));
            }

            $migrirano++;
        }

        $this->newLine();
        $this->info("Ukupno pronađeno: {$ukupno}");
        $this->info("Migrirano: {$migrirano}");
        $this->info("Preskočeno: {$preskoceno}");
        if (!$dryRun) {
            $this->info("Zapisa bez datoteke: {$bezDatoteke}");
        }

        return self::SUCCESS;
    }
}
