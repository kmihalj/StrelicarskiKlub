<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model TreninziVanjski predstavlja zapis baze podataka i definira relacije te pomoćne metode za rad s podacima.
 */
class TreninziVanjski extends Model
{

    protected $table = 'treninzi_vanjski';

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
     * Evidencija vanjskog treninga je povezan s jednim zapisom: korisnički račun.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Evidencija vanjskog treninga je povezan s jednim zapisom: člana kluba.
     */
    public function clan(): BelongsTo
    {
        return $this->belongsTo(Clanovi::class, 'clan_id');
    }
}
