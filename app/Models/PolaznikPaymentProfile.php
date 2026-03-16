<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Model PolaznikPaymentProfile predstavlja zapis baze podataka i definira relacije te pomoćne metode za rad s podacima.
 */
class PolaznikPaymentProfile extends Model
{
    protected $fillable = [
        'polaznik_skole_id',
        'payment_mode',
        'tuition_amount',
        'started_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'polaznik_skole_id' => 'integer',
        'tuition_amount' => 'decimal:2',
        'started_at' => 'date',
        'created_by' => 'integer',
        'updated_by' => 'integer',
    ];

    /**
     * Profil školarine polaznika je povezan s jednim zapisom: polaznika škole.
     */
    public function polaznik(): BelongsTo
    {
        return $this->belongsTo(PolaznikSkole::class, 'polaznik_skole_id', 'id');
    }

    /**
     * Profil školarine polaznika može imati više povezanih zapisa: stavke školarine polaznika.
     */
    public function charges(): HasMany
    {
        return $this->hasMany(PolaznikPaymentCharge::class, 'polaznik_payment_profile_id', 'id');
    }
}

