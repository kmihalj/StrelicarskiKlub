<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @method static orderBy(string $string)
 * @method static find(mixed $get)
 *
 * @property mixed $Prezime
 * @property mixed $Ime
 * @property mixed $datum_rodjenja
 * @property mixed $oib
 * @property mixed $br_telefona
 * @property mixed $email
 * @property mixed|string $broj_licence
 * @property mixed $spol
 * @property false|mixed $aktivan
 * @property mixed $clan_od
 * @property mixed $id
 */
class Clanovi extends Model
{
    protected $fillable = ['Ime', 'Prezime', 'slika_link', 'datum_rodjenja', 'br_telefona', 'email', 'clan_od', 'datum_pocetka_clanstva', 'aktivan', 'spol', 'oib', 'broj_licence', 'lijecnicki_do', 'lijecnicki_dokument'];

    protected $casts = [
        'datum_pocetka_clanstva' => 'date',
    ];

    /**
     * Vraca sve pojedinacne rezultate koje je clan ostvario na turnirima.
     */
    /** @noinspection PhpUnused */
    public function rezultatiOpci(): HasMany
    {
        return $this->hasMany(RezultatiOpci::class, 'clan_id', 'id');
    }

    /**
     * Vraca detaljne stavke rezultata clana po poljima tipa turnira.
     */
    public function rezultatiPoTipuTurnira(): HasMany
    {
        return $this->hasMany(RezultatiPoTipuTurnira::class, 'clan_id', 'id');
    }

    /**
     * Vraca funkcije koje clan obavlja u klubu (npr. trener, tajnik).
     */
    /** @noinspection PhpUnused */
    public function funkcijeUklubu(): HasMany
    {
        return $this->hasMany(clanoviFunkcije::class, 'clan_id', 'id');
    }

    /**
     * Vraca sve evidentirane lijecnicke preglede clana.
     */
    public function lijecnickiPregledi(): HasMany
    {
        return $this->hasMany(ClanLijecnickiPregled::class, 'clan_id', 'id');
    }

    /**
     * Vraca zadnji vazeci lijecnicki pregled clana (po datumu `vrijedi_do`).
     */
    /** @noinspection PhpUnused */
    public function zadnjiLijecnickiPregled(): HasOne
    {
        return $this->hasOne(ClanLijecnickiPregled::class, 'clan_id', 'id')->latestOfMany('vrijedi_do');
    }

    /**
     * Vraca sve dokumente koji su ucitani za clana.
     */
    public function dokumenti(): HasMany
    {
        return $this->hasMany(ClanDokument::class, 'clan_id', 'id');
    }

    /**
     * Vraca korisnicki racun koji je povezan s ovim clanom.
     */
    public function korisnik(): HasOne
    {
        return $this->hasOne(User::class, 'clan_id', 'id');
    }

    /**
     * Vraca roditeljske korisnicke racune koji imaju pristup podacima ovog clana.
     */
    public function roditelji(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'roditelj_clan', 'clan_id', 'roditelj_user_id');
    }

    /**
     * Vraca zapise skole strelicarstva koji su kasnije prebaceni na ovog clana.
     */
    /** @noinspection PhpUnused */
    public function evidencijeSkole(): HasMany
    {
        return $this->hasMany(PolaznikSkole::class, 'prebacen_u_clana_id', 'id');
    }

    /**
     * Vraca sve dvoranske treninge evidentirane za clana.
     */
    /** @noinspection PhpUnused */
    public function treninziDvorana(): HasMany
    {
        return $this->hasMany(TreninziDvorana::class, 'clan_id', 'id');
    }

    /**
     * Vraca sve vanjske treninge evidentirane za clana.
     */
    /** @noinspection PhpUnused */
    public function treninziVanjski(): HasMany
    {
        return $this->hasMany(TreninziVanjski::class, 'clan_id', 'id');
    }

    /**
     * Vraca aktivni profil pracenja clanarine za clana.
     */
    /** @noinspection PhpUnused */
    public function paymentProfile(): HasOne
    {
        return $this->hasOne(ClanPaymentProfile::class, 'clan_id', 'id');
    }

    /**
     * Vraca sve stavke zaduzenja i uplata clanarine ovog clana.
     */
    /** @noinspection PhpUnused */
    public function paymentCharges(): HasMany
    {
        return $this->hasMany(ClanPaymentCharge::class, 'clan_id', 'id');
    }

    /**
     * Vraca prijave clana na nadolazece turnire.
     */
    /** @noinspection PhpUnused */
    public function prijaveTurnira(): HasMany
    {
        return $this->hasMany(PrijavaTurnira::class, 'clan_id', 'id');
    }

    /**
     * Vraca oglase koje je clan objavio u oglasniku.
     */
    public function oglasi(): HasMany
    {
        return $this->hasMany(Oglas::class, 'clan_id', 'id');
    }

    /**
     * Azurira polje `lijecnicki_do` na clanu prema najdaljem datumu iz svih evidentiranih pregleda.
     */
    public function osvjeziLijecnickiDo(): void
    {
        $this->lijecnicki_do = $this->lijecnickiPregledi()->max('vrijedi_do');
        $this->save();
    }
}
