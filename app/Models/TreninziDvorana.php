<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model TreninziDvorana predstavlja zapis baze podataka i definira relacije te pomoćne metode za rad s podacima.
 */
class TreninziDvorana extends Model
{

    protected $table = 'treninzi_dvorana';

    protected $fillable = [
        'user_id',
        'clan_id',
        'datum',
        'runda1',
        'runda2',
    ];

    protected $casts = [
        'datum' => 'date',
        'runda1' => 'array',
        'runda2' => 'array',
    ];

    /**
     * Evidencija dvoranskog treninga je povezan s jednim zapisom: korisnički račun.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Evidencija dvoranskog treninga je povezan s jednim zapisom: člana kluba.
     */
    public function clan(): BelongsTo
    {
        return $this->belongsTo(Clanovi::class, 'clan_id');
    }
}
