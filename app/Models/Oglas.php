<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Model Oglas predstavlja jednu stavku oglasnika.
 */
class Oglas extends Model
{
    protected $table = 'oglasi';

    protected $fillable = [
        'clan_id',
        'created_by',
        'updated_by',
        'naslov',
        'opis',
        'cijena',
        'kontakt_telefon',
        'kontakt_email',
        'is_active',
        'deactivated_at',
    ];

    protected $casts = [
        'clan_id' => 'integer',
        'created_by' => 'integer',
        'updated_by' => 'integer',
        'cijena' => 'decimal:2',
        'is_active' => 'boolean',
        'deactivated_at' => 'datetime',
    ];

    /**
     * Oglas pripada jednom clanu kluba.
     */
    public function clan(): BelongsTo
    {
        return $this->belongsTo(Clanovi::class, 'clan_id', 'id');
    }

    /**
     * Vlasnik korisnickog racuna koji je kreirao oglas.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    /**
     * Korisnicki racun koji je zadnji azurirao oglas.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by', 'id');
    }

    /**
     * Sve slike povezane s oglasom.
     */
    public function slike(): HasMany
    {
        return $this->hasMany(OglasSlika::class, 'oglas_id', 'id')->orderBy('redni_broj')->orderBy('id');
    }
}
