<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model ClanPaymentCharge predstavlja zapis baze podataka i definira relacije te pomoćne metode za rad s podacima.
 */
class ClanPaymentCharge extends Model
{
    use HasFactory;

    protected $fillable = [
        'clan_id',
        'clan_payment_profile_id',
        'membership_payment_option_id',
        'source',
        'period_key',
        'period_start',
        'period_end',
        'due_date',
        'title',
        'description',
        'amount',
        'currency',
        'status',
        'paid_at',
        'confirmed_by',
        'created_by',
        'updated_by',
        'metadata',
    ];

    protected $casts = [
        'clan_id' => 'integer',
        'clan_payment_profile_id' => 'integer',
        'membership_payment_option_id' => 'integer',
        'period_start' => 'date',
        'period_end' => 'date',
        'due_date' => 'date',
        'amount' => 'decimal:2',
        'paid_at' => 'date',
        'confirmed_by' => 'integer',
        'created_by' => 'integer',
        'updated_by' => 'integer',
        'metadata' => 'array',
    ];

    /**
     * Stavka plaćanja članarine je povezan s jednim zapisom: člana kluba.
     */
    public function clan(): BelongsTo
    {
        return $this->belongsTo(Clanovi::class, 'clan_id', 'id');
    }

    /**
     * Stavka plaćanja članarine je povezan s jednim zapisom: profil članarine člana.
     */
    public function profile(): BelongsTo
    {
        return $this->belongsTo(ClanPaymentProfile::class, 'clan_payment_profile_id', 'id');
    }

    /**
     * Stavka plaćanja članarine je povezan s jednim zapisom: model članarine.
     */
    public function paymentOption(): BelongsTo
    {
        return $this->belongsTo(MembershipPaymentOption::class, 'membership_payment_option_id', 'id');
    }

    /**
     * Stavka plaćanja članarine je povezan s jednim zapisom: korisnički račun.
     */
    public function confirmer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by', 'id');
    }

    /**
     * Stavka plaćanja članarine je povezan s jednim zapisom: korisnički račun.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    /**
     * Stavka plaćanja članarine je povezan s jednim zapisom: korisnički račun.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by', 'id');
    }
}
