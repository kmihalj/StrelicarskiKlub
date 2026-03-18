<?php

namespace App\Models;

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

    protected $fillable = ['naziv', 'datum', 'lokacija', 'opis', 'opis2', 'tipovi_turnira_id', 'ima_timove'];

    protected $casts = [
        'ima_timove' => 'bool',
    ];

    /**
     * Svaki turnir ima jedan tip turnira (npr. WA 2x18, WA 720), a isti tip može koristiti više turnira.
     */
    public function tipTurnira(): BelongsTo
    {
        return $this->belongsTo(TipoviTurnira::class, 'tipovi_turnira_id');
    }

    /**
     * Vraća sve pojedinačne rezultate članova unesene za ovaj turnir.
     */
    public function rezultatiOpci(): HasMany
    {
        return $this->hasMany(RezultatiOpci::class, 'turnir_id', 'id');
    }

    /**
     * Vraća detaljna polja rezultata (po tipu turnira) za ovaj turnir.
     */
    public function rezultatiPoTipuTurnira(): HasMany
    {
        return $this->hasMany(RezultatiPoTipuTurnira::class, 'turnir_id', 'id');
    }

    /**
     * Vraća slike i video zapise povezane s ovim turnirom.
     */
    /** @noinspection PhpUnused */
    public function mediji(): HasMany
    {
        return $this->hasMany(RezultatiSlike::class, 'turnir_id', 'id');
    }

    /**
     * Vraća sve timske rezultate povezane s ovim turnirom.
     */
    public function rezultatiTimovi(): HasMany
    {
        return $this->hasMany(RezultatiTim::class, 'turnir_id', 'id');
    }
}
