<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
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
    use HasFactory;

    protected $table = 'rezultati_timovi';

    protected $fillable = [
        'turnir_id',
        'stil_id',
        'kategorija_id',
        'plasman',
        'rezultat',
    ];

    public function turnir(): BelongsTo
    {
        return $this->belongsTo(Turniri::class, 'turnir_id');
    }

    public function stil(): BelongsTo
    {
        return $this->belongsTo(Stilovi::class, 'stil_id');
    }

    public function kategorija(): BelongsTo
    {
        return $this->belongsTo(Kategorije::class, 'kategorija_id');
    }

    public function clanoviStavke(): HasMany
    {
        return $this->hasMany(RezultatiTimClan::class, 'rezultati_tim_id');
    }
}
