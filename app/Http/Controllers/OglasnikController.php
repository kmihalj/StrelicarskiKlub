<?php

namespace App\Http\Controllers;

use App\Models\Clanovi;
use App\Models\Oglas;
use App\Models\OglasSlika;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Javni i korisnicki kontroler za "mali oglasnik" opreme clanova kluba.
 */
class OglasnikController extends Controller
{
    private const MAX_SLIKE = 5;

    /**
     * Prikazuje javni popis aktivnih oglasa.
     */
    public function index(): View
    {
        $oglasi = Oglas::query()
            ->with([
                'clan' => fn ($query) => $query->select(['id', 'Ime', 'Prezime']),
                'slike',
            ])
            ->where('is_active', true)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(12)
            ->withQueryString();

        $this->dodajOpisHtml($oglasi->getCollection());

        $korisnik = auth()->user();
        $mozePredati = $korisnik instanceof User && $this->korisnikMozePredati($korisnik);
        $upravljanjeMapa = $this->mapaUpravljanja($oglasi->getCollection(), $korisnik instanceof User ? $korisnik : null);

        return view('javno.oglasnik.index', [
            'oglasi' => $oglasi,
            'mozePredati' => $mozePredati,
            'upravljanjeMapa' => $upravljanjeMapa,
        ]);
    }

    /**
     * Prikazuje formu za predaju novog oglasa.
     */
    public function create(): View
    {
        $korisnik = $this->autorizirajPredaju();
        $clanoviZaObjavu = $this->clanoviZaObjavu($korisnik);
        $odabraniClanId = $this->odabraniClanIdZaFormu($korisnik, $clanoviZaObjavu, (int) old('clan_id', 0));

        if ($clanoviZaObjavu->isEmpty()) {
            abort(403);
        }

        return view('javno.oglasnik.form', [
            'mode' => 'create',
            'oglas' => null,
            'clanoviZaObjavu' => $clanoviZaObjavu,
            'odabraniClanId' => $odabraniClanId,
            'kontaktPoClanu' => $this->kontaktPoClanu($clanoviZaObjavu),
            'maxSlike' => self::MAX_SLIKE,
            'mozeOdabratiClana' => $this->korisnikJeAdmin($korisnik),
        ]);
    }

    /**
     * Sprema novi oglas.
     */
    public function store(Request $request): RedirectResponse
    {
        $korisnik = $this->autorizirajPredaju();
        $clanoviZaObjavu = $this->clanoviZaObjavu($korisnik);

        $validated = $this->validirajOglas($request, $this->korisnikJeAdmin($korisnik), self::MAX_SLIKE);
        $clanId = $this->odrediClanIdZaSpremanje($korisnik, $validated, $clanoviZaObjavu);
        $iznos = $this->normalizirajIznos($validated['cijena'] ?? null);
        if ($iznos === null) {
            throw ValidationException::withMessages([
                'cijena' => 'Cijena mora biti valjani broj.',
            ]);
        }

        $kontaktTelefon = trim((string) ($validated['kontakt_telefon'] ?? ''));
        $kontaktEmail = trim((string) ($validated['kontakt_email'] ?? ''));
        $slike = $request->file('slike', []);
        if (! is_array($slike)) {
            $slike = [];
        }

        $oglas = DB::transaction(function () use ($clanId, $korisnik, $validated, $iznos, $kontaktTelefon, $kontaktEmail, $slike): Oglas {
            $oglas = new Oglas;
            $oglas->clan_id = $clanId;
            $oglas->created_by = (int) $korisnik->id;
            $oglas->updated_by = (int) $korisnik->id;
            $oglas->naslov = trim((string) $validated['naslov']);
            $oglas->opis = trim((string) $validated['opis']);
            $oglas->cijena = $iznos;
            $oglas->kontakt_telefon = $kontaktTelefon;
            $oglas->kontakt_email = $kontaktEmail;
            $oglas->is_active = true;
            $oglas->deactivated_at = null;
            $oglas->save();

            $this->spremiNoveSlike($oglas, $slike);

            return $oglas;
        });

        return redirect()
            ->route('javno.oglasnik.mine')
            ->with('success', 'Oglas je uspješno predan.');
    }

    /**
     * Prikazuje formu za uredjivanje postojeceg oglasa.
     */
    public function edit(Oglas $oglas): View
    {
        $korisnik = $this->autorizirajPredaju();
        $oglas->loadMissing(['clan', 'slike']);
        $this->autorizirajUpravljanje($korisnik, $oglas);

        $clanoviZaObjavu = $this->clanoviZaObjavu($korisnik);
        $odabraniClanId = (int) old('clan_id', (int) $oglas->clan_id);
        if (! $clanoviZaObjavu->contains('id', $odabraniClanId)) {
            $odabraniClanId = $this->odabraniClanIdZaFormu($korisnik, $clanoviZaObjavu, 0);
        }

        return view('javno.oglasnik.form', [
            'mode' => 'edit',
            'oglas' => $oglas,
            'clanoviZaObjavu' => $clanoviZaObjavu,
            'odabraniClanId' => $odabraniClanId,
            'kontaktPoClanu' => $this->kontaktPoClanu($clanoviZaObjavu),
            'maxSlike' => self::MAX_SLIKE,
            'mozeOdabratiClana' => $this->korisnikJeAdmin($korisnik),
        ]);
    }

    /**
     * Sprema izmjene oglasa.
     */
    public function update(Request $request, Oglas $oglas): RedirectResponse
    {
        $korisnik = $this->autorizirajPredaju();
        $oglas->loadMissing(['slike']);
        $this->autorizirajUpravljanje($korisnik, $oglas);

        $clanoviZaObjavu = $this->clanoviZaObjavu($korisnik);
        $brojPostojecihSlika = (int) $oglas->slike->count();

        $oznaceneZaBrisanje = collect((array) $request->input('obrisi_slike', []))
            ->map(fn ($id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values();

        $brojStvarnoOznacenih = $oglas->slike
            ->filter(fn (OglasSlika $slika): bool => $oznaceneZaBrisanje->contains((int) $slika->id))
            ->count();

        $maksNovih = max(self::MAX_SLIKE - ($brojPostojecihSlika - $brojStvarnoOznacenih), 0);
        $validated = $this->validirajOglas($request, $this->korisnikJeAdmin($korisnik), $maksNovih, true);

        $slike = $request->file('slike', []);
        if (! is_array($slike)) {
            $slike = [];
        }

        if ($brojPostojecihSlika - $brojStvarnoOznacenih + count($slike) > self::MAX_SLIKE) {
            throw ValidationException::withMessages([
                'slike' => 'Maksimalno je dozvoljeno '.self::MAX_SLIKE.' slika po oglasu.',
            ]);
        }

        $clanId = $this->odrediClanIdZaSpremanje($korisnik, $validated, $clanoviZaObjavu, (int) $oglas->clan_id);
        $iznos = $this->normalizirajIznos($validated['cijena'] ?? null);
        if ($iznos === null) {
            throw ValidationException::withMessages([
                'cijena' => 'Cijena mora biti valjani broj.',
            ]);
        }

        $kontaktTelefon = trim((string) ($validated['kontakt_telefon'] ?? ''));
        $kontaktEmail = trim((string) ($validated['kontakt_email'] ?? ''));

        DB::transaction(function () use ($oglas, $korisnik, $validated, $clanId, $iznos, $kontaktTelefon, $kontaktEmail, $oznaceneZaBrisanje, $slike): void {
            $oglas->clan_id = $clanId;
            $oglas->updated_by = (int) $korisnik->id;
            $oglas->naslov = trim((string) $validated['naslov']);
            $oglas->opis = trim((string) $validated['opis']);
            $oglas->cijena = $iznos;
            $oglas->kontakt_telefon = $kontaktTelefon;
            $oglas->kontakt_email = $kontaktEmail;
            $oglas->save();

            $this->obrisiSlikePoId($oglas, $oznaceneZaBrisanje->all());
            $this->spremiNoveSlike($oglas, $slike);
        });

        return redirect()
            ->route('javno.oglasnik.edit', $oglas)
            ->with('success', 'Oglas je ažuriran.');
    }

    /**
     * Prikazuje oglase trenutnog korisnika podijeljene na aktivne i deaktivirane.
     */
    public function mine(): View
    {
        $korisnik = $this->autorizirajPredaju();

        $aktivni = $this->upitMojihOglasa($korisnik)
            ->with([
                'clan' => fn ($query) => $query->select(['id', 'Ime', 'Prezime']),
                'slike',
            ])
            ->where('is_active', true)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();

        $deaktivirani = $this->upitMojihOglasa($korisnik)
            ->with([
                'clan' => fn ($query) => $query->select(['id', 'Ime', 'Prezime']),
                'slike',
            ])
            ->where('is_active', false)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();

        $deaktiviraniDrugi = collect();
        if ($this->korisnikJeAdmin($korisnik)) {
            $deaktiviraniDrugi = Oglas::query()
                ->with([
                    'clan' => fn ($query) => $query->select(['id', 'Ime', 'Prezime']),
                    'slike',
                ])
                ->where('is_active', false)
                ->whereNotIn('id', $deaktivirani->pluck('id')->map(fn ($id): int => (int) $id)->all())
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->get();
        }

        $this->dodajOpisHtml($aktivni);
        $this->dodajOpisHtml($deaktivirani);
        $this->dodajOpisHtml($deaktiviraniDrugi);

        return view('javno.oglasnik.mine', [
            'aktivniOglasi' => $aktivni,
            'deaktiviraniOglasi' => $deaktivirani,
            'deaktiviraniDrugiOglasi' => $deaktiviraniDrugi,
            'jeAdmin' => $this->korisnikJeAdmin($korisnik),
        ]);
    }

    /**
     * Deaktivira oglas (vise nije vidljiv na javnom popisu).
     */
    public function deactivate(Oglas $oglas): RedirectResponse
    {
        $korisnik = $this->autorizirajPredaju();
        $this->autorizirajUpravljanje($korisnik, $oglas);

        $oglas->is_active = false;
        $oglas->deactivated_at = now();
        $oglas->updated_by = (int) $korisnik->id;
        $oglas->save();

        return redirect()->back()->with('success', 'Oglas je deaktiviran.');
    }

    /**
     * Reaktivira prethodno deaktivirani oglas.
     */
    public function reactivate(Oglas $oglas): RedirectResponse
    {
        $korisnik = $this->autorizirajPredaju();
        $this->autorizirajUpravljanje($korisnik, $oglas);

        $oglas->is_active = true;
        $oglas->deactivated_at = null;
        $oglas->updated_by = (int) $korisnik->id;
        $oglas->save();

        return redirect()->back()->with('success', 'Oglas je ponovno aktiviran.');
    }

    /**
     * Trajno brise oglas i sve povezane slike.
     */
    public function destroy(Oglas $oglas): RedirectResponse
    {
        $korisnik = $this->autorizirajPredaju();
        $oglas->loadMissing('slike');
        $this->autorizirajUpravljanje($korisnik, $oglas);

        DB::transaction(function () use ($oglas): void {
            foreach ($oglas->slike as $slika) {
                if (! empty($slika->putanja) && Storage::disk('public')->exists($slika->putanja)) {
                    Storage::disk('public')->delete($slika->putanja);
                }
            }

            Storage::disk('public')->deleteDirectory('oglasi/'.(int) $oglas->id);
            $oglas->delete();
        });

        return redirect()->back()->with('success', 'Oglas je obrisan.');
    }

    /**
     * Provjerava je li korisnik prijavljen i ima li pravo predaje oglasa.
     */
    private function autorizirajPredaju(): User
    {
        $korisnik = auth()->user();
        if (! $korisnik instanceof User || ! $this->korisnikMozePredati($korisnik)) {
            abort(403);
        }

        return $korisnik;
    }

    /**
     * Provjerava moze li korisnik upravljati trazenim oglasom.
     */
    private function autorizirajUpravljanje(User $korisnik, Oglas $oglas): void
    {
        if ($this->korisnikMozeUpravljatiOglasom($korisnik, $oglas)) {
            return;
        }

        abort(403);
    }

    /**
     * Pravilo predaje oglasa: samo role 1 (admin) i 2 (clan).
     */
    private function korisnikMozePredati(User $korisnik): bool
    {
        $rola = (int) $korisnik->rola;
        if ($rola === 1) {
            return true;
        }

        return $rola === 2 && (int) ($korisnik->clan_id ?? 0) > 0;
    }

    /**
     * Provjera admin role.
     */
    private function korisnikJeAdmin(User $korisnik): bool
    {
        return (int) $korisnik->rola === 1;
    }

    /**
     * Provjerava pravo upravljanja oglasom (admin sve, clan samo svoje).
     */
    private function korisnikMozeUpravljatiOglasom(User $korisnik, Oglas $oglas): bool
    {
        if ($this->korisnikJeAdmin($korisnik)) {
            return true;
        }

        return (int) $korisnik->clan_id > 0 && (int) $korisnik->clan_id === (int) $oglas->clan_id;
    }

    /**
     * Mapira dostupnost upravljanja po oglasu za prikaz akcijskih gumba.
     *
     * @return array<int, bool>
     */
    private function mapaUpravljanja(Collection $oglasi, ?User $korisnik): array
    {
        if (! $korisnik instanceof User) {
            return [];
        }

        $mapa = [];
        foreach ($oglasi as $oglas) {
            if (! $oglas instanceof Oglas) {
                continue;
            }
            $mapa[(int) $oglas->id] = $this->korisnikMozeUpravljatiOglasom($korisnik, $oglas);
        }

        return $mapa;
    }

    /**
     * Vraca listu clanova koje korisnik smije odabrati za objavu oglasa.
     */
    private function clanoviZaObjavu(User $korisnik): Collection
    {
        if ($this->korisnikJeAdmin($korisnik)) {
            return Clanovi::query()
                ->orderBy('Prezime')
                ->orderBy('Ime')
                ->get(['id', 'Ime', 'Prezime', 'br_telefona', 'email']);
        }

        $clanId = (int) ($korisnik->clan_id ?? 0);
        if ($clanId <= 0) {
            return collect();
        }

        return Clanovi::query()
            ->where('id', $clanId)
            ->get(['id', 'Ime', 'Prezime', 'br_telefona', 'email']);
    }

    /**
     * Vraca default odabir clana u formi.
     */
    private function odabraniClanIdZaFormu(User $korisnik, Collection $clanoviZaObjavu, int $oldClanId): int
    {
        if ($oldClanId > 0 && $clanoviZaObjavu->contains('id', $oldClanId)) {
            return $oldClanId;
        }

        $korisnikovClanId = (int) ($korisnik->clan_id ?? 0);
        if ($korisnikovClanId > 0 && $clanoviZaObjavu->contains('id', $korisnikovClanId)) {
            return $korisnikovClanId;
        }

        return (int) ($clanoviZaObjavu->first()->id ?? 0);
    }

    /**
     * Kontakt podatci po clanu za automatsko popunjavanje forme.
     *
     * @return array<int, array{telefon:string, email:string}>
     */
    private function kontaktPoClanu(Collection $clanovi): array
    {
        $mapa = [];
        foreach ($clanovi as $clan) {
            if (! $clan instanceof Clanovi) {
                continue;
            }

            $mapa[(int) $clan->id] = [
                'telefon' => trim((string) ($clan->br_telefona ?? '')),
                'email' => trim((string) ($clan->email ?? '')),
            ];
        }

        return $mapa;
    }

    /**
     * Validira payload forme oglasa.
     *
     * @return array<string, mixed>
     */
    private function validirajOglas(Request $request, bool $adminMode, int $maksNovihSlika, bool $isUpdate = false): array
    {
        $maksNovihSlika = max(min($maksNovihSlika, self::MAX_SLIKE), 0);
        $rules = [
            'naslov' => ['required', 'string', 'max:191'],
            'opis' => ['required', 'string', 'max:5000'],
            'cijena' => ['required', 'string', 'max:32'],
            'kontakt_telefon' => ['required', 'string', 'max:64'],
            'kontakt_email' => ['nullable', 'email', 'max:191'],
            'slike' => ['nullable', 'array', 'max:'.$maksNovihSlika],
            'slike.*' => ['file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:6144'],
        ];

        if ($adminMode) {
            $rules['clan_id'] = ['required', 'integer', 'exists:clanovis,id'];
        } else {
            $rules['clan_id'] = ['nullable', 'integer'];
        }

        if ($isUpdate) {
            $rules['obrisi_slike'] = ['nullable', 'array'];
            $rules['obrisi_slike.*'] = ['integer'];
        }

        return $request->validate($rules, [
            'slike.max' => 'Možete dodati najviše '.$maksNovihSlika.' novih slika.',
            'slike.*.image' => 'Svaka datoteka u galeriji mora biti slika.',
        ]);
    }

    /**
     * Odreduje clan_id koji ce biti spremljen na oglasu.
     *
     * @param  array<string, mixed>  $validated
     */
    private function odrediClanIdZaSpremanje(User $korisnik, array $validated, Collection $clanoviZaObjavu, int $fallbackClanId = 0): int
    {
        if ($this->korisnikJeAdmin($korisnik)) {
            $clanId = (int) ($validated['clan_id'] ?? 0);
            if ($clanId > 0 && $clanoviZaObjavu->contains('id', $clanId)) {
                return $clanId;
            }

            throw ValidationException::withMessages([
                'clan_id' => 'Potrebno je odabrati člana za kojeg se predaje oglas.',
            ]);
        }

        $clanId = (int) ($korisnik->clan_id ?? 0);
        if ($clanId > 0 && $clanoviZaObjavu->contains('id', $clanId)) {
            return $clanId;
        }

        if ($fallbackClanId > 0 && $clanoviZaObjavu->contains('id', $fallbackClanId)) {
            return $fallbackClanId;
        }

        abort(403);
    }

    /**
     * Sprema nove slike oglasa.
     *
     * @param  array<int, UploadedFile>  $slike
     */
    private function spremiNoveSlike(Oglas $oglas, array $slike): void
    {
        $postojeciMax = (int) OglasSlika::query()
            ->where('oglas_id', (int) $oglas->id)
            ->max('redni_broj');

        $redni = $postojeciMax;
        foreach ($slike as $slika) {
            if (! $slika instanceof UploadedFile) {
                continue;
            }

            $redni++;
            $ekstenzija = strtolower((string) $slika->extension());
            $naziv = now()->format('Ymd_His').'_'.$redni.'_'.Str::random(8).'.'.$ekstenzija;
            $putanja = $slika->storeAs('oglasi/'.(int) $oglas->id, $naziv, 'public');

            OglasSlika::query()->create([
                'oglas_id' => (int) $oglas->id,
                'putanja' => $putanja,
                'redni_broj' => $redni,
            ]);
        }
    }

    /**
     * Brise slike oglasa po ID-u i renumerira redoslijed.
     *
     * @param  array<int, int>  $ids
     */
    private function obrisiSlikePoId(Oglas $oglas, array $ids): void
    {
        if (count($ids) === 0) {
            return;
        }

        $slike = OglasSlika::query()
            ->where('oglas_id', (int) $oglas->id)
            ->whereIn('id', $ids)
            ->get();

        foreach ($slike as $slika) {
            if (! empty($slika->putanja) && Storage::disk('public')->exists($slika->putanja)) {
                Storage::disk('public')->delete($slika->putanja);
            }
            $slika->delete();
        }

        $preostale = OglasSlika::query()
            ->where('oglas_id', (int) $oglas->id)
            ->orderBy('redni_broj')
            ->orderBy('id')
            ->get();

        $redni = 1;
        foreach ($preostale as $slika) {
            if ((int) $slika->redni_broj !== $redni) {
                $slika->redni_broj = $redni;
                $slika->save();
            }
            $redni++;
        }
    }

    /**
     * Upit "mojih" oglasa za trenutnog korisnika.
     */
    private function upitMojihOglasa(User $korisnik): Builder
    {
        if ($this->korisnikJeAdmin($korisnik)) {
            $adminClanId = (int) ($korisnik->clan_id ?? 0);

            if ($adminClanId > 0) {
                return Oglas::query()->where('clan_id', $adminClanId);
            }

            return Oglas::query()->where('created_by', (int) $korisnik->id);
        }

        if ((int) $korisnik->clan_id > 0) {
            return Oglas::query()->where('clan_id', (int) $korisnik->clan_id);
        }

        return Oglas::query()->where('created_by', (int) $korisnik->id);
    }

    /**
     * Pretvara opis oglasa u HTML s linkovima (http/https => target blank).
     */
    private function formatirajOpisHtml(?string $opis): string
    {
        $tekst = str_replace(["\r\n", "\r"], "\n", trim((string) $opis));
        if ($tekst === '') {
            return '';
        }

        $regex = '/https?:\/\/[^\s<>"\']+/iu';
        preg_match_all($regex, $tekst, $matches, PREG_OFFSET_CAPTURE);

        $rezultat = '';
        $cursor = 0;
        foreach ($matches[0] as $match) {
            $rawUrl = (string) ($match[0] ?? '');
            $offset = (int) ($match[1] ?? 0);

            if ($offset > $cursor) {
                $rezultat .= nl2br(e(substr($tekst, $cursor, $offset - $cursor)), false);
            }

            [$url, $suffix] = $this->odvojiSufiksUrl($rawUrl);
            if ($this->valjanHttpUrl($url)) {
                $escapedUrl = e($url);
                $rezultat .= '<a href="'.$escapedUrl.'" target="_blank" rel="noopener noreferrer">'.$escapedUrl.'</a>';
                if ($suffix !== '') {
                    $rezultat .= e($suffix);
                }
            } else {
                $rezultat .= e($rawUrl);
            }

            $cursor = $offset + strlen($rawUrl);
        }

        if ($cursor < strlen($tekst)) {
            $rezultat .= nl2br(e(substr($tekst, $cursor)), false);
        }

        return $rezultat;
    }

    /**
     * Razdvaja URL od zavrsne interpunkcije koja ne pripada linku.
     *
     * @return array{0:string, 1:string}
     */
    private function odvojiSufiksUrl(string $rawUrl): array
    {
        $url = $rawUrl;
        $suffix = '';

        while ($url !== '' && preg_match('/[),.!?;:\]]$/u', $url) === 1) {
            $zadnji = mb_substr($url, -1, 1, 'UTF-8');
            $url = mb_substr($url, 0, mb_strlen($url, 'UTF-8') - 1, 'UTF-8');
            $suffix = $zadnji.$suffix;
        }

        return [$url, $suffix];
    }

    /**
     * Validira da je URL http ili https.
     */
    private function valjanHttpUrl(string $url): bool
    {
        if ($url === '' || ! filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }

        return str_starts_with(strtolower($url), 'http://') || str_starts_with(strtolower($url), 'https://');
    }

    /**
     * Upisuje formatirani opis u atribute oglasa za prikaz u Bladeu.
     */
    private function dodajOpisHtml(Collection $oglasi): void
    {
        foreach ($oglasi as $oglas) {
            if (! $oglas instanceof Oglas) {
                continue;
            }

            $oglas->setAttribute('opis_html', $this->formatirajOpisHtml((string) $oglas->opis));
        }
    }

    /**
     * Normalizira decimalni iznos iz forme.
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
}
