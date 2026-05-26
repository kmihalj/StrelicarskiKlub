<?php

namespace App\Http\Controllers;

use App\Models\Clanovi;
use App\Models\clanoviFunkcije;
use App\Models\DokumentiKluba;
use App\Models\Klub;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Admin kontroler za podatke kluba i dokumente koji se prikazuju članovima i javnosti.
 */
class KlubController extends Controller
{
    private const FUNKCIJE_KOTIZACIJA = ['Predsjednik kluba', 'Trener'];

    /**
     * Prikazuje administracijski pregled kluba (osnovni podaci, treneri i dokumenti).
     * @noinspection PhpMissingReturnTypeInspection
     */
    public function index()
    {
        $klub = Klub::with(['dokumenti' => fn ($query) => $query->orderBy('created_at', 'desc')])->first();
        $clanovi = Clanovi::with('funkcijeUklubu')
            ->where('aktivan', true)
            ->whereRaw('TIMESTAMPDIFF(YEAR, datum_rodjenja, NOW()) >= 18')
            ->orderBy('Prezime')
            ->orderBy('Ime')
            ->get();
        $treneri = clanoviFunkcije::with('clan')
            ->where('funkcija', '=', 'Trener')
            ->orderBy('id')
            ->get();
        $predsjednik = clanoviFunkcije::with('clan')
            ->where('funkcija', '=', 'Predsjednik kluba')
            ->first();

        return view('admin.klub.podaciOklubu', [
            'klub' => $klub,
            'clanovi' => $clanovi,
            'treneri' => $treneri,
            'predsjednik' => $predsjednik,
        ]);
    }

    /**
     * Prikazuje javnu stranicu kluba s osnovnim podacima, kontaktima i dokumentima.
     */
    public function oKlubu()
    {
        $klub = Klub::with(['dokumenti' => fn ($query) => $query->orderBy('created_at', 'desc')])->first();
        $clanovi = clanoviFunkcije::get();
        return view('javno.oKlubu', ['klub' => $klub, 'clanovi' => $clanovi]);
    }

    /**
     * @noinspection PhpMissingReturnTypeInspection
     */
    public function spremanjePodataka(Request $request)
    {
        $klub = Klub::first();
        if (is_null($klub)) {
            $klub = new Klub();
        }
        $klub->naziv = $request->input('naziv');
        $klub->adresa = $request->input('adresa');
        $klub->telefon = $request->input('telefon');
        $klub->email = $request->input('email');
        $klub->racun = $request->input('racun');
        $klub->save();
        return redirect()->route('admin.klub.naslovna');
    }

    /**
     * @noinspection PhpMissingReturnTypeInspection
     */
    public function spremanjeFunkcija(Request $request)
    {

        //klub
        $klub = $request->input('klub_id');

        // predsjednik
        $predsjednik = clanoviFunkcije::where('funkcija', '=', 'Predsjednik kluba')->first();
        if (is_null($predsjednik)) {
            $predsjednik = new clanoviFunkcije();
        }
        $predsjednik->klub_id = $klub;
        $predsjednik->clan_id = $request->input('predsjednik');
        $predsjednik->funkcija = 'Predsjednik kluba';
        $predsjednik->save();

        // tajnik
        $tajnik = clanoviFunkcije::where('funkcija', '=', 'Tajnik')->first();
        if (is_null($tajnik)) {
            $tajnik = new clanoviFunkcije();
        }
        $tajnik->klub_id = $klub;
        $tajnik->clan_id = $request->input('tajnik');
        $tajnik->funkcija = 'Tajnik';
        $tajnik->save();

        // upravni odbor
        $upravni = clanoviFunkcije::where('funkcija', '=', 'Upravni odbor')->get();
        if ($upravni->count() != 0) {
            foreach ($upravni as $clan) {
                $clan->delete();
            }
        }
        $upravni1 = new clanoviFunkcije();
        $upravni1->klub_id = $klub;
        $upravni1->clan_id = $request->input('upravni1');
        $upravni1->redniBroj = 1;
        $upravni1->funkcija = 'Upravni odbor';
        $upravni1->save();
        $upravni2 = new clanoviFunkcije();
        $upravni2->klub_id = $klub;
        $upravni2->clan_id = $request->input('upravni2');
        $upravni2->redniBroj = 2;
        $upravni2->funkcija = 'Upravni odbor';
        $upravni2->save();

        // nadzorni odbor
        $nadzorni = clanoviFunkcije::where('funkcija', '=', 'Nadzorni odbor')->get();
        if ($nadzorni->count() != 0) {
            foreach ($nadzorni as $clan) {
                $clan->delete();
            }
        }
        $nadzorni1 = new clanoviFunkcije();
        $nadzorni1->klub_id = $klub;
        $nadzorni1->clan_id = $request->input('nadzorni1');
        $nadzorni1->redniBroj = 1;
        $nadzorni1->funkcija = 'Nadzorni odbor';
        $nadzorni1->save();
        $nadzorni2 = new clanoviFunkcije();
        $nadzorni2->klub_id = $klub;
        $nadzorni2->clan_id = $request->input('nadzorni2');
        $nadzorni2->redniBroj = 2;
        $nadzorni2->funkcija = 'Nadzorni odbor';
        $nadzorni2->save();
        $nadzorni3 = new clanoviFunkcije();
        $nadzorni3->klub_id = $klub;
        $nadzorni3->clan_id = $request->input('nadzorni3');
        $nadzorni3->redniBroj = 3;
        $nadzorni3->funkcija = 'Nadzorni odbor';
        $nadzorni3->save();

        // arbitražno vijeće odbor
        $arbitrazni = clanoviFunkcije::where('funkcija', '=', 'Arbitražno vijeće')->get();
        if ($arbitrazni->count() != 0) {
            foreach ($arbitrazni as $clan) {
                $clan->delete();
            }
        }
        $arbitrazni1 = new clanoviFunkcije();
        $arbitrazni1->klub_id = $klub;
        $arbitrazni1->clan_id = $request->input('arbitrazni1');
        $arbitrazni1->redniBroj = 1;
        $arbitrazni1->funkcija = 'Arbitražno vijeće';
        $arbitrazni1->save();
        $arbitrazni2 = new clanoviFunkcije();
        $arbitrazni2->klub_id = $klub;
        $arbitrazni2->clan_id = $request->input('arbitrazni2');
        $arbitrazni2->redniBroj = 2;
        $arbitrazni2->funkcija = 'Arbitražno vijeće';
        $arbitrazni2->save();
        $arbitrazni3 = new clanoviFunkcije();
        $arbitrazni3->klub_id = $klub;
        $arbitrazni3->clan_id = $request->input('arbitrazni3');
        $arbitrazni3->redniBroj = 3;
        $arbitrazni3->funkcija = 'Arbitražno vijeće';
        $arbitrazni3->save();


        return redirect()->route('admin.klub.naslovna');
    }

    /** @noinspection PhpMissingReturnTypeInspection */
    public function spremanjeTrenera(Request $request)
    {
        $klub = $request->input('klub_id');
        $clan = Clanovi::findOrFail((int) $request->input('trener'));
        $podaciPlacanja = $this->validirajPodatkeKotizacije($request, $clan);

        $trener = new clanoviFunkcije();
        $trener->klub_id = $klub;
        $trener->clan_id = $clan->id;
        $trener->funkcija = "Trener";
        $trener->kotizacija_primatelj = $podaciPlacanja['primatelj'];
        $trener->kotizacija_iban = $podaciPlacanja['iban'];
        $trener->save();
        return redirect()->route('admin.klub.naslovna');
    }

    public function spremanjePodatakaZaKotizacije(Request $request, int $funkcija): RedirectResponse
    {
        $funkcijaClana = clanoviFunkcije::with('clan')->findOrFail($funkcija);
        if (! in_array((string) $funkcijaClana->funkcija, self::FUNKCIJE_KOTIZACIJA, true)) {
            return redirect()
                ->route('admin.klub.naslovna')
                ->with('error', 'Podaci za uplatu kotizacije mogu se upisati samo za predsjednika kluba i trenere.');
        }

        $podaciPlacanja = $this->validirajPodatkeKotizacije($request, $funkcijaClana->clan);
        $funkcijaClana->kotizacija_primatelj = $podaciPlacanja['primatelj'];
        $funkcijaClana->kotizacija_iban = $podaciPlacanja['iban'];
        $funkcijaClana->save();

        return redirect()
            ->route('admin.klub.naslovna')
            ->with('success', 'Podaci za uplatu kotizacija su spremljeni.');
    }

    /**
     * Briše osobu iz popisa trenera/funkcija kluba.
     */
    public function obrisiTrenera(int $id): RedirectResponse
    {
        $trener = clanoviFunkcije::findOrFail($id);
        $trener->delete();
        return redirect()->route('admin.klub.naslovna');
    }

    /**
     * Validira upload datoteka, sprema ih u storage i upisuje metapodatke u bazu.
     */
    public function uploadMedija(Request $request): RedirectResponse
    {
        if (!(Storage::exists('public/klub'))) {
            Storage::makeDirectory('public/klub');
        }
        $rules = array('medij' => 'required|extensions:jpg,jpeg,png,webp,mp4,pdf,doc,docx,xls,xlsx');
        $messages = array('medij.required' => 'Nije odabrana datoteka.', 'medij.extensions' => 'Datoteka nije slika (jpg,jpeg,png,webp), dokument (pdf,doc,docx,xls,xlsx) niti video (mp4).');
        $validator = Validator::make($request->all(), $rules, $messages);
        $klub = Klub::findOrFail((int)$request->input('klub_id'));
        if ($validator->errors()->isEmpty()) {
            if (!(Storage::exists('public/klub'))) {
                Storage::makeDirectory('public/klub');
            }
            $timeStamp = date("d_m_Y_H_i_s");
            $ime_datoteke = $timeStamp . '.' . $request->file('medij')->extension();
            $request->file('medij')->storeAs('public/klub/' . $ime_datoteke);
            $medij = new DokumentiKluba();
            $medij->klub_id = $klub->id;
            $medij->opis = $request->input('opis');
            $medij->javno = (bool)$request->input('javno');
            $medij->link_text = $ime_datoteke;
            $medij->save();

            return redirect()->route('admin.klub.naslovna');
        } else {
            return redirect()->route('admin.klub.naslovna')->with('error', $validator->errors()->first());
        }
    }

    public function brisanjeMedija(Request $request): RedirectResponse
    {
        $medij = DokumentiKluba::findOrFail((int)$request->input('medijBrisanje'));
        Storage::delete('public/klub/' . $medij->link_text);
        $medij->delete();
        return redirect()->route('admin.klub.naslovna');
    }

    /**
     * Ažurira opis i javnu vidljivost dokumenta kluba u administraciji.
     */
    public function updateMedija(Request $request): RedirectResponse
    {
        $medij = DokumentiKluba::findOrFail((int)$request->input('dokument_id'));
        $medij->opis = $request->input('opis');
        $medij->javno = (bool)$request->input('javno');
        $medij->save();
        return redirect()->route('admin.klub.naslovna');
    }

    /**
     * Normalizira IBAN i ime primatelja za kotizacije; prazna oba polja znače brisanje podataka.
     *
     * @return array{primatelj: ?string, iban: ?string}
     */
    private function validirajPodatkeKotizacije(Request $request, ?Clanovi $clan): array
    {
        $validated = $request->validate([
            'kotizacija_primatelj' => ['nullable', 'string', 'max:70'],
            'kotizacija_iban' => ['nullable', 'string', 'max:50'],
        ]);

        $primatelj = trim((string) ($validated['kotizacija_primatelj'] ?? ''));
        $primatelj = $primatelj === '' ? null : $primatelj;

        $iban = strtoupper((string) preg_replace('/\s+/', '', (string) ($validated['kotizacija_iban'] ?? '')));
        $iban = $iban === '' ? null : $iban;

        if ($iban !== null && ! preg_match('/^[A-Z]{2}[0-9A-Z]{13,32}$/', $iban)) {
            throw ValidationException::withMessages([
                'kotizacija_iban' => 'IBAN nije u ispravnom formatu.',
            ]);
        }

        if ($iban !== null && $primatelj === null && $clan instanceof Clanovi) {
            $primatelj = trim((string) $clan->Ime.' '.(string) $clan->Prezime);
        }

        if ($primatelj !== null && $iban === null) {
            throw ValidationException::withMessages([
                'kotizacija_iban' => 'Za primatelja kotizacije potrebno je upisati i IBAN.',
            ]);
        }

        return [
            'primatelj' => $primatelj,
            'iban' => $iban,
        ];
    }

}
