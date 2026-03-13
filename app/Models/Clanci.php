<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Clanci extends Model
{
    use HasFactory;

    protected $fillable = ['vrsta', 'naslov', 'datum', 'sadrzaj', 'menu', 'menu_naslov', 'galerija'];

    public function mediji(): HasMany
    {
        return $this->hasMany(MedijiClanaka::class, 'clanak_id', 'id');
    }
}
