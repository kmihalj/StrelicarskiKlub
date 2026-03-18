<?php

namespace App\Http\Controllers;

use App\Models\ClanDokument;
use App\Models\ClanLijecnickiPregled;
use App\Models\Clanovi;
use App\Services\PaymentTrackingService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

/**
 * Admin i korisnički kontroler za profile članova, liječničke preglede, dokumente i evidenciju treninga.
 */
class ClanoviController extends Controller
{
    private const DOKUMENTI_EKSTENZIJE = 'pdf,doc,docx,jpg,jpeg,png,webp,xls,xlsx';
    private const DOKUMENTI_VRSTE = ['Upisnica', 'GDPR', 'Slika', 'Ostalo'];

    /**
     * Sprema novog člana kluba s osnovnim osobnim podacima i statusom aktivnosti.
     */
    public function store(Request $request): RedirectResponse
    {
        try {
            $datumPocetkaClanstva = $request->input('datum_pocetka_clanstva');
            $clan = new Clanovi();
            $clan->Prezime = $request->input('Prezime');
            $clan->Ime = $request->input('Ime');
            $clan->datum_rodjenja = $request->input('datum_rodjenja');
            $clan->oib = $request->input('oib');
            $clan->br_telefona = $request->input('br_telefona');
            $clan->email = $request->input('email');
            $clan->datum_pocetka_clanstva = $datumPocetkaClanstva;
            $clan->clan_od = empty($datumPocetkaClanstva) ? null : (int)date('Y', strtotime((string)$datumPocetkaClanstva));
            $clan->broj_licence = (isset($request->broj_licence)) ? $request->broj_licence : 'nema licencu';
            $clan->spol = $request->input('spol');
            $clan->aktivan = (bool)$request->input('aktivan');
            $clan->save();
            return redirect()->route('javno.clanovi');
        } catch (Throwable $e) {
            return redirect()->route('javno.clanovi')->with('error', $e->getMessage());
        }
    }

    /**
     * Otvara administracijsko uređivanje profila člana zajedno s dokumentima i statusom plaćanja.
     */
    public function edit(Clanovi $clan): View
    {
        $clan->load([
            'lijecnickiPregledi' => fn ($query) => $query->orderByDesc('vrijedi_do')->orderByDesc('id'),
            'dokumenti' => fn ($query) => $query->orderByDesc('datum_dokumenta')->orderByDesc('id'),
        ]);

        $paymentService = app(PaymentTrackingService::class);

        return view('admin.clanovi.uredjivanje', [
            'clan' => $clan,
            'paymentSetup' => $paymentService->setupViewData(),
            'paymentSummary' => $paymentService->memberSummary($clan),
            'paymentNotice' => $paymentService->noticeForClan($clan),
            'otvoriPlacanja' => request()->boolean('open_payments'),
        ]);
    }

    /**
     * Ažurira osnovne podatke člana (identitet, kontakt, članstvo i aktivnost).
     */
    public function update(Request $request): RedirectResponse
    {
        $clan = Clanovi::where('id', $request->input('clan_id'))->firstOrFail();
        $datumPocetkaClanstva = $request->input('datum_pocetka_clanstva');
        $clan->Prezime = $request->input('Prezime');
        $clan->Ime = $request->input('Ime');
        $clan->datum_rodjenja = $request->input('datum_rodjenja');
        $clan->oib = $request->input('oib');
        $clan->br_telefona = $request->input('br_telefona');
        $clan->email = $request->input('email');
        $clan->datum_pocetka_clanstva = $datumPocetkaClanstva;
        if (!empty($datumPocetkaClanstva)) {
            $clan->clan_od = (int)date('Y', strtotime((string)$datumPocetkaClanstva));
        }
        $clan->broj_licence = (isset($request->broj_licence)) ? $request->broj_licence : 'nema licencu';
        $clan->spol = $request->input('spol');
        $clan->aktivan = (bool)$request->input('aktivan');
        $clan->save();
        return redirect()->route('admin.clanovi.prikaz_clana', $clan)->with('success', 'Spremanje podataka OK');
    }

    /**
     * Briše člana i sve povezane datoteke (slike, liječničke preglede i dokumente).
     */
    public function destroy(int $id): RedirectResponse
    {
        $clan = Clanovi::with(['lijecnickiPregledi', 'dokumenti'])->where('id', $id)->firstOrFail();

        foreach ($clan->lijecnickiPregledi as $pregled) {
            $this->obrisiDatotekuAkoPostoji($pregled->putanja);
        }

        foreach ($clan->dokumenti as $dokument) {
            $this->obrisiDatotekuAkoPostoji($dokument->putanja);
        }

        $this->obrisiDatotekuAkoPostoji($clan->slika_link ? 'public/slike_clanova/' . $clan->slika_link : null);
        $this->obrisiDatotekuAkoPostoji($clan->lijecnicki_dokument ? 'public/lijecnicki_dokumenti/' . $clan->lijecnicki_dokument : null);

        $clan->delete();
        return redirect()->route('javno.clanovi');
    }

    /**
     * Validira upload datoteka, sprema ih u storage i upisuje metapodatke u bazu.
     */
    public function upload_slike_clana(Request $request): RedirectResponse
    {
        if (!Storage::disk('local')->exists('public/slike_clanova')) {
            Storage::disk('local')->makeDirectory('public/slike_clanova');
        }

        $rules = array(
            'clan_slika' => 'required|image'
        );
        $messages = array(
                'clan_slika.required' => 'Nije odabrana datoteka.',
                'clan_slika.image' => 'Nije odabrana slika.'
        );
        $validator = Validator::make( $request->all(), $rules, $messages );
        $clan = Clanovi::where('id',  $request->input('clan_id'))->firstOrFail();
        if ($validator->errors()->isEmpty()) {
            $ime_datoteke = $request->input('clan_id') . '.' . $request->file('clan_slika')->extension();
            if(!(empty($clan->slika_link))) {
                Storage::disk('local')->delete('public/slike_clanova/' . $clan->slika_link);
            }
            $request->file('clan_slika')->storeAs('public/slike_clanova', $ime_datoteke, 'local');
            $clan->slika_link = $ime_datoteke;
            $clan->save();
            return redirect()->route('admin.clanovi.prikaz_clana', $clan)->with('success', 'Upload slike OK');
        } else {
            return redirect()->route('admin.clanovi.prikaz_clana', $clan)->with('error', $validator->errors()->first());
        }
    }

    /**
     * Briše odabrani zapis i po potrebi čisti povezane podatke/datoteke.
     */
    public function brisanje_slike_clana(Request $request): RedirectResponse
    {
        $clan = Clanovi::where('id',  $request->input('clan_id'))->firstOrFail();
        $this->obrisiDatotekuAkoPostoji($clan->slika_link ? 'public/slike_clanova/' . $clan->slika_link : null);
        $clan->slika_link = NULL;
        $clan->save();
        return redirect()->route('admin.clanovi.prikaz_clana', $clan)->with('success', 'Brisanje slike OK');
    }

    /**
     * Sprema novi liječnički pregled člana i priloženu dokumentaciju.
     */
    public function spremi_lijecnicki_pregled(Request $request, Clanovi $clan): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'vrijedi_do' => 'required|date',
            'lijecnicki_dokument' => 'required|mimes:pdf',
        ], [
            'vrijedi_do.required' => 'Potrebno je unijeti datum do kada vrijedi liječnički.',
            'vrijedi_do.date' => 'Datum liječničkog nije ispravan.',
            'lijecnicki_dokument.required' => 'Liječnički dokument je obavezan.',
            'lijecnicki_dokument.mimes' => 'Liječnički dokument mora biti PDF.',
        ]);

        if ($validator->errors()->isNotEmpty()) {
            return redirect()->route('admin.clanovi.prikaz_clana', ['clan' => $clan, 'open_documents' => 1])->with('error', $validator->errors()->first());
        }

        $pregled = new ClanLijecnickiPregled();
        $pregled->clan_id = $clan->id;
        $pregled->vrijedi_do = $request->input('vrijedi_do');
        $pregled->created_by = auth()->id();
        $pregled->legacy_import = false;

        if ($request->hasFile('lijecnicki_dokument')) {
            $pohrana = $this->spremiDatoteku('lijecnicki_dokument', 'private/clanovi/' . $clan->id . '/lijecnicki');
            $pregled->putanja = $pohrana['putanja'];
            $pregled->originalni_naziv = $pohrana['originalni_naziv'];
        }

        $pregled->save();
        $clan->osvjeziLijecnickiDo();

        return redirect()->route('admin.clanovi.prikaz_clana', ['clan' => $clan, 'open_documents' => 1])->with('success', 'Liječnički pregled je spremljen.');
    }

    /**
     * Ažurira postojeći liječnički pregled člana (datumi, napomena i dokument).
     */
    public function update_lijecnicki_pregled(Request $request, Clanovi $clan, ClanLijecnickiPregled $pregled): RedirectResponse
    {
        $this->potvrdiPripadnostClanu($clan->id, $pregled->clan_id);

        $validator = Validator::make($request->all(), [
            'vrijedi_do' => 'required|date',
            'lijecnicki_dokument' => 'nullable|mimes:pdf',
        ], [
            'vrijedi_do.required' => 'Potrebno je unijeti datum do kada vrijedi liječnički.',
            'vrijedi_do.date' => 'Datum liječničkog nije ispravan.',
            'lijecnicki_dokument.mimes' => 'Liječnički dokument mora biti PDF.',
        ]);

        if ($validator->errors()->isNotEmpty()) {
            return redirect()->route('admin.clanovi.prikaz_clana', ['clan' => $clan, 'open_documents' => 1])->with('error', $validator->errors()->first());
        }

        $pregled->vrijedi_do = $request->input('vrijedi_do');

        if ($request->hasFile('lijecnicki_dokument')) {
            $this->obrisiDatotekuAkoPostoji($pregled->putanja);
            $pohrana = $this->spremiDatoteku('lijecnicki_dokument', 'private/clanovi/' . $clan->id . '/lijecnicki');
            $pregled->putanja = $pohrana['putanja'];
            $pregled->originalni_naziv = $pohrana['originalni_naziv'];
        }

        $pregled->save();
        $clan->osvjeziLijecnickiDo();

        return redirect()->route('admin.clanovi.prikaz_clana', ['clan' => $clan, 'open_documents' => 1])->with('success', 'Liječnički pregled je ažuriran.');
    }

    /**
     * Briše liječnički pregled člana i pripadajuću datoteku ako postoji.
     */
    public function obrisi_lijecnicki_pregled(Clanovi $clan, ClanLijecnickiPregled $pregled): RedirectResponse
    {
        $this->potvrdiPripadnostClanu($clan->id, $pregled->clan_id);

        $this->obrisiDatotekuAkoPostoji($pregled->putanja);
        $pregled->delete();
        $clan->osvjeziLijecnickiDo();

        return redirect()->route('admin.clanovi.prikaz_clana', ['clan' => $clan, 'open_documents' => 1])->with('success', 'Liječnički pregled je obrisan.');
    }

    /**
     * U admin sučelju preuzima liječnički dokument odabranog člana.
     */
    public function preuzmi_lijecnicki_pregled(Clanovi $clan, ClanLijecnickiPregled $pregled): BinaryFileResponse
    {
        $this->potvrdiPripadnostClanu($clan->id, $pregled->clan_id);
        if (empty($pregled->putanja) || !Storage::disk('local')->exists($pregled->putanja)) {
            abort(404);
        }

        return response()->file(Storage::disk('local')->path($pregled->putanja));
    }

    /**
     * Sprema novi dokument člana (naziv, datum i datoteka).
     */
    public function spremi_dokument(Request $request, Clanovi $clan): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
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
            return redirect()->route('admin.clanovi.prikaz_clana', ['clan' => $clan, 'open_documents' => 1])->with('error', $validator->errors()->first());
        }

        $pohrana = $this->spremiDatoteku('dokument', 'private/clanovi/' . $clan->id . '/dokumenti');

        $vrsta = (string)$request->input('vrsta');
        $dokument = new ClanDokument();
        $dokument->clan_id = $clan->id;
        $dokument->vrsta = $vrsta;
        $dokument->naziv = $this->odrediNazivDokumenta($vrsta, (string)$request->input('naziv'));
        $dokument->datum_dokumenta = $request->input('datum_dokumenta');
        $dokument->napomena = $request->input('napomena');
        $dokument->putanja = $pohrana['putanja'];
        $dokument->originalni_naziv = $pohrana['originalni_naziv'];
        $dokument->created_by = auth()->id();
        $dokument->save();

        return redirect()->route('admin.clanovi.prikaz_clana', ['clan' => $clan, 'open_documents' => 1])->with('success', 'Dokument je spremljen.');
    }

    /**
     * Ažurira postojeći dokument člana (naziv, datum i datoteku).
     */
    public function update_dokument(Request $request, Clanovi $clan, ClanDokument $dokument): RedirectResponse
    {
        $this->potvrdiPripadnostClanu($clan->id, $dokument->clan_id);

        $validator = Validator::make($request->all(), [
            'vrsta' => ['required', 'string', Rule::in(self::DOKUMENTI_VRSTE)],
            'naziv' => 'nullable|string|max:255|required_if:vrsta,Ostalo',
            'datum_dokumenta' => 'nullable|date',
            'napomena' => 'nullable|string|max:1000',
            'dokument' => 'nullable|mimes:' . self::DOKUMENTI_EKSTENZIJE,
        ], [
            'vrsta.required' => 'Potrebno je odabrati vrstu dokumenta.',
            'vrsta.in' => 'Odabrana vrsta dokumenta nije podržana.',
            'naziv.required_if' => 'Potrebno je unijeti naziv dokumenta za vrstu Ostalo.',
            'dokument.mimes' => 'Datoteka mora biti PDF, DOC, DOCX, JPG, JPEG, PNG, WEBP, XLS ili XLSX.',
        ]);

        if ($validator->errors()->isNotEmpty()) {
            return redirect()->route('admin.clanovi.prikaz_clana', ['clan' => $clan, 'open_documents' => 1])->with('error', $validator->errors()->first());
        }

        $vrsta = (string)$request->input('vrsta');
        $dokument->vrsta = $vrsta;
        $dokument->naziv = $this->odrediNazivDokumenta($vrsta, (string)$request->input('naziv'));
        $dokument->datum_dokumenta = $request->input('datum_dokumenta');
        $dokument->napomena = $request->input('napomena');

        if ($request->hasFile('dokument')) {
            $this->obrisiDatotekuAkoPostoji($dokument->putanja);
            $pohrana = $this->spremiDatoteku('dokument', 'private/clanovi/' . $clan->id . '/dokumenti');
            $dokument->putanja = $pohrana['putanja'];
            $dokument->originalni_naziv = $pohrana['originalni_naziv'];
        }

        $dokument->save();

        return redirect()->route('admin.clanovi.prikaz_clana', ['clan' => $clan, 'open_documents' => 1])->with('success', 'Dokument je ažuriran.');
    }

    /**
     * Briše dokument člana i pripadajuću datoteku sa diska.
     */
    public function obrisi_dokument(Clanovi $clan, ClanDokument $dokument): RedirectResponse
    {
        $this->potvrdiPripadnostClanu($clan->id, $dokument->clan_id);

        $this->obrisiDatotekuAkoPostoji($dokument->putanja);
        $dokument->delete();

        return redirect()->route('admin.clanovi.prikaz_clana', ['clan' => $clan, 'open_documents' => 1])->with('success', 'Dokument je obrisan.');
    }

    /**
     * U admin sučelju preuzima odabrani dokument člana.
     */
    public function preuzmi_dokument(Clanovi $clan, ClanDokument $dokument): BinaryFileResponse
    {
        $this->potvrdiPripadnostClanu($clan->id, $dokument->clan_id);
        if (!Storage::disk('local')->exists($dokument->putanja)) {
            abort(404);
        }

        return response()->file(Storage::disk('local')->path($dokument->putanja));
    }

    /**
     * Pohranjuje uploadanu datoteku člana u privatni direktorij i vraća putanju/naziv.
     */
    private function spremiDatoteku(string $inputName, string $direktorij): array
    {
        if (!Storage::disk('local')->exists($direktorij)) {
            Storage::disk('local')->makeDirectory($direktorij);
        }

        $ekstenzija = $this->ekstenzijaDatoteke($inputName);
        $imeDatoteke = now()->format('Ymd_His') . '_' . Str::lower(Str::random(10)) . '.' . $ekstenzija;

        $uploadedFile = request()->file($inputName);
        $uploadedFile->storeAs($direktorij, $imeDatoteke, 'local');

        return [
            'putanja' => $direktorij . '/' . $imeDatoteke,
            'originalni_naziv' => $uploadedFile->getClientOriginalName(),
        ];
    }

    /**
     * Vraća ekstenziju dokumenta/slike kako bi se datoteka pravilno pohranila i prikazala.
     */
    private function ekstenzijaDatoteke(string $inputName): string
    {
        return request()->file($inputName)->extension();
    }

    /**
     * Briše datoteku s diska ako putanja postoji.
     */
    private function obrisiDatotekuAkoPostoji(?string $putanja): void
    {
        if (!empty($putanja) && Storage::disk('local')->exists($putanja)) {
            Storage::disk('local')->delete($putanja);
        }
    }

    /**
     * Provjerava da zapis koji se uređuje stvarno pripada odabranom članu.
     */
    private function potvrdiPripadnostClanu(int $clanId, int $resourceClanId): void
    {
        if ($clanId !== $resourceClanId) {
            abort(404);
        }
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
}
