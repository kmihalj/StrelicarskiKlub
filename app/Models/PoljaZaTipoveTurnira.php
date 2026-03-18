<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property mixed $naziv
 * @property mixed $tipovi_turnira_id
 * @method static find(mixed $get)
 */
class PoljaZaTipoveTurnira extends Model
{

    protected $fillable = ['naziv', 'tipovi_turnira_id'];

    /**
     * Vraća tip turnira kojem pripada definicija ovog dodatnog polja.
     */
    public function polje(): BelongsTo
    {
        return $this->belongsTo(TipoviTurnira::class, 'tipovi_turnira_id');
    }

    /**
     * Vraća sve unesene vrijednosti ovog polja kroz rezultate turnira.
     */
    /** @noinspection PhpUnused */
    public function rezultatiPoTipu(): HasMany
    {
        return $this->hasMany(RezultatiPoTipuTurnira::class, 'polje_za_tipove_turnira_id', 'id');
    }

}
