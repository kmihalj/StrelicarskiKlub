<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PolaznikSkoleDokument extends Model
{
    use HasFactory;

    protected $table = 'polaznik_skole_dokumenti';

    protected $fillable = [
        'polaznik_skole_id',
        'vrsta',
        'naziv',
        'datum_dokumenta',
        'putanja',
        'originalni_naziv',
        'napomena',
        'created_by',
    ];

    protected $casts = [
        'datum_dokumenta' => 'date',
    ];

    public function polaznik(): BelongsTo
    {
        return $this->belongsTo(PolaznikSkole::class, 'polaznik_skole_id');
    }

    public function autor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

