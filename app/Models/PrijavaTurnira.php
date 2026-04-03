<?php

namespace App\Models;

use Carbon\Carbon;
use Carbon\CarbonInterface;
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

    public const OBROK_DA = 'da';

    public const OBROK_DA_VEGETARIJANSKI = 'da_vegetarijanski';

    public const OBROK_NE = 'ne';

    protected $table = 'prijave_turnira';

    protected $fillable = [
        'nadolazeci_turnir_id',
        'clan_id',
        'prijavio_user_id',
        'kategorija_id',
        'stil_id',
        'sudjelujem_u_kupu',
        'smjena',
        'odabrani_dan',
        'obrok',
        'napomena_clana',
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
        'odabrani_dan' => 'date',
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

    /**
     * Vraća oznaku termina prijave (smjena ili odabrani dan kod višednevnog turnira).
     */
    public function terminPrijaveLabel(): string
    {
        if ($this->jeVisednevniTurnir()) {
            return $this->odabraniDanLabel() ?? 'nebitno';
        }

        $smjena = trim((string) $this->smjena);

        return $smjena !== '' ? $smjena : 'nebitno';
    }

    /**
     * Vraća datum turnira za prikaz u korisničkim tablicama.
     */
    public function datumTurniraZaPrikazLabel(): string
    {
        if ($this->jeVisednevniTurnir()) {
            $odabraniDan = $this->odabrani_dan;
            if ($odabraniDan instanceof CarbonInterface) {
                return $odabraniDan->format('d.m.Y.');
            }
        }

        $turnir = $this->turnir;

        return $turnir instanceof NadolazeciTurnir
            ? $turnir->datumRasponLabel()
            : '-';
    }

    /**
     * Vraća formatirani odabrani dan ako postoji.
     */
    public function odabraniDanLabel(): ?string
    {
        $odabraniDan = $this->odabrani_dan;
        if ($odabraniDan instanceof CarbonInterface) {
            return $odabraniDan->format('d.m.Y.');
        }

        $tekst = trim((string) $odabraniDan);
        if ($tekst === '') {
            return null;
        }

        try {
            return Carbon::parse($tekst)->format('d.m.Y.');
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Provjerava je li prijava vezana na višednevni turnir.
     */
    public function jeVisednevniTurnir(): bool
    {
        $turnir = $this->turnir;
        if (! $turnir instanceof NadolazeciTurnir) {
            return false;
        }

        $start = $turnir->datum;
        $end = $turnir->datum_do;

        return $start instanceof CarbonInterface
            && $end instanceof CarbonInterface
            && $end->gt($start);
    }

    /**
     * Vraća dozvoljene opcije obroka za prijavu.
     *
     * @return array<string, string>
     */
    public static function obrokOpcije(): array
    {
        return [
            self::OBROK_DA => 'DA',
            self::OBROK_DA_VEGETARIJANSKI => 'DA - vegetarijanski',
            self::OBROK_NE => 'NE',
        ];
    }

    /**
     * Vraća korisničku oznaku odabranog obroka.
     */
    public function obrokLabel(): string
    {
        $opcije = self::obrokOpcije();
        $vrijednost = trim((string) $this->obrok);

        return $opcije[$vrijednost] ?? $opcije[self::OBROK_NE];
    }
}
