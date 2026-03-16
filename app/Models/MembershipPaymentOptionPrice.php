<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model MembershipPaymentOptionPrice predstavlja zapis baze podataka i definira relacije te pomoćne metode za rad s podacima.
 */
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

    /**
     * Cjenik modela članarine je povezan s jednim zapisom: model članarine.
     */
    public function option(): BelongsTo
    {
        return $this->belongsTo(MembershipPaymentOption::class, 'membership_payment_option_id', 'id');
    }

    /**
     * Cjenik modela članarine je povezan s jednim zapisom: korisnički račun.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }
}
