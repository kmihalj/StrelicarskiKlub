<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model PrijavaTurnira predstavlja zapis baze podataka i definira relacije te pomoćne metode za rad s podacima.
 */
class PrijavaTurnira extends Model
{
    public const STATUS_ACTIVE = 'active';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_REMOVED = 'removed';

    protected $table = 'prijave_turnira';

    protected $fillable = [
        'nadolazeci_turnir_id',
        'clan_id',
        'prijavio_user_id',
        'kategorija_id',
        'stil_id',
        'sudjelujem_u_kupu',
        'smjena',
        'status',
        'napomena_admin',
        'removed_by',
        'removed_at',
        'cancelled_at',
        'clan_payment_charge_id',
    ];

    protected $casts = [
        'nadolazeci_turnir_id' => 'integer',
        'clan_id' => 'integer',
        'prijavio_user_id' => 'integer',
        'kategorija_id' => 'integer',
        'stil_id' => 'integer',
        'sudjelujem_u_kupu' => 'boolean',
        'removed_by' => 'integer',
        'removed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'clan_payment_charge_id' => 'integer',
    ];

    /**
     * Prijava pripada jednom nadolazećem turniru.
     */
    public function turnir(): BelongsTo
    {
        return $this->belongsTo(NadolazeciTurnir::class, 'nadolazeci_turnir_id', 'id');
    }

    /**
     * Prijava pripada jednom članu.
     */
    public function clan(): BelongsTo
    {
        return $this->belongsTo(Clanovi::class, 'clan_id', 'id');
    }

    /**
     * Prijava ima odabranu kategoriju natjecanja.
     */
    public function kategorija(): BelongsTo
    {
        return $this->belongsTo(Kategorije::class, 'kategorija_id', 'id');
    }

    /**
     * Prijava ima odabrani stil luka.
     */
    public function stil(): BelongsTo
    {
        return $this->belongsTo(Stilovi::class, 'stil_id', 'id');
    }

    /**
     * Vraća korisnički račun koji je napravio prijavu.
     */
    public function prijavioUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'prijavio_user_id', 'id');
    }

    /**
     * Vraća korisnički račun koji je uklonio prijavu.
     */
    public function removedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'removed_by', 'id');
    }

    /**
     * Prijava može imati povezano zaduženje kotizacije.
     */
    public function paymentCharge(): BelongsTo
    {
        return $this->belongsTo(ClanPaymentCharge::class, 'clan_payment_charge_id', 'id');
    }

    /**
     * Provjerava je li prijava aktivna.
     */
    public function aktivna(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }
}
