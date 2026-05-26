<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model clanoviFunkcije predstavlja zapis baze podataka i definira relacije te pomoćne metode za rad s podacima.
 */
class clanoviFunkcije extends Model
{

    protected $fillable = [
        'klub_id',
        'clan_id',
        'funkcija',
        'redniBroj',
        'kotizacija_primatelj',
        'kotizacija_iban',
    ];

    /**
     * Funkcija člana u klubu je povezan s jednim zapisom: klub.
     */
    public function klub(): BelongsTo
    {
        return $this->belongsTo(Klub::class, 'klub_id');
    }

    /**
     * Funkcija člana u klubu je povezan s jednim zapisom: člana kluba.
     */
    public function clan(): BelongsTo
    {
        return $this->belongsTo(Clanovi::class, 'clan_id');
    }

    public function kotizacijaPrimateljLabel(): string
    {
        $primatelj = trim((string) $this->kotizacija_primatelj);
        if ($primatelj !== '') {
            return $primatelj;
        }

        $clan = $this->clan;
        if ($clan instanceof Clanovi) {
            return trim((string) $clan->Ime.' '.(string) $clan->Prezime);
        }

        return '';
    }

    public function imaPodatkeZaKotizacije(): bool
    {
        return trim((string) $this->kotizacija_iban) !== ''
            && $this->kotizacijaPrimateljLabel() !== '';
    }
}
