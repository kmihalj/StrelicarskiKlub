<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model PolaznikSkoleDokument predstavlja zapis baze podataka i definira relacije te pomoćne metode za rad s podacima.
 */
class PolaznikSkoleDokument extends Model
{

    protected $table = 'polaznik_skole_dokumenti';

    protected $fillable = [
        'polaznik_skole_id',
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
     * Dokument polaznika škole je povezan s jednim zapisom: polaznika škole.
     */
    public function polaznik(): BelongsTo
    {
        return $this->belongsTo(PolaznikSkole::class, 'polaznik_skole_id');
    }

    /**
     * Dokument polaznika škole je povezan s jednim zapisom: korisnički račun.
     */
    public function autor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

