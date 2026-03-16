<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Model Clanci predstavlja zapis baze podataka i definira relacije te pomoćne metode za rad s podacima.
 */
class Clanci extends Model
{
    use HasFactory;

    protected $fillable = ['vrsta', 'naslov', 'datum', 'sadrzaj', 'menu', 'menu_naslov', 'galerija'];

    /**
     * Članak može imati više povezanih zapisa: medijske priloge članka.
     */
    public function mediji(): HasMany
    {
        return $this->hasMany(MedijiClanaka::class, 'clanak_id', 'id');
    }
}
