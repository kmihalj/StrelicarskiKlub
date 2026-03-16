<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model MedijiClanaka predstavlja zapis baze podataka i definira relacije te pomoćne metode za rad s podacima.
 */
class MedijiClanaka extends Model
{
    use HasFactory;

    protected $fillable = ['vrsta', 'link', 'clanak_id'];

    /**
     * Medij članka je povezan s jednim zapisom: članak.
     */
    public function clanak(): BelongsTo
    {
        return $this->belongsTo(Clanci::class, 'clanak_id');
    }
}
