<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Model Klub predstavlja zapis baze podataka i definira relacije te pomoćne metode za rad s podacima.
 */
class Klub extends Model
{

    protected $fillable = ['naziv', 'adresa', 'telefon', 'email', 'racun'];

    /**
     * Klub može imati više povezanih zapisa: funkcije članova u klubu.
     */
    /** @noinspection PhpUnused */
    public function funkcije(): HasMany
    {
        return $this->hasMany(clanoviFunkcije::class, 'klub_id', 'id');
    }

    /**
     * Klub može imati više povezanih zapisa: dokumente kluba.
     */
    public function dokumenti(): HasMany
    {
        return $this->hasMany(DokumentiKluba::class, 'klub_id', 'id');
    }
}
