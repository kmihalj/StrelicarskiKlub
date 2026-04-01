<?php

namespace App\Http\Controllers;

use App\Models\Clanovi;
use App\Models\ClanPaymentCharge;
use App\Models\Kategorije;
use App\Models\NadolazeciTurnir;
use App\Models\PrijavaTurnira;
use App\Models\RezultatiOpci;
use App\Models\RezultatiPoTipuTurnira;
use App\Models\Stilovi;
use App\Models\TipoviTurnira;
use App\Models\Turniri;
use App\Models\User;
use App\Services\PaymentTrackingService;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

/**
 * Kontroler za administraciju nadolazećih turnira i prijave članova/roditelja.
 */
class NadolazeciTurniriController extends Controller
{
    private const STANDARDNI_LUK_STIL_ID = 7;

    private const VIDLJIVOST_DANA_ZA_PRIJAVU = 60;

    private const SMJENE = ['jutarnja', 'popodnevna', 'nebitno'];

    private const STIL_REDOSLIJED_KODOVA = ['CU', 'RB', 'BB', 'TB', 'LB'];

    private const CSV_EXPORT_POLJA_PRIJAVA = [
        'ime' => 'Ime',
        'prezime' => 'Prezime',
        'datum_rodjenja' => 'Datum rođenja',
        'broj_licence' => 'Br. licence',
        'lijecnicki_do' => 'Trajanje liječničkog',
        'stil' => 'Stil',
        'kategorija' => 'Kategorija',
        'oib' => 'OIB',
        'smjena' => 'Smjena / dan',
        'kup' => 'KUP',
    ];

    private const CSV_EXPORT_ZADANA_POLJA_PRIJAVA = [
        'ime',
        'prezime',
        'datum_rodjenja',
        'broj_licence',
        'lijecnicki_do',
        'stil',
        'kategorija',
        'oib',
        'smjena',
        'kup',
    ];

    /**
     * Prikazuje administratorski popis nadolazećih turnira i formu za unos/izmjenu.
     */
    public function adminIndex(Request $request): View
    {
        $danas = now()->startOfDay()->toDateString();
        $this->obrisiProsleTurnireBezAktivnihPrijava($danas);

        $baseQuery = NadolazeciTurnir::query()
            ->with('tipTurnira')
            ->with([
                'prijave' => fn ($query) => $query
                    ->where('status', PrijavaTurnira::STATUS_ACTIVE)
                    ->with([
                        'clan' => fn ($clanQuery) => $clanQuery->select(['id', 'Ime', 'Prezime', 'lijecnicki_do']),
                    ])
                    ->orderBy('clan_id'),
            ])
            ->withCount([
                'prijave as aktivne_prijave_count' => fn ($query) => $query->where('status', PrijavaTurnira::STATUS_ACTIVE),
            ]);

        $nadolazeciTurniri = (clone $baseQuery)
            ->whereDate('datum', '>', $danas)
            ->orderBy('datum')
            ->orderBy('id')
            ->paginate(20, ['*'], 'upcoming_page')
            ->withQueryString();

        $prosliTurniri = (clone $baseQuery)
            ->whereDate('datum', '<=', $danas)
            ->orderByDesc('datum')
            ->orderByDesc('id')
            ->paginate(20, ['*'], 'past_page')
            ->withQueryString();

        $tipoviTurnira = TipoviTurnira::query()->orderBy('naziv')->get();
        $urediTurnir = null;
        $urediId = (int) $request->query('uredi', 0);
        if ($urediId > 0) {
            $urediTurnir = NadolazeciTurnir::query()->find($urediId);
        }

        return view('admin.nadolazeciTurniri.index', [
            'nadolazeciTurniri' => $nadolazeciTurniri,
            'prosliTurniri' => $prosliTurniri,
            'tipoviTurnira' => $tipoviTurnira,
            'urediTurnir' => $urediTurnir,
        ]);
    }

    /**
     * Pokreće import nadolazećih turnira s archery.hr za tekuću i sljedeću godinu.
     */
    public function adminImportArchery(): RedirectResponse
    {
        $tekucaGodina = (int) now()->year;
        $sljedecaGodina = $tekucaGodina + 1;
        $report = [
            'ok' => false,
            'exit_code' => 1,
            'years' => [$tekucaGodina, $sljedecaGodina],
            'generated_at' => now()->format('d.m.Y. H:i:s'),
            'output' => '',
        ];

        try {
            $exitCode = Artisan::call('turniri:import-archery', [
                '--year' => [$tekucaGodina, $sljedecaGodina],
            ]);
            $output = trim((string) Artisan::output());
            if ($output === '') {
                $output = 'Komanda nije vratila dodatni izlaz.';
            }

            $report['ok'] = $exitCode === 0;
            $report['exit_code'] = (int) $exitCode;
            $report['output'] = $output;
        } catch (Throwable $e) {
            $report['output'] = 'Greška pri pokretanju importa: '.$e->getMessage();
        }

        return redirect()
            ->route('admin.nadolazeci_turniri.index')
            ->with('archery_import_report', $report);
    }

    /**
     * Sprema novi nadolazeći turnir.
     */
    public function adminStore(Request $request): RedirectResponse
    {
        $validated = $this->validateAdminTurnir($request);
        $turnir = new NadolazeciTurnir;
        $this->saveTurnir($turnir, $validated, $request);

        return redirect()
            ->route('admin.nadolazeci_turniri.index')
            ->with('success', 'Nadolazeći turnir je spremljen.');
    }

    /**
     * Ažurira postojeći nadolazeći turnir.
     */
    public function adminUpdate(Request $request, NadolazeciTurnir $turnir): RedirectResponse
    {
        $validated = $this->validateAdminTurnir($request);
        $this->saveTurnir($turnir, $validated, $request);

        return redirect()
            ->route('admin.nadolazeci_turniri.index')
            ->with('success', 'Nadolazeći turnir je ažuriran.');
    }

    /**
     * Briše nadolazeći turnir i povezane prijave.
     */
    public function adminDestroy(NadolazeciTurnir $turnir): RedirectResponse
    {
        $turnir->loadMissing('prijave.paymentCharge');

        foreach ($turnir->prijave as $prijava) {
            $this->ukloniKotizacijuAkoMoguce($prijava, (int) auth()->id());
        }

        if (! empty($turnir->poziv_pdf_path) && Storage::disk('public')->exists($turnir->poziv_pdf_path)) {
            Storage::disk('public')->delete($turnir->poziv_pdf_path);
        }

        $turnir->delete();

        return redirect()
            ->route('admin.nadolazeci_turniri.index')
            ->with('success', 'Turnir je obrisan.');
    }

    /**
     * Prikazuje detalje turnira i popis prijava članova.
     */
    public function adminShow(NadolazeciTurnir $turnir): View
    {
        $turnir->loadMissing('tipTurnira');

        $svePrijave = PrijavaTurnira::query()
            ->with([
                'clan',
                'kategorija',
                'stil',
                'turnir',
                'prijavioUser',
                'paymentCharge',
            ])
            ->where('nadolazeci_turnir_id', (int) $turnir->id)
            ->orderByRaw("CASE status WHEN 'active' THEN 0 WHEN 'cancelled' THEN 1 ELSE 2 END")
            ->orderBy('id')
            ->get();

        $prijave = $svePrijave
            ->filter(fn (PrijavaTurnira $prijava): bool => $prijava->status !== PrijavaTurnira::STATUS_REMOVED)
            ->values();
        $uklonjenePrijave = $svePrijave
            ->filter(fn (PrijavaTurnira $prijava): bool => $prijava->status === PrijavaTurnira::STATUS_REMOVED)
            ->values();

        return view('admin.nadolazeciTurniri.show', [
            'turnir' => $turnir,
            'prijave' => $prijave,
            'uklonjenePrijave' => $uklonjenePrijave,
            'csvPoljaPrijava' => self::CSV_EXPORT_POLJA_PRIJAVA,
            'csvZadanaPoljaPrijava' => self::CSV_EXPORT_ZADANA_POLJA_PRIJAVA,
        ]);
    }

    /**
     * Iz prošlog nadolazećeg turnira kreira turnir rezultata i početne retke prijavljenih članova.
     */
    public function adminKreirajRezultate(NadolazeciTurnir $turnir): RedirectResponse
    {
        $turnir->loadMissing([
            'tipTurnira.polja',
            'prijave' => fn ($query) => $query
                ->where('status', PrijavaTurnira::STATUS_ACTIVE)
                ->with(['clan', 'kategorija', 'stil'])
                ->orderBy('id'),
        ]);

        $datumPocetka = $turnir->datum?->copy()->startOfDay();
        if (! $datumPocetka instanceof CarbonInterface || $datumPocetka->gt(now()->startOfDay())) {
            return redirect()
                ->route('admin.nadolazeci_turniri.show', $turnir)
                ->with('error', 'Rezultate je moguće kreirati samo za turnire kojima je datum početka danas ili ranije.');
        }

        if (! $turnir->tipTurnira instanceof TipoviTurnira) {
            return redirect()
                ->route('admin.nadolazeci_turniri.show', $turnir)
                ->with('error', 'Nadolazeći turnir nema valjan tip turnira.');
        }

        $rezultatiTurnirId = 0;
        $dodaniOpci = 0;
        $dodanaPolja = 0;

        try {
            DB::transaction(function () use ($turnir, &$rezultatiTurnirId, &$dodaniOpci, &$dodanaPolja): void {
                $rezultatiTurnir = $this->pronadiIliKreirajRezultatskiTurnir($turnir);
                $rezultatiTurnir->loadMissing('tipTurnira.polja');

                $poljaTipa = collect($rezultatiTurnir->tipTurnira?->polja ?? [])
                    ->sortBy('id')
                    ->values();

                $sortiranePrijave = $this->sortirajPrijaveZaKreiranjeRezultata($turnir->prijave ?? collect());

                foreach ($sortiranePrijave as $prijava) {
                    $clan = $prijava->clan;
                    $kategorija = $prijava->kategorija;
                    $stil = $prijava->stil;

                    if (! ($clan instanceof Clanovi) || ! ($kategorija instanceof Kategorije) || ! ($stil instanceof Stilovi)) {
                        continue;
                    }

                    $rezultatOpci = RezultatiOpci::query()->firstOrCreate([
                        'turnir_id' => (int) $rezultatiTurnir->id,
                        'clan_id' => (int) $clan->id,
                        'kategorija_id' => (int) $kategorija->id,
                        'stil_id' => (int) $stil->id,
                    ], [
                        'plasman' => 0,
                        'plasman_nakon_eliminacija' => null,
                        'bez_eliminacija' => false,
                    ]);

                    if ($rezultatOpci->wasRecentlyCreated) {
                        $dodaniOpci++;
                    }

                    foreach ($poljaTipa as $poljeTipa) {
                        $rezultatPoTipu = RezultatiPoTipuTurnira::query()->firstOrCreate([
                            'turnir_id' => (int) $rezultatiTurnir->id,
                            'clan_id' => (int) $clan->id,
                            'kategorija_id' => (int) $kategorija->id,
                            'stil_id' => (int) $stil->id,
                            'polje_za_tipove_turnira_id' => (int) $poljeTipa->id,
                        ], [
                            'rezultat' => 0,
                        ]);

                        if ($rezultatPoTipu->wasRecentlyCreated) {
                            $dodanaPolja++;
                        }
                    }
                }

                $rezultatiTurnirId = (int) $rezultatiTurnir->id;
            });
        } catch (Throwable $e) {
            report($e);

            return redirect()
                ->route('admin.nadolazeci_turniri.show', $turnir)
                ->with('error', 'Kreiranje rezultata nije uspjelo.');
        }

        if ($rezultatiTurnirId <= 0) {
            return redirect()
                ->route('admin.nadolazeci_turniri.show', $turnir)
                ->with('error', 'Kreiranje rezultata nije uspjelo.');
        }

        return redirect()
            ->route('admin.rezultati.unosRezultata', $rezultatiTurnirId)
            ->with('success', 'Kreiran je unos rezultata. Dodano: '.$dodaniOpci.' članova i '.$dodanaPolja.' polja.');
    }

    /**
     * Izvozi CSV aktivnih prijava za odabrani turnir uz odabir stupaca.
     */
    public function adminExportCsv(Request $request, NadolazeciTurnir $turnir): StreamedResponse
    {
        $odabranaPolja = $this->odabranaCsvPoljaPrijava($request);

        $prijave = PrijavaTurnira::query()
            ->with(['clan', 'kategorija', 'stil', 'turnir'])
            ->where('nadolazeci_turnir_id', (int) $turnir->id)
            ->where('status', PrijavaTurnira::STATUS_ACTIVE)
            ->orderBy('id')
            ->get();

        $zaglavlja = $this->csvZaglavljaPrijava($odabranaPolja);
        $nazivDatoteke = 'turnir_prijave_'.Str::slug((string) $turnir->naziv, '_').'_'.now()->format('Ymd_His').'.csv';

        return response()->streamDownload(function () use ($prijave, $odabranaPolja, $zaglavlja) {
            $izlaz = fopen('php://output', 'wb');
            if ($izlaz === false) {
                return;
            }

            fwrite($izlaz, "\xEF\xBB\xBF");
            fputcsv($izlaz, $zaglavlja, ';');

            foreach ($prijave as $prijava) {
                $red = [];
                foreach ($odabranaPolja as $polje) {
                    $red[] = $this->vrijednostPoljaPrijaveZaCsv($polje, $prijava);
                }

                fputcsv($izlaz, $red, ';');
            }

            fclose($izlaz);
        }, $nazivDatoteke, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Administrator uklanja aktivnu prijavu člana na turnir uz obaveznu napomenu.
     */
    public function adminUkloniPrijavu(Request $request, NadolazeciTurnir $turnir, PrijavaTurnira $prijava): RedirectResponse
    {
        if ((int) $prijava->nadolazeci_turnir_id !== (int) $turnir->id) {
            abort(404);
        }

        $validated = $request->validate([
            'napomena_admin' => ['required', 'string', 'max:2000'],
        ], [
            'napomena_admin.required' => 'Potrebno je unijeti napomenu zašto je član maknut s turnira.',
        ]);

        if ($prijava->status !== PrijavaTurnira::STATUS_ACTIVE) {
            return redirect()
                ->route('admin.nadolazeci_turniri.show', $turnir)
                ->with('error', 'Prijava više nije aktivna.');
        }

        $prijava->status = PrijavaTurnira::STATUS_REMOVED;
        $prijava->napomena_admin = trim((string) $validated['napomena_admin']);
        $prijava->removed_by = (int) auth()->id();
        $prijava->removed_at = now();
        $prijava->cancelled_at = null;
        $prijava->save();

        $this->ukloniKotizacijuAkoMoguce($prijava, (int) auth()->id());

        return redirect()
            ->route('admin.nadolazeci_turniri.show', $turnir)
            ->with('success', 'Član je maknut s turnira.');
    }

    /**
     * Prikazuje korisničku stranicu prijava na turnire.
     */
    public function userIndex(): View
    {
        $korisnik = auth()->user();
        if (! $korisnik instanceof User) {
            abort(403);
        }

        $clanoviZaPrijavu = $this->dostupniClanoviKorisnika($korisnik);
        $clanIds = $clanoviZaPrijavu->pluck('id')->map(fn ($id): int => (int) $id)->all();

        $danas = now()->startOfDay();
        $maxDatum = now()->addDays(self::VIDLJIVOST_DANA_ZA_PRIJAVU)->endOfDay();

        $dostupniTurniri = NadolazeciTurnir::query()
            ->with('tipTurnira')
            ->whereDate('datum', '>', $danas->toDateString())
            ->whereDate('datum', '<=', $maxDatum->toDateString())
            ->orderBy('datum')
            ->orderBy('id')
            ->get();

        $sveAktivnePrijave = PrijavaTurnira::query()
            ->with([
                'turnir.tipTurnira',
                'clan',
                'kategorija',
                'stil',
                'paymentCharge',
            ])
            ->whereIn('clan_id', $clanIds)
            ->where('status', PrijavaTurnira::STATUS_ACTIVE)
            ->get()
            ->sortBy(function (PrijavaTurnira $prijava): array {
                $datum = $prijava->turnir?->datum?->format('Y-m-d') ?? '9999-12-31';

                return [$datum, (int) $prijava->id];
            })
            ->values();

        $aktivnePrijave = $sveAktivnePrijave
            ->filter(function (PrijavaTurnira $prijava) use ($danas): bool {
                $datum = $prijava->turnir?->datum;

                return $datum !== null && $datum->copy()->startOfDay()->gt($danas);
            })
            ->values();

        $proslePrijave = $sveAktivnePrijave
            ->filter(function (PrijavaTurnira $prijava) use ($danas): bool {
                $datum = $prijava->turnir?->datum;

                return $datum !== null && $datum->copy()->startOfDay()->lte($danas);
            })
            ->values();

        $aktivnoPoClanuTurniru = PrijavaTurnira::query()
            ->select(['clan_id', 'nadolazeci_turnir_id'])
            ->whereIn('clan_id', $clanIds)
            ->where('status', PrijavaTurnira::STATUS_ACTIVE)
            ->get()
            ->groupBy('clan_id')
            ->map(static function (Collection $stavke): array {
                return $stavke
                    ->pluck('nadolazeci_turnir_id')
                    ->map(fn ($id): int => (int) $id)
                    ->all();
            })
            ->all();

        $turnirIds = $dostupniTurniri
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();
        $prijavljeniPoTurniru = [];
        if (count($turnirIds) > 0) {
            $prijavljeniPoTurniru = PrijavaTurnira::query()
                ->with([
                    'clan' => fn ($query) => $query->select(['id', 'Ime', 'Prezime']),
                ])
                ->whereIn('nadolazeci_turnir_id', $turnirIds)
                ->where('status', PrijavaTurnira::STATUS_ACTIVE)
                ->get()
                ->groupBy('nadolazeci_turnir_id')
                ->map(static function (Collection $stavke): array {
                    return $stavke
                        ->map(static function (PrijavaTurnira $prijava): ?array {
                            $clan = $prijava->clan;
                            if (! ($clan instanceof Clanovi)) {
                                return null;
                            }

                            $naziv = trim((string) $clan->Ime.' '.(string) $clan->Prezime);

                            return [
                                'clan_id' => (int) $clan->id,
                                'naziv' => $naziv,
                                'url' => route('javno.clanovi.prikaz_clana', (int) $clan->id),
                            ];
                        })
                        ->filter()
                        ->sortBy(static fn (array $stavka): string => Str::ascii(mb_strtolower((string) $stavka['naziv'], 'UTF-8')))
                        ->values()
                        ->all();
                })
                ->all();
        }

        $stilovi = $this->stiloviZaPrijavu();

        $kategorijePoClanu = $this->kategorijePoClanu($clanoviZaPrijavu);
        $clanoviMetaZaKategoriju = $this->clanoviMetaZaKategoriju($clanoviZaPrijavu);
        $lijecnickiUpozorenja = $this->lijecnickiUpozorenjaZaPrijave($aktivnePrijave);
        $prikaziOdabirClana = $korisnik->jeRoditelj();
        $zadaniClanId = $this->zadaniClanIdZaPrijavu($korisnik, $clanoviZaPrijavu);

        return view('javno.prijaveTurnira.index', [
            'clanoviZaPrijavu' => $clanoviZaPrijavu,
            'dostupniTurniri' => $dostupniTurniri,
            'aktivnePrijave' => $aktivnePrijave,
            'proslePrijave' => $proslePrijave,
            'aktivnoPoClanuTurniru' => $aktivnoPoClanuTurniru,
            'stilovi' => $stilovi,
            'kategorijePoClanu' => $kategorijePoClanu,
            'clanoviMetaZaKategoriju' => $clanoviMetaZaKategoriju,
            'smjeneOpcije' => self::SMJENE,
            'lijecnickiUpozorenja' => $lijecnickiUpozorenja,
            'daniVidljivosti' => self::VIDLJIVOST_DANA_ZA_PRIJAVU,
            'prijavljeniPoTurniru' => $prijavljeniPoTurniru,
            'prikaziOdabirClana' => $prikaziOdabirClana,
            'zadaniClanId' => $zadaniClanId,
        ]);
    }

    /**
     * Sprema novu prijavu člana/roditelja na odabrani turnir.
     */
    public function userStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'clan_id' => ['nullable', 'integer', 'exists:clanovis,id'],
            'nadolazeci_turnir_id' => ['required', 'integer', 'exists:nadolazeci_turniri,id'],
            'kategorija_id' => ['required', 'integer', 'exists:kategorijes,id'],
            'stil_id' => ['required', 'integer', 'exists:stilovis,id'],
            'sudjelujem_u_kupu' => ['nullable', 'boolean'],
            'smjena' => ['nullable', 'in:jutarnja,popodnevna,nebitno'],
            'odabrani_dan' => ['nullable', 'date'],
        ]);

        $korisnik = auth()->user();
        if (! $korisnik instanceof User) {
            abort(403);
        }

        $clanId = $this->odrediClanIdZaPrijavu($korisnik, isset($validated['clan_id']) ? (int) $validated['clan_id'] : 0);
        if ($clanId <= 0) {
            return redirect()
                ->route('javno.prijave_turnira.index')
                ->with('error', 'Nije moguće odrediti člana za prijavu.');
        }

        $clan = Clanovi::query()->findOrFail($clanId);
        if (! $this->korisnikMozePrijavitiClana($korisnik, (int) $clan->id)) {
            abort(403);
        }

        $turnir = NadolazeciTurnir::query()->findOrFail((int) $validated['nadolazeci_turnir_id']);
        $porukaZabrane = $this->porukaZaNedostupanTurnir($turnir, true);
        if ($porukaZabrane !== null) {
            return redirect()
                ->route('javno.prijave_turnira.index')
                ->with('error', $porukaZabrane);
        }

        $kategorija = Kategorije::query()->findOrFail((int) $validated['kategorija_id']);
        $stil = Stilovi::query()->where('id', '!=', self::STANDARDNI_LUK_STIL_ID)->findOrFail((int) $validated['stil_id']);
        if (! $this->kategorijaOdgovaraSpoluClana($clan, $kategorija)) {
            return redirect()
                ->route('javno.prijave_turnira.index')
                ->with('error', 'Odabrana kategorija ne odgovara spolu člana.');
        }

        $terminPrijave = $this->odrediTerminPrijave(
            $turnir,
            $validated['smjena'] ?? null,
            $validated['odabrani_dan'] ?? null
        );

        $prijava = PrijavaTurnira::query()->firstOrNew([
            'nadolazeci_turnir_id' => (int) $turnir->id,
            'clan_id' => (int) $clan->id,
        ]);

        if ($prijava->exists && $prijava->status === PrijavaTurnira::STATUS_ACTIVE) {
            return redirect()
                ->route('javno.prijave_turnira.index')
                ->with('error', 'Član je već prijavljen na odabrani turnir.');
        }

        if ($prijava->exists && $prijava->status === PrijavaTurnira::STATUS_REMOVED) {
            $poruka = 'Administrator je maknuo ovu prijavu.';
            if (! empty($prijava->napomena_admin)) {
                $poruka .= ' Napomena: '.$prijava->napomena_admin;
            }

            return redirect()
                ->route('javno.prijave_turnira.index')
                ->with('error', $poruka);
        }

        $prijava->prijavio_user_id = (int) $korisnik->id;
        $prijava->kategorija_id = (int) $kategorija->id;
        $prijava->stil_id = (int) $stil->id;
        $prijava->sudjelujem_u_kupu = $request->boolean('sudjelujem_u_kupu');
        $prijava->smjena = $terminPrijave['smjena'];
        $prijava->odabrani_dan = $terminPrijave['odabrani_dan'];
        $prijava->status = PrijavaTurnira::STATUS_ACTIVE;
        $prijava->napomena_admin = null;
        $prijava->removed_by = null;
        $prijava->removed_at = null;
        $prijava->cancelled_at = null;
        $prijava->save();

        $this->syncKotizacijaZaPrijavu($prijava, $turnir, (int) $korisnik->id);

        $warning = $this->lijecnickoUpozorenje($clan, $turnir);
        $poruka = 'Prijava je spremljena.';
        if ($warning !== null) {
            $poruka .= ' '.$warning;
        }

        return redirect()
            ->route('javno.prijave_turnira.show', $prijava)
            ->with('success', $poruka);
    }

    /**
     * Prikazuje detalje jedne prijave i formu za izmjenu/odjavu.
     */
    public function userShow(PrijavaTurnira $prijava): View
    {
        $korisnik = auth()->user();
        if (! $korisnik instanceof User) {
            abort(403);
        }

        $prijava->loadMissing(['turnir.tipTurnira', 'clan', 'kategorija', 'stil', 'paymentCharge']);
        if (! $this->korisnikMozePrijavitiClana($korisnik, (int) $prijava->clan_id)) {
            abort(403);
        }

        $clan = $prijava->clan;
        $turnir = $prijava->turnir;
        if (! ($clan instanceof Clanovi) || ! ($turnir instanceof NadolazeciTurnir)) {
            abort(404);
        }

        $kategorije = $this->kategorijeZaClana($clan);
        $stilovi = $this->stiloviZaPrijavu();
        $smjene = self::SMJENE;
        $turnirJeVisednevni = $this->turnirJeVisednevni($turnir);
        $odabirDanaOpcije = $this->odabirDanaOpcijeZaTurnir($turnir);
        $zakljucano = $turnir->prijaveZakljucane();
        $lijecnickoUpozorenje = $this->lijecnickoUpozorenje($clan, $turnir);
        $prijavljeniClanoviTurnira = PrijavaTurnira::query()
            ->with([
                'clan' => fn ($query) => $query->select(['id', 'Ime', 'Prezime']),
            ])
            ->where('nadolazeci_turnir_id', (int) $turnir->id)
            ->where('status', PrijavaTurnira::STATUS_ACTIVE)
            ->get()
            ->map(static function (PrijavaTurnira $stavka): ?array {
                $prijavljeniClan = $stavka->clan;
                if (! ($prijavljeniClan instanceof Clanovi)) {
                    return null;
                }

                $naziv = trim((string) $prijavljeniClan->Ime.' '.(string) $prijavljeniClan->Prezime);

                return [
                    'clan_id' => (int) $prijavljeniClan->id,
                    'naziv' => $naziv,
                    'url' => route('javno.clanovi.prikaz_clana', (int) $prijavljeniClan->id),
                ];
            })
            ->filter()
            ->sortBy(static fn (array $stavka): string => Str::ascii(mb_strtolower((string) $stavka['naziv'], 'UTF-8')))
            ->values();

        return view('javno.prijaveTurnira.show', [
            'prijava' => $prijava,
            'turnir' => $turnir,
            'clan' => $clan,
            'kategorije' => $kategorije,
            'stilovi' => $stilovi,
            'smjene' => $smjene,
            'turnirJeVisednevni' => $turnirJeVisednevni,
            'odabirDanaOpcije' => $odabirDanaOpcije,
            'zakljucano' => $zakljucano,
            'lijecnickoUpozorenje' => $lijecnickoUpozorenje,
            'prijavljeniClanoviTurnira' => $prijavljeniClanoviTurnira,
        ]);
    }

    /**
     * Ažurira postojeću aktivnu prijavu.
     */
    public function userUpdate(Request $request, PrijavaTurnira $prijava): RedirectResponse
    {
        $korisnik = auth()->user();
        if (! $korisnik instanceof User) {
            abort(403);
        }

        $prijava->loadMissing(['turnir', 'clan', 'paymentCharge']);
        if (! $this->korisnikMozePrijavitiClana($korisnik, (int) $prijava->clan_id)) {
            abort(403);
        }

        if ($prijava->status !== PrijavaTurnira::STATUS_ACTIVE) {
            return redirect()
                ->route('javno.prijave_turnira.show', $prijava)
                ->with('error', 'Samo aktivnu prijavu je moguće uređivati.');
        }

        $turnir = $prijava->turnir;
        $clan = $prijava->clan;
        if (! ($turnir instanceof NadolazeciTurnir) || ! ($clan instanceof Clanovi)) {
            abort(404);
        }

        if ($turnir->prijaveZakljucane()) {
            return redirect()
                ->route('javno.prijave_turnira.show', $prijava)
                ->with('error', 'Prijave za ovaj turnir su zaključane.');
        }

        $validated = $request->validate([
            'kategorija_id' => ['required', 'integer', 'exists:kategorijes,id'],
            'stil_id' => ['required', 'integer', 'exists:stilovis,id'],
            'sudjelujem_u_kupu' => ['nullable', 'boolean'],
            'smjena' => ['nullable', 'in:jutarnja,popodnevna,nebitno'],
            'odabrani_dan' => ['nullable', 'date'],
        ]);

        $kategorija = Kategorije::query()->findOrFail((int) $validated['kategorija_id']);
        $stil = Stilovi::query()->where('id', '!=', self::STANDARDNI_LUK_STIL_ID)->findOrFail((int) $validated['stil_id']);
        if (! $this->kategorijaOdgovaraSpoluClana($clan, $kategorija)) {
            return redirect()
                ->route('javno.prijave_turnira.show', $prijava)
                ->with('error', 'Odabrana kategorija ne odgovara spolu člana.');
        }

        $terminPrijave = $this->odrediTerminPrijave(
            $turnir,
            $validated['smjena'] ?? null,
            $validated['odabrani_dan'] ?? null
        );

        $prijava->kategorija_id = (int) $kategorija->id;
        $prijava->stil_id = (int) $stil->id;
        $prijava->sudjelujem_u_kupu = $request->boolean('sudjelujem_u_kupu');
        $prijava->smjena = $terminPrijave['smjena'];
        $prijava->odabrani_dan = $terminPrijave['odabrani_dan'];
        $prijava->save();

        $this->syncKotizacijaZaPrijavu($prijava, $turnir, (int) $korisnik->id);

        return redirect()
            ->route('javno.prijave_turnira.show', $prijava)
            ->with('success', 'Prijava je ažurirana.');
    }

    /**
     * Odjavljuje člana s aktivne prijave.
     */
    public function userOdjava(PrijavaTurnira $prijava): RedirectResponse
    {
        $korisnik = auth()->user();
        if (! $korisnik instanceof User) {
            abort(403);
        }

        $prijava->loadMissing(['turnir', 'paymentCharge']);
        if (! $this->korisnikMozePrijavitiClana($korisnik, (int) $prijava->clan_id)) {
            abort(403);
        }

        if ($prijava->status !== PrijavaTurnira::STATUS_ACTIVE) {
            return redirect()
                ->route('javno.prijave_turnira.index')
                ->with('error', 'Prijava više nije aktivna.');
        }

        $turnir = $prijava->turnir;
        if (! ($turnir instanceof NadolazeciTurnir)) {
            abort(404);
        }

        if ($turnir->prijaveZakljucane()) {
            return redirect()
                ->route('javno.prijave_turnira.show', $prijava)
                ->with('error', 'Prijave za ovaj turnir su zaključane. Obratite se administratoru.');
        }

        $prijava->status = PrijavaTurnira::STATUS_CANCELLED;
        $prijava->cancelled_at = now();
        $prijava->save();

        $this->ukloniKotizacijuAkoMoguce($prijava, (int) $korisnik->id);

        return redirect()
            ->route('javno.prijave_turnira.index')
            ->with('success', 'Turnir je odjavljen.');
    }

    /**
     * Vraća aktivne prijave za zadane članove (koristi se na naslovnici).
     */
    public function aktivnePrijaveZaClanove(array $clanIds): Collection
    {
        if (count($clanIds) === 0) {
            return collect();
        }

        return PrijavaTurnira::query()
            ->with(['turnir.tipTurnira', 'clan', 'kategorija', 'stil', 'paymentCharge'])
            ->whereIn('clan_id', $clanIds)
            ->where('status', PrijavaTurnira::STATUS_ACTIVE)
            ->whereHas('turnir', function ($query): void {
                $query->whereDate('datum', '>', now()->startOfDay()->toDateString());
            })
            ->get()
            ->sortBy(function (PrijavaTurnira $prijava): array {
                $datum = $prijava->turnir?->datum?->format('Y-m-d') ?? '9999-12-31';

                return [$datum, (int) $prijava->id];
            })
            ->values();
    }

    /**
     * Vraća mapu liječničkih upozorenja za zadanu kolekciju prijava.
     *
     * @return array<int, string>
     */
    public function mapaLijecnickihUpozorenja(Collection $prijave): array
    {
        return $this->lijecnickiUpozorenjaZaPrijave($prijave);
    }

    /**
     * Vraća članove koje trenutni korisnik smije prijavljivati na turnire.
     */
    public function dostupniClanoviKorisnika(User $korisnik): Collection
    {
        $clanovi = collect();

        if ((int) $korisnik->rola <= 2 && (int) $korisnik->clan_id > 0) {
            $vlastitiClan = Clanovi::query()
                ->where('id', (int) $korisnik->clan_id)
                ->where('aktivan', true)
                ->first(['id', 'Ime', 'Prezime', 'spol', 'datum_rodjenja', 'lijecnicki_do']);
            if ($vlastitiClan instanceof Clanovi) {
                $clanovi->push($vlastitiClan);
            }
        }

        if ($korisnik->jeRoditelj()) {
            $korisnik->loadMissing([
                'djecaClanovi' => fn ($query) => $query
                    ->where('aktivan', true)
                    ->select(['clanovis.id', 'Ime', 'Prezime', 'spol', 'datum_rodjenja', 'lijecnicki_do']),
            ]);

            foreach ($korisnik->djecaClanovi as $dijeteClan) {
                if ($dijeteClan instanceof Clanovi) {
                    $clanovi->push($dijeteClan);
                }
            }
        }

        return $clanovi
            ->unique('id')
            ->sortBy([
                ['Prezime', 'asc'],
                ['Ime', 'asc'],
            ])
            ->values();
    }

    /**
     * Provjerava može li korisnik upravljati prijavom za traženog člana.
     */
    private function korisnikMozePrijavitiClana(User $korisnik, int $clanId): bool
    {
        if ((int) $korisnik->rola === 4) {
            return false;
        }

        return $korisnik->mozePregledavatiClana($clanId);
    }

    /**
     * Validira administratorsku formu nadolazećeg turnira.
     */
    private function validateAdminTurnir(Request $request): array
    {
        $validated = $request->validate([
            'naziv' => ['required', 'string', 'max:191'],
            'organizator' => ['nullable', 'string', 'max:191'],
            'mjesto' => ['required', 'string', 'max:191'],
            'datum' => ['required', 'date'],
            'datum_do' => ['nullable', 'date', 'after_or_equal:datum'],
            'napomena' => ['nullable', 'string', 'max:500'],
            'tipovi_turnira_id' => ['required', 'integer', 'exists:tipovi_turniras,id'],
            'prijave_otvorene_do' => ['nullable', 'date'],
            'is_zakljucan' => ['nullable', 'boolean'],
            'kotizacija_nacin' => ['nullable', 'in:undefined,bank,cash'],
            'kotizacija_iznos' => ['nullable', 'string', 'max:32'],
            'kotizacija_rok_uplate' => ['nullable', 'date'],
            'poziv_pdf' => ['nullable', 'file', 'mimes:pdf', 'max:15360'],
            'obrisi_poziv_pdf' => ['nullable', 'boolean'],
        ], [
            'poziv_pdf.mimes' => 'Poziv na turnir mora biti PDF datoteka.',
        ]);

        $nacin = trim((string) ($validated['kotizacija_nacin'] ?? 'undefined'));
        if ($nacin === '' || $nacin === 'undefined') {
            $validated['kotizacija_nacin'] = null;
            $validated['kotizacija_iznos'] = null;
            $validated['kotizacija_rok_uplate'] = null;
        }

        $iznos = $this->normalizirajIznos($validated['kotizacija_iznos'] ?? null);
        if ($nacin === 'bank') {
            if ($iznos !== null && $iznos <= 0) {
                throw ValidationException::withMessages([
                    'kotizacija_iznos' => 'Iznos kotizacije mora biti veći od 0.',
                ]);
            }
            $validated['kotizacija_iznos'] = $iznos;
        } elseif ($nacin === 'cash') {
            $validated['kotizacija_iznos'] = $iznos;
            $validated['kotizacija_rok_uplate'] = null;
        } else {
            $validated['kotizacija_nacin'] = null;
            $validated['kotizacija_iznos'] = null;
            $validated['kotizacija_rok_uplate'] = null;
        }

        if (! empty($validated['prijave_otvorene_do']) && ! empty($validated['datum'])) {
            $rok = Carbon::parse((string) $validated['prijave_otvorene_do'])->startOfDay();
            $datumTurnira = Carbon::parse((string) $validated['datum'])->startOfDay();
            if ($rok->gt($datumTurnira)) {
                throw ValidationException::withMessages([
                    'prijave_otvorene_do' => 'Rok prijava ne može biti nakon datuma turnira.',
                ]);
            }
        }

        if ($nacin === 'bank' && ! empty($validated['kotizacija_rok_uplate']) && ! empty($validated['datum'])) {
            $rokUplate = Carbon::parse((string) $validated['kotizacija_rok_uplate'])->startOfDay();
            $datumTurnira = Carbon::parse((string) $validated['datum'])->startOfDay();
            if ($rokUplate->gt($datumTurnira)) {
                throw ValidationException::withMessages([
                    'kotizacija_rok_uplate' => 'Rok uplate ne može biti nakon datuma turnira.',
                ]);
            }
        }

        if (($validated['kotizacija_nacin'] ?? null) !== 'bank') {
            $validated['kotizacija_rok_uplate'] = null;
        }

        return $validated;
    }

    /**
     * Sprema podatke nadolazećeg turnira i sinkronizira kotizacije prijava.
     */
    private function saveTurnir(NadolazeciTurnir $turnir, array $validated, Request $request): void
    {
        $turnir->naziv = trim((string) $validated['naziv']);
        $turnir->organizator = $this->normalizirajTekst($validated['organizator'] ?? null);
        $turnir->mjesto = trim((string) $validated['mjesto']);
        $turnir->datum = Carbon::parse((string) $validated['datum'])->toDateString();
        $turnir->datum_do = ! empty($validated['datum_do'])
            ? Carbon::parse((string) $validated['datum_do'])->toDateString()
            : null;
        $turnir->napomena = $this->normalizirajTekst($validated['napomena'] ?? null);
        $turnir->tipovi_turnira_id = (int) $validated['tipovi_turnira_id'];
        $turnir->boduje_za_kup = false;
        $turnir->ima_smjene = false;
        $turnir->smjene_opis = null;
        $turnir->prijave_otvorene_do = ! empty($validated['prijave_otvorene_do'])
            ? Carbon::parse((string) $validated['prijave_otvorene_do'])->toDateString()
            : null;
        $turnir->is_zakljucan = $request->boolean('is_zakljucan');
        $turnir->kotizacija_nacin = $validated['kotizacija_nacin'] ?? null;
        $turnir->kotizacija_iznos = $validated['kotizacija_iznos'] ?? null;
        $turnir->kotizacija_rok_uplate = ! empty($validated['kotizacija_rok_uplate'])
            ? Carbon::parse((string) $validated['kotizacija_rok_uplate'])->toDateString()
            : null;
        if (! $turnir->exists) {
            $turnir->created_by = (int) auth()->id();
        }
        $turnir->updated_by = (int) auth()->id();
        $turnir->save();

        if ($request->boolean('obrisi_poziv_pdf') && ! empty($turnir->poziv_pdf_path)) {
            if (Storage::disk('public')->exists($turnir->poziv_pdf_path)) {
                Storage::disk('public')->delete($turnir->poziv_pdf_path);
            }
            $turnir->poziv_pdf_path = null;
        }

        if ($request->hasFile('poziv_pdf')) {
            if (! empty($turnir->poziv_pdf_path) && Storage::disk('public')->exists($turnir->poziv_pdf_path)) {
                Storage::disk('public')->delete($turnir->poziv_pdf_path);
            }

            $nazivDatoteke = now()->format('Ymd_His').'_'.Str::random(8).'.pdf';
            $path = $request->file('poziv_pdf')->storeAs('pozivi-turnira/'.(int) $turnir->id, $nazivDatoteke, 'public');
            $turnir->poziv_pdf_path = $path;
        }

        $turnir->save();
        $this->syncKotizacijeZaTurnir($turnir, (int) auth()->id());
    }

    /**
     * Sinkronizira zaduženja kotizacija za sve aktivne prijave turnira.
     */
    private function syncKotizacijeZaTurnir(NadolazeciTurnir $turnir, int $adminUserId): void
    {
        $turnir->loadMissing([
            'prijave' => fn ($query) => $query
                ->where('status', PrijavaTurnira::STATUS_ACTIVE)
                ->with(['clan', 'turnir', 'paymentCharge']),
        ]);

        foreach ($turnir->prijave as $prijava) {
            $this->syncKotizacijaZaPrijavu($prijava, $turnir, $adminUserId);
        }
    }

    /**
     * Vraća postojeći turnir rezultata po ključnim podacima ili kreira novi zapis iz nadolazećeg turnira.
     */
    private function pronadiIliKreirajRezultatskiTurnir(NadolazeciTurnir $nadolazeciTurnir): Turniri
    {
        $datumTurnira = $nadolazeciTurnir->datum?->toDateString();
        if (! is_string($datumTurnira) || trim($datumTurnira) === '') {
            throw new \RuntimeException('Nedostaje datum nadolazećeg turnira.');
        }

        $postojeci = Turniri::query()
            ->whereDate('datum', $datumTurnira)
            ->where('naziv', (string) $nadolazeciTurnir->naziv)
            ->where('lokacija', (string) $nadolazeciTurnir->mjesto)
            ->where('tipovi_turnira_id', (int) $nadolazeciTurnir->tipovi_turnira_id)
            ->first();
        if ($postojeci instanceof Turniri) {
            return $postojeci;
        }

        $turnir = new Turniri();
        $turnir->datum = $datumTurnira;
        $turnir->naziv = (string) $nadolazeciTurnir->naziv;
        $turnir->lokacija = (string) $nadolazeciTurnir->mjesto;
        $turnir->tipovi_turnira_id = (int) $nadolazeciTurnir->tipovi_turnira_id;
        $turnir->eliminacije = false;
        $turnir->save();

        return $turnir;
    }

    /**
     * Sortira prijave za inicijalni unos rezultata: stil (CU/RB/BB/TB/LB), kategorija, prezime i ime.
     */
    private function sortirajPrijaveZaKreiranjeRezultata(Collection $prijave): Collection
    {
        return $prijave
            ->filter(function ($prijava): bool {
                if (! $prijava instanceof PrijavaTurnira) {
                    return false;
                }

                return $prijava->status === PrijavaTurnira::STATUS_ACTIVE
                    && $prijava->clan instanceof Clanovi
                    && $prijava->kategorija instanceof Kategorije
                    && $prijava->stil instanceof Stilovi;
            })
            ->sortBy(function (PrijavaTurnira $prijava): array {
                $stilNaziv = (string) ($prijava->stil?->naziv ?? '');
                $kategorijaNaziv = (string) ($prijava->kategorija?->naziv ?? '');
                $prezime = (string) ($prijava->clan?->Prezime ?? '');
                $ime = (string) ($prijava->clan?->Ime ?? '');

                return [
                    $this->prioritetStila($stilNaziv),
                    Str::ascii(mb_strtolower($stilNaziv, 'UTF-8')),
                    Str::ascii(mb_strtolower($kategorijaNaziv, 'UTF-8')),
                    Str::ascii(mb_strtolower($prezime, 'UTF-8')),
                    Str::ascii(mb_strtolower($ime, 'UTF-8')),
                    (int) $prijava->id,
                ];
            })
            ->values();
    }

    /**
     * Kreira ili ažurira zaduženje kotizacije za jednu prijavu.
     */
    private function syncKotizacijaZaPrijavu(PrijavaTurnira $prijava, NadolazeciTurnir $turnir, int $actorUserId): void
    {
        $prijava->loadMissing(['clan', 'turnir', 'paymentCharge']);

        if (! $turnir->trebaKotizacijaNaRacun()) {
            $this->ukloniKotizacijuAkoMoguce($prijava, $actorUserId);

            return;
        }

        $clan = $prijava->clan;
        if (! ($clan instanceof Clanovi)) {
            return;
        }

        $periodKey = 'turnir-'.(int) $turnir->id;
        $charge = $prijava->paymentCharge;
        if (! ($charge instanceof ClanPaymentCharge)) {
            $charge = ClanPaymentCharge::query()
                ->where('clan_id', (int) $clan->id)
                ->where('source', PaymentTrackingService::SOURCE_TOURNAMENT_FEE)
                ->where('period_key', $periodKey)
                ->first();
        }

        if (! ($charge instanceof ClanPaymentCharge)) {
            $charge = new ClanPaymentCharge;
            $charge->clan_id = (int) $clan->id;
            $charge->source = PaymentTrackingService::SOURCE_TOURNAMENT_FEE;
            $charge->period_key = $periodKey;
            $charge->currency = 'EUR';
            $charge->status = PaymentTrackingService::STATUS_OPEN;
            $charge->created_by = $actorUserId > 0 ? $actorUserId : null;
        }

        $charge->title = 'Kotizacija - '.$turnir->naziv;
        $charge->description = 'Kotizacija za turnir '.$turnir->naziv.' ('.$turnir->mjesto.', '.$turnir->datumRasponLabel().')';
        $charge->amount = round((float) $turnir->kotizacija_iznos, 2);
        $charge->due_date = $turnir->kotizacija_rok_uplate?->toDateString();
        $charge->period_start = $charge->due_date;
        $charge->period_end = $charge->due_date;

        if ($charge->status === PaymentTrackingService::STATUS_DELETED) {
            $charge->status = PaymentTrackingService::STATUS_OPEN;
            $charge->paid_at = null;
            $charge->confirmed_by = null;
        }

        $metadata = is_array($charge->metadata) ? $charge->metadata : [];
        $metadata['tournament_id'] = (int) $turnir->id;
        $metadata['tournament_name'] = (string) $turnir->naziv;
        $metadata['tournament_place'] = (string) $turnir->mjesto;
        $metadata['tournament_date'] = $turnir->datum?->toDateString();
        $metadata['registration_id'] = (int) $prijava->id;
        $metadata['hub_description'] = $this->opisKotizacijeZaHub($clan, $turnir);
        $charge->metadata = $metadata;
        $charge->updated_by = $actorUserId > 0 ? $actorUserId : $charge->updated_by;
        $charge->save();

        if ((int) ($prijava->clan_payment_charge_id ?? 0) !== (int) $charge->id) {
            $prijava->clan_payment_charge_id = (int) $charge->id;
            $prijava->save();
        }
    }

    /**
     * Uklanja zaduženje kotizacije ako je stavka još otvorena.
     */
    private function ukloniKotizacijuAkoMoguce(PrijavaTurnira $prijava, int $actorUserId): void
    {
        $prijava->loadMissing('paymentCharge');
        $charge = $prijava->paymentCharge;
        if ($charge instanceof ClanPaymentCharge) {
            if ($charge->status === PaymentTrackingService::STATUS_OPEN) {
                $charge->status = PaymentTrackingService::STATUS_DELETED;
                $charge->paid_at = null;
                $charge->confirmed_by = null;
                $charge->updated_by = $actorUserId > 0 ? $actorUserId : $charge->updated_by;
                $charge->save();
            }

            $prijava->clan_payment_charge_id = null;
            $prijava->save();

            return;
        }

        $periodKey = 'turnir-'.(int) $prijava->nadolazeci_turnir_id;
        $postojeca = ClanPaymentCharge::query()
            ->where('clan_id', (int) $prijava->clan_id)
            ->where('source', PaymentTrackingService::SOURCE_TOURNAMENT_FEE)
            ->where('period_key', $periodKey)
            ->where('status', PaymentTrackingService::STATUS_OPEN)
            ->first();

        if ($postojeca instanceof ClanPaymentCharge) {
            $postojeca->status = PaymentTrackingService::STATUS_DELETED;
            $postojeca->updated_by = $actorUserId > 0 ? $actorUserId : $postojeca->updated_by;
            $postojeca->save();
        }
    }

    /**
     * Slaže opis plaćanja za HUB-3A kod kotizacije.
     */
    private function opisKotizacijeZaHub(Clanovi $clan, NadolazeciTurnir $turnir): string
    {
        $imePrezime = trim((string) $clan->Ime.' '.(string) $clan->Prezime);
        $datum = $turnir->datumRasponLabel();

        return 'Kotizacija za: '.$imePrezime.'; za turnir: '.$turnir->naziv
            .' u '.$turnir->mjesto.' dana '.$datum;
    }

    /**
     * Vraća stilove za prijavu u unaprijed definiranom redoslijedu.
     */
    private function stiloviZaPrijavu(): Collection
    {
        return Stilovi::query()
            ->where('id', '!=', self::STANDARDNI_LUK_STIL_ID)
            ->get(['id', 'naziv'])
            ->sortBy(function (Stilovi $stil): array {
                $naziv = (string) $stil->naziv;

                return [
                    $this->prioritetStila($naziv),
                    Str::ascii(mb_strtolower($naziv, 'UTF-8')),
                ];
            })
            ->values();
    }

    /**
     * Vraća prioritet stila prema oznakama (CU, RB, BB, TB, LB).
     */
    private function prioritetStila(string $naziv): int
    {
        $upper = mb_strtoupper($naziv, 'UTF-8');
        foreach (self::STIL_REDOSLIJED_KODOVA as $index => $kod) {
            if (str_contains($upper, '('.$kod.')')) {
                return $index;
            }
        }

        return count(self::STIL_REDOSLIJED_KODOVA) + 100;
    }

    /**
     * Vraća metapodatke članova potrebne za automatski odabir kategorije na formi.
     *
     * @return array<int, array{spol: string, datum_rodjenja: ?string}>
     */
    private function clanoviMetaZaKategoriju(Collection $clanovi): array
    {
        $meta = [];
        foreach ($clanovi as $clan) {
            if (! ($clan instanceof Clanovi)) {
                continue;
            }

            $meta[(int) $clan->id] = [
                'spol' => $this->normalizirajSpol((string) $clan->spol),
                'datum_rodjenja' => $this->normalizirajDatumZaUsporedbu($clan->datum_rodjenja ?? null),
            ];
        }

        return $meta;
    }

    /**
     * Određuje termin prijave: smjena (jednodnevni) ili odabrani dan (višednevni).
     *
     * @return array{smjena: ?string, odabrani_dan: ?string}
     */
    private function odrediTerminPrijave(NadolazeciTurnir $turnir, mixed $smjena, mixed $odabraniDan): array
    {
        if ($this->turnirJeVisednevni($turnir)) {
            return [
                'smjena' => null,
                'odabrani_dan' => $this->normalizirajOdabraniDan($turnir, $odabraniDan),
            ];
        }

        return [
            'smjena' => $this->normalizirajSmjenu(is_string($smjena) ? $smjena : null),
            'odabrani_dan' => null,
        ];
    }

    /**
     * Provjerava je li turnir višednevni (datum_do > datum).
     */
    private function turnirJeVisednevni(NadolazeciTurnir $turnir): bool
    {
        $start = $turnir->datum;
        $end = $turnir->datum_do;

        return $start instanceof CarbonInterface
            && $end instanceof CarbonInterface
            && $end->gt($start);
    }

    /**
     * Vraća opcije dana za višednevni turnir.
     *
     * @return array<int, array{value: string, label: string}>
     */
    private function odabirDanaOpcijeZaTurnir(NadolazeciTurnir $turnir): array
    {
        if (! $this->turnirJeVisednevni($turnir)) {
            return [];
        }

        $vrijednosti = array_values(array_unique(array_filter([
            $turnir->datum?->toDateString(),
            $turnir->datum_do?->toDateString(),
        ])));

        $opcije = [];
        foreach ($vrijednosti as $vrijednost) {
            try {
                $opcije[] = [
                    'value' => (string) $vrijednost,
                    'label' => Carbon::parse((string) $vrijednost)->format('d.m.Y.'),
                ];
            } catch (Throwable) {
                continue;
            }
        }

        return $opcije;
    }

    /**
     * Validira i normalizira odabrani dan za višednevni turnir.
     */
    private function normalizirajOdabraniDan(NadolazeciTurnir $turnir, mixed $odabraniDan): ?string
    {
        $tekst = trim((string) $odabraniDan);
        if ($tekst === '' || mb_strtolower($tekst, 'UTF-8') === 'nebitno') {
            return null;
        }

        try {
            $datum = Carbon::parse($tekst)->toDateString();
        } catch (Throwable) {
            throw ValidationException::withMessages([
                'odabrani_dan' => 'Odabrani dan nije valjan.',
            ]);
        }

        $dozvoljeniDatumi = collect($this->odabirDanaOpcijeZaTurnir($turnir))
            ->pluck('value')
            ->all();
        if (! in_array($datum, $dozvoljeniDatumi, true)) {
            throw ValidationException::withMessages([
                'odabrani_dan' => 'Odabrani dan mora biti prvi ili drugi dan turnira.',
            ]);
        }

        return $datum;
    }

    /**
     * Normalizira datum u ISO oblik (Y-m-d) za usporedbe u frontend logici.
     */
    private function normalizirajDatumZaUsporedbu(mixed $vrijednost): ?string
    {
        if ($vrijednost instanceof CarbonInterface) {
            return $vrijednost->toDateString();
        }

        $tekst = trim((string) $vrijednost);
        if ($tekst === '') {
            return null;
        }

        try {
            return Carbon::parse($tekst)->toDateString();
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Vraća mapu kategorija po članu uz filtriranje po spolu.
     */
    private function kategorijePoClanu(Collection $clanovi): array
    {
        $kategorije = Kategorije::query()
            ->orderBy('naziv')
            ->get(['id', 'naziv', 'spol']);

        $poSpolu = [
            'M' => [],
            'Z' => [],
        ];
        foreach ($kategorije as $kategorija) {
            $spol = $this->normalizirajSpol((string) $kategorija->spol);
            if (! isset($poSpolu[$spol])) {
                continue;
            }
            $poSpolu[$spol][] = [
                'id' => (int) $kategorija->id,
                'naziv' => (string) $kategorija->naziv,
            ];
        }

        $rezultat = [];
        foreach ($clanovi as $clan) {
            if (! ($clan instanceof Clanovi)) {
                continue;
            }

            $spol = $this->normalizirajSpol((string) $clan->spol);
            $rezultat[(int) $clan->id] = $poSpolu[$spol] ?? [];
        }

        return $rezultat;
    }

    /**
     * Vraća filtriranu listu kategorija za jednog člana.
     */
    private function kategorijeZaClana(Clanovi $clan): Collection
    {
        $spol = $this->normalizirajSpol((string) $clan->spol);

        return Kategorije::query()
            ->orderBy('naziv')
            ->get(['id', 'naziv', 'spol'])
            ->filter(function (Kategorije $kategorija) use ($spol): bool {
                return $this->normalizirajSpol((string) $kategorija->spol) === $spol;
            })
            ->values();
    }

    /**
     * Provjerava odgovara li odabrana kategorija spolu člana.
     */
    private function kategorijaOdgovaraSpoluClana(Clanovi $clan, Kategorije $kategorija): bool
    {
        return $this->normalizirajSpol((string) $clan->spol) === $this->normalizirajSpol((string) $kategorija->spol);
    }

    /**
     * Normalizira oznaku spola na M ili Z.
     */
    private function normalizirajSpol(?string $spol): string
    {
        $vrijednost = Str::ascii(mb_strtoupper(trim((string) $spol), 'UTF-8'));
        if (str_starts_with($vrijednost, 'M')) {
            return 'M';
        }
        if (str_starts_with($vrijednost, 'Z')) {
            return 'Z';
        }

        return '';
    }

    /**
     * Vraća zadani član_id za prijavu na temelju korisnika i dostupnih članova.
     */
    private function zadaniClanIdZaPrijavu(User $korisnik, Collection $clanoviZaPrijavu): int
    {
        $oldClanId = (int) old('clan_id', 0);
        if ($oldClanId > 0) {
            return $oldClanId;
        }

        if (! $korisnik->jeRoditelj()) {
            return (int) ($korisnik->clan_id ?? 0);
        }

        return (int) ($clanoviZaPrijavu->first()?->id ?? 0);
    }

    /**
     * Određuje član_id za prijavu na temelju pravila roditelj/ne-roditelj.
     */
    private function odrediClanIdZaPrijavu(User $korisnik, int $clanIdIzZahtjeva): int
    {
        if (! $korisnik->jeRoditelj()) {
            $vlastitiClanId = (int) ($korisnik->clan_id ?? 0);
            if ($vlastitiClanId <= 0) {
                return 0;
            }

            if ($clanIdIzZahtjeva > 0 && $clanIdIzZahtjeva !== $vlastitiClanId) {
                abort(403);
            }

            return $vlastitiClanId;
        }

        return $clanIdIzZahtjeva > 0 ? $clanIdIzZahtjeva : 0;
    }

    /**
     * Validira i normalizira odabranu smjenu za prijavu.
     */
    private function normalizirajSmjenu(?string $smjena): string
    {
        $vrijednost = mb_strtolower(trim((string) $smjena), 'UTF-8');
        foreach (self::SMJENE as $opcija) {
            if ($vrijednost === $opcija) {
                return $opcija;
            }
        }

        return 'nebitno';
    }

    /**
     * Vraća razlog zbog kojeg turnir nije dostupan za prijavu.
     */
    private function porukaZaNedostupanTurnir(NadolazeciTurnir $turnir, bool $primijeniPravilo60Dana): ?string
    {
        if ($primijeniPravilo60Dana) {
            $danas = now()->startOfDay();
            $maxDatum = now()->addDays(self::VIDLJIVOST_DANA_ZA_PRIJAVU)->endOfDay();
            if ($turnir->datum === null || $turnir->datum->lte($danas) || $turnir->datum->gt($maxDatum)) {
                return 'Turnir nije u rasponu dostupnom za prijavu ('.self::VIDLJIVOST_DANA_ZA_PRIJAVU.' dana).';
            }
        }

        if ($turnir->prijaveZakljucane()) {
            return 'Prijave za odabrani turnir su zaključane.';
        }

        return null;
    }

    /**
     * Briše prošle turnire (datum početka danas ili ranije) koji nemaju aktivnih prijava.
     */
    private function obrisiProsleTurnireBezAktivnihPrijava(string $danas): void
    {
        $turniriZaBrisanje = NadolazeciTurnir::query()
            ->whereDate('datum', '<=', $danas)
            ->whereDoesntHave('prijave', fn ($query) => $query->where('status', PrijavaTurnira::STATUS_ACTIVE))
            ->get();

        foreach ($turniriZaBrisanje as $turnir) {
            if (! empty($turnir->poziv_pdf_path) && Storage::disk('public')->exists($turnir->poziv_pdf_path)) {
                Storage::disk('public')->delete($turnir->poziv_pdf_path);
            }
            $turnir->delete();
        }
    }

    /**
     * Vraća upozorenje o liječničkom pregledu za turnir ili null ako je sve u redu.
     */
    private function lijecnickoUpozorenje(Clanovi $clan, NadolazeciTurnir $turnir): ?string
    {
        $datumTurnira = $turnir->datum;
        if ($datumTurnira === null) {
            return null;
        }

        if (empty($clan->lijecnicki_do)) {
            return 'Član nema evidentiran važeći liječnički pregled. Potrebno je obaviti pregled prije turnira i dostaviti dokument klubu.';
        }

        try {
            $vrijediDo = Carbon::parse((string) $clan->lijecnicki_do)->endOfDay();
        } catch (Throwable) {
            return 'Liječnički pregled člana nije valjano evidentiran. Potrebno je provjeriti dokumentaciju prije turnira.';
        }

        if ($vrijediDo->lt($datumTurnira->copy()->startOfDay())) {
            return 'Liječnički pregled istječe '.$vrijediDo->format('d.m.Y.').'. - prije turnira. Potrebno je obaviti novi pregled i dostaviti dokument klubu.';
        }

        return null;
    }

    /**
     * Vraća mapu liječničkih upozorenja za listu prijava.
     */
    private function lijecnickiUpozorenjaZaPrijave(Collection $prijave): array
    {
        $upozorenja = [];
        foreach ($prijave as $prijava) {
            if (! ($prijava instanceof PrijavaTurnira)) {
                continue;
            }

            $clan = $prijava->clan;
            $turnir = $prijava->turnir;
            if (! ($clan instanceof Clanovi) || ! ($turnir instanceof NadolazeciTurnir)) {
                continue;
            }

            $warning = $this->lijecnickoUpozorenje($clan, $turnir);
            if ($warning !== null) {
                $upozorenja[(int) $prijava->id] = $warning;
            }
        }

        return $upozorenja;
    }

    /**
     * Normalizira tekstualni unos.
     */
    private function normalizirajTekst(mixed $vrijednost): ?string
    {
        $tekst = trim((string) $vrijednost);

        return $tekst === '' ? null : $tekst;
    }

    /**
     * Normalizira brojčani iznos iz forme.
     */
    private function normalizirajIznos(mixed $vrijednost): ?float
    {
        $tekst = trim((string) $vrijednost);
        if ($tekst === '') {
            return null;
        }

        $tekst = str_replace([' ', ','], ['', '.'], $tekst);
        if (! is_numeric($tekst)) {
            return null;
        }

        return round((float) $tekst, 2);
    }

    /**
     * Vraća odabrana CSV polja za export prijava turnira.
     *
     * @return array<int, string>
     */
    private function odabranaCsvPoljaPrijava(Request $request): array
    {
        $trazenaPolja = array_map('strval', (array) $request->query('fields', []));
        $odabranaPolja = [];

        foreach (array_keys(self::CSV_EXPORT_POLJA_PRIJAVA) as $polje) {
            if (in_array($polje, $trazenaPolja, true)) {
                $odabranaPolja[] = $polje;
            }
        }

        if (count($odabranaPolja) === 0) {
            return self::CSV_EXPORT_ZADANA_POLJA_PRIJAVA;
        }

        return $odabranaPolja;
    }

    /**
     * Vraća zaglavlja CSV datoteke za export prijava turnira.
     *
     * @param  array<int, string>  $odabranaPolja
     * @return array<int, string>
     */
    private function csvZaglavljaPrijava(array $odabranaPolja): array
    {
        $zaglavlja = [];
        foreach ($odabranaPolja as $polje) {
            $zaglavlja[] = self::CSV_EXPORT_POLJA_PRIJAVA[$polje] ?? $polje;
        }

        return $zaglavlja;
    }

    /**
     * Vraća jednu vrijednost odabranog polja za CSV red prijave turnira.
     */
    private function vrijednostPoljaPrijaveZaCsv(string $polje, PrijavaTurnira $prijava): string
    {
        $clan = $prijava->clan;

        return match ($polje) {
            'ime' => trim((string) ($clan?->Ime ?? '')),
            'prezime' => trim((string) ($clan?->Prezime ?? '')),
            'datum_rodjenja' => $this->formatirajDatumZaCsv($clan?->datum_rodjenja ?? null),
            'broj_licence' => $this->formatirajTekstualnoPoljeZaCsv($clan?->broj_licence ?? null),
            'lijecnicki_do' => $this->formatirajDatumZaCsv($clan?->lijecnicki_do ?? null),
            'stil' => trim((string) ($prijava->stil?->naziv ?? '')),
            'kategorija' => trim((string) ($prijava->kategorija?->naziv ?? '')),
            'oib' => $this->formatirajTekstualnoPoljeZaCsv($clan?->oib ?? null),
            'smjena' => $prijava->terminPrijaveLabel() === 'nebitno' ? '' : trim((string) $prijava->terminPrijaveLabel()),
            'kup' => $prijava->sudjelujem_u_kupu ? 'DA' : '',
            default => '',
        };
    }

    /**
     * Formatira datum u d.m.Y. oblik za CSV.
     */
    private function formatirajDatumZaCsv(mixed $vrijednost): string
    {
        if ($vrijednost instanceof Carbon) {
            return $vrijednost->format('d.m.Y.');
        }

        $tekst = trim((string) $vrijednost);
        if ($tekst === '') {
            return '';
        }

        try {
            return Carbon::parse($tekst)->format('d.m.Y.');
        } catch (Throwable) {
            return '';
        }
    }

    /**
     * Formatira tekstualno polje kao tekst za CSV (sprječava gubitak vodećih nula).
     */
    private function formatirajTekstualnoPoljeZaCsv(mixed $vrijednost): string
    {
        $tekst = trim((string) $vrijednost);
        if ($tekst === '') {
            return '';
        }

        return "\t".$tekst;
    }
}
