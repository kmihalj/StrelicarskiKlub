<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model ClanDokument predstavlja zapis baze podataka i definira relacije te pomoćne metode za rad s podacima.
 */
class ClanDokument extends Model
{

    protected $table = 'clan_dokumenti';

    protected $fillable = [
        'clan_id',
        'vrsta',
        'naziv',
        'datum_dokumenta',
        'putanja',
        'originalni_naziv',
        'napomena',
        'created_by',
    ];

    protected $casts = [
        'datum_dokumenta' => 'date',
    ];

    /**
     * Dokument člana je povezan s jednim zapisom: člana kluba.
     */
    public function clan(): BelongsTo
    {
        return $this->belongsTo(Clanovi::class, 'clan_id');
    }

    /**
     * Dokument člana je povezan s jednim zapisom: korisnički račun.
     */
    public function autor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
