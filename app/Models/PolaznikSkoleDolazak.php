<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

/**
 * Model PolaznikSkoleDolazak predstavlja zapis baze podataka i definira relacije te pomoćne metode za rad s podacima.
 */
class PolaznikSkoleDolazak extends Model
{

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

    public static function verzija(Collection $dolasci): string
    {
        $datumi = $dolasci->filter(fn (self $dolazak): bool => $dolazak->datum !== null)
            ->mapWithKeys(fn (self $dolazak): array => [$dolazak->redni_broj => $dolazak->datum->toDateString()])
            ->sortKeys()->all();

        return hash('sha256', json_encode($datumi, JSON_THROW_ON_ERROR));
    }

    /**
     * Dolazak polaznika škole je povezan s jednim zapisom: polaznika škole.
     */
    public function polaznik(): BelongsTo
    {
        return $this->belongsTo(PolaznikSkole::class, 'polaznik_skole_id', 'id');
    }
}

