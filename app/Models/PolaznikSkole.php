<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PolaznikSkole extends Model
{
    use HasFactory;

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

    public function dolasci(): HasMany
    {
        return $this->hasMany(PolaznikSkoleDolazak::class, 'polaznik_skole_id', 'id');
    }

    public function povezaniKorisnik(): HasOne
    {
        return $this->hasOne(User::class, 'polaznik_id', 'id');
    }

    public function prebacenClan(): BelongsTo
    {
        return $this->belongsTo(Clanovi::class, 'prebacen_u_clana_id');
    }

    public function dokumenti(): HasMany
    {
        return $this->hasMany(PolaznikSkoleDokument::class, 'polaznik_skole_id', 'id');
    }

    public function roditelji(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'roditelj_polaznik', 'polaznik_id', 'roditelj_user_id');
    }

    public function paymentProfile(): HasOne
    {
        return $this->hasOne(PolaznikPaymentProfile::class, 'polaznik_skole_id', 'id');
    }

    public function paymentCharges(): HasMany
    {
        return $this->hasMany(PolaznikPaymentCharge::class, 'polaznik_skole_id', 'id');
    }
}
