<?php

namespace App\Http\Controllers;

use App\Models\Clanovi;
use App\Models\TreninziDvorana;
use App\Models\TreninziVanjski;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class TreninziController extends Controller
{
    private const KONFIG_DVORANSKI = [
        'tip' => 'dvoranski',
        'naziv' => 'Dvoranski trening',
        'broj_serija' => 10,
        'broj_strijela_u_seriji' => 3,
        'ima_x_kolonu' => false,
    ];

    private const KONFIG_VANJSKI = [
        'tip' => 'vanjski',
        'naziv' => 'Vanjski trening',
        'broj_serija' => 6,
        'broj_strijela_u_seriji' => 6,
        'ima_x_kolonu' => true,
    ];

    public function index(): View
    {
        $clanKorisnika = $this->dohvatiPovezanogClana();
        $podaciPrikaza = $this->pripremiPrikazTreningaZaClana($clanKorisnika, (int)auth()->id());

        return view('javno.mojiTreninzi', [
            'clanKorisnika' => $clanKorisnika,
            'dvoranskiPrikaz' => $podaciPrikaza['dvoranskiPrikaz'],
            'vanjskiPrikaz' => $podaciPrikaza['vanjskiPrikaz'],
            'grafDvoranski' => $podaciPrikaza['grafDvoranski'],
            'grafVanjski' => $podaciPrikaza['grafVanjski'],
        ]);
    }

    public function adminIndex(Clanovi $clan): View
    {
        $this->potvrdiAdminPravo();
        $podaciPrikaza = $this->pripremiPrikazTreningaZaClana($clan);

        return view('javno.pregledTreningaClana', [
            'clan' => $clan,
            'dvoranskiPrikaz' => $podaciPrikaza['dvoranskiPrikaz'],
            'vanjskiPrikaz' => $podaciPrikaza['vanjskiPrikaz'],
            'grafDvoranski' => $podaciPrikaza['grafDvoranski'],
            'grafVanjski' => $podaciPrikaza['grafVanjski'],
            'mozeUredjivati' => true,
        ]);
    }

    public function pregledClana(Clanovi $clan): View
    {
        $this->potvrdiPravoNaPregledTreningaClana($clan);
        $podaciPrikaza = $this->pripremiPrikazTreningaZaClana($clan);

        return view('javno.pregledTreningaClana', [
            'clan' => $clan,
            'dvoranskiPrikaz' => $podaciPrikaza['dvoranskiPrikaz'],
            'vanjskiPrikaz' => $podaciPrikaza['vanjskiPrikaz'],
            'grafDvoranski' => $podaciPrikaza['grafDvoranski'],
            'grafVanjski' => $podaciPrikaza['grafVanjski'],
            'mozeUredjivati' => false,
        ]);
    }

    public function pripremiPrikazTreningaZaClana(Clanovi $clan, ?int $userId = null): array
    {
        $dvoranskiUpit = TreninziDvorana::query()
            ->where('clan_id', (int)$clan->id);
        $vanjskiUpit = TreninziVanjski::query()
            ->where('clan_id', (int)$clan->id);

        if (!is_null($userId)) {
            $dvoranskiUpit->where('user_id', $userId);
            $vanjskiUpit->where('user_id', $userId);
        }

        $dvoranskiPrikaz = $dvoranskiUpit
            ->orderByDesc('datum')
            ->orderByDesc('id')
            ->get()
            ->map(fn (TreninziDvorana $trening) => $this->pripremiTreningZaPrikaz($trening, self::KONFIG_DVORANSKI));
        $vanjskiPrikaz = $vanjskiUpit
            ->orderByDesc('datum')
            ->orderByDesc('id')
            ->get()
            ->map(fn (TreninziVanjski $trening) => $this->pripremiTreningZaPrikaz($trening, self::KONFIG_VANJSKI));

        return [
            'dvoranskiPrikaz' => $dvoranskiPrikaz,
            'vanjskiPrikaz' => $vanjskiPrikaz,
            'grafDvoranski' => $this->pripremiGrafPodatke($dvoranskiPrikaz),
            'grafVanjski' => $this->pripremiGrafPodatke($vanjskiPrikaz),
        ];
    }

    public function createDvoranski(): View
    {
        return $this->prikazUnosaTreninga(
            self::KONFIG_DVORANSKI,
            route('javno.treninzi.dvoranski.store')
        );
    }

    public function editDvoranski(TreninziDvorana $trening): View
    {
        $this->potvrdiVlasnistvoNadTreningom((int)$trening->user_id);
        $this->potvrdiPripadnostClanu((int)$this->dohvatiPovezanogClana()->id, (int)$trening->clan_id);

        return $this->prikazUnosaTreninga(
            self::KONFIG_DVORANSKI,
            route('javno.treninzi.dvoranski.update', $trening),
            'PUT',
            'Uredjivanje',
            'Spremi izmjene',
            $trening
        );
    }

    public function storeDvoranski(Request $request): RedirectResponse|JsonResponse
    {
        return $this->spremiTrening($request, self::KONFIG_DVORANSKI, function (Clanovi $clanKorisnika, string $datum, array $runda1, array $runda2): void {
            TreninziDvorana::create([
                'user_id' => (int)auth()->id(),
                'clan_id' => (int)$clanKorisnika->id,
                'datum' => $datum,
                'runda1' => $runda1,
                'runda2' => $runda2,
            ]);
        });
    }

    public function updateDvoranski(Request $request, TreninziDvorana $trening): RedirectResponse|JsonResponse
    {
        $this->potvrdiVlasnistvoNadTreningom((int)$trening->user_id);
        $this->potvrdiPripadnostClanu((int)$this->dohvatiPovezanogClana()->id, (int)$trening->clan_id);

        return $this->spremiTrening(
            $request,
            self::KONFIG_DVORANSKI,
            function (Clanovi $clanKorisnika, string $datum, array $runda1, array $runda2) use ($trening): void {
                $this->potvrdiPripadnostClanu((int)$clanKorisnika->id, (int)$trening->clan_id);
                $trening->update([
                    'datum' => $datum,
                    'runda1' => $runda1,
                    'runda2' => $runda2,
                ]);
            },
            'Dvoranski trening je ažuriran.'
        );
    }

    public function destroyDvoranski(TreninziDvorana $trening): RedirectResponse
    {
        $this->potvrdiVlasnistvoNadTreningom((int)$trening->user_id);
        $trening->delete();

        return redirect()->route('javno.treninzi.index')->with('success', 'Dvoranski trening je obrisan.');
    }

    public function destroyDvoranskiAdmin(Clanovi $clan, TreninziDvorana $trening): RedirectResponse
    {
        $this->potvrdiAdminPravo();
        $this->potvrdiPripadnostClanu((int)$clan->id, (int)$trening->clan_id);
        $trening->delete();

        return redirect()->route('admin.treninzi.index', $clan)->with('success', 'Dvoranski trening je obrisan.');
    }

    public function createVanjski(): View
    {
        return $this->prikazUnosaTreninga(
            self::KONFIG_VANJSKI,
            route('javno.treninzi.vanjski.store')
        );
    }

    public function editVanjski(TreninziVanjski $trening): View
    {
        $this->potvrdiVlasnistvoNadTreningom((int)$trening->user_id);
        $this->potvrdiPripadnostClanu((int)$this->dohvatiPovezanogClana()->id, (int)$trening->clan_id);

        return $this->prikazUnosaTreninga(
            self::KONFIG_VANJSKI,
            route('javno.treninzi.vanjski.update', $trening),
            'PUT',
            'Uredjivanje',
            'Spremi izmjene',
            $trening
        );
    }

    public function storeVanjski(Request $request): RedirectResponse|JsonResponse
    {
        return $this->spremiTrening($request, self::KONFIG_VANJSKI, function (Clanovi $clanKorisnika, string $datum, array $runda1, array $runda2): void {
            TreninziVanjski::create([
                'user_id' => (int)auth()->id(),
                'clan_id' => (int)$clanKorisnika->id,
                'datum' => $datum,
                'runda1' => $runda1,
                'runda2' => $runda2,
            ]);
        });
    }

    public function updateVanjski(Request $request, TreninziVanjski $trening): RedirectResponse|JsonResponse
    {
        $this->potvrdiVlasnistvoNadTreningom((int)$trening->user_id);
        $this->potvrdiPripadnostClanu((int)$this->dohvatiPovezanogClana()->id, (int)$trening->clan_id);

        return $this->spremiTrening(
            $request,
            self::KONFIG_VANJSKI,
            function (Clanovi $clanKorisnika, string $datum, array $runda1, array $runda2) use ($trening): void {
                $this->potvrdiPripadnostClanu((int)$clanKorisnika->id, (int)$trening->clan_id);
                $trening->update([
                    'datum' => $datum,
                    'runda1' => $runda1,
                    'runda2' => $runda2,
                ]);
            },
            'Vanjski trening je ažuriran.'
        );
    }

    public function destroyVanjski(TreninziVanjski $trening): RedirectResponse
    {
        $this->potvrdiVlasnistvoNadTreningom((int)$trening->user_id);
        $trening->delete();

        return redirect()->route('javno.treninzi.index')->with('success', 'Vanjski trening je obrisan.');
    }

    public function destroyVanjskiAdmin(Clanovi $clan, TreninziVanjski $trening): RedirectResponse
    {
        $this->potvrdiAdminPravo();
        $this->potvrdiPripadnostClanu((int)$clan->id, (int)$trening->clan_id);
        $trening->delete();

        return redirect()->route('admin.treninzi.index', $clan)->with('success', 'Vanjski trening je obrisan.');
    }

    private function prikazUnosaTreninga(
        array $konfig,
        string $formAction,
        string $formMethod = 'POST',
        string $naslovForme = 'Unos',
        string $submitLabel = 'Spremi',
        TreninziDvorana|TreninziVanjski|null $trening = null
    ): View
    {
        $clanKorisnika = $this->dohvatiPovezanogClana();

        if (!is_null($trening)) {
            $inicijalniUnos = [
                'runda1' => $this->normalizirajRundu(
                    $trening->runda1,
                    (int)$konfig['broj_serija'],
                    (int)$konfig['broj_strijela_u_seriji']
                ),
                'runda2' => $this->normalizirajRundu(
                    $trening->runda2,
                    (int)$konfig['broj_serija'],
                    (int)$konfig['broj_strijela_u_seriji']
                ),
            ];
        } else {
            $inicijalniUnos = [
                'runda1' => $this->inicijalnaRunda((int)$konfig['broj_serija'], (int)$konfig['broj_strijela_u_seriji']),
                'runda2' => $this->inicijalnaRunda((int)$konfig['broj_serija'], (int)$konfig['broj_strijela_u_seriji']),
            ];
        }

        $stariUnosJson = old('unos_json');
        if (!empty($stariUnosJson)) {
            $parsiraniUnos = json_decode((string)$stariUnosJson, true);
            if (is_array($parsiraniUnos)) {
                $inicijalniUnos = [
                    'runda1' => $this->normalizirajRundu(
                        $parsiraniUnos['runda1'] ?? [],
                        (int)$konfig['broj_serija'],
                        (int)$konfig['broj_strijela_u_seriji']
                    ),
                    'runda2' => $this->normalizirajRundu(
                        $parsiraniUnos['runda2'] ?? [],
                        (int)$konfig['broj_serija'],
                        (int)$konfig['broj_strijela_u_seriji']
                    ),
                ];
            }
        }

        return view('javno.mojiTreninziUnos', [
            'clanKorisnika' => $clanKorisnika,
            'konfig' => $konfig,
            'formAction' => $formAction,
            'formMethod' => strtoupper($formMethod),
            'naslovForme' => $naslovForme,
            'submitLabel' => $submitLabel,
            'closeRoute' => route('javno.treninzi.index'),
            'zadaniDatum' => old('datum', $trening?->datum?->format('Y-m-d') ?? date('Y-m-d')),
            'inicijalniUnos' => $inicijalniUnos,
        ]);
    }

    private function spremiTrening(
        Request $request,
        array $konfig,
        callable $saveCallback,
        ?string $uspjesnaPoruka = null
    ): RedirectResponse|JsonResponse
    {
        $clanKorisnika = $this->dohvatiPovezanogClana();

        $validirano = $request->validate([
            'datum' => ['required', 'date'],
            'unos_json' => ['required', 'string'],
            'zatvori_nakon_spremanja' => ['nullable', 'boolean'],
        ]);

        $parsiraniUnos = json_decode((string)$validirano['unos_json'], true);
        if (!is_array($parsiraniUnos)) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Podaci treninga nisu ispravni.');
        }

        $runda1 = $this->normalizirajRundu(
            $parsiraniUnos['runda1'] ?? [],
            (int)$konfig['broj_serija'],
            (int)$konfig['broj_strijela_u_seriji']
        );
        $runda2 = $this->normalizirajRundu(
            $parsiraniUnos['runda2'] ?? [],
            (int)$konfig['broj_serija'],
            (int)$konfig['broj_strijela_u_seriji']
        );

        $saveCallback($clanKorisnika, $validirano['datum'], $runda1, $runda2);

        $poruka = $uspjesnaPoruka ?? ($konfig['naziv'] . ' je spremljen.');

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $poruka,
                'redirect' => $request->boolean('zatvori_nakon_spremanja') ? route('javno.treninzi.index') : null,
            ]);
        }

        if ($request->boolean('zatvori_nakon_spremanja')) {
            return redirect()
                ->route('javno.treninzi.index')
                ->with('success', $poruka);
        }

        return redirect()
            ->back()
            ->with('saved_toast', $poruka);
    }

    private function pripremiTreningZaPrikaz(TreninziDvorana|TreninziVanjski $trening, array $konfig): array
    {
        $runda1 = $this->izracunajRundu(
            $this->normalizirajRundu($trening->runda1, (int)$konfig['broj_serija'], (int)$konfig['broj_strijela_u_seriji']),
            $konfig
        );
        $runda2 = $this->izracunajRundu(
            $this->normalizirajRundu($trening->runda2, (int)$konfig['broj_serija'], (int)$konfig['broj_strijela_u_seriji']),
            $konfig
        );
        $statistika = $this->izracunajStatistiku($runda1, $runda2);

        return [
            'trening' => $trening,
            'konfig' => $konfig,
            'runda1' => $runda1,
            'runda2' => $runda2,
            'ukupno' => [
                'imaUnosa' => $runda1['imaUnosa'] || $runda2['imaUnosa'],
                'total' => $runda1['total'] + $runda2['total'],
                'devetke' => $runda1['devetke'] + $runda2['devetke'],
                'desetke' => $runda1['desetke'] + $runda2['desetke'],
                'x' => ($runda1['x'] ?? 0) + ($runda2['x'] ?? 0),
            ],
            'statistika' => $statistika,
            'graf' => [
                'datum_sort' => $trening->datum ? $trening->datum->format('Y-m-d') : null,
                'datum_label' => $trening->datum ? $trening->datum->format('d.m.Y.') : '-',
                'total' => ($runda1['imaUnosa'] || $runda2['imaUnosa']) ? ($runda1['total'] + $runda2['total']) : null,
            ],
        ];
    }

    private function pripremiGrafPodatke(Collection $treninziPrikaz): array
    {
        return $treninziPrikaz
            ->filter(fn (array $stavka) => !is_null($stavka['graf']['total']))
            ->sortBy('graf.datum_sort')
            ->values()
            ->map(fn (array $stavka) => [
                'datum' => $stavka['graf']['datum_label'],
                'total' => $stavka['graf']['total'],
            ])
            ->all();
    }

    private function izracunajRundu(array $runda, array $konfig): array
    {
        $serije = [];
        $ukupniTotal = 0;
        $ukupnoDevetki = 0;
        $ukupnoDesetki = 0;
        $ukupnoXeva = 0;
        $vrijednostiPogodaka = [];

        for ($i = 0; $i < (int)$konfig['broj_serija']; $i++) {
            $red = is_array($runda[$i] ?? null) ? $runda[$i] : [];
            $pogodci = [];
            $vrijednostiReda = [];
            $brojDevetkiRed = 0;
            $brojDesetkiRed = 0;
            $brojXevaRed = 0;

            for ($j = 0; $j < (int)$konfig['broj_strijela_u_seriji']; $j++) {
                $pogodak = $this->normalizirajPogodak($red[$j] ?? null);
                $pogodci[] = $pogodak;

                $vrijednost = $this->vrijednostPogotka($pogodak);
                if (!is_null($vrijednost)) {
                    $vrijednostiReda[] = $vrijednost;
                    $vrijednostiPogodaka[] = $vrijednost;
                    if ($vrijednost === 9) {
                        $brojDevetkiRed++;
                    }
                    if ($vrijednost === 10) {
                        $brojDesetkiRed++;
                    }
                    if ($pogodak === 'X') {
                        $brojXevaRed++;
                    }
                }
            }

            $imaUnosa = count($vrijednostiReda) > 0;
            $zbrojReda = $imaUnosa ? array_sum($vrijednostiReda) : null;

            if ($imaUnosa && !is_null($zbrojReda)) {
                $ukupniTotal += $zbrojReda;
                $ukupnoDevetki += $brojDevetkiRed;
                $ukupnoDesetki += $brojDesetkiRed;
                $ukupnoXeva += $brojXevaRed;
            }

            $serije[] = [
                'broj' => $i + 1,
                'pogodci' => $pogodci,
                'zbroj' => $zbrojReda,
                'total' => $imaUnosa ? $ukupniTotal : null,
                'devetke' => $imaUnosa ? $brojDevetkiRed : null,
                'desetke' => $imaUnosa ? $brojDesetkiRed : null,
                'x' => ($konfig['ima_x_kolonu'] && $imaUnosa) ? $brojXevaRed : null,
                'imaUnosa' => $imaUnosa,
            ];
        }

        return [
            'serije' => $serije,
            'imaUnosa' => count($vrijednostiPogodaka) > 0,
            'total' => $ukupniTotal,
            'devetke' => $ukupnoDevetki,
            'desetke' => $ukupnoDesetki,
            'x' => $konfig['ima_x_kolonu'] ? $ukupnoXeva : null,
            'vrijednosti' => $vrijednostiPogodaka,
        ];
    }

    private function izracunajStatistiku(array $runda1, array $runda2): array
    {
        $sveVrijednosti = array_merge($runda1['vrijednosti'], $runda2['vrijednosti']);
        $prosjek = null;
        $najcesciPogodak = null;
        $najcesciPogodakBroj = null;

        if (count($sveVrijednosti) > 0) {
            $prosjek = round(array_sum($sveVrijednosti) / count($sveVrijednosti), 2);
            $frekvencije = array_count_values($sveVrijednosti);
            $najvecaFrekvencija = max($frekvencije);
            $najcesciPogoci = [];

            foreach ($frekvencije as $vrijednost => $broj) {
                if ($broj === $najvecaFrekvencija) {
                    $najcesciPogoci[] = $this->vrijednostPogotkaUOznaku((int)$vrijednost);
                }
            }

            $najcesciPogodak = implode(', ', $najcesciPogoci);
            $najcesciPogodakBroj = $najvecaFrekvencija;
        }

        $sveSerije = [];
        foreach ([1 => $runda1['serije'], 2 => $runda2['serije']] as $brojRunde => $serijeRunde) {
            foreach ($serijeRunde as $serija) {
                if ($serija['imaUnosa'] && !is_null($serija['zbroj'])) {
                    $pogodci = array_values(array_filter(
                        $serija['pogodci'] ?? [],
                        fn ($pogodak) => !is_null($pogodak)
                    ));
                    $xPogoci = array_values(array_filter(
                        $pogodci,
                        fn ($pogodak) => $pogodak === 'X'
                    ));
                    $potpisBezXIDesetki = array_values(array_filter(
                        $pogodci,
                        fn ($pogodak) => $pogodak !== 'X' && $pogodak !== '10'
                    ));
                    sort($potpisBezXIDesetki);

                    $sveSerije[] = [
                        'oznaka' => 'R' . $brojRunde . '/S' . $serija['broj'],
                        'zbroj' => (int)$serija['zbroj'],
                        'brojXeva' => count($xPogoci),
                        'potpisBezXIDesetki' => implode('|', $potpisBezXIDesetki),
                    ];
                }
            }
        }

        $najboljeSerije = null;
        $najboljiZbroj = null;
        $najlosijeSerije = null;
        $najlosijiZbroj = null;

        if (count($sveSerije) > 0) {
            $najboljiZbroj = max(array_column($sveSerije, 'zbroj'));
            $najlosijiZbroj = min(array_column($sveSerije, 'zbroj'));

            $kandidatiNajbolje = array_values(array_filter(
                $sveSerije,
                fn (array $serija) => $serija['zbroj'] === $najboljiZbroj
            ));
            $kandidatiNajlosije = array_values(array_filter(
                $sveSerije,
                fn (array $serija) => $serija['zbroj'] === $najlosijiZbroj
            ));

            $odabraneNajbolje = [];
            $grupeNajboljih = [];
            foreach ($kandidatiNajbolje as $kandidat) {
                $grupeNajboljih[$kandidat['potpisBezXIDesetki']][] = $kandidat;
            }
            foreach ($grupeNajboljih as $grupa) {
                $maxX = max(array_column($grupa, 'brojXeva'));
                foreach ($grupa as $kandidat) {
                    if ($kandidat['brojXeva'] === $maxX) {
                        $odabraneNajbolje[] = $kandidat;
                    }
                }
            }

            $odabraneNajlosije = [];
            $grupeNajlosijih = [];
            foreach ($kandidatiNajlosije as $kandidat) {
                $grupeNajlosijih[$kandidat['potpisBezXIDesetki']][] = $kandidat;
            }
            foreach ($grupeNajlosijih as $grupa) {
                $minX = min(array_column($grupa, 'brojXeva'));
                foreach ($grupa as $kandidat) {
                    if ($kandidat['brojXeva'] === $minX) {
                        $odabraneNajlosije[] = $kandidat;
                    }
                }
            }

            $najboljeSerije = implode(', ', array_map(
                fn (array $serija) => $serija['oznaka'],
                $odabraneNajbolje
            ));
            $najlosijeSerije = implode(', ', array_map(
                fn (array $serija) => $serija['oznaka'],
                $odabraneNajlosije
            ));
        }

        return [
            'prosjek' => $prosjek,
            'najcesciPogodak' => $najcesciPogodak,
            'najcesciPogodakBroj' => $najcesciPogodakBroj,
            'najboljeSerije' => $najboljeSerije,
            'najboljiZbroj' => $najboljiZbroj,
            'najlosijeSerije' => $najlosijeSerije,
            'najlosijiZbroj' => $najlosijiZbroj,
        ];
    }

    private function inicijalnaRunda(int $brojSerija, int $brojStrijelaUSeriji): array
    {
        $runda = [];
        for ($i = 0; $i < $brojSerija; $i++) {
            $runda[] = array_fill(0, $brojStrijelaUSeriji, null);
        }

        return $runda;
    }

    private function normalizirajRundu(mixed $runda, int $brojSerija, int $brojStrijelaUSeriji): array
    {
        $normaliziranaRunda = [];

        for ($i = 0; $i < $brojSerija; $i++) {
            $red = is_array($runda) && array_key_exists($i, $runda) && is_array($runda[$i]) ? $runda[$i] : [];
            $normaliziraniRed = [];

            for ($j = 0; $j < $brojStrijelaUSeriji; $j++) {
                $normaliziraniRed[] = $this->normalizirajPogodak($red[$j] ?? null);
            }

            $normaliziranaRunda[$i] = $this->posloziRedPogodaka($normaliziraniRed, $brojStrijelaUSeriji);
        }

        return $normaliziranaRunda;
    }

    private function posloziRedPogodaka(array $red, int $brojStrijelaUSeriji): array
    {
        $redoslijed = [
            'X' => 0,
            '10' => 1,
            '9' => 2,
            '8' => 3,
            '7' => 4,
            '6' => 5,
            '5' => 6,
            '4' => 7,
            '3' => 8,
            '2' => 9,
            '1' => 10,
            'M' => 11,
        ];

        $uneseniPogoci = array_values(array_filter($red, fn (?string $pogodak) => !is_null($pogodak)));

        usort($uneseniPogoci, fn (string $a, string $b) => ($redoslijed[$a] ?? 999) <=> ($redoslijed[$b] ?? 999));

        $praznaPolja = array_fill(0, max($brojStrijelaUSeriji - count($uneseniPogoci), 0), null);

        return array_merge($uneseniPogoci, $praznaPolja);
    }

    private function normalizirajPogodak(mixed $pogodak): ?string
    {
        if (is_null($pogodak)) {
            return null;
        }

        $vrijednost = strtoupper(trim((string)$pogodak));
        if ($vrijednost === '') {
            return null;
        }

        if ($vrijednost === 'X' || $vrijednost === 'M') {
            return $vrijednost;
        }

        if (ctype_digit($vrijednost)) {
            $broj = (int)$vrijednost;
            if ($broj >= 1 && $broj <= 10) {
                return (string)$broj;
            }
        }

        return null;
    }

    private function vrijednostPogotka(?string $pogodak): ?int
    {
        if (is_null($pogodak)) {
            return null;
        }

        if ($pogodak === 'X' || $pogodak === '10') {
            return 10;
        }

        if ($pogodak === 'M') {
            return 0;
        }

        if (ctype_digit($pogodak)) {
            $broj = (int)$pogodak;
            if ($broj >= 1 && $broj <= 9) {
                return $broj;
            }
        }

        return null;
    }

    private function vrijednostPogotkaUOznaku(int $vrijednost): string
    {
        if ($vrijednost === 0) {
            return 'M';
        }

        return (string)$vrijednost;
    }

    private function dohvatiPovezanogClana(): Clanovi
    {
        if (!auth()->check() || empty(auth()->user()->clan_id)) {
            abort(403);
        }

        $clan = Clanovi::find((int)auth()->user()->clan_id);
        if (!$clan) {
            abort(403);
        }

        return $clan;
    }

    private function potvrdiVlasnistvoNadTreningom(int $userIdTreninga): void
    {
        if (!auth()->check() || (int)auth()->id() !== $userIdTreninga) {
            abort(403);
        }
    }

    private function potvrdiAdminPravo(): void
    {
        if (!auth()->check() || (int)auth()->user()->rola !== 1) {
            abort(403);
        }
    }

    private function potvrdiPravoNaPregledTreningaClana(Clanovi $clan): void
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
}
