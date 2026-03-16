<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property mixed $turnir_id
 * @property mixed $clan_id
 * @property mixed $kategorija_id
 * @property mixed $stil_id
 * @property mixed $plasman
 * @property mixed|null $plasman_nakon_eliminacija
 */
class RezultatiOpci extends Model
{
    use HasFactory;

    protected $fillable = ['turnir_id', 'clan_id', 'kategorija_id', 'stil_id', 'plasman', 'plasman_nakon_eliminacija' ];

    public function turnir(): BelongsTo
    {
        return $this->belongsTo(Turniri::class, 'turnir_id');
    }

    public function clan(): BelongsTo
    {
        return $this->belongsTo(Clanovi::class, 'clan_id');
    }

    public function kategorija(): BelongsTo
    {
        return $this->belongsTo(Kategorije::class, 'kategorija_id');
    }

    public function stil(): BelongsTo
    {
        return $this->belongsTo(Stilovi::class, 'stil_id');
    }

    public function timStavke(): HasMany
    {
        return $this->hasMany(RezultatiTimClan::class, 'rezultat_opci_id');
    }
}
