<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClanPaymentProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'clan_id',
        'membership_payment_option_id',
        'start_date',
        'membership_amount_override',
        'opening_debt_amount',
        'opening_debt_note',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'clan_id' => 'integer',
        'membership_payment_option_id' => 'integer',
        'start_date' => 'date',
        'membership_amount_override' => 'decimal:2',
        'opening_debt_amount' => 'decimal:2',
        'created_by' => 'integer',
        'updated_by' => 'integer',
    ];

    public function clan(): BelongsTo
    {
        return $this->belongsTo(Clanovi::class, 'clan_id', 'id');
    }

    public function paymentOption(): BelongsTo
    {
        return $this->belongsTo(MembershipPaymentOption::class, 'membership_payment_option_id', 'id');
    }

    public function charges(): HasMany
    {
        return $this->hasMany(ClanPaymentCharge::class, 'clan_payment_profile_id', 'id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by', 'id');
    }
}
