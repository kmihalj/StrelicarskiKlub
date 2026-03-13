<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Klub extends Model
{
    use HasFactory;

    protected $fillable = ['naziv', 'adresa', 'telefon', 'email', 'racun'];

    public function funkcije(): HasMany
    {
        return $this->hasMany(clanoviFunkcije::class, 'klub_id', 'id');
    }

    public function dokumenti(): HasMany
    {
        return $this->hasMany(DokumentiKluba::class, 'klub_id', 'id');
    }
}
