<?php

namespace App\Services;

use App\Models\PolaznikPaymentCharge;
use App\Models\PolaznikPaymentProfile;
use App\Models\PolaznikSkole;
use App\Models\SiteSetting;
use Carbon\Carbon;
use InvalidArgumentException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Servis za školarinu polaznika škole streličarstva.
 *
 * Pokriva:
 * - automatsko otvaranje stavki školarine,
 * - model plaćanja u cijelosti ili u dvije rate,
 * - logiku aktivacije druge rate nakon određenog broja treninga,
 * - pripremu statusa i poruka za admin i korisničke prikaze.
 */
class SchoolPaymentService
{
    public const STATUS_OPEN = 'open';
    public const STATUS_PAID = 'paid';
    public const STATUS_DELETED = 'deleted';

    public const MODE_FULL = 'full';
    public const MODE_INSTALLMENTS = 'installments';
    public const MODE_EXEMPT = 'exempt';

    public const SETTLEMENT_FULL = 'full';
    public const SETTLEMENT_HALF = 'half';

    public const SOURCE_TUITION = 'school_tuition';
    public const SOURCE_TUITION_SECOND = 'school_tuition_second_installment';

    public const SECOND_INSTALLMENT_AFTER_TRAININGS = 8;

    private const DEFAULT_ADULT_AMOUNT = 100.00;
    private const DEFAULT_MINOR_AMOUNT = 70.00;

    /**
     * Provjerava je li praćenje školarine uključeno u postavkama sitea.
     */
    public function isEnabled(): bool
    {
        if (!$this->supportsSchoolPayments()) {
            return false;
        }

        return (bool)SiteSetting::query()->value('payment_tracking_enabled');
    }

    /**
     * Vraća trenutačno važeći iznos školarine za punoljetne polaznike.
     */
    public function adultAmount(): float
    {
        if (!$this->supportsSchoolPayments()) {
            return self::DEFAULT_ADULT_AMOUNT;
        }

        $raw = SiteSetting::query()->value('school_tuition_adult_amount');
        if (!is_numeric($raw)) {
            return self::DEFAULT_ADULT_AMOUNT;
        }

        $value = (float)$raw;
        return $value > 0 ? round($value, 2) : self::DEFAULT_ADULT_AMOUNT;
    }

    /**
     * Vraća trenutačno važeći iznos školarine za maloljetne polaznike.
     */
    public function minorAmount(): float
    {
        if (!$this->supportsSchoolPayments()) {
            return self::DEFAULT_MINOR_AMOUNT;
        }

        $raw = SiteSetting::query()->value('school_tuition_minor_amount');
        if (!is_numeric($raw)) {
            return self::DEFAULT_MINOR_AMOUNT;
        }

        $value = (float)$raw;
        return $value > 0 ? round($value, 2) : self::DEFAULT_MINOR_AMOUNT;
    }

    /**
     * Sprema globalne postavke školarine (uključeno/isključeno i iznosi za punoljetne/maloljetne).
     */
    /** @noinspection PhpUnused */
    public function updateSetup(array $data): void
    {
        if (!$this->supportsSchoolPayments()) {
            return;
        }

        $siteSettings = SiteSetting::query()->first();
        if ($siteSettings === null) {
            return;
        }

        if (array_key_exists('school_tuition_adult_amount', $data)) {
            $adult = $this->normalizeAmount($data['school_tuition_adult_amount'] ?? null);
            if ($adult !== null && $adult > 0) {
                $siteSettings->school_tuition_adult_amount = $adult;
            }
        }

        if (array_key_exists('school_tuition_minor_amount', $data)) {
            $minor = $this->normalizeAmount($data['school_tuition_minor_amount'] ?? null);
            if ($minor !== null && $minor > 0) {
                $siteSettings->school_tuition_minor_amount = $minor;
            }
        }

        if ($siteSettings->isDirty()) {
            $siteSettings->save();
        }
    }

    /**
     * Osigurava da polaznik ima payment profil i inicijalni iznos školarine.
     *
     * Poziva se prije svih operacija nad stavkama kako bi stanje bilo konzistentno.
     */
    public function ensureProfile(PolaznikSkole $polaznik, ?int $adminUserId = null): ?PolaznikPaymentProfile
    {
        if (!$this->isEnabled()) {
            return null;
        }

        $profile = PolaznikPaymentProfile::query()->firstOrNew([
            'polaznik_skole_id' => (int)$polaznik->id,
        ]);

        if (!$profile->exists) {
            $referenceDate = $polaznik->datum_upisa?->copy()->startOfDay() ?? now()->startOfDay();
            $profile->payment_mode = self::MODE_FULL;
            $profile->tuition_amount = $this->baseTuitionAmountForPolaznik($polaznik, $referenceDate);
            $profile->started_at = $referenceDate->toDateString();
            $profile->created_by = $adminUserId;
            $profile->updated_by = $adminUserId;
            $profile->save();
        } elseif ((float)$profile->tuition_amount <= 0) {
            $referenceDate = $profile->started_at?->copy()->startOfDay()
                ?? $polaznik->datum_upisa?->copy()->startOfDay()
                ?? now()->startOfDay();
            $profile->tuition_amount = $this->baseTuitionAmountForPolaznik($polaznik, $referenceDate);
            $profile->updated_by = $adminUserId ?? $profile->updated_by;
            $profile->save();
        }

        return $profile->fresh();
    }

    /**
     * Dodjeljuje model plaćanja (cijelost/rate/oslobođen) i sinkronizira stavke.
     */
    public function assignMode(PolaznikSkole $polaznik, string $mode, int $adminUserId): ?PolaznikPaymentProfile
    {
        $normalizedMode = $this->normalizeMode($mode) ?? self::MODE_FULL;
        $profile = $this->ensureProfile($polaznik, $adminUserId);
        if ($profile === null) {
            return null;
        }

        if ($profile->payment_mode !== $normalizedMode) {
            $profile->payment_mode = $normalizedMode;
            $profile->updated_by = $adminUserId;
            $profile->save();
        }

        $this->syncCharges($profile, $adminUserId);

        return $profile->fresh();
    }

    /**
     * Potvrđuje ili vraća status stavke školarine.
     *
     * Kod prve stavke podržava odabir "pola" kako bi se automatski otvorila druga rata.
     */
    public function updateChargeStatus(
        PolaznikSkole $polaznik,
        PolaznikPaymentCharge $charge,
        bool $isPaid,
        ?string $paidAt,
        ?string $settlementType,
        int $adminUserId
    ): void {
        if ((int)$charge->polaznik_skole_id !== (int)$polaznik->id) {
            throw new InvalidArgumentException('Stavka plaćanja ne pripada odabranom polazniku.');
        }

        $profile = $this->ensureProfile($polaznik, $adminUserId);
        if ($profile === null) {
            return;
        }

        $this->syncCharges($profile, $adminUserId);

        $charge->refresh();
        if ($charge->status === self::STATUS_DELETED) {
            throw new InvalidArgumentException('Stavka plaćanja je arhivirana i ne može se mijenjati.');
        }

        if (!$isPaid) {
            $charge->status = self::STATUS_OPEN;
            $charge->paid_at = null;
            $charge->updated_by = $adminUserId;
            $charge->save();

            $this->syncCharges($profile, $adminUserId);
            return;
        }

        $paidDate = $this->normalizeDate($paidAt) ?? now()->toDateString();
        $settlement = $settlementType;
        if (!in_array($settlement, [self::SETTLEMENT_FULL, self::SETTLEMENT_HALF], true)) {
            $settlement = self::SETTLEMENT_FULL;
        }

        if ($charge->source === self::SOURCE_TUITION) {
            // Prva uplata određuje režim:
            // - full => smatra se da je cijela školarina podmirena,
            // - half => otvara se logika druge rate nakon praga treninga.
            if ($settlement === self::SETTLEMENT_HALF) {
                $profile->payment_mode = self::MODE_INSTALLMENTS;
                $profile->updated_by = $adminUserId;
                $profile->save();

                [$firstHalf, ] = $this->splitAmount((float)$profile->tuition_amount);
                $charge->amount = $firstHalf;
            } else {
                $profile->payment_mode = self::MODE_FULL;
                $profile->updated_by = $adminUserId;
                $profile->save();
                $charge->amount = (float)$profile->tuition_amount;
            }
        }

        $charge->status = self::STATUS_PAID;
        $charge->paid_at = $paidDate;
        $metadata = $this->normalizeMetadata($charge->metadata);
        if ($charge->source === self::SOURCE_TUITION) {
            $metadata['settlement_type'] = $settlement;
        }
        $charge->metadata = $metadata;
        $charge->updated_by = $adminUserId;
        $charge->save();

        $freshProfile = $profile->fresh();
        if ($freshProfile instanceof PolaznikPaymentProfile) {
            $this->syncCharges($freshProfile, $adminUserId);
        }
    }

    /**
     * Slaže sažetak školarine polaznika: profil, otvorene/plaćene stavke, broj dolazaka i iduću obvezu.
     */
    public function summary(PolaznikSkole $polaznik): array
    {
        if (!$this->isEnabled()) {
            return [
                'enabled' => false,
                'profile' => null,
                'charges' => collect(),
                'openCharges' => collect(),
                'paidCharges' => collect(),
                'attendanceCount' => 0,
                'nextOpenCharge' => null,
            ];
        }

        $profile = $this->ensureProfile($polaznik);
        if ($profile !== null) {
            $this->syncCharges($profile, null);
        }

        $charges = PolaznikPaymentCharge::query()
            ->where('polaznik_skole_id', (int)$polaznik->id)
            ->where('status', '!=', self::STATUS_DELETED)
            ->orderBy('id')
            ->get();

        $openCharges = $charges
            ->filter(fn (PolaznikPaymentCharge $charge): bool => $charge->status === self::STATUS_OPEN)
            ->values();
        $paidCharges = $charges
            ->filter(fn (PolaznikPaymentCharge $charge): bool => $charge->status === self::STATUS_PAID)
            ->values();

        $attendanceCount = $this->attendanceCount($polaznik);
        $nextOpenCharge = $openCharges
            ->sortBy(function (PolaznikPaymentCharge $charge): array {
                $priority = $charge->due_training_count === null ? 0 : 1;
                $dueTrainings = (int)($charge->due_training_count ?? 999);
                return [$priority, $dueTrainings, (int)$charge->id];
            })
            ->first();

        return [
            'enabled' => true,
            'profile' => $profile?->fresh(),
            'charges' => $charges,
            'openCharges' => $openCharges,
            'paidCharges' => $paidCharges,
            'attendanceCount' => $attendanceCount,
            'nextOpenCharge' => $nextOpenCharge,
        ];
    }

    /**
     * Generira korisničku obavijest o stanju školarine (plaćeno, djelomično, dug).
     */
    public function noticeForPolaznik(PolaznikSkole $polaznik): ?array
    {
        $summary = $this->summary($polaznik);
        if (!($summary['enabled'] ?? false)) {
            return null;
        }

        /** @var PolaznikPaymentProfile|null $profile */
        $profile = $summary['profile'] ?? null;
        /** @var Collection<int,PolaznikPaymentCharge> $openCharges */
        $openCharges = $summary['openCharges'] ?? collect();
        $attendanceCount = (int)($summary['attendanceCount'] ?? 0);

        if ($profile === null) {
            return [
                'variant' => 'secondary',
                'title' => 'Školarina nije postavljena',
                'message' => 'Administrator još nije postavio školarinu za ovog polaznika.',
            ];
        }

        if ($profile->payment_mode === self::MODE_EXEMPT) {
            return [
                'variant' => 'success',
                'title' => 'Oslobođen školarine',
                'message' => 'Za ovog polaznika nije predviđeno plaćanje školarine.',
            ];
        }

        if ($openCharges->isEmpty()) {
            return [
                'variant' => 'success',
                'title' => 'Školarina podmirena',
                'message' => 'Sve obveze školarine su podmirene.',
            ];
        }

        $nextOpen = $summary['nextOpenCharge'] ?? null;
        if (!$nextOpen instanceof PolaznikPaymentCharge) {
            return [
                'variant' => 'warning',
                'title' => 'Postoji otvorena školarina',
                'message' => 'Potrebno je podmiriti otvorenu stavku školarine.',
            ];
        }

        $openAmount = (float)$nextOpen->amount;
        if ($nextOpen->source === self::SOURCE_TUITION_SECOND && $nextOpen->due_training_count !== null) {
            $limit = (int)$nextOpen->due_training_count;
            if ($attendanceCount < $limit) {
                return [
                    'variant' => 'success',
                    'title' => 'Školarina plaćena djelomično',
                    'message' => 'Plaćeno je ' . number_format((float)$profile->tuition_amount - $openAmount, 2, ',', '.')
                        . ' EUR. Preostaje ' . number_format($openAmount, 2, ',', '.')
                        . ' EUR nakon ' . $limit . ' treninga (' . $attendanceCount . '/' . $limit . ').',
                ];
            }

            return [
                'variant' => 'danger',
                'title' => 'Nepodmirena druga rata školarine',
                'message' => 'Preostaje ' . number_format($openAmount, 2, ',', '.')
                    . ' EUR. Evidentirano je ' . $attendanceCount . ' treninga (limit ' . $limit . ').',
            ];
        }

        return [
            'variant' => 'danger',
            'title' => 'Potrebno je podmiriti školarinu',
            'message' => 'Otvorena stavka: ' . $nextOpen->title
                . ' (' . number_format($openAmount, 2, ',', '.') . ' EUR).',
        ];
    }

    /**
     * Vraća čitljiv naziv odabranog modela plaćanja školarine.
     */
    /** @noinspection PhpUnused */
    public function modeLabel(?string $mode): string
    {
        return match ($mode) {
            self::MODE_EXEMPT => 'Oslobođen školarine',
            self::MODE_INSTALLMENTS => 'U dvije rate',
            default => 'U cijelosti',
        };
    }

    /**
     * Vraća dostupne opcije podmirenja za početnu stavku školarine (u cijelosti ili pola iznosa).
     */
    public function settlementOptionsForCharge(PolaznikPaymentCharge $charge): array
    {
        if ($charge->source !== self::SOURCE_TUITION) {
            return [];
        }

        return [
            ['value' => self::SETTLEMENT_FULL, 'label' => 'U cijelosti'],
            ['value' => self::SETTLEMENT_HALF, 'label' => 'Pola iznosa'],
        ];
    }

    /**
     * Vraća sažeti status školarine polaznika za prikaz u tablicama/listama (plaćeno, dug, pending).
     */
    public function listStatusForPolaznik(PolaznikSkole $polaznik): ?array
    {
        $summary = $this->summary($polaznik);
        if (!($summary['enabled'] ?? false)) {
            return null;
        }

        /** @var PolaznikPaymentProfile|null $profile */
        $profile = $summary['profile'] ?? null;
        if ($profile === null) {
            return null;
        }

        if ($profile->payment_mode === self::MODE_EXEMPT) {
            return [
                'state' => 'paid',
                'amount' => 0.0,
            ];
        }

        /** @var Collection<int,PolaznikPaymentCharge> $openCharges */
        $openCharges = $summary['openCharges'] ?? collect();
        if ($openCharges->isEmpty()) {
            return [
                'state' => 'paid',
                'amount' => 0.0,
            ];
        }

        $attendanceCount = (int)($summary['attendanceCount'] ?? 0);
        $nextOpen = $summary['nextOpenCharge'] ?? null;
        if ($nextOpen instanceof PolaznikPaymentCharge
            && $nextOpen->source === self::SOURCE_TUITION_SECOND
            && $nextOpen->due_training_count !== null
        ) {
            $limit = (int)$nextOpen->due_training_count;
            if ($attendanceCount < $limit) {
                return [
                    'state' => 'pending',
                    'amount' => round((float)$nextOpen->amount, 2),
                    'attendance' => $attendanceCount,
                    'limit' => $limit,
                ];
            }
        }

        return [
            'state' => 'debt',
            'amount' => round((float)$openCharges->sum(fn (PolaznikPaymentCharge $charge): float => (float)$charge->amount), 2),
        ];
    }

    /**
     * Generira ili ažurira zaduženja školarine prema broju odrađenih treninga i odabranom modelu plaćanja.
     */
    private function syncCharges(PolaznikPaymentProfile $profile, ?int $adminUserId): void
    {
        $mode = $this->normalizeMode($profile->payment_mode) ?? self::MODE_FULL;
        $totalAmount = round((float)$profile->tuition_amount, 2);
        [$firstHalf, $secondHalf] = $this->splitAmount($totalAmount);

        $primary = PolaznikPaymentCharge::query()
            ->where('polaznik_skole_id', (int)$profile->polaznik_skole_id)
            ->where('source', self::SOURCE_TUITION)
            ->where('status', '!=', self::STATUS_DELETED)
            ->orderBy('id')
            ->first();

        if ($mode === self::MODE_EXEMPT) {
            PolaznikPaymentCharge::query()
                ->where('polaznik_skole_id', (int)$profile->polaznik_skole_id)
                ->where('status', self::STATUS_OPEN)
                ->update([
                    'status' => self::STATUS_DELETED,
                    'updated_by' => $adminUserId,
                ]);

            return;
        }

        if ($primary === null) {
            $primary = PolaznikPaymentCharge::query()->create([
                'polaznik_skole_id' => (int)$profile->polaznik_skole_id,
                'polaznik_payment_profile_id' => (int)$profile->id,
                'source' => self::SOURCE_TUITION,
                'title' => $mode === self::MODE_INSTALLMENTS ? 'Školarina - 1. rata' : 'Školarina',
                'amount' => $mode === self::MODE_INSTALLMENTS ? $firstHalf : $totalAmount,
                'status' => self::STATUS_OPEN,
                'created_by' => $adminUserId,
                'updated_by' => $adminUserId,
            ]);
        } elseif ($primary->status === self::STATUS_OPEN) {
            $primary->title = $mode === self::MODE_INSTALLMENTS ? 'Školarina - 1. rata' : 'Školarina';
            $primary->amount = $mode === self::MODE_INSTALLMENTS ? $firstHalf : $totalAmount;
            $primary->polaznik_payment_profile_id = (int)$profile->id;
            $primary->updated_by = $adminUserId ?? $primary->updated_by;
            $primary->save();
        }

        $second = PolaznikPaymentCharge::query()
            ->where('polaznik_skole_id', (int)$profile->polaznik_skole_id)
            ->where('source', self::SOURCE_TUITION_SECOND)
            ->where('status', '!=', self::STATUS_DELETED)
            ->orderBy('id')
            ->first();

        if ($mode !== self::MODE_INSTALLMENTS) {
            if ($second !== null && $second->status === self::STATUS_OPEN) {
                $second->status = self::STATUS_DELETED;
                $second->updated_by = $adminUserId ?? $second->updated_by;
                $second->save();
            }
            return;
        }

        $firstPaid = $primary !== null && $primary->status === self::STATUS_PAID;
        if (!$firstPaid) {
            if ($second !== null && $second->status === self::STATUS_OPEN) {
                $second->status = self::STATUS_DELETED;
                $second->updated_by = $adminUserId ?? $second->updated_by;
                $second->save();
            }
            return;
        }

        if ($second === null) {
            PolaznikPaymentCharge::query()->create([
                'polaznik_skole_id' => (int)$profile->polaznik_skole_id,
                'polaznik_payment_profile_id' => (int)$profile->id,
                'source' => self::SOURCE_TUITION_SECOND,
                'title' => 'Školarina - 2. rata',
                'amount' => $secondHalf,
                'due_training_count' => self::SECOND_INSTALLMENT_AFTER_TRAININGS,
                'status' => self::STATUS_OPEN,
                'created_by' => $adminUserId,
                'updated_by' => $adminUserId,
            ]);

            return;
        }

        if ($second->status === self::STATUS_OPEN) {
            $second->title = 'Školarina - 2. rata';
            $second->amount = $secondHalf;
            $second->due_training_count = self::SECOND_INSTALLMENT_AFTER_TRAININGS;
            $second->polaznik_payment_profile_id = (int)$profile->id;
            $second->updated_by = $adminUserId ?? $second->updated_by;
            $second->save();
        }
    }

    /**
     * Broji evidentirane dolaske polaznika koji ulaze u pravilo druge rate.
     */
    private function attendanceCount(PolaznikSkole $polaznik): int
    {
        return $polaznik->dolasci()
            ->whereNotNull('datum')
            ->count();
    }

    /**
     * Određuje osnovni iznos školarine prema dobi polaznika na referentni datum.
     */
    private function baseTuitionAmountForPolaznik(PolaznikSkole $polaznik, Carbon $referenceDate): float
    {
        $isAdult = false;
        if ($polaznik->datum_rodjenja !== null) {
            $adultFrom = $polaznik->datum_rodjenja->copy()->addYears(18)->startOfDay();
            $isAdult = $adultFrom->lte($referenceDate->copy()->startOfDay());
        }

        return $isAdult ? $this->adultAmount() : $this->minorAmount();
    }

    /**
     * Dijeli iznos školarine na dvije rate (prva i druga polovica).
     */
    private function splitAmount(float $total): array
    {
        $first = round($total / 2, 2);
        $second = round($total - $first, 2);
        return [$first, $second];
    }

    /**
     * Normalizira model školarine na podržane vrijednosti (`full`, `installments`, `exempt`).
     */
    private function normalizeMode(?string $mode): ?string
    {
        $value = trim((string)$mode);
        if ($value === '') {
            return null;
        }

        if (!in_array($value, [self::MODE_FULL, self::MODE_INSTALLMENTS, self::MODE_EXEMPT], true)) {
            return null;
        }

        return $value;
    }

    /**
     * Normalizira datum plaćanja školarine u format `Y-m-d`.
     */
    private function normalizeDate(?string $value): ?string
    {
        $candidate = trim((string)$value);
        if ($candidate === '') {
            return null;
        }

        try {
            return Carbon::parse($candidate)->toDateString();
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Normalizira iznos školarine iz forme u decimalni broj.
     */
    private function normalizeAmount(mixed $value): ?float
    {
        if (is_int($value) || is_float($value)) {
            return round((float)$value, 2);
        }

        $stringValue = trim((string)$value);
        if ($stringValue === '') {
            return null;
        }

        $normalized = str_replace([' ', ','], ['', '.'], $stringValue);
        if (!is_numeric($normalized)) {
            return null;
        }

        return round((float)$normalized, 2);
    }

    /**
     * Osigurava da metadata školarine uvijek bude u ispravnom polju tipa array.
     */
    private function normalizeMetadata(mixed $metadata): array
    {
        return is_array($metadata) ? $metadata : [];
    }

    /**
     * Provjerava podržava li okruženje ili konfiguracija traženu mogućnost.
     */
    private function supportsSchoolPayments(): bool
    {
        return Schema::hasTable('site_settings')
            && Schema::hasColumn('site_settings', 'payment_tracking_enabled')
            && Schema::hasColumn('site_settings', 'school_tuition_adult_amount')
            && Schema::hasColumn('site_settings', 'school_tuition_minor_amount')
            && Schema::hasTable('polaznik_payment_profiles')
            && Schema::hasTable('polaznik_payment_charges');
    }
}
