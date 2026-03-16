<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model PolaznikSkoleDolazak predstavlja zapis baze podataka i definira relacije te pomoćne metode za rad s podacima.
 */
class PolaznikSkoleDolazak extends Model
{
    use HasFactory;

    protected $table = 'polaznici_skole_dolasci';

    protected $fillable = [
        'polaznik_skole_id',
        'redni_broj',
        'datum',
    ];

    protected $casts = [
        'redni_broj' => 'integer',
        'datum' => 'date',
    ];

    /**
     * Dolazak polaznika škole je povezan s jednim zapisom: polaznika škole.
     */
    public function polaznik(): BelongsTo
    {
        return $this->belongsTo(PolaznikSkole::class, 'polaznik_skole_id', 'id');
    }
}

