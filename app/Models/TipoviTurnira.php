<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property mixed $naziv
 * @method static orderBy(string $string)
 * @method static find(mixed $get)
 */
class TipoviTurnira extends Model
{

    protected $fillable = ['naziv'];

    /**
     * Vraća polja koja definiraju unos rezultata za ovaj tip turnira.
     */
    public function polja(): HasMany
    {
        return $this->hasMany(PoljaZaTipoveTurnira::class, 'tipovi_turnira_id', 'id');
    }

    /**
     * Vraća sve turnire koji koriste ovaj tip turnira.
     */
    public function turniri(): HasMany
    {
        return $this->hasMany(Turniri::class, 'tipovi_turnira_id', 'id');
    }
}
