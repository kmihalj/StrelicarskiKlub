<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property mixed $naziv
 * @method static orderBy(string $string)
 * @method static find(mixed $get)
 */
class TipoviTurnira extends Model
{
    use HasFactory;

    protected $fillable = ['naziv'];

    public function polja(): HasMany
    {
        return $this->hasMany(PoljaZaTipoveTurnira::class, 'tipovi_turnira_id', 'id');
    }

    public function turniri(): HasMany
    {
        return $this->hasMany(Turniri::class, 'tipovi_turnira_id', 'id');
    }
}
