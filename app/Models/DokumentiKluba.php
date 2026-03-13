<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DokumentiKluba extends Model
{
    use HasFactory;

    protected $fillable = ['klub_id', 'opis', 'link_text', 'javno'];

    public function klub(): BelongsTo
    {
        return $this->belongsTo(Klub::class, 'klub_id');
    }
}
