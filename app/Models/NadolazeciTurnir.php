<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Model NadolazeciTurnir predstavlja zapis baze podataka i definira relacije te pomoćne metode za rad s podacima.
 */
class NadolazeciTurnir extends Model
{
    protected $table = 'nadolazeci_turniri';

    protected $fillable = [
        'naziv',
        'organizator',
        'mjesto',
        'datum',
        'tipovi_turnira_id',
        'boduje_za_kup',
        'ima_smjene',
        'smjene_opis',
        'prijave_otvorene_do',
        'is_zakljucan',
        'poziv_pdf_path',
        'kotizacija_nacin',
        'kotizacija_iznos',
        'kotizacija_rok_uplate',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'datum' => 'date',
        'boduje_za_kup' => 'boolean',
        'ima_smjene' => 'boolean',
        'prijave_otvorene_do' => 'date',
        'is_zakljucan' => 'boolean',
        'kotizacija_iznos' => 'decimal:2',
        'kotizacija_rok_uplate' => 'date',
        'created_by' => 'integer',
        'updated_by' => 'integer',
    ];

    /**
     * Nadolazeći turnir ima jednu disciplinu (tip turnira).
     */
    public function tipTurnira(): BelongsTo
    {
        return $this->belongsTo(TipoviTurnira::class, 'tipovi_turnira_id', 'id');
    }

    /**
     * Nadolazeći turnir može imati više prijava članova.
     */
    public function prijave(): HasMany
    {
        return $this->hasMany(PrijavaTurnira::class, 'nadolazeci_turnir_id', 'id');
    }

    /**
     * Vraća korisnički račun koji je kreirao turnir.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    /**
     * Vraća korisnički račun koji je zadnji ažurirao turnir.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by', 'id');
    }

    /**
     * Provjerava jesu li prijave zaključane ručno ili datumom zaključavanja.
     */
    public function prijaveZakljucane(?CarbonInterface $trenutak = null): bool
    {
        if ($this->is_zakljucan) {
            return true;
        }

        $now = $trenutak ?? now();

        if ($this->datum !== null && $this->datum->copy()->endOfDay()->lt($now)) {
            return true;
        }

        if ($this->prijave_otvorene_do === null) {
            return false;
        }

        return $this->prijave_otvorene_do->copy()->endOfDay()->lt($now);
    }

    /**
     * Provjerava treba li za ovaj turnir napraviti zaduženje kotizacije putem računa.
     */
    public function trebaKotizacijaNaRacun(): bool
    {
        return $this->kotizacija_nacin === 'bank'
            && $this->kotizacija_iznos !== null
            && (float) $this->kotizacija_iznos > 0;
    }
}
