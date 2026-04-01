<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model OglasSlika predstavlja jednu sliku u galeriji oglasa.
 */
class OglasSlika extends Model
{
    protected $table = 'oglas_slike';

    protected $fillable = [
        'oglas_id',
        'putanja',
        'redni_broj',
    ];

    protected $casts = [
        'oglas_id' => 'integer',
        'redni_broj' => 'integer',
    ];

    /**
     * Slika pripada jednom oglasu.
     */
    public function oglas(): BelongsTo
    {
        return $this->belongsTo(Oglas::class, 'oglas_id', 'id');
    }
}
