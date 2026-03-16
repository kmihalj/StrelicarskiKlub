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
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class JavnoController extends Controller
{


    /** @noinspection PhpUndefinedMethodInspection
     * @noinspection PhpMissingReturnTypeInspection
     *
     * stavka u meniju "REZULTATI"
     */
    public function prikazRezultata()
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

        $turniri = Turniri::with($with)->orderByDesc('datum')->paginate(10);
        $tipoviTurnira = TipoviTurnira::orderBy('naziv')->get();

        // statistika za tekuću i prošlu godinu
        $godina = date('Y');
        for ($i = 0; $i <= 1; $i++) {
            $godina = $godina - $i;
            $timoviZaGodinu = null;
            if ($this->timskeTabliceDostupne()) {
                $timoviZaGodinu = RezultatiTim::query()->whereHas('turnir', function ($query) use ($godina) {
                    $query->whereYear('datum', $godina);
                });
            }
            //prva mjesta ukupno
            $rezultati[$godina][1] = RezultatiOpci::whereHas('turnir', function ($query) use ($godina) {
                    $query->whereYear('datum', $godina)->where('eliminacije', '=', 0);
                })->where('plasman', '=', 1)->count() + RezultatiOpci::whereHas('turnir', function ($query) use ($godina) {
                    $query->whereYear('datum', $godina)->where('eliminacije', '=', 1);
                })->where('plasman_nakon_eliminacija', '=', 1)->count() + ($timoviZaGodinu ? (clone $timoviZaGodinu)->where('plasman', 1)->count() : 0);
            //druga mjesta ukupno
            $rezultati[$godina][2] = RezultatiOpci::whereHas('turnir', function ($query) use ($godina) {
                    $query->whereYear('datum', $godina)->where('eliminacije', '=', 0);
                })->where('plasman', '=', 2)->count() + RezultatiOpci::whereHas('turnir', function ($query) use ($godina) {
                    $query->whereYear('datum', $godina)->where('eliminacije', '=', 1);
                })->where('plasman_nakon_eliminacija', '=', 2)->count() + ($timoviZaGodinu ? (clone $timoviZaGodinu)->where('plasman', 2)->count() : 0);
            //treća mjesta ukupno
            $rezultati[$godina][3] = RezultatiOpci::whereHas('turnir', function ($query) use ($godina) {
                    $query->whereYear('datum', $godina)->where('eliminacije', '=', 0);
                })->where('plasman', '=', 3)->count() + RezultatiOpci::whereHas('turnir', function ($query) use ($godina) {
                    $query->whereYear('datum', $godina)->where('eliminacije', '=', 1);
                })->where('plasman_nakon_eliminacija', '=', 3)->count() + ($timoviZaGodinu ? (clone $timoviZaGodinu)->where('plasman', 3)->count() : 0);
        }
        return view('javno.rezultati', ['turniri' => $turniri, 'statistika' => $rezultati, 'tipoviTurnira' => $tipoviTurnira]);
    }


    /** @noinspection PhpMissingReturnTypeInspection
     * @noinspection PhpUndefinedMethodInspection
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
        $clanKorisnika = null;

        if (auth()->check() && !empty(auth()->user()->clan_id)) {
            $clanKorisnika = Clanovi::query()
                ->where('id', auth()->user()->clan_id)
                ->first(['id', 'Ime', 'Prezime', 'lijecnicki_do']);

            if ($clanKorisnika) {
                $statusLijecnickiKorisnika = $this->pripremiStatusLijecnickogZaClana($clanKorisnika);

                if ($paymentTrackingEnabled) {
                    $placanjeSummary = $paymentService->memberSummary($clanKorisnika);
                    $placanjeProfilPostavljen = isset($placanjeSummary['profile'])
                        && $placanjeSummary['profile'] !== null
                        && $placanjeSummary['profile']->paymentOption !== null;
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
                        $placanjeProfilPostavljen = isset($placanjeSummary['profile'])
                            && $placanjeSummary['profile'] !== null
                            && $placanjeSummary['profile']->paymentOption !== null;
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
                        $placanjeProfilPostavljen = isset($placanjeSummary['profile'])
                            && $placanjeSummary['profile'] !== null
                            && $placanjeSummary['profile']->paymentOption !== null;
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

        $stavkeRezultataIClanaka = $turniri->map(fn (Turniri $turnir) => [
            'tip' => 'turnir',
            'datum' => $turnir->datum,
            'model' => $turnir,
        ]);

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
                ])
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

    /** @noinspection PhpUndefinedMethodInspection */
    public function popisClanova(): Renderable
    {
        $paymentService = app(PaymentTrackingService::class);
        $clanovi = Clanovi::orderBy('Prezime')->orderBy('Ime')->get();
        $showPaymentColumn = auth()->check()
            && (int)auth()->user()->rola === 1
            && $paymentService->isEnabled();

        $paymentStatusByClan = [];
        if ($showPaymentColumn) {
            foreach ($clanovi as $clan) {
                $paymentStatusByClan[(int)$clan->id] = $paymentService->listStatusForClan($clan);
            }
        }

        return view('javno.clanovi', [
            'clanovi' => $clanovi,
            'paymentTrackingEnabled' => $paymentService->isEnabled(),
            'showPaymentColumn' => $showPaymentColumn,
            'paymentStatusByClan' => $paymentStatusByClan,
        ]);
    }

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
     * @noinspection PhpMissingReturnTypeInspection
     * @noinspection PhpUndefinedMethodInspection
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
            $profilPostavljen = isset($paymentSummary['profile'])
                && $paymentSummary['profile'] !== null
                && $paymentSummary['profile']->paymentOption !== null;
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
        // svi tipovi turnira u kojima se natjecao
        $tipoviTurnira = TipoviTurnira::whereHas('turniri', function ($query) use ($cl) {
            $query->whereHas('rezultatiOpci', function ($query) use ($cl) {
                $query->where('clan_id', '=', $cl);
            });
        })->get();

        // sve kategorije u kojima se natjecao
        $kategorije = Kategorije::whereHas('rezultatiOpci', function ($query) use ($cl) {
            $query->where('clan_id', '=', $cl);
        })->get();

        // svi stilovi u kojima se natjecao
        $stilovi = Stilovi::whereHas('rezultatiOpci', function ($query) use ($cl) {
            $query->where('clan_id', '=', $cl);
        })->get();

        //izvlačenje najboljih rezultata za streličara
        $i = 0;
        foreach ($tipoviTurnira as $tipturnira) {
            foreach ($stilovi as $stil) {
                foreach ($kategorije as $kategorija) {
                    $najboljiRezultat = RezultatiPoTipuTurnira::
                    whereHas('turnir', function ($query) use ($tipturnira) {
                        $query->where('tipovi_turnira_id', '=', $tipturnira->id);
                    })->whereHas('poljeZaTipTurnira', function ($query) {
                        $query->where('naziv', '=', 'Ukupno');
                    })->where('clan_id', '=', $cl)->where('kategorija_id', '=', $kategorija->id)->where('stil_id', '=', $stil->id)->max('rezultat');
                    if ($najboljiRezultat) {
                        $osobniRekordi[$i]['stil'] = $stil->naziv;
                        $osobniRekordi[$i]['kategorija'] = $kategorija->naziv;
                        $osobniRekordi[$i]['tipTurnira'] = $tipturnira->naziv;
                        $osobniRekordi[$i]['rezultat'] = $najboljiRezultat;

                        //turnir na kojem je postavio osobni rekord
                        $osobniRekordi[$i]['turnir'] = Turniri::find(RezultatiPoTipuTurnira::
                        whereHas('turnir', function ($query) use ($tipturnira) {
                            $query->where('tipovi_turnira_id', '=', $tipturnira->id);
                        })->whereHas('poljeZaTipTurnira', function ($query) {
                            $query->where('naziv', '=', 'Ukupno');
                        })->where('clan_id', '=', $cl)->where('kategorija_id', '=', $kategorija->id)->where('stil_id', '=', $stil->id)->where('rezultat', '=', $najboljiRezultat)->get()->first()->turnir_id);

                        //datumi ostvarenih maksimalnih rezultata
                        $datumiRekorda[] = $osobniRekordi[$i]['turnir']->datum;
                        $i++;
                    }
                }
            }
        }

        if (!isset($osobniRekordi)) $osobniRekordi = array();
        if (!isset($datumiRekorda)) $datumiRekorda = array();

        //popis svih turnira na kojima je sudjelovao (pojedinačno ili u timu)
        $turniriPopis = Turniri::where(function ($query) use ($cl) {
            $query->whereHas('rezultatiOpci', function ($rezultatiQuery) use ($cl) {
                $rezultatiQuery->where('clan_id', '=', $cl);
            });
            if ($this->timskeTabliceDostupne()) {
                $query->orWhereHas('rezultatiTimovi.clanoviStavke.rezultatOpci', function ($timQuery) use ($cl) {
                    $timQuery->where('clan_id', '=', $cl);
                });
            }
        })->orderByDesc('datum')->get();
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

    public function preuzmi_lijecnicki_pregled(Clanovi $clan, ClanLijecnickiPregled $pregled): BinaryFileResponse
    {
        $this->potvrdiPravoNaDokumente($clan);
        $this->potvrdiPripadnostClanu((int)$clan->id, (int)$pregled->clan_id);

        if (empty($pregled->putanja) || !Storage::disk('local')->exists($pregled->putanja)) {
            abort(404);
        }

        return response()->file(Storage::disk('local')->path($pregled->putanja));
    }

    public function preuzmi_dokument(Clanovi $clan, ClanDokument $dokument): BinaryFileResponse
    {
        $this->potvrdiPravoNaDokumente($clan);
        $this->potvrdiPripadnostClanu((int)$clan->id, (int)$dokument->clan_id);

        if (empty($dokument->putanja) || !Storage::disk('local')->exists($dokument->putanja)) {
            abort(404);
        }

        return response()->file(Storage::disk('local')->path($dokument->putanja));
    }

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

    private function potvrdiPripadnostClanu(int $clanId, int $resourceClanId): void
    {
        if ($clanId !== $resourceClanId) {
            abort(404);
        }
    }

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

    private function formatirajOibZaCsv(?string $oib): string
    {
        $vrijednost = trim((string)$oib);

        if ($vrijednost === '') {
            return '';
        }

        // OIB šaljemo kao tekstualnu formulu da Excel/LibreOffice ne maknu vodeće nule.
        return '="' . str_replace('"', '""', $vrijednost) . '"';
    }

    /** @noinspection PhpUndefinedFieldInspection
     * @noinspection PhpUndefinedMethodInspection
     */
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

    private function timskeTabliceDostupne(): bool
    {
        return Schema::hasTable('rezultati_timovi') && Schema::hasTable('rezultati_tim_clanovi');
    }

    /**
     * @return array
     */
    public function menu(): array
    {
        $menu['Obavijesti'] = Clanci::where('vrsta', '=', 'Obavijest')->where('menu', '=', '1')->orderByDesc('datum')->get(['id', 'menu_naslov']);
        $menu['O nama'] = Clanci::where('vrsta', '=', 'O nama')->where('menu', '=', '1')->orderByDesc('datum')->get(['id', 'menu_naslov']);
        $menu['Strelicarstvo'] = Clanci::where('vrsta', '=', 'Streličarstvo')->where('menu', '=', '1')->orderByDesc('datum')->get(['id', 'menu_naslov']);
        return $menu;
    }
}
