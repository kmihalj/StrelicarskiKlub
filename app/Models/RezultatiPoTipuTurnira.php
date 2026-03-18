<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property mixed $clan_id
 * @property mixed $turnir_id
 * @property mixed $kategorija_id
 * @property mixed $polje_za_tipove_turnira_id
 * @property mixed $stil_id
 * @property mixed $rezultat
 */
class RezultatiPoTipuTurnira extends Model
{

    protected $fillable = ['turnir_id', 'clan_id', 'kategorija_id', 'stil_id', 'polje_za_tipove_turnira_id', 'rezultat' ];

    /**
     * Detalj rezultata prema tipu turnira je povezan s jednim zapisom: turnir.
     */
    public function turnir(): BelongsTo
    {
        return $this->belongsTo(Turniri::class, 'turnir_id');
    }

    /**
     * Detalj rezultata prema tipu turnira je povezan s jednim zapisom: člana kluba.
     */
    public function clan(): BelongsTo
    {
        return $this->belongsTo(Clanovi::class, 'clan_id');
    }

    /**
     * Detalj rezultata prema tipu turnira je povezan s jednim zapisom: natjecateljsku kategoriju.
     */
    public function kategorija(): BelongsTo
    {
        return $this->belongsTo(Kategorije::class, 'kategorija_id');
    }

    /**
     * Detalj rezultata prema tipu turnira je povezan s jednim zapisom: stil luka.
     */
    public function stil(): BelongsTo
    {
        return $this->belongsTo(Stilovi::class, 'stil_id');
    }
    /**
     * Detalj rezultata prema tipu turnira je povezan s jednim zapisom: definiciju dodatnog polja tipa turnira.
     */
    /** @noinspection PhpUnused */
    public function poljeZaTipTurnira(): BelongsTo
    {
        return $this->belongsTo(PoljaZaTipoveTurnira::class, 'polje_za_tipove_turnira_id');
    }
}
