<?php

namespace App\Services;

use App\Models\Clanovi;
use App\Models\PolaznikSkole;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * Servis povezuje korisničke račune sa stvarnim članovima kluba i polaznicima škole na temelju identiteta.
 */
class KorisnikClanService
{
    /**
     * Pokušava pronaći postojećeg člana koji se registrira tako da uspoređuje OIB, e-mail, ime i telefon.
     */
    public function pronadiClanaZaRegistraciju(string $imePrezime, string $email, string $oib, string $telefon): ?Clanovi
    {
        $normaliziraniOib = $this->normalizirajOib($oib);
        if ($normaliziraniOib === null) {
            return null;
        }

        $clan = Clanovi::where('oib', $normaliziraniOib)->first();
        if ($clan === null) {
            return null;
        }

        if ($this->normalizirajEmail($clan->email) !== $this->normalizirajEmail($email)) {
            return null;
        }

        if ($this->normalizirajImePrezime($clan->Ime . ' ' . $clan->Prezime) !== $this->normalizirajImePrezime($imePrezime)) {
            return null;
        }

        if ($this->normalizirajTelefon($clan->br_telefona) !== $this->normalizirajTelefon($telefon)) {
            return null;
        }

        return $clan;
    }

    /**
     * Pokušava pronaći postojećeg polaznika škole koji se registrira, uz strogu provjeru identiteta.
     */
    public function pronadiPolaznikaZaRegistraciju(string $imePrezime, string $email, string $oib, string $telefon): ?PolaznikSkole
    {
        $normaliziraniOib = $this->normalizirajOib($oib);
        if ($normaliziraniOib === null) {
            return null;
        }

        $polaznik = PolaznikSkole::query()
            ->whereNull('prebacen_u_clana_id')
            ->where('oib', $normaliziraniOib)
            ->first();

        if ($polaznik === null) {
            return null;
        }

        if ($this->normalizirajEmail($polaznik->email) !== $this->normalizirajEmail($email)) {
            return null;
        }

        if ($this->normalizirajImePrezime($polaznik->Ime . ' ' . $polaznik->Prezime) !== $this->normalizirajImePrezime($imePrezime)) {
            return null;
        }

        if ($this->normalizirajTelefon($polaznik->br_telefona) !== $this->normalizirajTelefon($telefon)) {
            return null;
        }

        return $polaznik;
    }

    /**
     * Traži najvjerojatnijeg člana za već postojeći korisnički račun (strogo, pa fallback po e-mailu/imenu).
     */
    public function pronadiClanaZaPostojecegKorisnika(User $user): ?Clanovi
    {
        if (!empty($user->oib) && !empty($user->br_telefona)) {
            $strogaPodudarnost = $this->pronadiClanaZaRegistraciju(
                (string)$user->name,
                (string)$user->email,
                (string)$user->oib,
                (string)$user->br_telefona
            );

            if ($strogaPodudarnost !== null) {
                return $strogaPodudarnost;
            }
        }

        $poEmailu = $this->pronadiJedinstvenoPoEmailu((string)$user->email);
        if ($poEmailu !== null) {
            return $poEmailu;
        }

        return $this->pronadiJedinstvenoPoImenu((string)$user->name);
    }

    /**
     * Provjerava je li član već povezan s nekim drugim korisničkim računom.
     */
    public function clanJeSlobodan(int $clanId, ?int $ignorirajUserId = null): bool
    {
        $query = User::where('clan_id', $clanId);
        if ($ignorirajUserId !== null) {
            $query->where('id', '!=', $ignorirajUserId);
        }

        return !$query->exists();
    }

    /**
     * Provjerava je li polaznik škole već povezan s nekim drugim korisničkim računom.
     */
    public function polaznikJeSlobodan(int $polaznikId, ?int $ignorirajUserId = null): bool
    {
        $query = User::where('polaznik_id', $polaznikId);
        if ($ignorirajUserId !== null) {
            $query->where('id', '!=', $ignorirajUserId);
        }

        return !$query->exists();
    }

    /**
     * Povezuje korisnički račun s članom kluba i po potrebi postavlja rolu člana.
     */
    public function poveziKorisnika(User $user, Clanovi $clan, bool $postaviRoluNaClana = true): bool
    {
        $promijenjeno = false;

        if (!empty($user->polaznik_id)) {
            $user->polaznik_id = null;
            $promijenjeno = true;
        }

        if ((int)$user->clan_id !== (int)$clan->id) {
            $user->clan_id = $clan->id;
            $promijenjeno = true;
        }

        $oib = (string)$clan->oib;
        if ($user->oib !== $oib) {
            $user->oib = $oib;
            $promijenjeno = true;
        }

        $telefon = $this->normalizirajTelefonZaPohranu($clan->br_telefona);
        if ($telefon !== null && $user->br_telefona !== $telefon) {
            $user->br_telefona = $telefon;
            $promijenjeno = true;
        }

        if ($postaviRoluNaClana && (int)$user->rola !== 1 && (int)$user->rola !== 2) {
            $user->rola = 2;
            $promijenjeno = true;
        }

        if ($promijenjeno) {
            $user->save();
        }

        return $promijenjeno;
    }

    /**
     * Povezuje korisnički račun s polaznikom škole i po potrebi postavlja rolu polaznika.
     */
    public function poveziKorisnikaSPolaznikom(User $user, PolaznikSkole $polaznik, bool $postaviRoluNaPolaznika = true): bool
    {
        $promijenjeno = false;

        if (!empty($user->clan_id)) {
            $user->clan_id = null;
            $promijenjeno = true;
        }

        if ((int)$user->polaznik_id !== (int)$polaznik->id) {
            $user->polaznik_id = $polaznik->id;
            $promijenjeno = true;
        }

        $oib = (string)$polaznik->oib;
        if ($user->oib !== $oib) {
            $user->oib = $oib;
            $promijenjeno = true;
        }

        $telefon = $this->normalizirajTelefonZaPohranu($polaznik->br_telefona);
        if ($telefon !== null && $user->br_telefona !== $telefon) {
            $user->br_telefona = $telefon;
            $promijenjeno = true;
        }

        if ($postaviRoluNaPolaznika && (int)$user->rola !== 1 && (int)$user->rola !== 4) {
            $user->rola = 4;
            $promijenjeno = true;
        }

        if ($promijenjeno) {
            $user->save();
        }

        return $promijenjeno;
    }

    /**
     * Odspaja korisnički račun od člana/polaznika i po potrebi vraća rolu na nepovezanog korisnika.
     */
    public function odspojiKorisnika(User $user, bool $postaviRoluNaNepovezanog = true): bool
    {
        $promijenjeno = false;

        if (!empty($user->clan_id)) {
            $user->clan_id = null;
            $promijenjeno = true;
        }

        if (!empty($user->polaznik_id)) {
            $user->polaznik_id = null;
            $promijenjeno = true;
        }

        if ($postaviRoluNaNepovezanog && (int)$user->rola !== 1 && (int)$user->rola !== 3) {
            $user->rola = 3;
            $promijenjeno = true;
        }

        if ($promijenjeno) {
            $user->save();
        }

        return $promijenjeno;
    }

    /**
     * Normalizira broj telefona u jedinstveni format za spremanje (s međunarodnim prefiksom).
     */
    public function normalizirajTelefonZaPohranu(?string $telefon): ?string
    {
        $normaliziraniTelefon = $this->normalizirajTelefon($telefon);

        return $normaliziraniTelefon === null ? null : '+' . $normaliziraniTelefon;
    }

    /**
     * Čisti i validira OIB te vraća samo ispravan 11-znamenkasti zapis.
     */
    public function normalizirajOib(?string $oib): ?string
    {
        $oib = preg_replace('/\D+/', '', (string)$oib);

        return strlen($oib) === 11 ? $oib : null;
    }

    /**
     * Traži člana jedinstveno po e-mailu (vraća rezultat samo ako postoji točno jedno podudaranje).
     */
    private function pronadiJedinstvenoPoEmailu(string $email): ?Clanovi
    {
        $normaliziraniEmail = $this->normalizirajEmail($email);
        if ($normaliziraniEmail === null) {
            return null;
        }

        $kandidati = Clanovi::query()
            ->whereNotNull('email')
            ->get()
            ->filter(fn (Clanovi $clan) => $this->normalizirajEmail($clan->email) === $normaliziraniEmail)
            ->values();

        return $kandidati->count() === 1 ? $kandidati->first() : null;
    }

    /**
     * Traži člana jedinstveno po imenu i prezimenu (vraća rezultat samo ako postoji točno jedno podudaranje).
     */
    private function pronadiJedinstvenoPoImenu(string $imePrezime): ?Clanovi
    {
        $normaliziranoIme = $this->normalizirajImePrezime($imePrezime);
        if ($normaliziranoIme === '') {
            return null;
        }

        $kandidati = Clanovi::query()
            ->get()
            ->filter(
                fn (Clanovi $clan) => $this->normalizirajImePrezime($clan->Ime . ' ' . $clan->Prezime) === $normaliziranoIme
            )
            ->values();

        return $kandidati->count() === 1 ? $kandidati->first() : null;
    }

    /**
     * Normalizira ime i prezime (mala slova, bez dijakritike, jedinstveni razmaci) radi pouzdane usporedbe.
     */
    private function normalizirajImePrezime(?string $imePrezime): string
    {
        $imePrezime = mb_strtolower(trim((string)$imePrezime));
        $imePrezime = strtr($imePrezime, [
            'č' => 'c',
            'ć' => 'c',
            'đ' => 'd',
            'š' => 's',
            'ž' => 'z',
        ]);
        $imePrezime = preg_replace('/[^a-z0-9]+/u', ' ', $imePrezime);

        return trim(preg_replace('/\s+/', ' ', (string)$imePrezime));
    }

    /**
     * Normalizira e-mail adresu (trim + lowercase) za usporedbu zapisa.
     */
    private function normalizirajEmail(?string $email): ?string
    {
        $email = mb_strtolower(trim((string)$email));

        return $email === '' ? null : $email;
    }

    /**
     * Normalizira broj telefona u međunarodni brojčani oblik (HR prefiks).
     */
    private function normalizirajTelefon(?string $telefon): ?string
    {
        $telefon = preg_replace('/\D+/', '', (string)$telefon);
        if ($telefon === '') {
            return null;
        }

        if (Str::startsWith($telefon, '00')) {
            $telefon = substr($telefon, 2);
        }

        if (Str::startsWith($telefon, '0')) {
            $telefon = '385' . substr($telefon, 1);
        }

        return $telefon;
    }
}
