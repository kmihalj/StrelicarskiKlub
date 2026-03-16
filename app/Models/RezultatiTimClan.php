<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $rezultati_tim_id
 * @property int $rezultat_opci_id
 * @property int|null $redni_broj
 */
class RezultatiTimClan extends Model
{
    use HasFactory;

    protected $table = 'rezultati_tim_clanovi';

    protected $fillable = [
        'rezultati_tim_id',
        'rezultat_opci_id',
        'redni_broj',
    ];

    public function tim(): BelongsTo
    {
        return $this->belongsTo(RezultatiTim::class, 'rezultati_tim_id');
    }

    public function rezultatOpci(): BelongsTo
    {
        return $this->belongsTo(RezultatiOpci::class, 'rezultat_opci_id');
    }
}

