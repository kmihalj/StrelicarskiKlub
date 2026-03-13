<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClanLijecnickiPregled extends Model
{
    use HasFactory;

    protected $table = 'clan_lijecnicki_pregledi';

    protected $fillable = [
        'clan_id',
        'vrijedi_do',
        'putanja',
        'originalni_naziv',
        'legacy_import',
        'created_by',
    ];

    protected $casts = [
        'vrijedi_do' => 'date',
        'legacy_import' => 'boolean',
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
