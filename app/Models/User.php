<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Notifications\ResetLozinkeNotification;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'oib',
        'br_telefona',
        'password',
        'rola',
        'clan_id',
        'polaznik_id',
        'je_roditelj',
        'is_bootstrap_admin',
        'theme_mode_preference',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'rola' => 'integer',
        'clan_id' => 'integer',
        'polaznik_id' => 'integer',
        'je_roditelj' => 'boolean',
        'is_bootstrap_admin' => 'boolean',
        'theme_mode_preference' => 'string',
    ];

    public function clan(): BelongsTo
    {
        return $this->belongsTo(Clanovi::class, 'clan_id');
    }

    public function polaznik(): BelongsTo
    {
        return $this->belongsTo(PolaznikSkole::class, 'polaznik_id');
    }

    public function djecaClanovi(): BelongsToMany
    {
        return $this->belongsToMany(Clanovi::class, 'roditelj_clan', 'roditelj_user_id', 'clan_id');
    }

    public function djecaPolaznici(): BelongsToMany
    {
        return $this->belongsToMany(PolaznikSkole::class, 'roditelj_polaznik', 'roditelj_user_id', 'polaznik_id');
    }

    public function treninziDvorana(): HasMany
    {
        return $this->hasMany(TreninziDvorana::class, 'user_id', 'id');
    }

    public function treninziVanjski(): HasMany
    {
        return $this->hasMany(TreninziVanjski::class, 'user_id', 'id');
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetLozinkeNotification($token));
    }

    public function jeRoditelj(): bool
    {
        return (bool)$this->je_roditelj;
    }

    public function imaRoditeljskogClana(): bool
    {
        if (!$this->jeRoditelj()) {
            return false;
        }

        if ($this->relationLoaded('djecaClanovi')) {
            return $this->djecaClanovi->isNotEmpty();
        }

        return $this->djecaClanovi()->exists();
    }

    public function imaRoditeljskogPolaznika(): bool
    {
        if (!$this->jeRoditelj()) {
            return false;
        }

        if ($this->relationLoaded('djecaPolaznici')) {
            return $this->djecaPolaznici->isNotEmpty();
        }

        return $this->djecaPolaznici()->exists();
    }

    public function imaPravoAdminOrMember(): bool
    {
        if ((int)$this->rola <= 2) {
            return true;
        }

        return $this->imaRoditeljskogClana();
    }

    public function imaPravoAdminMemberOrSchool(): bool
    {
        if (in_array((int)$this->rola, [1, 2, 4], true)) {
            return true;
        }

        return $this->imaRoditeljskogClana() || $this->imaRoditeljskogPolaznika();
    }

    public function mozePregledavatiClana(int $clanId): bool
    {
        if ((int)$this->rola === 1) {
            return true;
        }

        if ((int)$this->rola <= 2 && (int)$this->clan_id === $clanId) {
            return true;
        }

        if (!$this->jeRoditelj()) {
            return false;
        }

        if ($this->relationLoaded('djecaClanovi')) {
            return $this->djecaClanovi->contains('id', $clanId);
        }

        return $this->djecaClanovi()->whereKey($clanId)->exists();
    }

    public function mozePregledavatiPolaznika(int $polaznikId): bool
    {
        if ((int)$this->rola <= 2) {
            return true;
        }

        if ((int)$this->rola === 4 && (int)$this->polaznik_id === $polaznikId) {
            return true;
        }

        if (!$this->jeRoditelj()) {
            return false;
        }

        if ($this->relationLoaded('djecaPolaznici')) {
            return $this->djecaPolaznici->contains('id', $polaznikId);
        }

        return $this->djecaPolaznici()->whereKey($polaznikId)->exists();
    }
}
