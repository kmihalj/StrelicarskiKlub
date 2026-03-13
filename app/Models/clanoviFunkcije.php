<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class clanoviFunkcije extends Model
{
    use HasFactory;

    protected $fillable = ['klub_id', 'clan_id', 'funkcija', 'redniBroj'];

    public function klub(): BelongsTo
    {
        return $this->belongsTo(Klub::class, 'klub_id');
    }

    public function clan(): BelongsTo
    {
        return $this->belongsTo(Clanovi::class, 'clan_id');
    }
}
