<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model DokumentiKluba predstavlja zapis baze podataka i definira relacije te pomoćne metode za rad s podacima.
 */
class DokumentiKluba extends Model
{

    protected $fillable = ['klub_id', 'opis', 'link_text', 'javno'];

    /**
     * Dokument kluba je povezan s jednim zapisom: klub.
     */
    public function klub(): BelongsTo
    {
        return $this->belongsTo(Klub::class, 'klub_id');
    }
}
