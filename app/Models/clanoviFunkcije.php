<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model clanoviFunkcije predstavlja zapis baze podataka i definira relacije te pomoćne metode za rad s podacima.
 */
class clanoviFunkcije extends Model
{
    use HasFactory;

    protected $fillable = ['klub_id', 'clan_id', 'funkcija', 'redniBroj'];

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
}
