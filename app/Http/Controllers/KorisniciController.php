<?php

namespace App\Http\Controllers;

use App\Models\Clanovi;
use App\Models\PolaznikSkole;
use App\Models\User;
use App\Services\KorisnikClanService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * Admin kontroler za korisničke račune: role, povezivanje s članom/polaznikom i roditeljske veze.
 */
class KorisniciController extends Controller
{
    /**
     * Učitava servis za povezivanje korisničkih računa s članovima i polaznicima.
     */
    public function __construct(private readonly KorisnikClanService $korisnikClanService)
    {
    }

    /**
     * Prikazuje popis svih korisničkih računa i njihovih rola.
     */
    public function index(): View
    {
        $users = User::query()
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'oib', 'br_telefona', 'rola', 'je_roditelj']);

        return view('admin.korisnici.index', [
            'users' => $users,
        ]);
    }

    /**
     * Otvara detaljno uređivanje korisnika, uključujući rolu i poveznice na članove/polaznike.
     */
    public function edit(User $user): View
    {
        $user->load([
            'clan',
            'polaznik',
            'djecaClanovi:id,Ime,Prezime,oib,datum_rodjenja,aktivan',
            'djecaPolaznici:id,Ime,Prezime,oib,datum_rodjenja,u_skoli,prebacen_u_clana_id,datum_upisa',
        ]);

        $podaciOdabira = $this->dohvatiPodatkeOdabiraZaKorisnika($user);

        return view('admin.korisnici.uredjivanje', array_merge([
            'user' => $user,
        ], $podaciOdabira));
    }

    /**
     * Ažurira korisnički račun, rolu i veze prema članu, polazniku škole i roditeljstvu.
     */
    public function update(Request $request, User $user): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'oib' => ['nullable', 'digits:11', Rule::unique('users', 'oib')->ignore($user->id)],
            'br_telefona' => ['nullable', 'regex:/^\+385\d{8,9}$/', 'max:13', Rule::unique('users', 'br_telefona')->ignore($user->id)],
            'rola' => ['required', Rule::in(['1', '2', '3', '4'])],
            'povezani_id' => ['nullable', 'integer'],
            'je_roditelj' => ['nullable', 'boolean'],
            'roditelj_clanovi' => ['nullable', 'array'],
            'roditelj_clanovi.*' => ['integer'],
            'roditelj_polaznici' => ['nullable', 'array'],
            'roditelj_polaznici.*' => ['integer'],
        ], [
            'povezani_id.integer' => 'Odabrani povezani profil nije ispravan.',
            'br_telefona.regex' => 'Broj telefona mora biti u formatu +385xxxxxxxxx.',
        ]);

        $validator->after(function ($validator) use ($request, $user): void {
            $rola = (int)$request->input('rola');
            $povezaniIdRaw = $request->input('povezani_id');
            $povezaniId = ($povezaniIdRaw === null || $povezaniIdRaw === '') ? null : (int)$povezaniIdRaw;
            $jeRoditelj = $request->boolean('je_roditelj');
            $granicaAktivnostiSkole = now()->startOfDay()->subMonthsNoOverflow(4)->toDateString();
            $granicaMaloljetnosti = now()->startOfDay()->subYears(18)->toDateString();

            $roditeljClanovi = collect((array)$request->input('roditelj_clanovi', []))
                ->map(fn ($id) => (int)$id)
                ->filter(fn (int $id) => $id > 0)
                ->unique()
                ->values();
            $roditeljPolaznici = collect((array)$request->input('roditelj_polaznici', []))
                ->map(fn ($id) => (int)$id)
                ->filter(fn (int $id) => $id > 0)
                ->unique()
                ->values();

            if ($rola === 3) {
                // Bez dodatnih obaveznih pravila za nepovezanog korisnika.
            } elseif (in_array($rola, [1, 2], true)) {
                if ($rola === 2 && empty($povezaniId)) {
                    $validator->errors()->add('povezani_id', 'Za rolu član potrebno je povezati korisnika sa članom.');
                    return;
                }

                if (!empty($povezaniId)) {
                    $clan = Clanovi::query()->where('aktivan', true)->find($povezaniId);
                    if ($clan === null) {
                        $validator->errors()->add('povezani_id', 'Odabrani član nije pronađen među aktivnim članovima.');
                        return;
                    }

                    $zauzetOdDrugog = User::query()
                        ->where('clan_id', $povezaniId)
                        ->where('id', '!=', $user->id)
                        ->exists();

                    if ($zauzetOdDrugog) {
                        $validator->errors()->add('povezani_id', 'Odabrani član je već povezan sa drugim korisnikom.');
                    }
                }
            } elseif ($rola === 4) {
                if (empty($povezaniId)) {
                    $validator->errors()->add('povezani_id', 'Za rolu polaznik škole potrebno je povezati korisnika sa polaznikom.');
                    return;
                }

                $polaznik = PolaznikSkole::query()
                    ->whereNull('prebacen_u_clana_id')
                    ->where('u_skoli', true)
                    ->where(function ($query) use ($granicaAktivnostiSkole) {
                        $query->whereNull('datum_upisa')
                            ->orWhereDate('datum_upisa', '>=', $granicaAktivnostiSkole);
                    })
                    ->find($povezaniId);
                if ($polaznik === null) {
                    $validator->errors()->add('povezani_id', 'Odabrani polaznik nije dostupan za povezivanje.');
                    return;
                }

                $zauzetOdDrugog = User::query()
                    ->where('polaznik_id', $povezaniId)
                    ->where('id', '!=', $user->id)
                    ->exists();

                if ($zauzetOdDrugog) {
                    $validator->errors()->add('povezani_id', 'Odabrani polaznik je već povezan sa drugim korisnikom.');
                }
            }

            if (!$jeRoditelj) {
                if ($roditeljClanovi->isNotEmpty() || $roditeljPolaznici->isNotEmpty()) {
                    $validator->errors()->add('je_roditelj', 'Za povezivanje djece potrebno je uključiti oznaku roditelja.');
                }

                return;
            }

            $ukupnoDjece = $roditeljClanovi->count() + $roditeljPolaznici->count();
            if ($ukupnoDjece > 3) {
                $validator->errors()->add('roditelj_clanovi', 'Roditelj može biti povezan s najviše 3 djece.');
                return;
            }

            foreach ($roditeljClanovi as $clanId) {
                $clan = Clanovi::query()
                    ->where('aktivan', true)
                    ->whereNotNull('datum_rodjenja')
                    ->whereDate('datum_rodjenja', '>', $granicaMaloljetnosti)
                    ->find($clanId);
                if ($clan === null) {
                    $validator->errors()->add('roditelj_clanovi', 'Odabrano dijete (član) nije dostupno ili nije maloljetno.');
                    continue;
                }

                $brojDrugihRoditelja = $clan->roditelji()
                    ->where('users.id', '!=', $user->id)
                    ->count();
                if ($brojDrugihRoditelja >= 2) {
                    $validator->errors()->add('roditelj_clanovi', 'Odabrano dijete (član) već ima maksimalan broj roditelja.');
                }
            }

            foreach ($roditeljPolaznici as $polaznikId) {
                $polaznik = PolaznikSkole::query()
                    ->whereNull('prebacen_u_clana_id')
                    ->where('u_skoli', true)
                    ->whereNotNull('datum_rodjenja')
                    ->whereDate('datum_rodjenja', '>', $granicaMaloljetnosti)
                    ->where(function ($query) use ($granicaAktivnostiSkole) {
                        $query->whereNull('datum_upisa')
                            ->orWhereDate('datum_upisa', '>=', $granicaAktivnostiSkole);
                    })
                    ->find($polaznikId);
                if ($polaznik === null) {
                    $validator->errors()->add('roditelj_polaznici', 'Odabrano dijete (polaznik) nije dostupno ili nije maloljetno.');
                    continue;
                }

                $brojDrugihRoditelja = $polaznik->roditelji()
                    ->where('users.id', '!=', $user->id)
                    ->count();
                if ($brojDrugihRoditelja >= 2) {
                    $validator->errors()->add('roditelj_polaznici', 'Odabrano dijete (polaznik) već ima maksimalan broj roditelja.');
                }
            }
        });

        $validated = $validator->validate();
        $rola = (int)$validated['rola'];
        $povezaniId = isset($validated['povezani_id']) && $validated['povezani_id'] !== ''
            ? (int)$validated['povezani_id']
            : null;
        $jeRoditelj = $request->boolean('je_roditelj');
        $targetWasAdmin = (int)$user->rola === 1;
        $roditeljClanovi = collect((array)$request->input('roditelj_clanovi', []))
            ->map(fn ($id) => (int)$id)
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->values()
            ->all();
        $roditeljPolaznici = collect((array)$request->input('roditelj_polaznici', []))
            ->map(fn ($id) => (int)$id)
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        $user->name = trim((string)$validated['name']);
        $user->email = mb_strtolower(trim((string)$validated['email']));
        $user->oib = $validated['oib'] ?? null;
        $user->br_telefona = $validated['br_telefona'] ?? null;
        $user->rola = $rola;
        $user->clan_id = in_array($rola, [1, 2], true) ? $povezaniId : null;
        $user->polaznik_id = $rola === 4 ? $povezaniId : null;
        $user->je_roditelj = $jeRoditelj;

        if (empty($user->oib) && !empty($user->clan_id)) {
            $clan = Clanovi::findOrFail($user->clan_id);
            $user->oib = (string)$clan->oib;
        } elseif (empty($user->oib) && !empty($user->polaznik_id)) {
            $polaznik = PolaznikSkole::findOrFail($user->polaznik_id);
            $user->oib = (string)$polaznik->oib;
        }

        if (empty($user->br_telefona) && !empty($user->clan_id)) {
            $clan = Clanovi::findOrFail($user->clan_id);
            $telefon = $this->korisnikClanService->normalizirajTelefonZaPohranu($clan->br_telefona);
            if ($telefon !== null) {
                $user->br_telefona = $telefon;
            }
        } elseif (empty($user->br_telefona) && !empty($user->polaznik_id)) {
            $polaznik = PolaznikSkole::findOrFail($user->polaznik_id);
            $telefon = $this->korisnikClanService->normalizirajTelefonZaPohranu($polaznik->br_telefona);
            if ($telefon !== null) {
                $user->br_telefona = $telefon;
            }
        }

        $user->save();

        if ($jeRoditelj) {
            $user->djecaClanovi()->sync($roditeljClanovi);
            $user->djecaPolaznici()->sync($roditeljPolaznici);
        } else {
            $user->djecaClanovi()->detach();
            $user->djecaPolaznici()->detach();
        }

        $trenutniKorisnik = Auth::user();
        $jeBootstrapAdmin = $trenutniKorisnik instanceof User
            && (bool)$trenutniKorisnik->is_bootstrap_admin;
        $promoviranNoviAdmin = !$targetWasAdmin && (int)$user->rola === 1;
        $promoviranJeDrugiKorisnik = $trenutniKorisnik instanceof User
            && (int)$trenutniKorisnik->id !== (int)$user->id;

        if ($jeBootstrapAdmin && $promoviranNoviAdmin && $promoviranJeDrugiKorisnik) {
            $bootstrapUserId = (int)$trenutniKorisnik->id;

            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            User::query()->whereKey($bootstrapUserId)->delete();

            return redirect()
                ->route('login')
                ->with('success', 'Novi administrator je postavljen. Početni korisnik Administrator je automatski uklonjen.');
        }

        return redirect()->route('admin.korisnici.edit', $user)->with('success', 'Podaci korisnika su spremljeni.');
    }

    /**
     * Briše korisnički račun iz sustava (uz zaštitu aktivne sesije administratora).
     */
    public function destroy(User $user): RedirectResponse
    {
        if ((int)auth()->id() === (int)$user->id) {
            return redirect()->route('admin.korisnici.index')->with('error', 'Ne možete obrisati trenutno prijavljenog korisnika.');
        }

        $user->delete();

        return redirect()->route('admin.korisnici.index')->with('success', 'Korisnik je obrisan.');
    }

    /**
     * Dohvaća potrebne podatke iz baze za prikaz ili daljnju obradu u modulu korisničkih računa i ovlasti.
     */
    private function dohvatiPodatkeOdabiraZaKorisnika(User $user): array
    {
        $granicaAktivnostiSkole = now()->startOfDay()->subMonthsNoOverflow(4)->toDateString();
        $granicaMaloljetnosti = now()->startOfDay()->subYears(18)->toDateString();

        $odabraniClanoviIds = $user->djecaClanovi->pluck('id')->map(fn ($id) => (int)$id)->all();
        $odabraniPolazniciIds = $user->djecaPolaznici->pluck('id')->map(fn ($id) => (int)$id)->all();

        $clanovi = Clanovi::query()
            ->where('aktivan', true)
            ->orderBy('Prezime')
            ->orderBy('Ime')
            ->get(['id', 'Ime', 'Prezime', 'oib']);

        $polaznici = PolaznikSkole::query()
            ->whereNull('prebacen_u_clana_id')
            ->where('u_skoli', true)
            ->where(function ($query) use ($granicaAktivnostiSkole) {
                $query->whereNull('datum_upisa')
                    ->orWhereDate('datum_upisa', '>=', $granicaAktivnostiSkole);
            })
            ->orderBy('Prezime')
            ->orderBy('Ime')
            ->get(['id', 'Ime', 'Prezime', 'oib', 'u_skoli', 'prebacen_u_clana_id']);

        $maloljetniClanovi = Clanovi::query()
            ->where('aktivan', true)
            ->whereNotNull('datum_rodjenja')
            ->whereDate('datum_rodjenja', '>', $granicaMaloljetnosti)
            ->withCount('roditelji')
            ->orderBy('Prezime')
            ->orderBy('Ime')
            ->get(['id', 'Ime', 'Prezime', 'oib'])
            ->filter(function (Clanovi $clan) use ($odabraniClanoviIds): bool {
                return (int)$clan->roditelji_count < 2 || in_array((int)$clan->id, $odabraniClanoviIds, true);
            })
            ->values();

        $maloljetniPolaznici = PolaznikSkole::query()
            ->whereNull('prebacen_u_clana_id')
            ->where('u_skoli', true)
            ->whereNotNull('datum_rodjenja')
            ->whereDate('datum_rodjenja', '>', $granicaMaloljetnosti)
            ->where(function ($query) use ($granicaAktivnostiSkole) {
                $query->whereNull('datum_upisa')
                    ->orWhereDate('datum_upisa', '>=', $granicaAktivnostiSkole);
            })
            ->withCount('roditelji')
            ->orderBy('Prezime')
            ->orderBy('Ime')
            ->get(['id', 'Ime', 'Prezime', 'oib'])
            ->filter(function (PolaznikSkole $polaznik) use ($odabraniPolazniciIds): bool {
                return (int)$polaznik->roditelji_count < 2 || in_array((int)$polaznik->id, $odabraniPolazniciIds, true);
            })
            ->values();

        $zauzetiClanovi = User::query()->whereNotNull('clan_id')->pluck('id', 'clan_id')->toArray();
        $zauzetiPolaznici = User::query()->whereNotNull('polaznik_id')->pluck('id', 'polaznik_id')->toArray();

        return [
            'clanovi' => $clanovi,
            'polaznici' => $polaznici,
            'maloljetniClanovi' => $maloljetniClanovi,
            'maloljetniPolaznici' => $maloljetniPolaznici,
            'zauzetiClanovi' => $zauzetiClanovi,
            'zauzetiPolaznici' => $zauzetiPolaznici,
        ];
    }
}
