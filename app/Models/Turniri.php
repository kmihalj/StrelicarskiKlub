<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @method static orderByDesc(string $string)
 * @method static find(int $id)
 * @method static whereYear(string $string, string $date)
 * @property mixed $datum
 * @property mixed $naziv
 * @property mixed $lokacija
 * @property mixed $tipovi_turnira_id
 * @property bool|mixed $eliminacije
 */
class Turniri extends Model
{
    use HasFactory;

    protected $fillable = ['naziv', 'datum', 'lokacija', 'opis', 'opis2', 'tipovi_turnira_id'];

    public function tipTurnira(): BelongsTo
    {
        return $this->belongsTo(TipoviTurnira::class, 'tipovi_turnira_id');
    }

    public function rezultatiOpci(): HasMany
    {
        return $this->hasMany(RezultatiOpci::class, 'turnir_id', 'id');
    }

    /** @noinspection PhpUnused */
    public function rezultatiPoTipuTurnira(): HasMany
    {
        return $this->hasMany(RezultatiPoTipuTurnira::class, 'turnir_id', 'id');
    }

    /** @noinspection PhpUnused */
    public function mediji(): HasMany
    {
        return $this->hasMany(RezultatiSlike::class, 'turnir_id', 'id');
    }
}
