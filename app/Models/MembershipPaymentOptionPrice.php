<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MembershipPaymentOptionPrice extends Model
{
    use HasFactory;

    protected $fillable = [
        'membership_payment_option_id',
        'amount',
        'valid_from',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'valid_from' => 'date',
        'membership_payment_option_id' => 'integer',
        'created_by' => 'integer',
    ];

    public function option(): BelongsTo
    {
        return $this->belongsTo(MembershipPaymentOption::class, 'membership_payment_option_id', 'id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }
}
