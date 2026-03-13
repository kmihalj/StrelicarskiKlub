<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PolaznikSkoleDolazak extends Model
{
    use HasFactory;

    protected $table = 'polaznici_skole_dolasci';

    protected $fillable = [
        'polaznik_skole_id',
        'redni_broj',
        'datum',
    ];

    protected $casts = [
        'redni_broj' => 'integer',
        'datum' => 'date',
    ];

    public function polaznik(): BelongsTo
    {
        return $this->belongsTo(PolaznikSkole::class, 'polaznik_skole_id', 'id');
    }
}

