<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PolaznikPaymentCharge extends Model
{
    protected $fillable = [
        'polaznik_skole_id',
        'polaznik_payment_profile_id',
        'source',
        'title',
        'description',
        'amount',
        'due_training_count',
        'status',
        'paid_at',
        'metadata',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'polaznik_skole_id' => 'integer',
        'polaznik_payment_profile_id' => 'integer',
        'amount' => 'decimal:2',
        'due_training_count' => 'integer',
        'paid_at' => 'date',
        'metadata' => 'array',
        'created_by' => 'integer',
        'updated_by' => 'integer',
    ];

    public function polaznik(): BelongsTo
    {
        return $this->belongsTo(PolaznikSkole::class, 'polaznik_skole_id', 'id');
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(PolaznikPaymentProfile::class, 'polaznik_payment_profile_id', 'id');
    }
}

