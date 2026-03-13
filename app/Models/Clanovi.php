<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @method static orderBy(string $string)
 * @method static find(mixed $get)
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
    use HasFactory;

    protected $fillable = ['Ime', 'Prezime', 'slika_link', 'datum_rodjenja', 'br_telefona', 'email', 'clan_od', 'datum_pocetka_clanstva', 'aktivan', 'spol', 'oib', 'broj_licence', 'lijecnicki_do', 'lijecnicki_dokument'];

    protected $casts = [
        'datum_pocetka_clanstva' => 'date',
    ];

    public function rezultatiOpci(): HasMany
    {
        return $this->hasMany(RezultatiOpci::class, 'clan_id', 'id');
    }

    public function rezultatiPoTipuTurnira(): HasMany
    {
        return $this->hasMany(RezultatiPoTipuTurnira::class, 'clan_id', 'id');
    }

    public function funkcijeUklubu(): HasMany
    {
        return $this->hasMany(clanoviFunkcije::class, 'clan_id', 'id');
    }

    public function lijecnickiPregledi(): HasMany
    {
        return $this->hasMany(ClanLijecnickiPregled::class, 'clan_id', 'id');
    }

    public function zadnjiLijecnickiPregled(): HasOne
    {
        return $this->hasOne(ClanLijecnickiPregled::class, 'clan_id', 'id')->latestOfMany('vrijedi_do');
    }

    public function dokumenti(): HasMany
    {
        return $this->hasMany(ClanDokument::class, 'clan_id', 'id');
    }

    public function korisnik(): HasOne
    {
        return $this->hasOne(User::class, 'clan_id', 'id');
    }

    public function roditelji(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'roditelj_clan', 'clan_id', 'roditelj_user_id');
    }

    public function evidencijeSkole(): HasMany
    {
        return $this->hasMany(PolaznikSkole::class, 'prebacen_u_clana_id', 'id');
    }

    public function treninziDvorana(): HasMany
    {
        return $this->hasMany(TreninziDvorana::class, 'clan_id', 'id');
    }

    public function treninziVanjski(): HasMany
    {
        return $this->hasMany(TreninziVanjski::class, 'clan_id', 'id');
    }

    public function paymentProfile(): HasOne
    {
        return $this->hasOne(ClanPaymentProfile::class, 'clan_id', 'id');
    }

    public function paymentCharges(): HasMany
    {
        return $this->hasMany(ClanPaymentCharge::class, 'clan_id', 'id');
    }

    public function osvjeziLijecnickiDo(): void
    {
        $this->lijecnicki_do = $this->lijecnickiPregledi()->max('vrijedi_do');
        $this->save();
    }
}
