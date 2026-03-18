<?php

namespace App\Http\Controllers;

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
                    $placanjeProfil = $placanjeSummary['profile'] ?? null;
                    $placanjeProfilPostavljen = $placanjeProfil !== null
                        && data_get($placanjeProfil, 'paymentOption') !== null;
                    $placanjeImaStavke = ($placanjeSummary['charges'] ?? collect())->count() > 0;
                    $placanjeVidljivo = $placanjeProfilPostavljen || $placanjeImaStavke;

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
                        $placanjeProfil = $placanjeSummary['profile'] ?? null;
                        $placanjeProfilPostavljen = $placanjeProfil !== null
                            && data_get($placanjeProfil, 'paymentOption') !== null;
                        $placanjeImaStavke = ($placanjeSummary['charges'] ?? collect())->count() > 0;
                        if ($placanjeProfilPostavljen || $placanjeImaStavke) {
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
                        $placanjeProfil = $placanjeSummary['profile'] ?? null;
                        $placanjeProfilPostavljen = $placanjeProfil !== null
                            && data_get($placanjeProfil, 'paymentOption') !== null;
                        $placanjeImaStavke = ($placanjeSummary['charges'] ?? collect())->count() > 0;

                        return [
                            'clan' => $clan,
                            'notice' => ($placanjeProfilPostavljen || $placanjeImaStavke)
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
        ]);
    }

    /**
     * Izvozi CSV popis aktivnih članova kluba s podacima za administrativni rad.
     */
    public function exportAktivnihClanovaCsv(): StreamedResponse
    {
        if (!auth()->check() || !auth()->user()->imaPravoAdminOrMember()) {
            abort(403);
        }

        $clanovi = Clanovi::query()
            ->where('aktivan', true)
            ->orderBy('Prezime')
            ->orderBy('Ime')
            ->get(['Prezime', 'Ime', 'oib', 'spol', 'datum_rodjenja']);

        $nazivDatoteke = 'aktivni_clanovi_' . date('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($clanovi) {
            $izlaz = fopen('php://output', 'wb');

            if ($izlaz === false) {
                return;
            }

            fwrite($izlaz, "\xEF\xBB\xBF");
            fputcsv($izlaz, ['Prezime', 'Ime', 'OIB', 'Spol', 'Datum rođenja'], ';');

            foreach ($clanovi as $clan) {
                fputcsv($izlaz, [
                    (string)$clan->Prezime,
                    (string)$clan->Ime,
                    $this->formatirajOibZaCsv($clan->oib),
                    $this->mapirajSpolZaCsv($clan->spol),
                    empty($clan->datum_rodjenja) ? '' : date('d.m.Y.', strtotime($clan->datum_rodjenja)),
                ], ';');
            }

            fclose($izlaz);
        }, $nazivDatoteke, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
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
            $paymentProfile = $paymentSummary['profile'] ?? null;
            $profilPostavljen = $paymentProfile !== null
                && data_get($paymentProfile, 'paymentOption') !== null;
            $imaStavkePlacanja = ($paymentSummary['charges'] ?? collect())->count() > 0;
            $paymentProfileConfigured = $profilPostavljen || $imaStavkePlacanja;
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
     * Formatira OIB za CSV izvoz tako da tablični alati ne uklone vodeće nule.
     */
    private function formatirajOibZaCsv(?string $oib): string
    {
        $vrijednost = trim((string)$oib);

        if ($vrijednost === '') {
            return '';
        }

        // OIB šaljemo kao tekstualnu formulu da Excel/LibreOffice ne maknu vodeće nule.
        return '="' . str_replace('"', '""', $vrijednost) . '"';
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
