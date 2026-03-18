<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $turnir_id
 * @property int|null $stil_id
 * @property int|null $kategorija_id
 * @property int $plasman
 * @property int $rezultat
 */
class RezultatiTim extends Model
{

    protected $table = 'rezultati_timovi';

    protected $fillable = [
        'turnir_id',
        'stil_id',
        'kategorija_id',
        'plasman',
        'rezultat',
    ];

    /**
     * Timski rezultat je povezan s jednim zapisom: turnir.
     */
    public function turnir(): BelongsTo
    {
        return $this->belongsTo(Turniri::class, 'turnir_id');
    }

    /**
     * Timski rezultat je povezan s jednim zapisom: stil luka.
     */
    public function stil(): BelongsTo
    {
        return $this->belongsTo(Stilovi::class, 'stil_id');
    }

    /**
     * Timski rezultat je povezan s jednim zapisom: natjecateljsku kategoriju.
     */
    public function kategorija(): BelongsTo
    {
        return $this->belongsTo(Kategorije::class, 'kategorija_id');
    }

    /**
     * Vraća sve članove tima i njihove pojedinačne rezultate za ovaj timski rezultat.
     */
    public function clanoviStavke(): HasMany
    {
        return $this->hasMany(RezultatiTimClan::class, 'rezultati_tim_id');
    }
}
