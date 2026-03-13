<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClanDokument extends Model
{
    use HasFactory;

    protected $table = 'clan_dokumenti';

    protected $fillable = [
        'clan_id',
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

    public function clan(): BelongsTo
    {
        return $this->belongsTo(Clanovi::class, 'clan_id');
    }

    public function autor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
