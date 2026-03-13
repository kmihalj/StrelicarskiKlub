<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
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
    use HasFactory;

    protected $fillable = ['naziv', 'tipovi_turnira_id'];

    public function polje(): BelongsTo
    {
        return $this->belongsTo(TipoviTurnira::class, 'tipovi_turnira_id');
    }

    public function rezultatiPoTipu(): HasMany
    {
        return $this->hasMany(RezultatiPoTipuTurnira::class, 'polje_za_tipove_turnira_id', 'id');
    }

}
