<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MedijiClanaka extends Model
{
    use HasFactory;

    protected $fillable = ['vrsta', 'link', 'clanak_id'];

    public function clanak(): BelongsTo
    {
        return $this->belongsTo(Clanci::class, 'clanak_id');
    }
}
