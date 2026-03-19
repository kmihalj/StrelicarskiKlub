<?php

namespace App\Http\Controllers;

use DateTimeInterface;
use App\Models\Clanci;
use App\Models\ClanDokument;
use App\Models\ClanLijecnickiPregled;
use App\Models\Clanovi;
use App\Models\Kategorije;
use App\Models\Klub;
use App\Models\PolaznikSkole;
use App\Models\RezultatiOpci;
use App\Models\RezultatiPoTipuTurnira;
use App\Models\RezultatiTim;
use App\Models\Stilovi;
use App\Models\TipoviTurnira;
use App\Models\Turniri;
use App\Services\PaymentTrackingService;
use App\Services\SchoolPaymentService;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Javni i korisnički kontroler za naslovnicu, profile članova, rezultate, treninge i preuzimanje dokumenata.
 */
class JavnoController extends Controller
{
    private const CSV_OPTIONAL_POLJA = [
        'phone',
        'email',
        'license_number',
        'member_since',
        'club_function',
        'last_medical_duration',
        'tournaments_total',
        'tournaments_year',
        'medals_total',
        'medals_gold_total',
        'medals_silver_total',
        'medals_bronze_total',
        'medals_year',
        'medals_gold_year',
        'medals_silver_year',
        'medals_bronze_year',
    ];

    private const CSV_GODISNJA_POLJA = [
        'tournaments_year',
        'medals_year',
        'medals_gold_year',
        'medals_silver_year',
        'medals_bronze_year',
    ];

    private const CSV_STAT_POLJA = [
        'tournaments_total',
        'tournaments_year',
        'medals_total',
        'medals_gold_total',
        'medals_silver_total',
        'medals_bronze_total',
        'medals_year',
        'medals_gold_year',
        'medals_silver_year',
        'medals_bronze_year',
    ];


    /**
     *
     * @noinspection PhpMissingReturnTypeInspection
     *
     * stavka u meniju "REZULTATI"
     */
    public function prikazRezultata(Request $request)
    {
        $with = [
            'tipTurnira.polja',
            'rezultatiOpci.clan',
            'rezultatiOpci.stil',
            'rezultatiOpci.kategorija',
            'rezultatiPoTipuTurnira',
            'mediji',
        ];

        if ($this->timskeTabliceDostupne()) {
            $with[] = 'rezultatiTimovi.stil';
            $with[] = 'rezultatiTimovi.kategorija';
            $with[] = 'rezultatiTimovi.clanoviStavke.rezultatOpci.clan';
        }

        $turniri = Turniri::with($with)->orderByDesc('datum')->paginate(10)->withQueryString();
        $tipoviTurnira = TipoviTurnira::orderBy('naziv')->get();
        $trenutnaGodina = (int)date('Y');
        $proslaGodina = $trenutnaGodina - 1;

        $dostupneGodine = Turniri::query()
            ->selectRaw('YEAR(datum) as godina')
            ->whereNotNull('datum')
            ->distinct()
            ->orderByDesc('godina')
            ->pluck('godina')
            ->map(static fn ($godina): int => (int)$godina)
            ->merge([$trenutnaGodina, $proslaGodina])
            ->unique()
            ->sortDesc()
            ->values();

        $odabranaGodina = (int)$request->query('godina', $proslaGodina);
        if (!$dostupneGodine->contains($odabranaGodina)) {
            $odabranaGodina = $trenutnaGodina;
        }

        $godineZaStatistiku = collect([$trenutnaGodina, $proslaGodina, $odabranaGodina])->unique()->values();
        $statistika = [];
        $statistikaGodine = [];

        foreach ($godineZaStatistiku as $godina) {
            $godinaStat = $this->izracunajGodisnjuStatistiku((int)$godina);
            $statistikaGodine[(int)$godina] = $godinaStat;
            $statistika[(int)$godina][1] = $godinaStat['zlato'];
            $statistika[(int)$godina][2] = $godinaStat['srebro'];
            $statistika[(int)$godina][3] = $godinaStat['bronca'];
        }

        return view('javno.rezultati', [
            'turniri' => $turniri,
            'statistika' => $statistika,
            'statistikaGodine' => $statistikaGodine,
            'detaljnaStatistikaGodine' => $statistikaGodine[$odabranaGodina] ?? $this->izracunajGodisnjuStatistiku($odabranaGodina),
            'odabranaGodinaStatistike' => $odabranaGodina,
            'dostupneGodineStatistike' => $dostupneGodine,
            'trenutnaGodina' => $trenutnaGodina,
            'proslaGodina' => $proslaGodina,
            'tipoviTurnira' => $tipoviTurnira,
        ]);
    }


    /** @noinspection PhpMissingReturnTypeInspection
     */
    public function naslovnaStranica()
    {
        $klub = Klub::first();
        $paymentService = app(PaymentTrackingService::class);
        $schoolPaymentService = app(SchoolPaymentService::class);
        $paymentTrackingEnabled = $paymentService->isEnabled();
        $schoolPaymentEnabled = $schoolPaymentService->isEnabled();
        $withTurniri = [
            'tipTurnira.polja',
            'rezultatiOpci.clan',
            'rezultatiOpci.stil',
            'rezultatiOpci.kategorija',
            'rezultatiPoTipuTurnira',
            'mediji',
        ];
        if ($this->timskeTabliceDostupne()) {
            $withTurniri[] = 'rezultatiTimovi.stil';
            $withTurniri[] = 'rezultatiTimovi.kategorija';
            $withTurniri[] = 'rezultatiTimovi.clanoviStavke.rezultatOpci.clan';
        }
        $turniri = Turniri::with($withTurniri)->orderByDesc('datum')->take(5)->get();
        $clanciNaslovnica = Clanci::where('vrsta', '=', 'Naslovnica')->orderByDesc('datum')->get();
        $danas = now();
        $rodendaniDanas = Clanovi::query()
            ->where('aktivan', true)
            ->whereNotNull('datum_rodjenja')
            ->whereMonth('datum_rodjenja', $danas->month)
            ->whereDay('datum_rodjenja', $danas->day)
            ->orderBy('Prezime')
            ->orderBy('Ime')
            ->get(['id', 'Ime', 'Prezime']);
        $statusLijecnickiKorisnika = null;
        $statusSkolaKorisnika = null;
        $statusLijecnickiDjeca = collect();
        $statusSkolaDjeca = collect();
        $statusPlacanjaKorisnika = null;
        $statusPlacanjaDjeca = collect();

        if (auth()->check() && !empty(auth()->user()->clan_id)) {
            $clanKorisnika = Clanovi::query()
                ->where('id', auth()->user()->clan_id)
                ->first(['id', 'Ime', 'Prezime', 'lijecnicki_do']);

            if ($clanKorisnika) {
                $statusLijecnickiKorisnika = $this->pripremiStatusLijecnickogZaClana($clanKorisnika);

                if ($paymentTrackingEnabled) {
                    $placanjeSummary = $paymentService->memberSummary($clanKorisnika);
                    $placanjeVidljivo = $this->jePlacanjeVidljivo($placanjeSummary);

                    if ($placanjeVidljivo) {
                        $placanjeNotice = $paymentService->noticeForClan($clanKorisnika);
                        $statusLijecnickiKorisnika['paymentNotice'] = $placanjeNotice;
                        $statusPlacanjaKorisnika = [
                            'clan' => $clanKorisnika,
                            'notice' => $placanjeNotice,
                        ];
                    }
                }
            }
        }

        if (auth()->check() && !empty(auth()->user()->polaznik_id)) {
            $polaznikKorisnika = PolaznikSkole::query()
                ->with(['dolasci' => fn ($query) => $query->whereNotNull('datum')->orderByDesc('datum')])
                ->where('id', auth()->user()->polaznik_id)
                ->first(['id', 'Ime', 'Prezime', 'datum_rodjenja', 'datum_upisa']);

            if ($polaznikKorisnika) {
                $statusSkolaKorisnika = [
                    'polaznik' => $polaznikKorisnika,
                    'brojDolazaka' => $polaznikKorisnika->dolasci->count(),
                    'zadnjiDolazak' => $polaznikKorisnika->dolasci->first()?->datum,
                ];
                if ($schoolPaymentEnabled) {
                    $statusSkolaKorisnika['paymentNotice'] = $schoolPaymentService->noticeForPolaznik($polaznikKorisnika);
                }
            }
        }

        if (auth()->check() && auth()->user()->jeRoditelj()) {
            $roditelj = auth()->user()->loadMissing([
                'djecaClanovi:id,Ime,Prezime,lijecnicki_do',
                'djecaPolaznici' => fn ($query) => $query
                    ->select(['polaznici_skole.id', 'Ime', 'Prezime', 'datum_rodjenja', 'datum_upisa'])
                    ->with(['dolasci' => fn ($dolasci) => $dolasci->whereNotNull('datum')->orderByDesc('datum')]),
            ]);

            $statusLijecnickiDjeca = $roditelj->djecaClanovi
                ->map(function (Clanovi $clan) use ($paymentTrackingEnabled, $paymentService): array {
                    $status = $this->pripremiStatusLijecnickogZaClana($clan);
                    if ($paymentTrackingEnabled) {
                        $placanjeSummary = $paymentService->memberSummary($clan);
                        if ($this->jePlacanjeVidljivo($placanjeSummary)) {
                            $status['paymentNotice'] = $paymentService->noticeForClan($clan);
                        }
                    }

                    return $status;
                })
                ->values();

            $statusSkolaDjeca = $roditelj->djecaPolaznici
                ->map(function (PolaznikSkole $polaznik) use ($schoolPaymentEnabled, $schoolPaymentService): array {
                    $status = [
                        'polaznik' => $polaznik,
                        'brojDolazaka' => $polaznik->dolasci->count(),
                        'zadnjiDolazak' => $polaznik->dolasci->first()?->datum,
                    ];

                    if ($schoolPaymentEnabled) {
                        $status['paymentNotice'] = $schoolPaymentService->noticeForPolaznik($polaznik);
                    }

                    return $status;
                })
                ->values();

            if ($paymentTrackingEnabled) {
                $statusPlacanjaDjeca = $roditelj->djecaClanovi
                    ->map(function (Clanovi $clan) use ($paymentService): array {
                        $placanjeSummary = $paymentService->memberSummary($clan);

                        return [
                            'clan' => $clan,
                            'notice' => $this->jePlacanjeVidljivo($placanjeSummary)
                                ? $paymentService->noticeForClan($clan)
                                : null,
                        ];
                    })
                    ->filter(fn (array $status): bool => !empty($status['notice']))
                    ->values();
            }
        }

        $stavkeRezultataIClanaka = collect($turniri->map(fn (Turniri $turnir) => [
            'tip' => 'turnir',
            'datum' => $turnir->datum,
            'model' => $turnir,
        ])->all());

        if ($turniri->isNotEmpty()) {
            $najstarijiDatumTurnira = $turniri->min('datum');
            $danasnjiDatum = now()->toDateString();

            $clanciIzmedjuTurnira = Clanci::whereIn('vrsta', ['Streličarstvo', 'O nama', 'Obavijest'])
                ->whereDate('datum', '>=', $najstarijiDatumTurnira)
                ->whereDate('datum', '<=', $danasnjiDatum)
                ->with('mediji')
                ->orderByDesc('datum')
                ->get();

            $stavkeRezultataIClanaka = $stavkeRezultataIClanaka->concat(
                $clanciIzmedjuTurnira->map(fn (Clanci $clanak) => [
                    'tip' => 'clanak',
                    'datum' => $clanak->datum,
                    'model' => $clanak,
                ])->all()
            );
        }

        $stavkeRezultataIClanaka = $stavkeRezultataIClanaka->sortByDesc('datum')->values();

        return view('javno.naslovnaStranica', [
            'turniri' => $turniri,
            'klub' => $klub,
            'clanciNaslovnica' => $clanciNaslovnica,
            'rodendaniDanas' => $rodendaniDanas,
            'statusLijecnickiKorisnika' => $statusLijecnickiKorisnika,
            'statusSkolaKorisnika' => $statusSkolaKorisnika,
            'statusLijecnickiDjeca' => $statusLijecnickiDjeca,
            'statusSkolaDjeca' => $statusSkolaDjeca,
            'statusPlacanjaKorisnika' => $statusPlacanjaKorisnika,
            'statusPlacanjaDjeca' => $statusPlacanjaDjeca,
            'paymentTrackingEnabled' => $paymentTrackingEnabled,
            'stavkeRezultataIClanaka' => $stavkeRezultataIClanaka,
        ]);
    }

    public function popisClanova(): Renderable
    {
        $paymentService = app(PaymentTrackingService::class);
        $clanovi = Clanovi::orderBy('Prezime')->orderBy('Ime')->get();
        $dostupneGodineCsv = Turniri::query()
            ->selectRaw('YEAR(datum) as godina')
            ->whereNotNull('datum')
            ->distinct()
            ->orderByDesc('godina')
            ->pluck('godina')
            ->map(static fn ($godina): int => (int)$godina)
            ->filter(static fn (int $godina): bool => $godina > 0)
            ->values();

        if ($dostupneGodineCsv->isEmpty()) {
            $dostupneGodineCsv = collect([(int)date('Y')]);
        }

        $zadanaGodinaCsv = (int)$dostupneGodineCsv->first();
        $showPaymentColumn = auth()->check()
            && (int)auth()->user()->rola === 1
            && $paymentService->isEnabled();

        $paymentStatusByClan = [];
        if ($showPaymentColumn) {
            $paymentStatusByClan = $paymentService->listStatusForClanIds($clanovi->pluck('id')->all());
        }

        return view('javno.clanovi', [
            'clanovi' => $clanovi,
            'paymentTrackingEnabled' => $paymentService->isEnabled(),
            'showPaymentColumn' => $showPaymentColumn,
            'paymentStatusByClan' => $paymentStatusByClan,
            'dostupneGodineCsv' => $dostupneGodineCsv,
            'zadanaGodinaCsv' => $zadanaGodinaCsv,
        ]);
    }

    /**
     * Izvozi CSV popis aktivnih članova kluba s podacima za administrativni rad.
     */
    public function exportAktivnihClanovaCsv(Request $request): StreamedResponse
    {
        if (!auth()->check() || (int)auth()->user()->rola !== 1) {
            abort(403);
        }

        $odabranaPolja = $this->odabranaCsvPolja($request);
        $imaGodisnjaPolja = $this->imaGodisnjaCsvPolja($odabranaPolja);
        $godineStatistike = $imaGodisnjaPolja ? $this->odrediGodineCsvStatistike($request) : [];

        $clanovi = Clanovi::query()
            ->with(['funkcijeUklubu' => fn ($query) => $query->orderBy('redniBroj')->orderBy('id')])
            ->where('aktivan', true)
            ->orderBy('Prezime')
            ->orderBy('Ime')
            ->get([
                'id',
                'Prezime',
                'Ime',
                'oib',
                'spol',
                'datum_rodjenja',
                'br_telefona',
                'email',
                'broj_licence',
                'clan_od',
                'datum_pocetka_clanstva',
                'lijecnicki_do',
            ]);

        $trebaStatistiku = count(array_intersect($odabranaPolja, self::CSV_STAT_POLJA)) > 0;
        $statistika = $trebaStatistiku
            ? $this->pripremiCsvStatistikuClanova($clanovi->pluck('id')->map(static fn ($id): int => (int)$id)->all())
            : [];
        $zaglavlja = $this->csvZaglavlja($odabranaPolja, $godineStatistike);
        $nazivDatoteke = 'aktivni_clanovi_' . date('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($clanovi, $odabranaPolja, $godineStatistike, $statistika, $zaglavlja) {
            $izlaz = fopen('php://output', 'wb');

            if ($izlaz === false) {
                return;
            }

            fwrite($izlaz, "\xEF\xBB\xBF");
            fputcsv($izlaz, $zaglavlja, ';');

            foreach ($clanovi as $clan) {
                $red = [
                    (string)$clan->Prezime,
                    (string)$clan->Ime,
                    $this->formatirajOibZaCsv($clan->oib),
                    $this->mapirajSpolZaCsv($clan->spol),
                    empty($clan->datum_rodjenja) ? '' : date('d.m.Y.', strtotime((string)$clan->datum_rodjenja)),
                ];

                $this->dodajCsvOpcionalnaPolja($red, $clan, $odabranaPolja, $godineStatistike, $statistika);
                fputcsv($izlaz, $red, ';');
            }

            fclose($izlaz);
        }, $nazivDatoteke, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Normalizira i validira listu opcionalnih CSV polja iz korisnickog zahtjeva.
     *
     * @return array<int, string>
     */
    private function odabranaCsvPolja(Request $request): array
    {
        $trazenaPolja = array_map('strval', (array)$request->query('fields', []));
        $odabranaPolja = [];

        foreach (self::CSV_OPTIONAL_POLJA as $polje) {
            if (in_array($polje, $trazenaPolja, true)) {
                $odabranaPolja[] = $polje;
            }
        }

        return $odabranaPolja;
    }

    /**
     * Provjerava treba li primijeniti odabranu godinu za CSV statistiku.
     *
     * @param array<int, string> $odabranaPolja
     */
    private function imaGodisnjaCsvPolja(array $odabranaPolja): bool
    {
        return count(array_intersect($odabranaPolja, self::CSV_GODISNJA_POLJA)) > 0;
    }

    /**
     * Dohvaca i validira godine za godisnje CSV stupce.
     *
     * @return array<int, int>
     */
    private function odrediGodineCsvStatistike(Request $request): array
    {
        $zadanaGodina = (int)date('Y');
        $godine = collect((array)$request->query('stat_years', []))
            ->map(static fn ($godina): int => (int)$godina)
            ->filter(static fn (int $godina): bool => $godina >= 1900 && $godina <= ($zadanaGodina + 1))
            ->unique()
            ->sortDesc()
            ->values()
            ->all();

        if (count($godine) === 0) {
            return [$zadanaGodina];
        }

        return $godine;
    }

    /**
     * Slaze zaglavlja CSV datoteke prema odabranim stupcima.
     *
     * @param array<int, string> $odabranaPolja
     * @return array<int, string>
     */
    private function csvZaglavlja(array $odabranaPolja, array $godineStatistike): array
    {
        $zaglavlja = ['Prezime', 'Ime', 'OIB', 'Spol', 'Datum rođenja'];

        foreach ($odabranaPolja as $polje) {
            if ($polje === 'phone') {
                $zaglavlja[] = 'Br. telefona';
                continue;
            }
            if ($polje === 'email') {
                $zaglavlja[] = 'E-mail';
                continue;
            }
            if ($polje === 'license_number') {
                $zaglavlja[] = 'Br. licence';
                continue;
            }
            if ($polje === 'member_since') {
                $zaglavlja[] = 'Član od';
                continue;
            }
            if ($polje === 'club_function') {
                $zaglavlja[] = 'Funkcija u klubu';
                continue;
            }
            if ($polje === 'last_medical_duration') {
                $zaglavlja[] = 'Trajanje zadnjeg liječničkog';
                continue;
            }
            if ($polje === 'tournaments_total') {
                $zaglavlja[] = 'Br. nastupa na turnirima (ukupno)';
                continue;
            }
            if ($polje === 'medals_total') {
                $zaglavlja[] = 'Broj osvojenih medalja (ukupno)';
                continue;
            }
            if ($polje === 'medals_gold_total') {
                $zaglavlja[] = 'Zlatne medalje (ukupno)';
                continue;
            }
            if ($polje === 'medals_silver_total') {
                $zaglavlja[] = 'Srebrne medalje (ukupno)';
                continue;
            }
            if ($polje === 'medals_bronze_total') {
                $zaglavlja[] = 'Brončane medalje (ukupno)';
            }
        }

        $odabranaGodisnjaPolja = array_values(array_intersect(self::CSV_GODISNJA_POLJA, $odabranaPolja));
        if (count($odabranaGodisnjaPolja) > 0) {
            foreach ($godineStatistike as $godina) {
                foreach ($odabranaGodisnjaPolja as $godisnjePolje) {
                    if ($godisnjePolje === 'tournaments_year') {
                        $zaglavlja[] = 'Br. nastupa na turnirima (' . $godina . ')';
                        continue;
                    }
                    if ($godisnjePolje === 'medals_year') {
                        $zaglavlja[] = 'Broj osvojenih medalja (' . $godina . ')';
                        continue;
                    }
                    if ($godisnjePolje === 'medals_gold_year') {
                        $zaglavlja[] = 'Zlatne medalje (' . $godina . ')';
                        continue;
                    }
                    if ($godisnjePolje === 'medals_silver_year') {
                        $zaglavlja[] = 'Srebrne medalje (' . $godina . ')';
                        continue;
                    }
                    if ($godisnjePolje === 'medals_bronze_year') {
                        $zaglavlja[] = 'Brončane medalje (' . $godina . ')';
                    }
                }
            }
        }

        return $zaglavlja;
    }

    /**
     * Dodaje odabrana opcionalna polja u jedan CSV red.
     *
     * @param array<int, mixed> $red
     * @param array<int, string> $odabranaPolja
     * @param array<int, int> $godineStatistike
     * @param array<string, mixed> $statistika
     */
    private function dodajCsvOpcionalnaPolja(array &$red, Clanovi $clan, array $odabranaPolja, array $godineStatistike, array $statistika): void
    {
        $clanId = (int)$clan->id;
        $praznaMedaljaStatistika = $this->praznaCsvMedaljaStatistika();
        $lijecnickiDoVrijednost = $clan->getAttribute('lijecnicki_do');
        $lijecnickiDo = is_string($lijecnickiDoVrijednost)
            ? $lijecnickiDoVrijednost
            : (empty($lijecnickiDoVrijednost) ? null : (string)$lijecnickiDoVrijednost);

        $turniriUkupno = (int)data_get($statistika, 'turniri_ukupno.' . $clanId, 0);
        $medaljeUkupno = data_get($statistika, 'medalje_ukupno.' . $clanId, $praznaMedaljaStatistika);

        foreach ($odabranaPolja as $polje) {
            if ($polje === 'phone') {
                $red[] = $this->formatirajTekstualnoPoljeZaCsv($clan->br_telefona);
                continue;
            }
            if ($polje === 'email') {
                $red[] = trim((string)$clan->email);
                continue;
            }
            if ($polje === 'license_number') {
                $red[] = $this->formatirajTekstualnoPoljeZaCsv($clan->broj_licence);
                continue;
            }
            if ($polje === 'member_since') {
                $red[] = $this->formatirajClanOdZaCsv($clan);
                continue;
            }
            if ($polje === 'club_function') {
                $red[] = $this->odrediFunkcijuClanaZaCsv($clan);
                continue;
            }
            if ($polje === 'last_medical_duration') {
                $red[] = $this->formatirajTrajanjeZadnjegLijecnickogZaCsv($lijecnickiDo);
                continue;
            }
            if ($polje === 'tournaments_total') {
                $red[] = $turniriUkupno;
                continue;
            }
            if ($polje === 'tournaments_year') {
                continue;
            }
            if ($polje === 'medals_total') {
                $red[] = (int)data_get($medaljeUkupno, 'ukupno', 0);
                continue;
            }
            if ($polje === 'medals_gold_total') {
                $red[] = (int)data_get($medaljeUkupno, 'zlato', 0);
                continue;
            }
            if ($polje === 'medals_silver_total') {
                $red[] = (int)data_get($medaljeUkupno, 'srebro', 0);
                continue;
            }
            if ($polje === 'medals_bronze_total') {
                $red[] = (int)data_get($medaljeUkupno, 'bronca', 0);
            }
        }

        $odabranaGodisnjaPolja = array_values(array_intersect(self::CSV_GODISNJA_POLJA, $odabranaPolja));
        if (count($odabranaGodisnjaPolja) > 0) {
            foreach ($godineStatistike as $godina) {
                $medaljePoGodini = data_get($statistika, 'medalje_po_godini.' . $godina . '.' . $clanId, $praznaMedaljaStatistika);
                $turniriPoGodini = (int)data_get($statistika, 'turniri_po_godini.' . $godina . '.' . $clanId, 0);

                foreach ($odabranaGodisnjaPolja as $godisnjePolje) {
                    if ($godisnjePolje === 'tournaments_year') {
                        $red[] = $turniriPoGodini;
                        continue;
                    }
                    if ($godisnjePolje === 'medals_year') {
                        $red[] = (int)data_get($medaljePoGodini, 'ukupno', 0);
                        continue;
                    }
                    if ($godisnjePolje === 'medals_gold_year') {
                        $red[] = (int)data_get($medaljePoGodini, 'zlato', 0);
                        continue;
                    }
                    if ($godisnjePolje === 'medals_silver_year') {
                        $red[] = (int)data_get($medaljePoGodini, 'srebro', 0);
                        continue;
                    }
                    if ($godisnjePolje === 'medals_bronze_year') {
                        $red[] = (int)data_get($medaljePoGodini, 'bronca', 0);
                    }
                }
            }
        }
    }

    /**
     * Vraca funkciju clana u klubu; ako nije definirana vraca "Clan".
     */
    private function odrediFunkcijuClanaZaCsv(Clanovi $clan): string
    {
        $funkcije = $clan->funkcijeUklubu;
        if ($funkcije === null || $funkcije->isEmpty()) {
            return 'Član';
        }

        $naziviFunkcija = $funkcije
            ->pluck('funkcija')
            ->map(static fn ($funkcija): string => trim((string)$funkcija))
            ->filter(static fn (string $funkcija): bool => $funkcija !== '')
            ->unique()
            ->values();

        if ($naziviFunkcija->isEmpty()) {
            return 'Član';
        }

        return $naziviFunkcija->implode(', ');
    }

    /**
     * Formatira podatak "Clan od" u obliku datuma ili godine.
     */
    private function formatirajClanOdZaCsv(Clanovi $clan): string
    {
        $datumPocetka = $clan->datum_pocetka_clanstva;
        if ($datumPocetka instanceof DateTimeInterface) {
            return $datumPocetka->format('d.m.Y.');
        }

        if (!empty($datumPocetka)) {
            $timestamp = strtotime((string)$datumPocetka);
            if ($timestamp !== false) {
                return date('d.m.Y.', $timestamp);
            }
        }

        if (!empty($clan->clan_od)) {
            return (string)(int)$clan->clan_od;
        }

        return '-';
    }

    /**
     * Formatira zadnje trajanje lijecnickog pregleda za CSV.
     */
    private function formatirajTrajanjeZadnjegLijecnickogZaCsv(?string $lijecnickiDo): string
    {
        if (empty($lijecnickiDo)) {
            return '-';
        }

        $timestamp = strtotime($lijecnickiDo);
        if ($timestamp === false) {
            return '-';
        }

        return date('d.m.Y.', $timestamp);
    }

    /**
     * Vracanje praznog skupa medalja za CSV.
     *
     * @return array<string, int>
     */
    private function praznaCsvMedaljaStatistika(): array
    {
        return [
            'ukupno' => 0,
            'zlato' => 0,
            'srebro' => 0,
            'bronca' => 0,
        ];
    }

    /**
     * Priprema agregate turnira i medalja po clanu za CSV izvoz.
     *
     * @param array<int, int> $clanoviIds
     * @return array<string, mixed>
     */
    private function pripremiCsvStatistikuClanova(array $clanoviIds): array
    {
        if (empty($clanoviIds)) {
            return [
                'turniri_ukupno' => [],
                'turniri_po_godini' => [],
                'medalje_ukupno' => [],
                'medalje_po_godini' => [],
            ];
        }

        $turniriUkupnoSet = [];
        $turniriPoGodiniSet = [];
        $medaljeUkupno = [];
        $medaljePoGodini = [];

        $individualniRezultati = RezultatiOpci::query()
            ->join('turniris', 'turniris.id', '=', 'rezultati_opcis.turnir_id')
            ->whereIn('rezultati_opcis.clan_id', $clanoviIds)
            ->select([
                'rezultati_opcis.clan_id',
                'rezultati_opcis.turnir_id',
                'turniris.eliminacije',
                'rezultati_opcis.plasman',
                'rezultati_opcis.plasman_nakon_eliminacija',
                DB::raw('YEAR(turniris.datum) as godina'),
            ])
            ->get();

        foreach ($individualniRezultati as $rezultat) {
            $clanId = (int)data_get($rezultat, 'clan_id', 0);
            $turnirId = (int)data_get($rezultat, 'turnir_id', 0);
            $godina = (int)data_get($rezultat, 'godina', 0);
            $eliminacije = (int)data_get($rezultat, 'eliminacije', 0);
            $plasman = $eliminacije === 1
                ? (int)data_get($rezultat, 'plasman_nakon_eliminacija', 0)
                : (int)data_get($rezultat, 'plasman', 0);

            $this->dodajCsvRezultatUStatistiku(
                $turniriUkupnoSet,
                $turniriPoGodiniSet,
                $medaljeUkupno,
                $medaljePoGodini,
                $clanId,
                $turnirId,
                $plasman,
                $godina
            );
        }

        if ($this->timskeTabliceDostupne()) {
            $timskiRezultati = DB::table('rezultati_tim_clanovi as rtc')
                ->join('rezultati_timovi as rt', 'rt.id', '=', 'rtc.rezultati_tim_id')
                ->join('turniris as t', 't.id', '=', 'rt.turnir_id')
                ->join('rezultati_opcis as ro', 'ro.id', '=', 'rtc.rezultat_opci_id')
                ->whereIn('ro.clan_id', $clanoviIds)
                ->select([
                    'ro.clan_id',
                    'rt.turnir_id',
                    'rt.plasman',
                    DB::raw('YEAR(t.datum) as godina'),
                ])
                ->get();

            foreach ($timskiRezultati as $rezultat) {
                $clanId = (int)data_get($rezultat, 'clan_id', 0);
                $turnirId = (int)data_get($rezultat, 'turnir_id', 0);
                $godina = (int)data_get($rezultat, 'godina', 0);
                $plasman = (int)data_get($rezultat, 'plasman', 0);

                $this->dodajCsvRezultatUStatistiku(
                    $turniriUkupnoSet,
                    $turniriPoGodiniSet,
                    $medaljeUkupno,
                    $medaljePoGodini,
                    $clanId,
                    $turnirId,
                    $plasman,
                    $godina
                );
            }
        }

        $turniriUkupno = [];
        foreach ($turniriUkupnoSet as $clanId => $turniri) {
            $turniriUkupno[(int)$clanId] = count($turniri);
        }

        $turniriPoGodini = [];
        foreach ($turniriPoGodiniSet as $godina => $turniriPoClanu) {
            foreach ($turniriPoClanu as $clanId => $turniri) {
                $turniriPoGodini[(int)$godina][(int)$clanId] = count($turniri);
            }
        }

        return [
            'turniri_ukupno' => $turniriUkupno,
            'turniri_po_godini' => $turniriPoGodini,
            'medalje_ukupno' => $medaljeUkupno,
            'medalje_po_godini' => $medaljePoGodini,
        ];
    }

    /**
     * Dodaje jednu rezultatnu stavku u agregate za CSV statistiku članova.
     *
     * @param array<int, array<int, bool>> $turniriUkupnoSet
     * @param array<int, array<int, array<int, bool>>> $turniriPoGodiniSet
     * @param array<int, array<string, int>> $medaljeUkupno
     * @param array<int, array<int, array<string, int>>> $medaljePoGodini
     */
    private function dodajCsvRezultatUStatistiku(
        array &$turniriUkupnoSet,
        array &$turniriPoGodiniSet,
        array &$medaljeUkupno,
        array &$medaljePoGodini,
        int $clanId,
        int $turnirId,
        int $plasman,
        int $godina
    ): void {
        $this->dodajTurnirClanu($turniriUkupnoSet, $clanId, $turnirId);
        $this->dodajMedaljuClanu($medaljeUkupno, $clanId, $plasman);

        if ($godina <= 0) {
            return;
        }

        if (!isset($turniriPoGodiniSet[$godina])) {
            $turniriPoGodiniSet[$godina] = [];
        }

        if (!isset($medaljePoGodini[$godina])) {
            $medaljePoGodini[$godina] = [];
        }

        $this->dodajTurnirClanu($turniriPoGodiniSet[$godina], $clanId, $turnirId);
        $this->dodajMedaljuClanu($medaljePoGodini[$godina], $clanId, $plasman);
    }

    /**
     * @param Clanovi $clan
     * @return Factory|View|\Illuminate\View\View
     */
    public function pregledClana(Clanovi $clan)
    {
        $paymentService = app(PaymentTrackingService::class);
        $paymentTrackingEnabled = $paymentService->isEnabled();
        $adminPregled = auth()->check() && (int)auth()->user()->rola <= 1;
        $vlastitiPregled = auth()->check()
            && (int)auth()->user()->rola <= 2
            && (int)auth()->user()->clan_id === (int)$clan->id;
        $roditeljPregled = auth()->check()
            && auth()->user()->jeRoditelj()
            && auth()->user()->mozePregledavatiClana((int)$clan->id);
        $mozeVidjetiDokumenteClana = $adminPregled || $vlastitiPregled || $roditeljPregled;
        $mozeVidjetiSkolaDolaske = $adminPregled || $vlastitiPregled || $roditeljPregled;
        $mozeVidjetiPlacanja = $paymentTrackingEnabled && ($adminPregled || $vlastitiPregled || $roditeljPregled);
        $evidencijeSkole = collect();
        $paymentSummary = null;
        $paymentNotice = null;
        $paymentProfileConfigured = false;
        $jeRodendanDanas = false;

        if (!empty($clan->datum_rodjenja)) {
            $jeRodendanDanas = date('m-d', strtotime((string)$clan->datum_rodjenja)) === now()->format('m-d');
        }

        if ($mozeVidjetiDokumenteClana) {
            $clan->load([
                'lijecnickiPregledi' => fn ($query) => $query->orderByDesc('vrijedi_do')->orderByDesc('id'),
                'dokumenti' => fn ($query) => $query->orderByDesc('datum_dokumenta')->orderByDesc('id'),
            ]);
        }

        if ($mozeVidjetiSkolaDolaske) {
            $evidencijeSkole = PolaznikSkole::query()
                ->where('prebacen_u_clana_id', (int)$clan->id)
                ->with(['dolasci' => fn ($query) => $query->orderBy('redni_broj')])
                ->orderByDesc('prebacen_at')
                ->get();
        }

        if ($mozeVidjetiPlacanja) {
            $paymentSummary = $paymentService->memberSummary($clan);
            $paymentProfileConfigured = $this->jePlacanjeVidljivo($paymentSummary);
            if ($paymentProfileConfigured) {
                $paymentNotice = $paymentService->noticeForClan($clan);
            }
        }

        $timoviClana = null;
        if ($this->timskeTabliceDostupne()) {
            $timoviClana = RezultatiTim::query()
                ->whereHas('clanoviStavke.rezultatOpci', function ($query) use ($clan) {
                    $query->where('clan_id', '=', $clan->id);
                });
        }

        $turniriPojedinacnoIds = RezultatiOpci::query()
            ->where('clan_id', '=', $clan->id)
            ->pluck('turnir_id');
        $turniriTimskiIds = $timoviClana ? (clone $timoviClana)->pluck('turnir_id') : collect();

        // broj odrađenih turnira (pojedinačno + timski)
        $turniri['ukupno'] = $turniriPojedinacnoIds
            ->merge($turniriTimskiIds)
            ->unique()
            ->count();

        // broj osvojenih medalja (pojedinačno + timski)
        $turniri['prva'] = RezultatiOpci::whereHas('turnir', function ($query) {
                $query->where('eliminacije', '=', 0);
            })->where('clan_id', '=', $clan->id)->where('plasman', '=', 1)->count() + RezultatiOpci::whereHas('turnir', function ($query) {
                $query->where('eliminacije', '=', 1);
            })->where('clan_id', '=', $clan->id)->where('plasman_nakon_eliminacija', '=', 1)->count() + ($timoviClana ? (clone $timoviClana)->where('plasman', 1)->count() : 0);
        $turniri['druga'] = RezultatiOpci::whereHas('turnir', function ($query) {
                $query->where('eliminacije', '=', 0);
            })->where('clan_id', '=', $clan->id)->where('plasman', '=', 2)->count() + RezultatiOpci::whereHas('turnir', function ($query) {
                $query->where('eliminacije', '=', 1);
            })->where('clan_id', '=', $clan->id)->where('plasman_nakon_eliminacija', '=', 2)->count() + ($timoviClana ? (clone $timoviClana)->where('plasman', 2)->count() : 0);
        $turniri['treca'] = RezultatiOpci::whereHas('turnir', function ($query) {
                $query->where('eliminacije', '=', 0);
            })->where('clan_id', '=', $clan->id)->where('plasman', '=', 3)->count() + RezultatiOpci::whereHas('turnir', function ($query) {
                $query->where('eliminacije', '=', 1);
            })->where('clan_id', '=', $clan->id)->where('plasman_nakon_eliminacija', '=', 3)->count() + ($timoviClana ? (clone $timoviClana)->where('plasman', 3)->count() : 0);
        $turniri['medalje'] = $turniri['prva'] + $turniri['druga'] + $turniri['treca'];

        $timskeMedalje = collect();
        if ($this->timskeTabliceDostupne()) {
            $timskeMedalje = RezultatiTim::query()
                ->whereIn('plasman', [1, 2, 3])
                ->whereHas('clanoviStavke.rezultatOpci', function ($query) use ($clan) {
                    $query->where('clan_id', '=', $clan->id);
                })
                ->with([
                    'turnir.tipTurnira.polja',
                    'turnir.rezultatiPoTipuTurnira',
                    'stil',
                    'kategorija',
                    'clanoviStavke' => fn ($query) => $query
                        ->with(['rezultatOpci.clan'])
                        ->orderBy('redni_broj')
                        ->orderBy('id'),
                ])
                ->get()
                ->sortByDesc(function (RezultatiTim $tim) {
                    return $tim->turnir?->datum ?? '';
                })
                ->values();
        }


        $cl = $clan->id;
        // Svi tipovi turnira u kojima član ima pojedinačni rezultat.
        $tipoviTurnira = TipoviTurnira::query()
            ->with('polja')
            ->whereHas('turniri.rezultatiOpci', function ($query) use ($cl) {
                $query->where('clan_id', '=', $cl);
            })
            ->get();

        // Osobni rekordi: jedan agregatni upit umjesto trostruke petlje tip × stil × kategorija.
        $rekordiKandidati = RezultatiPoTipuTurnira::query()
            ->select([
                'rezultati_po_tipu_turniras.turnir_id',
                'rezultati_po_tipu_turniras.stil_id',
                'rezultati_po_tipu_turniras.kategorija_id',
                'rezultati_po_tipu_turniras.rezultat',
                'turniris.tipovi_turnira_id as tip_turnira_id',
            ])
            ->join('turniris', 'turniris.id', '=', 'rezultati_po_tipu_turniras.turnir_id')
            ->join('polja_za_tipove_turniras as polja_ukupno', 'polja_ukupno.id', '=', 'rezultati_po_tipu_turniras.polje_za_tipove_turnira_id')
            ->where('rezultati_po_tipu_turniras.clan_id', '=', $cl)
            ->where('polja_ukupno.naziv', '=', 'Ukupno')
            ->orderBy('turniris.tipovi_turnira_id')
            ->orderBy('rezultati_po_tipu_turniras.stil_id')
            ->orderBy('rezultati_po_tipu_turniras.kategorija_id')
            ->orderByDesc('rezultati_po_tipu_turniras.rezultat')
            ->orderByDesc('turniris.datum')
            ->orderByDesc('rezultati_po_tipu_turniras.id')
            ->get();

        $osobniRekordi = [];
        $datumiRekorda = [];
        if ($rekordiKandidati->isNotEmpty()) {
            $tipoviMapa = $tipoviTurnira->pluck('naziv', 'id');
            $stiloviMapa = Stilovi::query()
                ->whereIn('id', $rekordiKandidati->pluck('stil_id')->unique()->values())
                ->pluck('naziv', 'id');
            $kategorijeMapa = Kategorije::query()
                ->whereIn('id', $rekordiKandidati->pluck('kategorija_id')->unique()->values())
                ->pluck('naziv', 'id');
            $turniriMapa = Turniri::query()
                ->whereIn('id', $rekordiKandidati->pluck('turnir_id')->unique()->values())
                ->get()
                ->keyBy('id');

            $obradeneKombinacije = [];
            foreach ($rekordiKandidati as $kandidat) {
                $kombinacijaKey = (int)$kandidat->tip_turnira_id . '|' . (int)$kandidat->stil_id . '|' . (int)$kandidat->kategorija_id;
                if (isset($obradeneKombinacije[$kombinacijaKey])) {
                    continue;
                }

                $tipNaziv = $tipoviMapa->get((int)$kandidat->tip_turnira_id);
                $stilNaziv = $stiloviMapa->get((int)$kandidat->stil_id);
                $kategorijaNaziv = $kategorijeMapa->get((int)$kandidat->kategorija_id);
                $turnirRekorda = $turniriMapa->get((int)$kandidat->turnir_id);
                if (empty($tipNaziv) || !($turnirRekorda instanceof Turniri) || empty($stilNaziv) || empty($kategorijaNaziv)) {
                    continue;
                }

                $osobniRekordi[] = [
                    'stil' => (string)$stilNaziv,
                    'kategorija' => (string)$kategorijaNaziv,
                    'tipTurnira' => (string)$tipNaziv,
                    'rezultat' => (int)$kandidat->rezultat,
                    'turnir' => $turnirRekorda,
                ];
                $datumiRekorda[] = $turnirRekorda->datum;
                $obradeneKombinacije[$kombinacijaKey] = true;
            }
        }

        //popis svih turnira na kojima je sudjelovao (pojedinačno ili u timu)
        $turniriPopis = Turniri::query()
            ->with([
                'tipTurnira.polja',
                'rezultatiOpci' => fn ($query) => $query
                    ->with(['clan', 'stil', 'kategorija'])
                    ->where('clan_id', '=', $cl)
                    ->orderBy('id'),
                'rezultatiPoTipuTurnira' => fn ($query) => $query
                    ->where('clan_id', '=', $cl)
                    ->orderBy('id'),
            ])
            ->where(function ($query) use ($cl) {
                $query->whereHas('rezultatiOpci', function ($rezultatiQuery) use ($cl) {
                    $rezultatiQuery->where('clan_id', '=', $cl);
                });
                if ($this->timskeTabliceDostupne()) {
                    $query->orWhereHas('rezultatiTimovi.clanoviStavke.rezultatOpci', function ($timQuery) use ($cl) {
                        $timQuery->where('clan_id', '=', $cl);
                    });
                }
            })
            ->orderByDesc('datum')
            ->get();
        return view('javno.pregledClana', [
            'clan' => $clan,
            'turniri' => $turniri,
            'osobniRekordi' => $osobniRekordi,
            'turniriPopis' => $turniriPopis,
            'datumiRekorda' => $datumiRekorda,
            'tipoviTurnira' => $tipoviTurnira,
            'adminPregled' => $adminPregled,
            'jeRoditeljPregled' => $roditeljPregled,
            'mozeVidjetiDokumenteClana' => $mozeVidjetiDokumenteClana,
            'mozeVidjetiSkolaDolaske' => $mozeVidjetiSkolaDolaske,
            'mozeVidjetiPlacanja' => $mozeVidjetiPlacanja,
            'paymentSummary' => $paymentSummary,
            'paymentNotice' => $paymentNotice,
            'paymentProfileConfigured' => $paymentProfileConfigured,
            'paymentTrackingEnabled' => $paymentTrackingEnabled,
            'evidencijeSkole' => $evidencijeSkole,
            'jeRodendanDanas' => $jeRodendanDanas,
            'timskeMedalje' => $timskeMedalje,
        ]);
    }

    /**
     * Omogućuje preuzimanje datoteke liječničkog pregleda člana uz provjeru ovlasti.
     */
    public function preuzmi_lijecnicki_pregled(Clanovi $clan, ClanLijecnickiPregled $pregled): BinaryFileResponse
    {
        $this->potvrdiPravoNaDokumente($clan);
        $this->potvrdiPripadnostClanu((int)$clan->id, (int)$pregled->clan_id);

        if (empty($pregled->putanja) || !Storage::disk('local')->exists($pregled->putanja)) {
            abort(404);
        }

        return response()->file(Storage::disk('local')->path($pregled->putanja));
    }

    /**
     * Omogućuje preuzimanje dokumenta člana uz provjeru ovlasti.
     */
    public function preuzmi_dokument(Clanovi $clan, ClanDokument $dokument): BinaryFileResponse
    {
        $this->potvrdiPravoNaDokumente($clan);
        $this->potvrdiPripadnostClanu((int)$clan->id, (int)$dokument->clan_id);

        if (empty($dokument->putanja) || !Storage::disk('local')->exists($dokument->putanja)) {
            abort(404);
        }

        return response()->file(Storage::disk('local')->path($dokument->putanja));
    }

    /**
     * Provjerava smije li korisnik preuzeti privatne dokumente traženog člana.
     */
    private function potvrdiPravoNaDokumente(Clanovi $clan): void
    {
        if (!auth()->check()) {
            abort(403);
        }

        if (auth()->user()->mozePregledavatiClana((int)$clan->id)) {
            return;
        }

        abort(403);
    }

    /**
     * Štiti rutu tako da se akcija izvodi samo nad članom navedenim u URL-u.
     */
    private function potvrdiPripadnostClanu(int $clanId, int $resourceClanId): void
    {
        if ($clanId !== $resourceClanId) {
            abort(404);
        }
    }

    /**
     * Pretvara internu oznaku spola u čitljiv tekst za CSV izvoz aktivnih članova.
     */
    private function mapirajSpolZaCsv(?string $spol): string
    {
        $vrijednost = trim((string)$spol);
        $vrijednostBezDijakritika = strtoupper(str_replace(['Ž', 'ž'], 'Z', $vrijednost));

        if (str_starts_with($vrijednostBezDijakritika, 'M')) {
            return 'Muško';
        }

        if (str_starts_with($vrijednostBezDijakritika, 'Z')) {
            return 'Žensko';
        }

        return (string)$spol;
    }

    /**
     * Slaže status liječničkog pregleda člana za prikaz na naslovnici/profilu.
     */
    private function pripremiStatusLijecnickogZaClana(Clanovi $clan): array
    {
        $status = [
            'clan' => $clan,
            'datum' => null,
            'brojDana' => null,
            'istekao' => false,
            'manjeOdDvadesetDana' => false,
        ];

        if (empty($clan->lijecnicki_do)) {
            return $status;
        }

        $datumLijecnickog = date_create((string)$clan->lijecnicki_do);
        if ($datumLijecnickog === false) {
            return $status;
        }

        $danasDatum = date_create(date('Y-m-d'));
        $razlikaDana = (int)$danasDatum->diff($datumLijecnickog)->format('%r%a');

        $status['datum'] = date('d.m.Y.', strtotime((string)$clan->lijecnicki_do));
        $status['brojDana'] = max($razlikaDana, 0);
        $status['istekao'] = $razlikaDana < 0;
        $status['manjeOdDvadesetDana'] = $razlikaDana >= 0 && $razlikaDana < 20;

        return $status;
    }

    /**
     * Određuje treba li status plaćanja biti prikazan korisniku.
     *
     * @param array<string, mixed> $placanjeSummary
     */
    private function jePlacanjeVidljivo(array $placanjeSummary): bool
    {
        $placanjeProfil = $placanjeSummary['profile'] ?? null;
        $placanjeProfilPostavljen = $placanjeProfil !== null
            && data_get($placanjeProfil, 'paymentOption') !== null;
        $placanjeImaStavke = ($placanjeSummary['charges'] ?? collect())->count() > 0;

        return $placanjeProfilPostavljen || $placanjeImaStavke;
    }

    /**
     * Formatira OIB za CSV izvoz tako da tablični alati ne uklone vodeće nule.
     */
    private function formatirajOibZaCsv(?string $oib): string
    {
        return $this->formatirajTekstualnoPoljeZaCsv($oib);
    }

    /**
     * Formatira vrijednost kao tekst za CSV (sprječava automatsko pretvaranje u broj u tabličnim alatima).
     */
    private function formatirajTekstualnoPoljeZaCsv(?string $vrijednost): string
    {
        $tekst = trim((string)$vrijednost);

        if ($tekst === '') {
            return '';
        }

        return "\t" . $tekst;
    }

    /**
     * Računa godišnju statistiku medalja i nastupa kluba za odabranu godinu.
     */
    private function izracunajGodisnjuStatistiku(int $godina): array
    {
        $zlato = 0;
        $srebro = 0;
        $bronca = 0;
        $medaljePoClanu = [];
        $turniriPoClanu = [];

        $individualniRezultati = RezultatiOpci::query()
            ->join('turniris', 'turniris.id', '=', 'rezultati_opcis.turnir_id')
            ->whereYear('turniris.datum', $godina)
            ->select([
                'rezultati_opcis.clan_id',
                'rezultati_opcis.turnir_id',
                'turniris.eliminacije',
                'rezultati_opcis.plasman',
                'rezultati_opcis.plasman_nakon_eliminacija',
            ])
            ->get()
            ->map(static function ($rezultat): array {
                return [
                    'clan_id' => (int)data_get($rezultat, 'clan_id', 0),
                    'turnir_id' => (int)data_get($rezultat, 'turnir_id', 0),
                    'eliminacije' => (int)data_get($rezultat, 'eliminacije', 0),
                    'plasman' => (int)data_get($rezultat, 'plasman', 0),
                    'plasman_nakon_eliminacija' => (int)data_get($rezultat, 'plasman_nakon_eliminacija', 0),
                ];
            });

        foreach ($individualniRezultati as $rezultat) {
            $clanId = $rezultat['clan_id'];
            $turnirId = $rezultat['turnir_id'];
            $plasman = ($rezultat['eliminacije'] === 1)
                ? $rezultat['plasman_nakon_eliminacija']
                : $rezultat['plasman'];

            $this->dodajTurnirClanu($turniriPoClanu, $clanId, $turnirId);
            $this->dodajMedaljuClanu($medaljePoClanu, $clanId, $plasman);
            $this->dodajKlubskuMedalju($plasman, $zlato, $srebro, $bronca);
        }

        if ($this->timskeTabliceDostupne()) {
            $timovi = DB::table('rezultati_timovi as rt')
                ->join('turniris as t', 't.id', '=', 'rt.turnir_id')
                ->whereYear('t.datum', $godina)
                ->select(['rt.turnir_id', 'rt.plasman'])
                ->get();

            foreach ($timovi as $tim) {
                $this->dodajKlubskuMedalju((int)$tim->plasman, $zlato, $srebro, $bronca);
            }

            $timskiClanovi = DB::table('rezultati_tim_clanovi as rtc')
                ->join('rezultati_timovi as rt', 'rt.id', '=', 'rtc.rezultati_tim_id')
                ->join('turniris as t', 't.id', '=', 'rt.turnir_id')
                ->join('rezultati_opcis as ro', 'ro.id', '=', 'rtc.rezultat_opci_id')
                ->whereYear('t.datum', $godina)
                ->select(['ro.clan_id', 'rt.turnir_id', 'rt.plasman'])
                ->get();

            foreach ($timskiClanovi as $timClan) {
                $this->dodajTurnirClanu($turniriPoClanu, (int)$timClan->clan_id, (int)$timClan->turnir_id);
                $this->dodajMedaljuClanu($medaljePoClanu, (int)$timClan->clan_id, (int)$timClan->plasman);
            }
        }

        $brojTurnira = Turniri::query()->whereYear('datum', $godina)->count();

        $turniriBrojPoClanu = [];
        foreach ($turniriPoClanu as $clanId => $turniriClana) {
            $turniriBrojPoClanu[(int)$clanId] = count($turniriClana);
        }

        $sviClanoviIds = array_values(array_unique(array_merge(
            array_keys($medaljePoClanu),
            array_keys($turniriBrojPoClanu)
        )));
        $imenaClanova = $this->dohvatiMapuImenaClanova($sviClanoviIds);

        $ukupnoMedaljaPoClanu = [];
        $zlatnePoClanu = [];
        $srebrnePoClanu = [];
        $broncanePoClanu = [];
        foreach ($medaljePoClanu as $clanId => $medalje) {
            $ukupnoMedaljaPoClanu[(int)$clanId] = (int)($medalje['ukupno'] ?? 0);
            $zlatnePoClanu[(int)$clanId] = (int)($medalje['zlato'] ?? 0);
            $srebrnePoClanu[(int)$clanId] = (int)($medalje['srebro'] ?? 0);
            $broncanePoClanu[(int)$clanId] = (int)($medalje['bronca'] ?? 0);
        }

        return [
            'godina' => $godina,
            'zlato' => $zlato,
            'srebro' => $srebro,
            'bronca' => $bronca,
            'ukupno' => $zlato + $srebro + $bronca,
            'broj_turnira' => $brojTurnira,
            'najvise_medalja' => $this->izracunajVodece($ukupnoMedaljaPoClanu, $imenaClanova),
            'najvise_zlatnih' => $this->izracunajVodece($zlatnePoClanu, $imenaClanova),
            'najvise_srebrnih' => $this->izracunajVodece($srebrnePoClanu, $imenaClanova),
            'najvise_broncanih' => $this->izracunajVodece($broncanePoClanu, $imenaClanova),
            'najvise_turnira' => $this->izracunajVodece($turniriBrojPoClanu, $imenaClanova),
        ];
    }

    /**
     * Validira ulaz i sprema promjene prema pravilima modula javnog prikaza stranice kluba.
     */
    private function dodajTurnirClanu(array &$turniriPoClanu, int $clanId, int $turnirId): void
    {
        if ($clanId <= 0 || $turnirId <= 0) {
            return;
        }

        if (!isset($turniriPoClanu[$clanId])) {
            $turniriPoClanu[$clanId] = [];
        }

        $turniriPoClanu[$clanId][$turnirId] = true;
    }

    /**
     * Validira ulaz i sprema promjene prema pravilima modula javnog prikaza stranice kluba.
     */
    private function dodajMedaljuClanu(array &$medaljePoClanu, int $clanId, int $plasman): void
    {
        if ($clanId <= 0 || !in_array($plasman, [1, 2, 3], true)) {
            return;
        }

        if (!isset($medaljePoClanu[$clanId])) {
            $medaljePoClanu[$clanId] = [
                'zlato' => 0,
                'srebro' => 0,
                'bronca' => 0,
                'ukupno' => 0,
            ];
        }

        if ($plasman === 1) {
            $medaljePoClanu[$clanId]['zlato']++;
        } elseif ($plasman === 2) {
            $medaljePoClanu[$clanId]['srebro']++;
        } else {
            $medaljePoClanu[$clanId]['bronca']++;
        }

        $medaljePoClanu[$clanId]['ukupno']++;
    }

    /**
     * Validira ulaz i sprema promjene prema pravilima modula javnog prikaza stranice kluba.
     */
    private function dodajKlubskuMedalju(int $plasman, int &$zlato, int &$srebro, int &$bronca): void
    {
        if ($plasman === 1) {
            $zlato++;
            return;
        }

        if ($plasman === 2) {
            $srebro++;
            return;
        }

        if ($plasman === 3) {
            $bronca++;
        }
    }

    /**
     * Dohvaća potrebne podatke iz baze za prikaz ili daljnju obradu u modulu javnog prikaza i korisničkih profila.
     */
    private function dohvatiMapuImenaClanova(array $clanoviIds): array
    {
        if (empty($clanoviIds)) {
            return [];
        }

        return Clanovi::query()
            ->whereIn('id', $clanoviIds)
            ->get(['id', 'Ime', 'Prezime'])
            ->mapWithKeys(static function (Clanovi $clan): array {
                return [(int)$clan->id => trim($clan->Prezime . ' ' . $clan->Ime)];
            })
            ->all();
    }

    /**
     * Izračunava vodeće članove po medaljama i broju nastupa u odabranoj godini.
     */
    private function izracunajVodece(array $vrijednostiPoClanu, array $imenaClanova): array
    {
        $filtrirano = [];
        foreach ($vrijednostiPoClanu as $clanId => $vrijednost) {
            $vrijednostInt = (int)$vrijednost;
            if ($vrijednostInt > 0) {
                $filtrirano[(int)$clanId] = $vrijednostInt;
            }
        }

        if (empty($filtrirano)) {
            return [
                'vrijednost' => 0,
                'clanovi' => [],
                'label' => '-',
            ];
        }

        $maksimum = max($filtrirano);
        $vodeciClanovi = array_map('intval', array_keys(array_filter(
            $filtrirano,
            static fn (int $vrijednost): bool => $vrijednost === $maksimum
        )));

        usort($vodeciClanovi, static function (int $clanA, int $clanB) use ($imenaClanova): int {
            $imeA = $imenaClanova[$clanA] ?? ('Član #' . $clanA);
            $imeB = $imenaClanova[$clanB] ?? ('Član #' . $clanB);
            return strcmp($imeA, $imeB);
        });

        $label = implode(', ', array_map(static function (int $clanId) use ($imenaClanova): string {
            return $imenaClanova[$clanId] ?? ('Član #' . $clanId);
        }, $vodeciClanovi));

        return [
            'vrijednost' => $maksimum,
            'clanovi' => $vodeciClanovi,
            'label' => $label,
        ];
    }

    public function pokaziTurnir(Turniri $turnir): View
    {
        $with = [
            'tipTurnira.polja',
            'rezultatiOpci.clan',
            'rezultatiOpci.stil',
            'rezultatiOpci.kategorija',
            'rezultatiPoTipuTurnira',
            'mediji',
        ];
        if ($this->timskeTabliceDostupne()) {
            $with[] = 'rezultatiTimovi.stil';
            $with[] = 'rezultatiTimovi.kategorija';
            $with[] = 'rezultatiTimovi.clanoviStavke.rezultatOpci.clan';
        }
        $turniri = Turniri::with($with)->where('id', '=', $turnir->id)->get();
        return view('javno.pregledTurnira', ['turniri' => $turniri]);
    }

    /**
     * Provjerava postoje li tablice za timske rezultate prije prikaza timskih sekcija.
     */
    private function timskeTabliceDostupne(): bool
    {
        return Schema::hasTable('rezultati_timovi') && Schema::hasTable('rezultati_tim_clanovi');
    }

}
