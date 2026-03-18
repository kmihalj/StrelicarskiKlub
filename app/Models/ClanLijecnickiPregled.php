<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model ClanLijecnickiPregled predstavlja zapis baze podataka i definira relacije te pomoćne metode za rad s podacima.
 */
class ClanLijecnickiPregled extends Model
{

    protected $table = 'clan_lijecnicki_pregledi';

    protected $fillable = [
        'clan_id',
        'vrijedi_do',
        'putanja',
        'originalni_naziv',
        'legacy_import',
        'created_by',
    ];

    protected $casts = [
        'vrijedi_do' => 'date',
        'legacy_import' => 'boolean',
    ];

    /**
     * Liječnički pregled člana je povezan s jednim zapisom: člana kluba.
     */
    public function clan(): BelongsTo
    {
        return $this->belongsTo(Clanovi::class, 'clan_id');
    }

    /**
     * Liječnički pregled člana je povezan s jednim zapisom: korisnički račun.
     */
    public function autor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
