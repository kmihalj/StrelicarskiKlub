<?php

namespace App\Services;

use App\Models\Clanovi;
use App\Models\PolaznikSkole;
use App\Models\User;
use Illuminate\Support\Str;

class KorisnikClanService
{
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

    public function clanJeSlobodan(int $clanId, ?int $ignorirajUserId = null): bool
    {
        $query = User::where('clan_id', $clanId);
        if ($ignorirajUserId !== null) {
            $query->where('id', '!=', $ignorirajUserId);
        }

        return !$query->exists();
    }

    public function polaznikJeSlobodan(int $polaznikId, ?int $ignorirajUserId = null): bool
    {
        $query = User::where('polaznik_id', $polaznikId);
        if ($ignorirajUserId !== null) {
            $query->where('id', '!=', $ignorirajUserId);
        }

        return !$query->exists();
    }

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

    public function normalizirajTelefonZaPohranu(?string $telefon): ?string
    {
        $normaliziraniTelefon = $this->normalizirajTelefon($telefon);

        return $normaliziraniTelefon === null ? null : '+' . $normaliziraniTelefon;
    }

    public function normalizirajOib(?string $oib): ?string
    {
        $oib = preg_replace('/\D+/', '', (string)$oib);

        return strlen($oib) === 11 ? $oib : null;
    }

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

    private function normalizirajEmail(?string $email): ?string
    {
        $email = mb_strtolower(trim((string)$email));

        return $email === '' ? null : $email;
    }

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
