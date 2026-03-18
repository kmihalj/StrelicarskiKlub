<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Model PolaznikSkole predstavlja zapis baze podataka i definira relacije te pomoćne metode za rad s podacima.
 */
class PolaznikSkole extends Model
{

    protected $table = 'polaznici_skole';

    protected $fillable = [
        'Ime',
        'Prezime',
        'datum_rodjenja',
        'datum_upisa',
        'oib',
        'br_telefona',
        'email',
        'spol',
        'u_skoli',
        'prebacen_u_clana_id',
        'prebacen_at',
    ];

    protected $casts = [
        'datum_rodjenja' => 'date',
        'datum_upisa' => 'date',
        'u_skoli' => 'boolean',
        'prebacen_at' => 'datetime',
    ];

    /**
     * Vraća evidenciju dolazaka polaznika škole na treninge.
     */
    public function dolasci(): HasMany
    {
        return $this->hasMany(PolaznikSkoleDolazak::class, 'polaznik_skole_id', 'id');
    }

    /**
     * Vraća korisnički račun povezan s ovim polaznikom.
     */
    public function povezaniKorisnik(): HasOne
    {
        return $this->hasOne(User::class, 'polaznik_id', 'id');
    }

    /**
     * Ako je polaznik prešao u članstvo, vraća povezani zapis člana.
     */
    /** @noinspection PhpUnused */
    public function prebacenClan(): BelongsTo
    {
        return $this->belongsTo(Clanovi::class, 'prebacen_u_clana_id');
    }

    /**
     * Vraća dokumente učitane za polaznika škole.
     */
    public function dokumenti(): HasMany
    {
        return $this->hasMany(PolaznikSkoleDokument::class, 'polaznik_skole_id', 'id');
    }

    /**
     * Vraća roditeljske račune koji imaju pristup podacima ovog polaznika.
     */
    public function roditelji(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'roditelj_polaznik', 'polaznik_id', 'roditelj_user_id');
    }

    /**
     * Vraća profil praćenja školarine za ovog polaznika.
     */
    /** @noinspection PhpUnused */
    public function paymentProfile(): HasOne
    {
        return $this->hasOne(PolaznikPaymentProfile::class, 'polaznik_skole_id', 'id');
    }

    /**
     * Vraća sve stavke zaduženja i uplata školarine polaznika.
     */
    /** @noinspection PhpUnused */
    public function paymentCharges(): HasMany
    {
        return $this->hasMany(PolaznikPaymentCharge::class, 'polaznik_skole_id', 'id');
    }
}
