<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    public function polaznik(): BelongsTo
    {
        return $this->belongsTo(PolaznikSkole::class, 'polaznik_skole_id', 'id');
    }

    public function charges(): HasMany
    {
        return $this->hasMany(PolaznikPaymentCharge::class, 'polaznik_payment_profile_id', 'id');
    }
}

