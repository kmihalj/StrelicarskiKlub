<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Model za poruke klupskog zida na naslovnici.
 */
class ClubWallMessage extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'author_clan_id',
        'author_name',
        'message',
        'is_highlighted',
        'highlighted_by_user_id',
        'deleted_by_user_id',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'author_clan_id' => 'integer',
        'is_highlighted' => 'boolean',
        'highlighted_by_user_id' => 'integer',
        'deleted_by_user_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Korisnik koji je autor poruke.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Član čiji je profil povezan s autorom poruke (ako postoji).
     */
    public function authorClan(): BelongsTo
    {
        return $this->belongsTo(Clanovi::class, 'author_clan_id');
    }

    /**
     * Administrator koji je istaknuo poruku.
     */
    public function highlightedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'highlighted_by_user_id');
    }

    /**
     * Administrator koji je obrisao poruku (soft-delete).
     */
    public function deletedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by_user_id');
    }
}

