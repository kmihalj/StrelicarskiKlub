<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TreninziVanjski extends Model
{
    use HasFactory;

    protected $table = 'treninzi_vanjski';

    protected $fillable = [
        'user_id',
        'clan_id',
        'datum',
        'runda1',
        'runda2',
    ];

    protected $casts = [
        'datum' => 'date',
        'runda1' => 'array',
        'runda2' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function clan(): BelongsTo
    {
        return $this->belongsTo(Clanovi::class, 'clan_id');
    }
}
