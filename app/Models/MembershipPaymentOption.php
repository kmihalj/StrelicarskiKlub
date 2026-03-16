<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Model MembershipPaymentOption predstavlja zapis baze podataka i definira relacije te pomoćne metode za rad s podacima.
 */
class MembershipPaymentOption extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'name',
        'description',
        'period_type',
        'period_anchor',
        'collection_method',
        'is_enabled',
        'is_archived',
        'sort_order',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'is_archived' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Vraća povijest cijena za ovaj model članarine.
     */
    public function prices(): HasMany
    {
        return $this->hasMany(MembershipPaymentOptionPrice::class, 'membership_payment_option_id', 'id');
    }

    /**
     * Vraća trenutno važeću (zadnju) cijenu ovog modela članarine.
     */
    public function latestPrice(): HasOne
    {
        return $this->hasOne(MembershipPaymentOptionPrice::class, 'membership_payment_option_id', 'id')
            ->ofMany('valid_from', 'max');
    }

    /**
     * Vraća profile članova koji koriste ovaj model članarine.
     */
    public function profiles(): HasMany
    {
        return $this->hasMany(ClanPaymentProfile::class, 'membership_payment_option_id', 'id');
    }

    /**
     * Vraća sve stavke članarine vezane uz ovaj model.
     */
    public function charges(): HasMany
    {
        return $this->hasMany(ClanPaymentCharge::class, 'membership_payment_option_id', 'id');
    }
}
