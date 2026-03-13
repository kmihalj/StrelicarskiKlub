<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property mixed $naziv
 */
class Stilovi extends Model
{
    use HasFactory;
    protected $fillable = ['naziv'];

    public function rezultatiOpci(): HasMany
    {
        return $this->hasMany(RezultatiOpci::class, 'stil_id', 'id');
    }

    public function rezultatiPoTipuTurnira(): HasMany
    {
        return $this->hasMany(RezultatiPoTipuTurnira::class, 'stil_id', 'id');
    }
}
