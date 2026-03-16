<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @method static orderBy(string $string)
 * @property mixed $spol
 * @property mixed $naziv
 */
class Kategorije extends Model
{
    use HasFactory;

    protected $fillable = ['naziv', 'spol'];

    /**
     * Kategorija može imati više povezanih zapisa: pojedinačne rezultate članova.
     */
    public function rezultatiOpci(): HasMany
    {
        return $this->hasMany(RezultatiOpci::class, 'kategorija_id', 'id');
    }

    /**
     * Kategorija može imati više povezanih zapisa: detaljna polja rezultata prema tipu turnira.
     */
    public function rezultatiPoTipuTurnira(): HasMany
    {
        return $this->hasMany(RezultatiPoTipuTurnira::class, 'kategorija_id', 'id');
    }
}
