<?php

namespace App\Http\Controllers;

use App\Models\ClanDokument;
use App\Models\Clanovi;
use App\Models\PolaznikPaymentCharge;
use App\Models\PolaznikSkole;
use App\Models\PolaznikSkoleDolazak;
use App\Models\PolaznikSkoleDokument;
use App\Services\SchoolPaymentService;
use Carbon\CarbonInterface;
use InvalidArgumentException;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

/**
 * Admin i korisnički kontroler za evidenciju polaznika škole, dolazaka, dokumenata i školarine.
 */
class PolazniciSkoleController extends Controller
{
    private const BROJ_DOLAZAKA = 16;
    private const DOKUMENTI_EKSTENZIJE = 'pdf,doc,docx,jpg,jpeg,png,webp,xls,xlsx';
    private const DOKUMENTI_VRSTE = ['Upisnica', 'GDPR', 'Slika', 'Ostalo'];

    /**
     * Učitava servise za rad s polaznicima škole i evidencijom školarine.
     */
    public function __construct(private readonly SchoolPaymentService $schoolPaymentService)
    {
    }

    /**
     * Prikazuje popis polaznika škole s ključnim podacima i statusom školarine.
     */
    public function index(): View
    {
        $this->oznaciIsteklePolaznike();
        $isAdmin = $this->jeAdmin();
        $paymentTrackingEnabled = $this->schoolPaymentService->isEnabled();
        $showPaymentColumn = $isAdmin && $paymentTrackingEnabled;
        $paymentStatusByPolaznik = [];

        $aktivniPolaznici = $this->queryAktivniPolazniciSkole()
            ->with('povezaniKorisnik')
            ->orderBy('Prezime')
            ->orderBy('Ime')
            ->get();

        if ($paymentTrackingEnabled) {
            foreach ($aktivniPolaznici as $polaznik) {
                if (!$polaznik instanceof PolaznikSkole) {
                    continue;
                }

                if ($showPaymentColumn) {
                    $paymentStatusByPolaznik[(int)$polaznik->id] = $this->schoolPaymentService->listStatusForPolaznik($polaznik);
                } else {
                    $this->schoolPaymentService->summary($polaznik);
                }
            }
        }

        $neaktivniPolaznici = collect();
        if ($this->jeAdminIliClan()) {
            $neaktivniPolaznici = $this->queryNeaktivniPolazniciSkole()
                ->with('povezaniKorisnik')
                ->orderBy('Prezime')
                ->orderBy('Ime')
                ->get();

            if ($showPaymentColumn) {
                foreach ($neaktivniPolaznici as $polaznik) {
                    if (!$polaznik instanceof PolaznikSkole) {
                        continue;
                    }

                    $paymentStatusByPolaznik[(int)$polaznik->id] = $this->schoolPaymentService->listStatusForPolaznik($polaznik);
                }
            }
        }

        return view('javno.polazniciSkole.index', [
            'aktivniPolaznici' => $aktivniPolaznici,
            'neaktivniPolaznici' => $neaktivniPolaznici,
            'paymentTrackingEnabled' => $paymentTrackingEnabled,
            'showPaymentColumn' => $showPaymentColumn,
            'paymentStatusByPolaznik' => $paymentStatusByPolaznik,
        ]);
    }

    /**
     * Prikazuje i sprema evidenciju dolazaka polaznika škole po datumima treninga.
     */
    public function evidencijaDolasaka(): View
    {
        $this->potvrdiAdmina();

        $this->oznaciIsteklePolaznike();

        $polaznici = $this->queryAktivniPolazniciSkole()
            ->with(['dolasci' => fn ($query) => $query->orderBy('redni_broj')])
            ->orderBy('Prezime')
            ->orderBy('Ime')
            ->get();

        if ($this->schoolPaymentService->isEnabled()) {
            foreach ($polaznici as $polaznik) {
                $this->schoolPaymentService->summary($polaznik);
            }
        }

        return view('javno.polazniciSkole.evidencija', [
            'polaznici' => $polaznici,
            'mozeUredjivati' => $this->jeAdmin(),
        ]);
    }

    /**
     * Sprema evidenciju dolazaka polaznika škole po rednim brojevima treninga.
     */
    public function spremiEvidencijuDolasaka(Request $request): RedirectResponse
    {
        $this->potvrdiAdmina();

        $request->validate([
            'dolasci' => ['nullable', 'array'],
            'dolasci.*' => ['nullable', 'array'],
            'dolasci.*.*' => ['nullable', 'date'],
        ]);

        $this->oznaciIsteklePolaznike();

        $aktivniPolaznici = $this->queryAktivniPolazniciSkole()
            ->get(['id'])
            ->keyBy('id');

        try {
            DB::transaction(function () use ($request, $aktivniPolaznici): void {
                foreach ((array)$request->input('dolasci', []) as $polaznikId => $dolasci) {
                    $polaznik = $aktivniPolaznici->get((int)$polaznikId);
                    if (!$polaznik instanceof PolaznikSkole) {
                        continue;
                    }

                    $this->syncDolasci($polaznik, is_array($dolasci) ? $dolasci : []);
                }
            });
        } catch (Throwable $exception) {
            report($exception);

            return redirect()
                ->route('javno.skola.evidencija.index')
                ->with('error', 'Spremanje evidencije dolazaka nije uspjelo.');
        }

        return redirect()
            ->route('javno.skola.evidencija.index')
            ->with('success', 'Evidencija dolazaka je spremljena.');
    }

    /**
     * Dohvaća detalje jedne stavke i priprema ih za prikaz.
     */
    public function show(PolaznikSkole $polaznik): View
    {
        $this->potvrdiPravoNaPolaznika($polaznik);

        $jeAdmin = $this->jeAdmin();
        $jeVlastitiPolaznik = $this->jeVlastitiPolaznik($polaznik);
        $jeRoditeljPolaznika = $this->jeRoditeljPovezanSaPolaznikom($polaznik);
        $mozeVidjetiPunePodatke = $jeAdmin || $jeVlastitiPolaznik || $jeRoditeljPolaznika;
        $mozeVidjetiDokumente = $mozeVidjetiPunePodatke;
        $mozeVidjetiEvidencijuDolasaka = $mozeVidjetiPunePodatke;
        $relacije = [
            'dolasci' => fn ($query) => $query->orderBy('redni_broj'),
            'povezaniKorisnik',
            'prebacenClan',
        ];

        if ($mozeVidjetiDokumente) {
            $relacije['dokumenti'] = fn ($query) => $query->orderByDesc('datum_dokumenta')->orderByDesc('id');
        }

        $polaznik->load($relacije);

        $schoolPaymentSummary = null;
        $schoolPaymentNotice = null;
        $schoolPaymentEnabled = false;
        if ($mozeVidjetiPunePodatke && $this->schoolPaymentService->isEnabled()) {
            $schoolPaymentSummary = $this->schoolPaymentService->summary($polaznik);
            $schoolPaymentNotice = $this->schoolPaymentService->noticeForPolaznik($polaznik);
            $schoolPaymentEnabled = (bool)($schoolPaymentSummary['enabled'] ?? false);
        }

        return view('javno.polazniciSkole.profil', [
            'polaznik' => $polaznik,
            'dolasci' => $this->normaliziraniDolasci($polaznik),
            'mozeUredjivati' => $jeAdmin,
            'mozeVidjetiPunePodatke' => $mozeVidjetiPunePodatke,
            'mozeVidjetiEvidencijuDolasaka' => $mozeVidjetiEvidencijuDolasaka,
            'mozeVidjetiDokumente' => $mozeVidjetiDokumente,
            'mozeUredjivatiDokumente' => $jeAdmin,
            'schoolPaymentEnabled' => $schoolPaymentEnabled,
            'schoolPaymentSummary' => $schoolPaymentSummary,
            'schoolPaymentNotice' => $schoolPaymentNotice,
            'otvoriPlacanja' => request()->boolean('open_payments'),
        ]);
    }

    /**
     * Upisuje novog polaznika škole i inicijalizira evidenciju dolazaka/školarine.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->potvrdiAdmina();
        $validated = $this->validirajPolaznika($request);

        try {
            $polaznik = DB::transaction(function () use ($validated): PolaznikSkole {
                $polaznik = new PolaznikSkole();
                $this->mapirajPodatkePolaznika($polaznik, $validated);
                $polaznik->u_skoli = true;
                $polaznik->save();

                $this->syncDolasci($polaznik, $validated['dolasci'] ?? []);

                return $polaznik;
            });
        } catch (Throwable $exception) {
            report($exception);

            return redirect()
                ->route('javno.skola.polaznici.index')
                ->withInput()
                ->with('error', 'Spremanje polaznika nije uspjelo.');
        }

        $this->schoolPaymentService->ensureProfile($polaznik, (int)auth()->id());
        $this->schoolPaymentService->summary($polaznik);

        return redirect()
            ->route('javno.skola.polaznici.show', $polaznik)
            ->with('success', 'Polaznik škole je uspješno dodan.');
    }

    /**
     * Ažurira osobne podatke polaznika škole.
     */
    public function update(Request $request, PolaznikSkole $polaznik): RedirectResponse
    {
        $this->potvrdiAdmina();
        $validated = $this->validirajPolaznika($request, $polaznik);
        $uSkoli = $request->boolean('u_skoli');

        try {
            DB::transaction(function () use ($polaznik, $validated, $uSkoli): void {
                $this->mapirajPodatkePolaznika($polaznik, $validated);
                $polaznik->u_skoli = $uSkoli;
                $polaznik->save();

                $this->syncDolasci($polaznik, $validated['dolasci'] ?? []);
            });
        } catch (Throwable $exception) {
            report($exception);

            return redirect()
                ->route('javno.skola.polaznici.show', ['polaznik' => $polaznik, 'open_documents' => request()->boolean('open_documents') ? 1 : 0])
                ->with('error', 'Spremanje podataka polaznika nije uspjelo.');
        }

        $this->schoolPaymentService->ensureProfile($polaznik, (int)auth()->id());
        $this->schoolPaymentService->summary($polaznik);

        return redirect()
            ->route('javno.skola.polaznici.show', ['polaznik' => $polaznik, 'open_documents' => request()->boolean('open_documents') ? 1 : 0])
            ->with('success', 'Podaci polaznika su spremljeni.');
    }

    /**
     * Sprema model školarine polaznika (oslobođen, jednokratno ili u dvije rate).
     */
    public function spremiSkolarinaProfil(Request $request, PolaznikSkole $polaznik): RedirectResponse
    {
        $this->potvrdiAdmina();

        $validated = $request->validate([
            'payment_mode' => ['required', Rule::in([
                SchoolPaymentService::MODE_FULL,
                SchoolPaymentService::MODE_INSTALLMENTS,
                SchoolPaymentService::MODE_EXEMPT,
            ])],
        ], [
            'payment_mode.required' => 'Potrebno je odabrati model školarine.',
            'payment_mode.in' => 'Odabrani model školarine nije podržan.',
        ]);

        $this->schoolPaymentService->assignMode(
            $polaznik,
            (string)$validated['payment_mode'],
            (int)auth()->id()
        );

        return redirect()
            ->route('javno.skola.polaznici.show', ['polaznik' => $polaznik, 'open_payments' => 1])
            ->with('success', 'Model školarine je spremljen.');
    }

    /**
     * Administrator potvrđuje uplatu školarine polaznika (u cijelosti ili u dvije rate).
     */
    public function updateSkolarinaStatus(
        Request $request,
        PolaznikSkole $polaznik,
        PolaznikPaymentCharge $charge
    ): RedirectResponse {
        $this->potvrdiAdmina();

        $validated = $request->validate([
            'is_paid' => ['nullable', 'boolean'],
            'paid_at' => ['nullable', 'date'],
            'settlement_type' => ['nullable', Rule::in([
                SchoolPaymentService::SETTLEMENT_FULL,
                SchoolPaymentService::SETTLEMENT_HALF,
            ])],
        ], [
            'paid_at.date' => 'Datum uplate nije ispravan.',
            'settlement_type.in' => 'Način podmirenja nije podržan.',
        ]);

        try {
            $this->schoolPaymentService->updateChargeStatus(
                $polaznik,
                $charge,
                (bool)($validated['is_paid'] ?? false),
                is_string($validated['paid_at'] ?? null) ? $validated['paid_at'] : null,
                is_string($validated['settlement_type'] ?? null) ? $validated['settlement_type'] : null,
                (int)auth()->id()
            );
        } catch (InvalidArgumentException $exception) {
            return redirect()
                ->route('javno.skola.polaznici.show', ['polaznik' => $polaznik, 'open_payments' => 1])
                ->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('javno.skola.polaznici.show', ['polaznik' => $polaznik, 'open_payments' => 1])
            ->with('success', 'Status školarine je ažuriran.');
    }

    /**
     * Briše polaznika škole i povezane evidencije (dolasci, dokumenti, školarina).
     */
    public function destroy(PolaznikSkole $polaznik): RedirectResponse
    {
        $this->potvrdiAdmina();

        try {
            DB::transaction(function () use ($polaznik): void {
                $polaznik->load(['dokumenti', 'povezaniKorisnik']);

                foreach ($polaznik->dokumenti as $dokument) {
                    $this->obrisiDatotekuAkoPostoji($dokument->putanja);
                }
                Storage::disk('local')->deleteDirectory('private/polaznici_skole/' . $polaznik->id);

                $korisnik = $polaznik->povezaniKorisnik;
                if ($korisnik !== null) {
                    $korisnik->polaznik_id = null;
                    if ((int)$korisnik->rola === 4) {
                        $korisnik->rola = 3;
                    }
                    $korisnik->save();
                }

                $polaznik->delete();
            });
        } catch (Throwable $exception) {
            report($exception);

            return redirect()
                ->route('javno.skola.polaznici.index')
                ->with('error', 'Brisanje polaznika nije uspjelo.');
        }

        return redirect()
            ->route('javno.skola.polaznici.index')
            ->with('success', 'Polaznik škole je obrisan.');
    }

    /**
     * Sprema novi dokument polaznika škole u njegov privatni profil.
     */
    public function spremiDokument(Request $request, PolaznikSkole $polaznik): RedirectResponse
    {
        $this->potvrdiAdmina();

        $validator = validator($request->all(), [
            'vrsta' => ['required', 'string', Rule::in(self::DOKUMENTI_VRSTE)],
            'naziv' => 'nullable|string|max:255|required_if:vrsta,Ostalo',
            'datum_dokumenta' => 'nullable|date',
            'napomena' => 'nullable|string|max:1000',
            'dokument' => 'required|mimes:' . self::DOKUMENTI_EKSTENZIJE,
        ], [
            'vrsta.required' => 'Potrebno je odabrati vrstu dokumenta.',
            'vrsta.in' => 'Odabrana vrsta dokumenta nije podržana.',
            'naziv.required_if' => 'Potrebno je unijeti naziv dokumenta za vrstu Ostalo.',
            'dokument.required' => 'Nije odabrana datoteka.',
            'dokument.mimes' => 'Datoteka mora biti PDF, DOC, DOCX, JPG, JPEG, PNG, WEBP, XLS ili XLSX.',
        ]);

        if ($validator->errors()->isNotEmpty()) {
            return redirect()
                ->route('javno.skola.polaznici.show', ['polaznik' => $polaznik, 'open_documents' => 1])
                ->with('error', $validator->errors()->first());
        }

        $pohrana = $this->spremiDatoteku('private/polaznici_skole/' . $polaznik->id . '/dokumenti');

        $vrsta = (string)$request->input('vrsta');
        PolaznikSkoleDokument::query()->create([
            'polaznik_skole_id' => $polaznik->id,
            'vrsta' => $vrsta,
            'naziv' => $this->odrediNazivDokumenta($vrsta, (string)$request->input('naziv')),
            'datum_dokumenta' => $request->input('datum_dokumenta'),
            'napomena' => $request->input('napomena'),
            'putanja' => $pohrana['putanja'],
            'originalni_naziv' => $pohrana['originalni_naziv'],
            'created_by' => auth()->id(),
        ]);

        return redirect()
            ->route('javno.skola.polaznici.show', ['polaznik' => $polaznik, 'open_documents' => 1])
            ->with('success', 'Dokument polaznika je spremljen.');
    }

    /**
     * Briše dokument polaznika škole i pripadajuću datoteku sa diska.
     */
    public function obrisiDokument(PolaznikSkole $polaznik, PolaznikSkoleDokument $dokument): RedirectResponse
    {
        $this->potvrdiAdmina();
        $this->potvrdiPripadnostPolazniku((int)$polaznik->id, (int)$dokument->polaznik_skole_id);

        $this->obrisiDatotekuAkoPostoji($dokument->putanja);
        $dokument->delete();

        return redirect()
            ->route('javno.skola.polaznici.show', ['polaznik' => $polaznik, 'open_documents' => 1])
            ->with('success', 'Dokument polaznika je obrisan.');
    }

    /**
     * Omogućuje preuzimanje dokumenta polaznika škole uz provjeru prava pristupa.
     */
    public function preuzmiDokument(PolaznikSkole $polaznik, PolaznikSkoleDokument $dokument): BinaryFileResponse
    {
        $this->potvrdiPravoNaDokumentPolaznika($polaznik);
        $this->potvrdiPripadnostPolazniku((int)$polaznik->id, (int)$dokument->polaznik_skole_id);

        if (empty($dokument->putanja) || !Storage::disk('local')->exists($dokument->putanja)) {
            abort(404);
        }

        return response()->file(Storage::disk('local')->path($dokument->putanja));
    }

    /**
     * Prebacuje polaznika škole u člana kluba i prenosi relevantne podatke/dokumente.
     */
    public function prebaciUClana(PolaznikSkole $polaznik): RedirectResponse
    {
        $this->potvrdiAdmina();

        if (!$polaznik->u_skoli && !empty($polaznik->prebacen_u_clana_id)) {
            return redirect()
                ->route('javno.clanovi.prikaz_clana', $polaznik->prebacen_u_clana_id)
                ->with('success', 'Polaznik je već prebačen u članove kluba.');
        }

        if (empty($polaznik->oib) || empty($polaznik->Ime) || empty($polaznik->Prezime)) {
            return redirect()
                ->route('javno.skola.polaznici.show', $polaznik)
                ->with('error', 'Za prebacivanje u članove obavezni su ime, prezime i OIB.');
        }

        try {
            $clan = DB::transaction(function () use ($polaznik): Clanovi {
                $polaznik->load(['dokumenti', 'povezaniKorisnik', 'roditelji']);

                $clan = Clanovi::query()->where('oib', $polaznik->oib)->first();

                if ($clan === null) {
                    $clan = new Clanovi();
                    $clan->oib = $polaznik->oib;
                }

                $clan->Prezime = $polaznik->Prezime;
                $clan->Ime = $polaznik->Ime;
                $clan->datum_rodjenja = $polaznik->datum_rodjenja ?? $clan->datum_rodjenja;
                $clan->br_telefona = $polaznik->br_telefona ?? $clan->br_telefona;
                $clan->email = $polaznik->email ?? $clan->email;
                $clan->spol = $polaznik->spol ?? ($clan->spol ?: 'M');
                $clan->clan_od = (int)date('Y');
                if (empty($clan->datum_pocetka_clanstva)) {
                    $clan->datum_pocetka_clanstva = now()->toDateString();
                }
                $clan->aktivan = true;
                $clan->broj_licence = $clan->broj_licence ?: 'nema licencu';
                $clan->save();

                foreach ($polaznik->dokumenti as $dokumentPolaznika) {
                    $novaPutanja = $this->kopirajDokumentZaClana($dokumentPolaznika, (int)$clan->id);

                    ClanDokument::create([
                        'clan_id' => $clan->id,
                        'vrsta' => $dokumentPolaznika->vrsta,
                        'naziv' => $dokumentPolaznika->naziv,
                        'datum_dokumenta' => $dokumentPolaznika->datum_dokumenta,
                        'putanja' => $novaPutanja,
                        'originalni_naziv' => $dokumentPolaznika->originalni_naziv,
                        'napomena' => $dokumentPolaznika->napomena,
                        'created_by' => auth()->id(),
                    ]);
                }

                $polaznik->u_skoli = false;
                $polaznik->prebacen_u_clana_id = $clan->id;
                $polaznik->prebacen_at = now();
                $polaznik->save();

                $postojeciRoditeljiClana = $clan->roditelji()->pluck('users.id')->all();
                $roditeljiPolaznika = $polaznik->roditelji->pluck('id')->all();
                $slobodnaMjestaRoditelja = max(2 - count($postojeciRoditeljiClana), 0);
                if ($slobodnaMjestaRoditelja > 0) {
                    $kandidati = array_values(array_diff($roditeljiPolaznika, $postojeciRoditeljiClana));
                    $zaPrebaciti = array_slice($kandidati, 0, $slobodnaMjestaRoditelja);
                    if (!empty($zaPrebaciti)) {
                        $clan->roditelji()->attach($zaPrebaciti);
                    }
                }
                $polaznik->roditelji()->detach();

                $korisnik = $polaznik->povezaniKorisnik;
                if ($korisnik !== null) {
                    $korisnik->clan_id = $clan->id;
                    $korisnik->polaznik_id = null;
                    if ((int)$korisnik->rola !== 1) {
                        $korisnik->rola = 2;
                    }
                    $korisnik->save();
                }

                return $clan;
            });
        } catch (Throwable $exception) {
            report($exception);

            return redirect()
                ->route('javno.skola.polaznici.show', $polaznik)
                ->with('error', 'Prebacivanje polaznika u članstvo nije uspjelo.');
        }

        return redirect()
            ->route('admin.clanovi.prikaz_clana', $clan)
            ->with('success', 'Polaznik je prebačen u članove kluba, a dokumenti su preneseni u profil člana.');
    }

    /**
     * Provjerava postoji li polaznik i je li validan za traženu akciju.
     */
    private function validirajPolaznika(Request $request, ?PolaznikSkole $polaznik = null): array
    {
        return $request->validate([
            'Prezime' => ['required', 'string', 'max:255'],
            'Ime' => ['required', 'string', 'max:255'],
            'datum_rodjenja' => ['nullable', 'date'],
            'oib' => [
                'required',
                'digits:11',
                Rule::unique('polaznici_skole', 'oib')->ignore($polaznik?->id),
            ],
            'br_telefona' => ['nullable', 'regex:/^\+385\d{8,9}$/', 'max:13'],
            'email' => ['nullable', 'string', 'email', 'max:255'],
            'spol' => ['nullable', Rule::in(['M', 'Ž'])],
            'datum_upisa' => ['required', 'date'],
            'u_skoli' => ['nullable', 'boolean'],
            'dolasci' => ['nullable', 'array'],
            'dolasci.*' => ['nullable', 'date'],
        ], [
            'oib.required' => 'OIB je obavezan.',
            'oib.digits' => 'OIB mora imati točno 11 znamenki.',
            'oib.unique' => 'OIB je već evidentiran kod drugog polaznika.',
            'br_telefona.regex' => 'Broj telefona mora biti u formatu +385xxxxxxxxx.',
        ]);
    }

    /**
     * Prepisuje validirane podatke forme u model polaznika prije spremanja.
     */
    private function mapirajPodatkePolaznika(PolaznikSkole $polaznik, array $validated): void
    {
        $polaznik->Prezime = trim((string)$validated['Prezime']);
        $polaznik->Ime = trim((string)$validated['Ime']);
        $polaznik->datum_rodjenja = $validated['datum_rodjenja'] ?? null;
        $polaznik->oib = (string)$validated['oib'];
        $polaznik->br_telefona = $validated['br_telefona'] ?? null;
        $polaznik->email = isset($validated['email']) ? mb_strtolower(trim((string)$validated['email'])) : null;
        $polaznik->spol = $validated['spol'] ?? null;
        $polaznik->datum_upisa = $validated['datum_upisa'];
    }

    /**
     * Sinkronizira 16 termina dolazaka polaznika prema trenutnom unosu u formi.
     */
    private function syncDolasci(PolaznikSkole $polaznik, array $dolasci): void
    {
        for ($i = 1; $i <= self::BROJ_DOLAZAKA; $i++) {
            $datum = $dolasci[$i] ?? null;

            PolaznikSkoleDolazak::query()->updateOrCreate(
                [
                    'polaznik_skole_id' => $polaznik->id,
                    'redni_broj' => $i,
                ],
                [
                    'datum' => empty($datum) ? null : $datum,
                ]
            );
        }
    }

    /**
     * Priprema polje dolazaka tako da su redni brojevi 1–16 uvijek dostupni za prikaz forme.
     */
    private function normaliziraniDolasci(PolaznikSkole $polaznik): array
    {
        $poRednom = $polaznik->dolasci->keyBy('redni_broj');
        $dolasci = [];

        for ($i = 1; $i <= self::BROJ_DOLAZAKA; $i++) {
            $dolasci[$i] = $poRednom[$i]->datum ?? null;
        }

        return $dolasci;
    }

    /**
     * Pohranjuje uploadanu datoteku polaznika u privatni direktorij i vraća putanju/naziv.
     */
    private function spremiDatoteku(string $direktorij): array
    {
        if (!Storage::disk('local')->exists($direktorij)) {
            Storage::disk('local')->makeDirectory($direktorij);
        }

        $uploadedFile = request()->file('dokument');
        if ($uploadedFile === null) {
            abort(422, 'Nije odabrana datoteka.');
        }

        $ekstenzija = $uploadedFile->extension();
        $imeDatoteke = now()->format('Ymd_His') . '_' . Str::lower(Str::random(10)) . '.' . $ekstenzija;
        $uploadedFile->storeAs($direktorij, $imeDatoteke, 'local');

        return [
            'putanja' => $direktorij . '/' . $imeDatoteke,
            'originalni_naziv' => $uploadedFile->getClientOriginalName(),
        ];
    }

    /**
     * Kopira dokument polaznika u dokumente člana prilikom prijelaza u članstvo.
     */
    private function kopirajDokumentZaClana(PolaznikSkoleDokument $dokument, int $clanId): string
    {
        if (empty($dokument->putanja) || !Storage::disk('local')->exists($dokument->putanja)) {
            return (string)$dokument->putanja;
        }

        $direktorij = 'private/clanovi/' . $clanId . '/dokumenti';
        if (!Storage::disk('local')->exists($direktorij)) {
            Storage::disk('local')->makeDirectory($direktorij);
        }

        $originalniNaziv = (string)($dokument->originalni_naziv ?? 'dokument');
        $bazaNaziva = pathinfo($originalniNaziv, PATHINFO_FILENAME);
        $slug = Str::slug($bazaNaziva);
        if ($slug === '') {
            $slug = 'dokument';
        }

        $ekstenzija = pathinfo($originalniNaziv, PATHINFO_EXTENSION);
        if ($ekstenzija === '') {
            $ekstenzija = pathinfo((string)$dokument->putanja, PATHINFO_EXTENSION);
        }

        $imeDatoteke = now()->format('Ymd_His') . '_' . $slug . '_' . Str::lower(Str::random(6));
        if ($ekstenzija !== '') {
            $imeDatoteke .= '.' . Str::lower($ekstenzija);
        }

        $novaPutanja = $direktorij . '/' . $imeDatoteke;
        Storage::disk('local')->copy($dokument->putanja, $novaPutanja);

        return $novaPutanja;
    }

    /**
     * Briše datoteku polaznika s diska ako putanja postoji.
     */
    private function obrisiDatotekuAkoPostoji(?string $putanja): void
    {
        if (!empty($putanja) && Storage::disk('local')->exists($putanja)) {
            Storage::disk('local')->delete($putanja);
        }
    }

    /**
     * Provjerava da profil polaznika smije otvoriti administrator, sam polaznik ili njegov povezani roditelj.
     */
    private function potvrdiPravoNaPolaznika(PolaznikSkole $polaznik): void
    {
        if ($this->jeAdminIliClan()) {
            return;
        }

        if ($this->jeVlastitiPolaznik($polaznik)) {
            return;
        }

        if ($this->jeRoditeljPovezanSaPolaznikom($polaznik)) {
            return;
        }

        abort(403);
    }

    /**
     * Provjerava pravo pristupa privatnim dokumentima polaznika (admin, polaznik, roditelj).
     */
    private function potvrdiPravoNaDokumentPolaznika(PolaznikSkole $polaznik): void
    {
        if ($this->jeAdmin()) {
            return;
        }

        if ($this->jeVlastitiPolaznik($polaznik)) {
            return;
        }

        if ($this->jeRoditeljPovezanSaPolaznikom($polaznik)) {
            return;
        }

        abort(403);
    }

    /**
     * Osigurava da se akcija izvodi nad istim polaznikom koji je otvoren u URL-u.
     */
    private function potvrdiPripadnostPolazniku(int $polaznikId, int $resourcePolaznikId): void
    {
        if ($polaznikId !== $resourcePolaznikId) {
            abort(404);
        }
    }

    /**
     * Za osjetljive akcije nad polaznicima dopušta pristup isključivo administratoru.
     */
    private function potvrdiAdmina(): void
    {
        if (!$this->jeAdmin()) {
            abort(403);
        }
    }

    /**
     * Vraća `true` ako je prijavljeni korisnik administrator ili član s administrativnim pravima.
     */
    private function jeAdminIliClan(): bool
    {
        return auth()->check() && auth()->user()->imaPravoAdminOrMember();
    }

    /**
     * Vraća `true` ako je prijavljeni korisnik administrator.
     */
    private function jeAdmin(): bool
    {
        return auth()->check() && (int)auth()->user()->rola === 1;
    }

    /**
     * Provjerava je li prijavljeni korisnik povezan baš s traženim polaznikom.
     */
    private function jeVlastitiPolaznik(PolaznikSkole $polaznik): bool
    {
        return auth()->check()
            && (int)auth()->user()->rola === 4
            && (int)auth()->user()->polaznik_id === (int)$polaznik->id;
    }

    /**
     * Provjerava je li prijavljeni roditelj povezan s traženim polaznikom.
     */
    private function jeRoditeljPovezanSaPolaznikom(PolaznikSkole $polaznik): bool
    {
        if (!auth()->check() || !auth()->user()->jeRoditelj()) {
            return false;
        }

        return auth()->user()->djecaPolaznici()
            ->whereKey((int)$polaznik->id)
            ->exists();
    }

    /**
     * Određuje ključne parametre potrebne za daljnju obradu.
     */
    private function odrediNazivDokumenta(string $vrsta, string $naziv): string
    {
        if ($vrsta !== 'Ostalo') {
            return $vrsta;
        }

        return trim($naziv);
    }

    /**
     * Vraća query aktivnih polaznika škole za listu i filtere.
     */
    private function queryAktivniPolazniciSkole(): Builder
    {
        $granica = $this->granicaAktivnostiSkole();

        return PolaznikSkole::query()
            ->whereNull('prebacen_u_clana_id')
            ->where('u_skoli', true)
            ->where(function (Builder $query) use ($granica) {
                $query->whereNull('datum_upisa')
                    ->orWhereDate('datum_upisa', '>=', $granica->toDateString());
            });
    }

    /**
     * Vraća query neaktivnih polaznika škole za listu i filtere.
     */
    private function queryNeaktivniPolazniciSkole(): Builder
    {
        $granica = $this->granicaAktivnostiSkole();

        return PolaznikSkole::query()
            ->whereNull('prebacen_u_clana_id')
            ->where(function (Builder $query) use ($granica) {
                $query->where('u_skoli', false)
                    ->orWhere(function (Builder $sub) use ($granica) {
                        $sub->whereNotNull('datum_upisa')
                            ->whereDate('datum_upisa', '<', $granica->toDateString());
                    });
            });
    }

    /**
     * Automatski označava polaznike kao neaktivne nakon isteka razdoblja aktivnosti.
     */
    private function oznaciIsteklePolaznike(): void
    {
        $granica = $this->granicaAktivnostiSkole();

        PolaznikSkole::query()
            ->whereNull('prebacen_u_clana_id')
            ->where('u_skoli', true)
            ->whereNotNull('datum_upisa')
            ->whereDate('datum_upisa', '<', $granica->toDateString())
            ->update(['u_skoli' => false]);
    }

    /**
     * Računa datumsku granicu nakon koje se polaznik smatra neaktivnim.
     */
    private function granicaAktivnostiSkole(): CarbonInterface
    {
        return now()->startOfDay()->subMonthsNoOverflow(4);
    }
}
