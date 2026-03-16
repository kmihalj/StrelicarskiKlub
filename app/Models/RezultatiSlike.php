<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property mixed $turnir_id
 * @property mixed|string $vrsta
 * @property mixed|string $link
 */
class RezultatiSlike extends Model
{
    use HasFactory;

    protected $fillable = ['vrsta', 'link', 'turnir_id'];

    /**
     * Medij rezultata turnira je povezan s jednim zapisom: turnir.
     */
    public function turnir(): BelongsTo
    {
        return $this->belongsTo(Turniri::class, 'turnir_id');
    }
}
