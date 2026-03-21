<?php

namespace App\Services;

use App\Models\ClanPaymentCharge;
use App\Models\ClanPaymentProfile;
use App\Models\Clanovi;
use App\Models\Klub;
use App\Models\MembershipPaymentOption;
use App\Models\MembershipPaymentOptionPrice;
use App\Models\SiteSetting;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

/**
 * Servis zadužen za kompletan životni ciklus članarina:
 * - konfiguraciju modela i cijena,
 * - automatsko generiranje obveza po modelu plaćanja člana,
 * - evidenciju statusa uplata,
 * - izračun dugovanja i pripremu podataka za HUB-3A barkod.
 *
 * Namjerno je centraliziran na jednom mjestu kako bi kontroleri ostali tanki,
 * a poslovna pravila konzistentna kroz admin i korisničke ekrane.
 */
class PaymentTrackingService
{
    public const STATUS_OPEN = 'open';
    public const STATUS_PAID = 'paid';
    public const STATUS_DELETED = 'deleted';

    public const SOURCE_AUTO = 'auto_membership';
    public const SOURCE_MANUAL = 'manual';
    public const SOURCE_OPENING = 'opening_debt';

    public const VARIANT_FULL = 'full';
    public const VARIANT_SUPPORTING = 'supporting';
    public const VARIANT_ANNUAL_SUPPORTING_INDOOR = 'supporting_indoor';
    public const VARIANT_ANNUAL_SUPPORTING_OUTDOOR = 'supporting_outdoor';
    public const VARIANT_ANNUAL_SUPPORTING_BOTH = 'supporting_both';
    public const COLLECTION_BANK = 'bank';
    public const COLLECTION_CASH = 'cash';

    private const DEFAULT_INFO_ARTICLE_ID = 33;
    private const SUPPORTING_MEMBER_RATIO = 1 / 3;
    private const ANNUAL_MIXED_SUPPORTING_RATIO = 2 / 3;
    private ?bool $optionArchivedColumnSupported = null;

    /**
     * Provjerava je li modul praćenja članarina aktivan i infrastrukturno podržan.
     */
    public function isEnabled(): bool
    {
        if (!$this->supportsPaymentTracking()) {
            return false;
        }

        return (bool)SiteSetting::query()->value('payment_tracking_enabled');
    }

    /**
     * Dohvaća ID članka s uputama za plaćanje (fallback na zadani članak ako nije postavljen).
     */
    public function paymentInfoArticleId(): int
    {
        if (!$this->supportsPaymentTracking()) {
            return self::DEFAULT_INFO_ARTICLE_ID;
        }

        $value = SiteSetting::query()->value('payment_info_clanak_id');

        return is_numeric($value) ? (int)$value : self::DEFAULT_INFO_ARTICLE_ID;
    }

    /**
     * Priprema sve podatke potrebne admin ekranu za setup plaćanja.
     *
     * Ujedno normalizira katalog sezonskih opcija kako bi u sučelju uvijek
     * postojale očekivane temeljne stavke (dvoranska i vanjska sezona).
     */
    public function setupViewData(): array
    {
        if (!$this->supportsPaymentTracking()) {
            return [
                'paymentTrackingEnabled' => false,
                'paymentInfoClanakId' => self::DEFAULT_INFO_ARTICLE_ID,
                'paymentOptions' => collect(),
                'paymentOptionsArchivedCount' => 0,
                'schoolTuitionAdultAmount' => 100.00,
                'schoolTuitionMinorAmount' => 70.00,
            ];
        }

        $this->normalizeSeasonalOptionsCatalog();

        $optionsQuery = MembershipPaymentOption::query()
            ->with(['latestPrice'])
            ->orderBy('sort_order')
            ->orderBy('id');

        if ($this->supportsArchivedOptions()) {
            $optionsQuery->where('is_archived', false);
        }

        $options = $optionsQuery
            ->get()
            ->map(function (MembershipPaymentOption $option): MembershipPaymentOption {
                // latestPrice vraćamo kao pomoćne atribute jer ih forma direktno prikazuje/uređuje.
                $latestPrice = $option->latestPrice;
                $option->setAttribute('latest_price_amount', $latestPrice ? (string)$latestPrice->amount : '0.00');
                $option->setAttribute('latest_price_valid_from', $latestPrice?->valid_from?->format('Y-m-d') ?? now()->toDateString());

                return $option;
            });

        $archivedCount = 0;
        if ($this->supportsArchivedOptions()) {
            $archivedCount = MembershipPaymentOption::query()
                ->where('is_archived', true)
                ->count();
        }

        $siteSettings = SiteSetting::query()->first();

        return [
            'paymentTrackingEnabled' => $this->isEnabled(),
            'paymentInfoClanakId' => $this->paymentInfoArticleId(),
            'paymentOptions' => $options,
            'paymentOptionsArchivedCount' => $archivedCount,
            'schoolTuitionAdultAmount' => $this->normalizeAmount($siteSettings->school_tuition_adult_amount ?? null) ?? 100.00,
            'schoolTuitionMinorAmount' => $this->normalizeAmount($siteSettings->school_tuition_minor_amount ?? null) ?? 70.00,
        ];
    }

    /**
     * Sprema globalne postavke i katalog modela plaćanja iz admin forme.
     *
     * Važno: cijene su vremenski verzionirane (`valid_from`) kako promjena cijene
     * ne bi retroaktivno mijenjala povijesne obveze.
     */
    public function updateSetup(array $data, int $adminUserId): void
    {
        if (!$this->supportsPaymentTracking()) {
            return;
        }

        $this->normalizeSeasonalOptionsCatalog();

        $siteSettings = SiteSetting::query()->first();
        if ($siteSettings === null) {
            $siteSettings = SiteSetting::query()->create([
                'theme_mode_policy' => ThemeService::MODE_AUTO,
                'payment_tracking_enabled' => false,
                'payment_info_clanak_id' => self::DEFAULT_INFO_ARTICLE_ID,
                'school_tuition_adult_amount' => 100.00,
                'school_tuition_minor_amount' => 70.00,
            ]);
        }

        $siteSettings->payment_tracking_enabled = (bool)($data['payment_tracking_enabled'] ?? false);
        if (array_key_exists('payment_info_clanak_id', $data)) {
            $siteSettings->payment_info_clanak_id = (int)$data['payment_info_clanak_id'];
        } elseif (empty($siteSettings->payment_info_clanak_id)) {
            $siteSettings->payment_info_clanak_id = self::DEFAULT_INFO_ARTICLE_ID;
        }
        if (array_key_exists('school_tuition_adult_amount', $data)) {
            $adult = $this->normalizeAmount($data['school_tuition_adult_amount'] ?? null);
            if ($adult !== null && $adult > 0) {
                $siteSettings->school_tuition_adult_amount = $adult;
            }
        } elseif (empty($siteSettings->school_tuition_adult_amount)) {
            $siteSettings->school_tuition_adult_amount = 100.00;
        }
        if (array_key_exists('school_tuition_minor_amount', $data)) {
            $minor = $this->normalizeAmount($data['school_tuition_minor_amount'] ?? null);
            if ($minor !== null && $minor > 0) {
                $siteSettings->school_tuition_minor_amount = $minor;
            }
        } elseif (empty($siteSettings->school_tuition_minor_amount)) {
            $siteSettings->school_tuition_minor_amount = 70.00;
        }
        $siteSettings->save();

        $optionsPayload = is_array($data['options'] ?? null) ? $data['options'] : [];
        $optionsQuery = MembershipPaymentOption::query()->orderBy('sort_order')->orderBy('id');
        if ($this->supportsArchivedOptions()) {
            $optionsQuery->where('is_archived', false);
        }

        $options = $optionsQuery->get();

        foreach ($options as $option) {
            // Svaka opcija ima payload pod svojim ključem; helper pokriva i legacy nazive ključeva.
            $payload = $this->resolveOptionPayload($optionsPayload, $option);
            $periodChanged = false;

            if (array_key_exists('name', $payload)) {
                $name = $this->normalizeText($payload['name'] ?? null);
                if ($name !== null) {
                    $option->name = $name;
                }
            }

            if (array_key_exists('description', $payload)) {
                $option->description = $this->normalizeText($payload['description'] ?? null);
            }

            if (array_key_exists('period_type', $payload) || array_key_exists('period_anchor', $payload)) {
                $rawPeriodType = trim((string)($payload['period_type'] ?? $option->period_type));
                $rawPeriodAnchor = $payload['period_anchor'] ?? $option->period_anchor;

                ['period_type' => $normalizedType, 'period_anchor' => $normalizedAnchor] = $this->normalizePeriodSettings(
                    $rawPeriodType,
                    $rawPeriodAnchor
                );

                if ($option->period_type !== $normalizedType || $option->period_anchor !== $normalizedAnchor) {
                    $option->period_type = $normalizedType;
                    $option->period_anchor = $normalizedAnchor;
                    $periodChanged = true;
                }
            }

            if ($this->supportsCollectionMethod() && array_key_exists('collection_method', $payload)) {
                $option->collection_method = $this->normalizeCollectionMethod($payload['collection_method'] ?? null);
            } elseif ($this->supportsCollectionMethod() && empty($option->collection_method)) {
                $option->collection_method = self::COLLECTION_BANK;
            }

            if (array_key_exists('enabled', $payload)) {
                $enabledRaw = $payload['enabled'] ?? false;
                $option->is_enabled = in_array((string)$enabledRaw, ['1', 'true', 'on'], true);
            }

            if ($option->isDirty()) {
                $option->save();
            }

            if ($periodChanged) {
                // Promjena perioda (npr. monthly -> seasonal) znači da treba regenerirati buduće obveze profila.
                $this->syncProfilesForOption((int)$option->id, true);
            }

            $amount = $option->period_type === 'exempt'
                ? 0.0
                : $this->normalizeAmount($payload['amount'] ?? null);
            if ($amount === null) {
                continue;
            }

            $validFrom = $this->normalizeDate($payload['valid_from'] ?? null) ?? now()->toDateString();

            $priceOnDate = MembershipPaymentOptionPrice::query()
                ->where('membership_payment_option_id', $option->id)
                ->whereDate('valid_from', $validFrom)
                ->first();

            if ($priceOnDate !== null) {
                if ((float)$priceOnDate->amount !== $amount) {
                    $priceOnDate->amount = $amount;
                    $priceOnDate->created_by = $adminUserId;
                    $priceOnDate->save();
                    $this->syncProfilesForOption((int)$option->id);
                }

                continue;
            }

            $latestPrice = MembershipPaymentOptionPrice::query()
                ->where('membership_payment_option_id', $option->id)
                ->orderByDesc('valid_from')
                ->orderByDesc('id')
                ->first();

            $latestAmount = $latestPrice ? (float)$latestPrice->amount : null;
            $latestDate = $latestPrice?->valid_from?->format('Y-m-d');

            if ($latestPrice === null || $latestAmount !== $amount || $latestDate !== $validFrom) {
                MembershipPaymentOptionPrice::query()->create([
                    'membership_payment_option_id' => $option->id,
                    'amount' => $amount,
                    'valid_from' => $validFrom,
                    'created_by' => $adminUserId,
                ]);

                $this->syncProfilesForOption((int)$option->id);
            }
        }
    }

    /**
     * Kreira novi model naplate članarine koji se kasnije može dodijeliti članovima.
     */
    public function createOption(array $data, int $adminUserId): MembershipPaymentOption
    {
        if (!$this->supportsPaymentTracking()) {
            throw new RuntimeException('Praćenje plaćanja nije dostupno.');
        }

        $name = $this->normalizeText($data['name'] ?? null);
        if ($name === null) {
            throw new InvalidArgumentException('Naziv vrste plaćanja je obavezan.');
        }

        ['period_type' => $periodType, 'period_anchor' => $periodAnchor] = $this->normalizePeriodSettings(
            trim((string)($data['period_type'] ?? '')),
            $data['period_anchor'] ?? null
        );
        $enabledRaw = $data['enabled'] ?? true;
        $isEnabled = in_array((string)$enabledRaw, ['1', 'true', 'on'], true);

        $baseKey = 'custom_' . trim(Str::slug($name, '_'), '_');
        if ($baseKey === 'custom_') {
            $baseKey = 'custom_model';
        }

        $key = $baseKey;
        $counter = 2;
        while (MembershipPaymentOption::query()->where('key', $key)->exists()) {
            $key = $baseKey . '_' . $counter;
            $counter++;
        }

        $nextSort = ((int)MembershipPaymentOption::query()->max('sort_order')) + 10;
        if ($nextSort <= 0) {
            $nextSort = 100;
        }

        $option = MembershipPaymentOption::query()->create([
            'key' => $key,
            'name' => $name,
            'description' => $this->normalizeText($data['description'] ?? null),
            'period_type' => $periodType,
            'period_anchor' => $periodAnchor,
            'collection_method' => $this->supportsCollectionMethod()
                ? $this->normalizeCollectionMethod($data['collection_method'] ?? null)
                : self::COLLECTION_BANK,
            'is_enabled' => $isEnabled,
            'sort_order' => $nextSort,
        ]);

        $amount = $periodType === 'exempt'
            ? 0.0
            : ($this->normalizeAmount($data['amount'] ?? null) ?? 0.0);
        $validFrom = $this->normalizeDate($data['valid_from'] ?? null) ?? now()->toDateString();

        MembershipPaymentOptionPrice::query()->create([
            'membership_payment_option_id' => $option->id,
            'amount' => $amount,
            'valid_from' => $validFrom,
            'created_by' => $adminUserId > 0 ? $adminUserId : null,
        ]);

        return $option;
    }

    /**
     * Arhivira zapis kako više ne bi bio aktivan u radu.
     */
    public function archiveOption(int $optionId): void
    {
        if (!$this->supportsPaymentTracking()) {
            throw new RuntimeException('Praćenje plaćanja nije dostupno.');
        }

        if (!$this->supportsArchivedOptions()) {
            throw new RuntimeException('Arhiviranje modela plaćanja nije dostupno dok se ne pokrene migracija baze.');
        }

        $option = MembershipPaymentOption::query()->findOrFail($optionId);
        if ($option->is_archived) {
            return;
        }

        $option->is_archived = true;
        $option->is_enabled = false;
        $option->save();
    }

    /**
     * Dodjeljuje model plaćanja članu i regenerira automatske stavke obveza.
     *
     * Koristi se iz admin profila člana nakon odabira modela (mjesečno/sezonski/godišnje/oslobođen).
     */
    public function assignProfileToClan(Clanovi $clan, array $data, int $adminUserId): ClanPaymentProfile
    {
        if (!$this->supportsPaymentTracking()) {
            throw new RuntimeException('Praćenje plaćanja nije dostupno.');
        }

        $this->normalizeSeasonalOptionsCatalog();

        $optionId = isset($data['membership_payment_option_id']) && $data['membership_payment_option_id'] !== ''
            ? (int)$data['membership_payment_option_id']
            : null;

        $option = null;
        if ($optionId !== null) {
            $option = MembershipPaymentOption::query()->findOrFail($optionId);
        }

        $profile = ClanPaymentProfile::query()->firstOrNew(['clan_id' => (int)$clan->id]);

        if (!$profile->exists) {
            $profile->created_by = $adminUserId;
        }

        $profile->membership_payment_option_id = $option?->id;
        $profile->start_date = $this->normalizeDate($data['start_date'] ?? null) ?? now()->toDateString();
        $profile->membership_amount_override = $this->normalizeAmount($data['membership_amount_override'] ?? null);
        $profile->opening_debt_amount = $this->normalizeAmount($data['opening_debt_amount'] ?? null) ?? 0;
        $profile->opening_debt_note = $this->normalizeText($data['opening_debt_note'] ?? null);
        $profile->updated_by = $adminUserId;
        $profile->save();

        $profile->load('paymentOption');

        // Nakon promjene profila odmah usklađujemo buduće obveze i početni dug.
        $this->syncProfileCharges($profile, true);
        $this->syncOpeningDebtCharge($profile, $adminUserId);

        return $profile;
    }

    /**
     * Kreira ručno dodatno zaduženje (npr. najam opreme, dvorana, druga klupska obveza).
     */
    public function createManualCharge(Clanovi $clan, array $data, int $adminUserId): ClanPaymentCharge
    {
        $amount = $this->normalizeAmount($data['amount'] ?? null);
        if ($amount === null || $amount <= 0) {
            throw new InvalidArgumentException('Iznos mora biti veći od 0.');
        }

        $title = $this->normalizeText($data['title'] ?? null);
        if ($title === null) {
            throw new InvalidArgumentException('Naziv dodatnog plaćanja je obavezan.');
        }

        $description = $this->normalizeText($data['description'] ?? null);
        $dueDate = $this->normalizeDate($data['due_date'] ?? null);

        $profile = ClanPaymentProfile::query()->where('clan_id', $clan->id)->first();

        return ClanPaymentCharge::query()->create([
            'clan_id' => (int)$clan->id,
            'clan_payment_profile_id' => $profile?->id,
            'membership_payment_option_id' => null,
            'source' => self::SOURCE_MANUAL,
            'period_key' => null,
            'period_start' => $dueDate,
            'period_end' => $dueDate,
            'due_date' => $dueDate,
            'title' => $title,
            'description' => $description,
            'amount' => $amount,
            'currency' => 'EUR',
            'status' => self::STATUS_OPEN,
            'paid_at' => null,
            'confirmed_by' => null,
            'created_by' => $adminUserId,
            'updated_by' => $adminUserId,
        ]);
    }

    /**
     * Mijenja status uplate pojedine stavke i po potrebi prilagođava iznos/varijantu članarine.
     */
    public function updateChargeStatus(
        ClanPaymentCharge $charge,
        bool $isPaid,
        ?string $paidAt,
        int $adminUserId,
        ?string $paymentVariant = null,
        ?string $amount = null
    ): void {
        $charge->loadMissing('paymentOption');
        $metadata = is_array($charge->metadata) ? $charge->metadata : [];
        $baseAmount = isset($metadata['base_amount']) && is_numeric($metadata['base_amount'])
            ? (float)$metadata['base_amount']
            : (float)$charge->amount;
        $baseTitle = $this->normalizeText($metadata['base_title'] ?? null) ?? $charge->title;

        if ($isPaid) {
            // Ako je stavka plaćena, računa se konačni iznos po odabranoj varijanti
            // (npr. puna/podupiruća) i zaključava se datum potvrde.
            $resolvedVariant = $this->resolveVariantForCharge(
                $charge,
                $paymentVariant
                    ?? ($metadata['preferred_variant'] ?? null)
                    ?? ($metadata['payment_variant'] ?? null)
            );

            $effectiveAmount = $this->calculateVariantAmount($baseAmount, $resolvedVariant);
            $manualAmount = $this->normalizeAmount($amount);
            if ($manualAmount !== null && $manualAmount > 0) {
                $effectiveAmount = $manualAmount;
            }

            $charge->status = self::STATUS_PAID;
            $charge->paid_at = $this->normalizeDate($paidAt) ?? now()->toDateString();
            $charge->confirmed_by = $adminUserId;
            $charge->amount = $effectiveAmount;
            $charge->title = $this->buildChargeTitleForVariant($baseTitle, $resolvedVariant);

            $metadata['base_amount'] = round($baseAmount, 2);
            $metadata['base_title'] = $baseTitle;
            if ($resolvedVariant !== null) {
                $metadata['payment_variant'] = $resolvedVariant;
            } else {
                unset($metadata['payment_variant']);
            }
            unset($metadata['preferred_variant']);
        } else {
            // Vraćanje na "open" resetira sve što je vezano uz potvrđenu uplatu.
            $charge->status = self::STATUS_OPEN;
            $charge->paid_at = null;
            $charge->confirmed_by = null;
            $charge->amount = $baseAmount;
            $charge->title = $baseTitle;
            unset($metadata['payment_variant']);
        }

        $charge->metadata = $metadata;
        $charge->updated_by = $adminUserId;
        $charge->save();
    }

    /**
     * Briše ili označava zapis kao obrisan.
     */
    public function deleteCharge(ClanPaymentCharge $charge, int $adminUserId): void
    {
        if ($charge->source === self::SOURCE_AUTO) {
            $charge->status = self::STATUS_DELETED;
            $charge->paid_at = null;
            $charge->confirmed_by = null;
            $charge->updated_by = $adminUserId > 0 ? $adminUserId : $charge->updated_by;
            $charge->save();
            return;
        }

        if ($charge->source === self::SOURCE_MANUAL) {
            $charge->delete();
            return;
        }

        if ($charge->source === self::SOURCE_OPENING) {
            $profile = ClanPaymentProfile::query()->where('clan_id', (int)$charge->clan_id)->first();
            if ($profile !== null) {
                $profile->opening_debt_amount = 0;
                $profile->opening_debt_note = null;
                $profile->updated_by = $adminUserId > 0 ? $adminUserId : $profile->updated_by;
                $profile->save();
            }

            $charge->delete();
            return;
        }

        throw new InvalidArgumentException('Stavku plaćanja nije moguće obrisati.');
    }

    /**
     * Vraća dopuštene varijante uplate za stavku članarine (npr. puna/podupirući) i izračunate iznose.
     */
    public function availableVariantsForCharge(ClanPaymentCharge $charge): array
    {
        $charge->loadMissing('paymentOption');
        if ($charge->source !== self::SOURCE_AUTO || $charge->paymentOption === null) {
            return [];
        }

        $option = $charge->paymentOption;
        $baseAmount = $this->baseAmountForCharge($charge);

        if ($option->period_type === 'seasonal') {
            return [
                [
                    'value' => self::VARIANT_FULL,
                    'label' => 'Članarina',
                    'amount' => round($baseAmount, 2),
                ],
                [
                    'value' => self::VARIANT_SUPPORTING,
                    'label' => 'Podupirući član',
                    'amount' => round($baseAmount * self::SUPPORTING_MEMBER_RATIO, 2),
                ],
            ];
        }

        if ($option->period_type === 'annual') {
            return [
                [
                    'value' => self::VARIANT_FULL,
                    'label' => 'Sezona dvoranska + sezona vanjska',
                    'amount' => round($baseAmount, 2),
                ],
                [
                    'value' => self::VARIANT_ANNUAL_SUPPORTING_INDOOR,
                    'label' => 'Sezona dvoranska podupirući + vanjska puna',
                    'amount' => round($baseAmount * self::ANNUAL_MIXED_SUPPORTING_RATIO, 2),
                ],
                [
                    'value' => self::VARIANT_ANNUAL_SUPPORTING_OUTDOOR,
                    'label' => 'Sezona dvoranska puna + vanjska podupirući',
                    'amount' => round($baseAmount * self::ANNUAL_MIXED_SUPPORTING_RATIO, 2),
                ],
                [
                    'value' => self::VARIANT_ANNUAL_SUPPORTING_BOTH,
                    'label' => 'Sezona dvoranska podupirući + vanjska podupirući',
                    'amount' => round($baseAmount * self::SUPPORTING_MEMBER_RATIO, 2),
                ],
            ];
        }

        return [];
    }

    /**
     * Vraća trenutno odabranu varijantu plaćanja za stavku članarine iz metadata podataka.
     */
    public function selectedVariantForCharge(ClanPaymentCharge $charge, bool $preferPreferred = false): ?string
    {
        $metadata = is_array($charge->metadata) ? $charge->metadata : [];
        $rawVariant = $preferPreferred
            ? ($metadata['preferred_variant'] ?? $metadata['payment_variant'] ?? null)
            : ($metadata['payment_variant'] ?? $metadata['preferred_variant'] ?? null);

        return $this->resolveVariantForCharge($charge, is_string($rawVariant) ? $rawVariant : null);
    }

    /**
     * Sprema korisnikov preferirani tip uplate za otvorenu automatsku stavku članarine.
     */
    public function setPreferredVariantForCharge(ClanPaymentCharge $charge, ?string $variant): void
    {
        if ($charge->source !== self::SOURCE_AUTO || $charge->status !== self::STATUS_OPEN) {
            return;
        }

        $charge->loadMissing('paymentOption');
        $resolvedVariant = $this->resolveVariantForCharge($charge, $variant);
        $metadata = is_array($charge->metadata) ? $charge->metadata : [];
        $metadata['base_amount'] = round($this->baseAmountForCharge($charge), 2);
        $metadata['base_title'] = $this->normalizeText($metadata['base_title'] ?? null) ?? $charge->title;

        if ($resolvedVariant === null) {
            unset($metadata['preferred_variant']);
        } else {
            $metadata['preferred_variant'] = $resolvedVariant;
        }

        $charge->metadata = $metadata;
        $charge->save();
    }

    /**
     * Računa konačni iznos stavke prema odabranoj varijanti plaćanja.
     */
    public function resolvedChargeAmount(ClanPaymentCharge $charge, bool $preferPreferred = false): float
    {
        $variant = $this->selectedVariantForCharge($charge, $preferPreferred);
        return $this->calculateVariantAmount($this->baseAmountForCharge($charge), $variant);
    }

    /**
     * Vraća korisnički naziv odabrane varijante plaćanja za stavku članarine.
     */
    public function variantLabelForCharge(ClanPaymentCharge $charge, ?string $variant): ?string
    {
        if ($variant === null) {
            return null;
        }

        foreach ($this->availableVariantsForCharge($charge) as $option) {
            if (($option['value'] ?? null) === $variant) {
                return (string)($option['label'] ?? $variant);
            }
        }

        return null;
    }

    /**
     * Vraća napomenu o ograničenju korištenja dvorane/terena za podupiruće varijante.
     */
    public function restrictionNoteForCharge(ClanPaymentCharge $charge, ?string $variant): ?string
    {
        if ($variant === null || !$this->isSupportingVariant($variant)) {
            return null;
        }

        if ($charge->paymentOption?->period_type === 'seasonal') {
            $periodKey = (string)($charge->period_key ?? '');
            if (str_contains($periodKey, 'season-oct')) {
                return 'Napomena: u ovoj dvoranskoj sezoni nema pravo korištenja dvorane.';
            }

            if (str_contains($periodKey, 'season-apr')) {
                return 'Napomena: u ovoj vanjskoj sezoni nema pravo korištenja terena.';
            }

            return 'Napomena: podupirući član nema pravo korištenja dvorane ili terena u plaćenoj sezoni.';
        }

        return match ($variant) {
            self::VARIANT_ANNUAL_SUPPORTING_INDOOR => 'Napomena: nema pravo korištenja dvorane u dvoranskom dijelu godine.',
            self::VARIANT_ANNUAL_SUPPORTING_OUTDOOR => 'Napomena: nema pravo korištenja terena u vanjskom dijelu godine.',
            self::VARIANT_ANNUAL_SUPPORTING_BOTH => 'Napomena: nema pravo korištenja dvorane ni terena u toj godišnjoj članarini.',
            default => null,
        };
    }

    /**
     * Provjerava naplaćuje li se ova stavka gotovinom (bez barkoda/HUB naloga).
     */
    public function isCashCollectionForCharge(ClanPaymentCharge $charge): bool
    {
        if ($charge->source !== self::SOURCE_AUTO) {
            return false;
        }

        $charge->loadMissing('paymentOption');
        return $this->optionUsesCash($charge->paymentOption);
    }

    /**
     * Vraća sažeti status članarine člana za prikaz u listama članova (plaćeno ili iznos duga).
     */
    /** @noinspection PhpUnused */
    public function listStatusForClan(Clanovi $clan): ?array
    {
        $summary = $this->memberSummaryReadOnly($clan);
        if (!($summary['enabled'] ?? false)) {
            return null;
        }

        $profile = $summary['profile'] ?? null;
        $profileConfigured = $profile !== null && $profile->paymentOption !== null;
        $hasCharges = (($summary['charges'] ?? collect())->count() > 0);
        if (!$profileConfigured && !$hasCharges) {
            return null;
        }

        $totalOpenAmount = round((float)($summary['totalOpenAmount'] ?? 0), 2);
        if ($totalOpenAmount > 0) {
            return [
                'state' => 'debt',
                'amount' => $totalOpenAmount,
            ];
        }

        return [
            'state' => 'paid',
            'amount' => 0.0,
        ];
    }

    /**
     * Vraća statuse plaćanja za više članova odjednom kako bi se izbjegao N+1 upit u listama.
     */
    public function listStatusForClanIds(iterable $clanIds): array
    {
        $ids = collect($clanIds)
            ->map(static fn ($id): int => (int)$id)
            ->filter(static fn (int $id): bool => $id > 0)
            ->unique()
            ->values();

        if ($ids->isEmpty() || !$this->isEnabled() || !$this->supportsPaymentTracking()) {
            return [];
        }

        $profilesByClan = ClanPaymentProfile::query()
            ->with('paymentOption')
            ->whereIn('clan_id', $ids)
            ->get()
            ->keyBy(fn (ClanPaymentProfile $profile): int => (int)$profile->clan_id);

        $chargesByClan = ClanPaymentCharge::query()
            ->with('paymentOption')
            ->whereIn('clan_id', $ids)
            ->where('status', '!=', self::STATUS_DELETED)
            ->orderBy('clan_id')
            ->orderBy('id')
            ->get();
        $chargesByClan = $chargesByClan->groupBy(fn (ClanPaymentCharge $charge): int => (int)$charge->clan_id);

        $statuses = [];
        foreach ($ids as $clanId) {
            $profileByClan = $profilesByClan->get($clanId);
            $profile = $profileByClan instanceof ClanPaymentProfile ? $profileByClan : null;
            $chargesByClanId = $chargesByClan->get($clanId);
            $charges = $chargesByClanId instanceof Collection ? $chargesByClanId : collect();
            $profileConfigured = $profile !== null && $profile->paymentOption !== null;
            if (!$profileConfigured && $charges->isEmpty()) {
                $statuses[$clanId] = null;
                continue;
            }

            $unpaidCharges = $charges->filter(fn (ClanPaymentCharge $charge): bool => $charge->status === self::STATUS_OPEN);
            $totalOpenAmount = round((float)$unpaidCharges->sum(
                fn (ClanPaymentCharge $charge): float => $this->resolvedChargeAmount($charge, true)
            ), 2);

            $statuses[$clanId] = $totalOpenAmount > 0
                ? ['state' => 'debt', 'amount' => $totalOpenAmount]
                : ['state' => 'paid', 'amount' => 0.0];
        }

        return $statuses;
    }

    /**
     * Sastavlja cjeloviti sažetak stanja plaćanja za člana uz automatsku sinkronizaciju stavki.
     */
    public function memberSummary(Clanovi $clan): array
    {
        return $this->buildMemberSummary($clan, true);
    }

    /**
     * Sastavlja cjeloviti sažetak stanja plaćanja bez upisa u bazu (read-only prikaz).
     */
    public function memberSummaryReadOnly(Clanovi $clan): array
    {
        return $this->buildMemberSummary($clan, false);
    }

    /**
     * Sastavlja cjeloviti sažetak stanja plaćanja za člana (otvoreno, plaćeno, tekuće, dugovanja).
     */
    private function buildMemberSummary(Clanovi $clan, bool $syncBeforeRead): array
    {
        $enabled = $this->isEnabled();

        if (!$enabled || !$this->supportsPaymentTracking()) {
            return [
                'enabled' => false,
                'profile' => null,
                'charges' => collect(),
                'unpaidCharges' => collect(),
                'paidCharges' => collect(),
                'currentCharges' => collect(),
                'currentUnpaidCharges' => collect(),
                'pastDueCharges' => collect(),
                'nextUnpaidCharge' => null,
                'isExempt' => false,
                'hasWarnings' => false,
                'totalOpenAmount' => 0,
            ];
        }

        $profile = ClanPaymentProfile::query()
            ->with('paymentOption')
            ->where('clan_id', $clan->id)
            ->first();

        if ($syncBeforeRead && $profile !== null && $profile->paymentOption !== null) {
            $this->syncProfileCharges($profile, false);
            $this->syncOpeningDebtCharge($profile, (int)($profile->updated_by ?? $profile->created_by ?? 0));
        }

        $charges = ClanPaymentCharge::query()
            ->with('paymentOption')
            ->where('clan_id', (int)$clan->id)
            ->where('status', '!=', self::STATUS_DELETED)
            ->orderByRaw('COALESCE(period_start, due_date, paid_at)')
            ->orderBy('id')
            ->get();

        $today = now()->startOfDay();

        $unpaidCharges = $charges->filter(fn (ClanPaymentCharge $charge): bool => $charge->status === self::STATUS_OPEN)->values();
        $paidCharges = $charges->filter(fn (ClanPaymentCharge $charge): bool => $charge->status === self::STATUS_PAID)->values();

        // Trenutno razdoblje: koristi period_start/period_end i služi za upozorenje "tekuća članarina".
        $currentCharges = $charges
            ->filter(function (ClanPaymentCharge $charge) use ($today): bool {
                if ($charge->source !== self::SOURCE_AUTO) {
                    return false;
                }

                if ($charge->period_start === null || $charge->period_end === null) {
                    return false;
                }

                return $today->betweenIncluded($charge->period_start->copy()->startOfDay(), $charge->period_end->copy()->endOfDay());
            })
            ->values();

        $currentUnpaidCharges = $currentCharges
            ->filter(fn (ClanPaymentCharge $charge): bool => $charge->status === self::STATUS_OPEN)
            ->values();

        // Povijesna dugovanja: otvorene stavke kojima je prošao period_end ili due_date.
        $pastDueCharges = $unpaidCharges
            ->filter(function (ClanPaymentCharge $charge) use ($today): bool {
                if ($charge->period_end !== null) {
                    return $charge->period_end->copy()->endOfDay()->lt($today);
                }

                if ($charge->due_date !== null) {
                    return $charge->due_date->copy()->endOfDay()->lt($today);
                }

                return false;
            })
            ->values();

        $nextUnpaidCharge = $unpaidCharges
            ->sortBy(function (ClanPaymentCharge $charge): array {
                $due = $charge->due_date?->format('Y-m-d')
                    ?? $charge->period_start?->format('Y-m-d')
                    ?? '9999-12-31';

                return [$due, (int)$charge->id];
            })
            ->first();

        $isExempt = $profile?->paymentOption?->period_type === 'exempt';
        $hasWarnings = !$isExempt && ($currentUnpaidCharges->isNotEmpty() || $pastDueCharges->isNotEmpty());
        $totalOpenAmount = (float)$unpaidCharges->sum(
            fn (ClanPaymentCharge $charge): float => $this->resolvedChargeAmount($charge, true)
        );

        return [
            'enabled' => true,
            'profile' => $profile,
            'charges' => $charges,
            'unpaidCharges' => $unpaidCharges,
            'paidCharges' => $paidCharges,
            'currentCharges' => $currentCharges,
            'currentUnpaidCharges' => $currentUnpaidCharges,
            'pastDueCharges' => $pastDueCharges,
            'nextUnpaidCharge' => $nextUnpaidCharge,
            'isExempt' => $isExempt,
            'hasWarnings' => $hasWarnings,
            'totalOpenAmount' => $totalOpenAmount,
        ];
    }

    /**
     * Generira korisnički tekst upozorenja koji se prikazuje na naslovnici/profilu člana.
     */
    public function noticeForClan(Clanovi $clan): ?array
    {
        $summary = $this->memberSummary($clan);
        if (!($summary['enabled'] ?? false)) {
            return null;
        }

        $profile = $summary['profile'];
        if ($profile === null || $profile->paymentOption === null) {
            $charges = $summary['charges'] ?? collect();
            if ($charges->count() > 0) {
                $openCount = ($summary['unpaidCharges'] ?? collect())->count();
                if ($openCount > 0) {
                    return [
                        'variant' => 'danger',
                        'title' => 'Potrebna uplata',
                        'message' => 'Evidentirana su dodatna neplaćena plaćanja.',
                    ];
                }

                return [
                    'variant' => 'success',
                    'title' => 'Plaćanja podmirena',
                    'message' => 'Sva evidentirana dodatna plaćanja su podmirena.',
                ];
            }

            return [
                'variant' => 'secondary',
                'title' => 'Plaćanja nisu postavljena',
                'message' => 'Administrator još nije definirao model plaćanja za ovog člana.',
            ];
        }

        if ($summary['isExempt'] ?? false) {
            return [
                'variant' => 'success',
                'title' => 'Oslobođen plaćanja',
                'message' => 'Za ovog člana nije predviđeno plaćanje članarine.',
            ];
        }

        $currentUnpaidCount = $summary['currentUnpaidCharges']->count();
        $pastDueCount = $summary['pastDueCharges']->count();

        if ($currentUnpaidCount > 0 || $pastDueCount > 0) {
            $parts = [];
            if ($currentUnpaidCount > 0) {
                $parts[] = 'tekuće razdoblje nije podmireno';
            }
            if ($pastDueCount > 0) {
                $parts[] = 'postoje dugovanja iz ranijih razdoblja';
            }

            return [
                'variant' => 'danger',
                'title' => 'Potrebna uplata članarine',
                'message' => ucfirst(implode(', ', $parts)) . '.',
            ];
        }

        // Poseban slučaj: podupiruća varijanta zahtijeva dodatnu napomenu o ograničenjima korištenja.
        $currentSupportingNotes = $summary['currentCharges']
            ->filter(function (ClanPaymentCharge $charge): bool {
                if ($charge->status !== self::STATUS_PAID) {
                    return false;
                }

                $variant = $this->selectedVariantForCharge($charge);
                return $this->isSupportingVariant($variant);
            })
            ->map(function (ClanPaymentCharge $charge): string {
                $variant = $this->selectedVariantForCharge($charge);
                return $this->restrictionNoteForCharge($charge, $variant) ?? 'Napomena: plaćeno je kao podupirući član.';
            })
            ->filter(static fn (?string $note): bool => !empty($note))
            ->unique()
            ->values();

        if ($currentSupportingNotes->isNotEmpty()) {
            return [
                'variant' => 'warning',
                'title' => 'Članarina - podupirući član',
                'message' => implode(' ', $currentSupportingNotes->all()),
            ];
        }

        return [
            'variant' => 'success',
            'title' => 'Članarina je podmirena',
            'message' => 'Trenutno nema neplaćenih obveza za tekuće razdoblje.',
        ];
    }

    /**
     * Priprema payload za HUB-3A barkod (računska uplata).
     *
     * Povratna vrijednost je `null` za gotovinska plaćanja jer za njih barkod nema smisla.
     */
    public function buildHubPayloadForCharge(Clanovi $clan, ClanPaymentCharge $charge): ?array
    {
        if ($this->isCashCollectionForCharge($charge)) {
            return null;
        }

        $amount = $this->resolvedChargeAmount($charge, true);
        if ($amount <= 0) {
            return null;
        }

        $klub = Klub::query()->first();
        if ($klub === null) {
            return null;
        }

        $iban = $this->normalizeIban((string)($klub->racun ?? ''));
        if ($iban === null) {
            return null;
        }

        [$receiverAddressRaw, $receiverCityRaw] = $this->splitAddress((string)($klub->adresa ?? ''));

        $receiverName = $this->sanitizeHubPayloadText((string)($klub->naziv ?? ''));
        $receiverAddress = $this->sanitizeHubPayloadText($receiverAddressRaw);
        $receiverCity = $this->sanitizeHubPayloadText($receiverCityRaw);
        $payerName = $this->sanitizeHubPayloadText(trim($clan->Ime . ' ' . $clan->Prezime));
        $payerAddress = '';
        $payerCity = '';
        $selectedVariant = $this->selectedVariantForCharge($charge, true);
        $descriptionFull = $this->buildHubDescription($clan, $charge, $selectedVariant);
        $description = $this->sanitizeHubPayloadText($descriptionFull);

        // Keep amount format identical to parepristizu.com generator (for example, 90.00).
        $amountValue = number_format($amount, 2, '.', '');

        $model = '00';
        $callNumber = $this->normalizeOib((string)($clan->oib ?? null))
            ?? $this->sanitizeHubLine('CLAN-' . $clan->id . '-' . $charge->id, 22);
        // Keep purpose code empty (same behavior as parepristizu.com default),
        // because some mobile banking apps reject non-matching purpose codes.
        $intent = '';

        $lines = [
            'HRVHUB30',
            'EUR',
            $amountValue,
            $payerName,
            $payerAddress,
            $payerCity,
            $receiverName,
            $receiverAddress,
            $receiverCity,
            $iban,
            'HR' . $model,
            $callNumber,
            $intent,
            $description,
        ];

        return [
            'payload' => implode("\n", $lines) . "\n",
            'fields' => [
                'iznos' => number_format($amount, 2, ',', '.'),
                'iban' => $iban,
                'primatelj' => $receiverName,
                'adresa' => $receiverAddress . ', ' . $receiverCity,
                'opis' => $descriptionFull,
                'poziv_na_broj' => $callNumber,
                'model' => 'HR' . $model,
            ],
        ];
    }

    /**
     * Provjerava podržava li okruženje ili konfiguracija traženu mogućnost.
     */
    private function supportsPaymentTracking(): bool
    {
        return Schema::hasTable('site_settings')
            && Schema::hasColumn('site_settings', 'payment_tracking_enabled')
            && Schema::hasTable('membership_payment_options')
            && Schema::hasTable('membership_payment_option_prices')
            && Schema::hasTable('clan_payment_profiles')
            && Schema::hasTable('clan_payment_charges');
    }

    /**
     * Usklađuje katalog sezonskih opcija članarine (dvoranska/vanjska) prema pravilima aplikacije.
     */
    private function normalizeSeasonalOptionsCatalog(): void
    {
        if (!$this->supportsArchivedOptions()) {
            return;
        }

        $activeSeasonal = MembershipPaymentOption::query()
            ->where('period_type', 'seasonal')
            ->where('is_archived', false)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $hasOct = $activeSeasonal->contains(fn (MembershipPaymentOption $option): bool => $option->period_anchor === 'oct');
        $hasApr = $activeSeasonal->contains(fn (MembershipPaymentOption $option): bool => $option->period_anchor === 'apr');

        if (!$hasOct || !$hasApr) {
            $archivedSeasonal = MembershipPaymentOption::query()
                ->where('period_type', 'seasonal')
                ->where('is_archived', true)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get();

            if (!$hasOct) {
                $octOption = $archivedSeasonal->first(
                    fn (MembershipPaymentOption $option): bool => $option->period_anchor === 'oct'
                );
                if ($octOption !== null) {
                    $octOption->is_archived = false;
                    $octOption->is_enabled = true;
                    $octOption->save();
                    $hasOct = true;
                }
            }

            if (!$hasApr) {
                $aprOption = $archivedSeasonal->first(
                    fn (MembershipPaymentOption $option): bool => $option->period_anchor === 'apr'
                );
                if ($aprOption !== null) {
                    $aprOption->is_archived = false;
                    $aprOption->is_enabled = true;
                    $aprOption->save();
                    $hasApr = true;
                }
            }
        }

        if ($hasOct || $hasApr) {
            MembershipPaymentOption::query()
                ->where('period_type', 'seasonal')
                ->where('period_anchor', 'both')
                ->where('is_archived', false)
                ->update([
                    'is_archived' => true,
                    'is_enabled' => false,
                    'updated_at' => now(),
                ]);
        }
    }

    /**
     * Nakon promjene modela naplate osvježava profile svih članova koji koriste taj model.
     */
    private function syncProfilesForOption(int $optionId, bool $resetFuture = false): void
    {
        $option = MembershipPaymentOption::query()->find($optionId);
        if ($option !== null && $option->period_type === 'seasonal') {
            ClanPaymentProfile::query()
                ->with('paymentOption')
                ->whereHas('paymentOption', function ($query): void {
                    $query->where('period_type', 'seasonal');
                })
                ->chunkById(100, function (Collection $profiles) use ($resetFuture): void {
                    /** @var ClanPaymentProfile $profile */
                    foreach ($profiles as $profile) {
                        $this->syncProfileCharges($profile, $resetFuture);
                    }
                });

            return;
        }

        ClanPaymentProfile::query()
            ->with('paymentOption')
            ->where('membership_payment_option_id', $optionId)
            ->chunkById(100, function (Collection $profiles) use ($resetFuture): void {
                /** @var ClanPaymentProfile $profile */
                foreach ($profiles as $profile) {
                    $this->syncProfileCharges($profile, $resetFuture);
                }
            });
    }

    /**
     * Ponovno računa zaduženja člana prema aktivnom modelu plaćanja i važećem cjeniku.
     */
    private function syncProfileCharges(ClanPaymentProfile $profile, bool $resetFuture): void
    {
        $profile->loadMissing('paymentOption');

        $startDate = $profile->start_date?->copy()->startOfDay() ?? now()->startOfDay();
        $clanId = (int)$profile->clan_id;
        $option = $profile->paymentOption;

        if ($option === null || $option->period_type === 'exempt') {
            if ($resetFuture) {
                ClanPaymentCharge::query()
                    ->where('clan_id', $clanId)
                    ->where('source', self::SOURCE_AUTO)
                    ->whereIn('status', [self::STATUS_OPEN, self::STATUS_DELETED])
                    ->whereDate('period_end', '>=', $startDate->toDateString())
                    ->delete();
            }

            return;
        }

        $horizonEnd = $this->resolveAutoChargeHorizonEnd($option, now());

        if ($resetFuture) {
            ClanPaymentCharge::query()
                ->where('clan_id', $clanId)
                ->where('source', self::SOURCE_AUTO)
                ->whereIn('status', [self::STATUS_OPEN, self::STATUS_DELETED])
                ->whereDate('period_end', '>=', $startDate->toDateString())
                ->delete();
        }

        $periods = $option->period_type === 'seasonal'
            ? $this->generateSeasonalPeriodsForProfile($option, $startDate, $horizonEnd)
            : $this->generatePeriods($option, $startDate, $horizonEnd);
        $periodKeys = [];

        foreach ($periods as $period) {
            $periodKey = $period['period_key'];
            $periodKeys[] = $periodKey;

            $charge = ClanPaymentCharge::query()->firstOrNew([
                'clan_id' => $clanId,
                'source' => self::SOURCE_AUTO,
                'period_key' => $periodKey,
            ]);

            if (!$charge->exists) {
                $charge->created_by = $profile->updated_by ?? $profile->created_by;
                $charge->status = self::STATUS_OPEN;
            }

            if ($charge->status === self::STATUS_PAID) {
                continue;
            }

            if ($charge->status === self::STATUS_DELETED) {
                continue;
            }

            $periodStart = Carbon::parse($period['period_start']);
            $priceOptionId = isset($period['price_option_id']) && is_numeric($period['price_option_id'])
                ? (int)$period['price_option_id']
                : (int)$option->id;
            $priceReferenceDate = $periodStart->lt($startDate) ? $startDate->copy() : $periodStart->copy();
            $resolvedBaseAmount = $profile->membership_amount_override !== null
                ? (float)$profile->membership_amount_override
                : $this->resolvePriceForOptionDate($priceOptionId, $priceReferenceDate);

            $metadata = is_array($charge->metadata) ? $charge->metadata : [];
            $metadata['base_amount'] = round($resolvedBaseAmount, 2);
            $metadata['base_title'] = $period['title'];
            if (array_key_exists('payment_variant', $metadata) && $charge->status !== self::STATUS_PAID) {
                unset($metadata['payment_variant']);
            }

            $charge->clan_payment_profile_id = (int)$profile->id;
            $charge->membership_payment_option_id = $priceOptionId;
            $charge->period_start = $period['period_start'];
            $charge->period_end = $period['period_end'];
            $charge->due_date = $period['due_date'];
            $charge->title = $period['title'];
            $charge->description = $period['description'];
            $charge->amount = $resolvedBaseAmount;
            $charge->currency = 'EUR';
            $charge->status = self::STATUS_OPEN;
            $charge->paid_at = null;
            $charge->confirmed_by = null;
            $charge->updated_by = $profile->updated_by ?? $profile->created_by;
            $charge->metadata = $metadata;
            $charge->save();
        }

        ClanPaymentCharge::query()
            ->where('clan_id', $clanId)
            ->where('source', self::SOURCE_AUTO)
            ->where('status', self::STATUS_OPEN)
            ->whereDate('period_end', '>=', $startDate->toDateString())
            ->whereDate('period_start', '<=', $horizonEnd->toDateString())
            ->whereNotIn('period_key', $periodKeys)
            ->delete();
    }

    /**
     * Održava početno dugovanje člana kao zasebnu stavku u evidenciji plaćanja.
     */
    private function syncOpeningDebtCharge(ClanPaymentProfile $profile, int $adminUserId): void
    {
        $amount = (float)($profile->opening_debt_amount ?? 0);

        if ($amount <= 0) {
            ClanPaymentCharge::query()
                ->where('clan_id', (int)$profile->clan_id)
                ->where('source', self::SOURCE_OPENING)
                ->where('period_key', 'opening-debt')
                ->where('status', self::STATUS_OPEN)
                ->delete();

            return;
        }

        $charge = ClanPaymentCharge::query()->firstOrNew([
            'clan_id' => (int)$profile->clan_id,
            'source' => self::SOURCE_OPENING,
            'period_key' => 'opening-debt',
        ]);

        if (!$charge->exists) {
            $charge->created_by = $adminUserId > 0 ? $adminUserId : null;
            $charge->status = self::STATUS_OPEN;
        }

        if ($charge->status === self::STATUS_PAID) {
            return;
        }

        $startDate = $profile->start_date?->format('Y-m-d') ?? now()->toDateString();

        $charge->clan_payment_profile_id = (int)$profile->id;
        $charge->membership_payment_option_id = null;
        $charge->period_start = $startDate;
        $charge->period_end = $startDate;
        $charge->due_date = $startDate;
        $charge->title = 'Početno dugovanje';
        $charge->description = $profile->opening_debt_note;
        $charge->amount = $amount;
        $charge->currency = 'EUR';
        $charge->status = self::STATUS_OPEN;
        $charge->paid_at = null;
        $charge->confirmed_by = null;
        $charge->updated_by = $adminUserId > 0 ? $adminUserId : null;
        $charge->save();
    }

    /**
     * Generira skup podataka prema poslovnim pravilima.
     */
    private function generatePeriods(MembershipPaymentOption $option, Carbon $startDate, Carbon $horizonEnd): array
    {
        return match ($option->period_type) {
            'monthly' => $this->generateMonthlyPeriods($startDate, $horizonEnd),
            'seasonal' => $this->generateSeasonalPeriods($option, $startDate, $horizonEnd),
            'annual' => $this->generateAnnualPeriods($option, $startDate, $horizonEnd),
            default => [],
        };
    }

    /**
     * Određuje krajnji datum do kojeg unaprijed generiramo automatske stavke članarine.
     */
    private function resolveAutoChargeHorizonEnd(MembershipPaymentOption $option, CarbonInterface $referenceDate): Carbon
    {
        $today = Carbon::instance($referenceDate)->startOfDay();
        $year = (int)$today->format('Y');
        $month = (int)$today->format('n');

        if ($option->period_type === 'monthly') {
            return $today->copy()->endOfMonth();
        }

        if ($option->period_type === 'seasonal') {
            if ($month >= 10) {
                return Carbon::create($year + 1, 3, 31)->endOfDay();
            }

            if ($month >= 4) {
                return Carbon::create($year, 9, 30)->endOfDay();
            }

            return Carbon::create($year, 3, 31)->endOfDay();
        }

        if ($option->period_type === 'annual') {
            if ((string)$option->period_anchor === 'oct') {
                $startYear = $month >= 10 ? $year : $year - 1;
                return Carbon::create($startYear + 1, 9, 30)->endOfDay();
            }

            $startYear = $month >= 4 ? $year : $year - 1;
            return Carbon::create($startYear + 1, 3, 31)->endOfDay();
        }

        return $today->copy()->endOfDay();
    }

    /**
     * Generira skup podataka prema poslovnim pravilima.
     */
    private function generateMonthlyPeriods(Carbon $startDate, Carbon $horizonEnd): array
    {
        $periods = [];
        $cursor = $startDate->copy()->firstOfMonth();

        while ($cursor->lte($horizonEnd)) {
            $periodStart = $cursor->copy()->startOfMonth();
            $periodEnd = $cursor->copy()->endOfMonth();

            if ($periodEnd->lt($startDate)) {
                $cursor->addMonthNoOverflow()->startOfMonth();
                continue;
            }

            $periods[] = [
                'period_key' => 'monthly-' . $periodStart->format('Y-m'),
                'period_start' => $periodStart->toDateString(),
                'period_end' => $periodEnd->toDateString(),
                'due_date' => $periodStart->toDateString(),
                'title' => 'Mjesečna članarina ' . $periodStart->format('m/Y'),
                'description' => null,
            ];

            $cursor->addMonthNoOverflow()->startOfMonth();
        }

        return $periods;
    }

    /**
     * Generira skup podataka prema poslovnim pravilima.
     */
    private function generateSeasonalPeriods(MembershipPaymentOption $option, Carbon $startDate, Carbon $horizonEnd): array
    {
        $periods = [];
        $startYear = (int)$startDate->copy()->subYear()->format('Y');
        $endYear = (int)$horizonEnd->copy()->addYear()->format('Y');

        for ($year = $startYear; $year <= $endYear; $year++) {
            $seasonDefinitions = [];

            if ($option->period_anchor === 'oct' || $option->period_anchor === 'both') {
                $seasonDefinitions[] = [
                    'period_start' => Carbon::create($year, 10)->startOfDay(),
                    'period_end' => Carbon::create($year + 1, 3, 31)->endOfDay(),
                    'period_key' => 'season-oct-' . $year,
                    'title' => 'Dvoranska sezona ' . $year . '/' . ($year + 1),
                ];
            }

            if ($option->period_anchor === 'apr' || $option->period_anchor === 'both') {
                $seasonDefinitions[] = [
                    'period_start' => Carbon::create($year, 4)->startOfDay(),
                    'period_end' => Carbon::create($year, 9, 30)->endOfDay(),
                    'period_key' => 'season-apr-' . $year,
                    'title' => 'Vanjska sezona ' . $year,
                ];
            }

            foreach ($seasonDefinitions as $seasonDefinition) {
                $periodStart = $seasonDefinition['period_start'];
                $periodEnd = $seasonDefinition['period_end'];

                if ($periodEnd->lt($startDate) || $periodStart->gt($horizonEnd)) {
                    continue;
                }

                $periods[] = [
                    'period_key' => $seasonDefinition['period_key'],
                    'period_start' => $periodStart->toDateString(),
                    'period_end' => $periodEnd->toDateString(),
                    'due_date' => $periodStart->toDateString(),
                    'title' => $seasonDefinition['title'],
                    'description' => null,
                ];
            }
        }

        usort($periods, static function (array $a, array $b): int {
            $byStart = strcmp((string)$a['period_start'], (string)$b['period_start']);
            if ($byStart !== 0) {
                return $byStart;
            }

            return strcmp((string)$a['period_key'], (string)$b['period_key']);
        });

        return $periods;
    }

    /**
     * Generira skup podataka prema poslovnim pravilima.
     */
    private function generateSeasonalPeriodsForProfile(MembershipPaymentOption $selectedOption, Carbon $startDate, Carbon $horizonEnd): array
    {
        $indoorOption = $this->resolveSeasonalOptionForAnchor($selectedOption, 'oct');
        $outdoorOption = $this->resolveSeasonalOptionForAnchor($selectedOption, 'apr');

        $periods = [];
        $startYear = (int)$startDate->copy()->subYear()->format('Y');
        $endYear = (int)$horizonEnd->copy()->addYear()->format('Y');

        for ($year = $startYear; $year <= $endYear; $year++) {
            $indoorStart = Carbon::create($year, 10)->startOfDay();
            $indoorEnd = Carbon::create($year + 1, 3, 31)->endOfDay();
            if (!$indoorEnd->lt($startDate) && !$indoorStart->gt($horizonEnd)) {
                $periods[] = [
                    'period_key' => 'season-oct-' . $year,
                    'period_start' => $indoorStart->toDateString(),
                    'period_end' => $indoorEnd->toDateString(),
                    'due_date' => $indoorStart->toDateString(),
                    'title' => 'Dvoranska sezona ' . $year . '/' . ($year + 1),
                    'description' => null,
                    'price_option_id' => (int)$indoorOption->id,
                ];
            }

            $outdoorStart = Carbon::create($year, 4)->startOfDay();
            $outdoorEnd = Carbon::create($year, 9, 30)->endOfDay();
            if (!$outdoorEnd->lt($startDate) && !$outdoorStart->gt($horizonEnd)) {
                $periods[] = [
                    'period_key' => 'season-apr-' . $year,
                    'period_start' => $outdoorStart->toDateString(),
                    'period_end' => $outdoorEnd->toDateString(),
                    'due_date' => $outdoorStart->toDateString(),
                    'title' => 'Vanjska sezona ' . $year,
                    'description' => null,
                    'price_option_id' => (int)$outdoorOption->id,
                ];
            }
        }

        usort($periods, static function (array $a, array $b): int {
            $byStart = strcmp((string)$a['period_start'], (string)$b['period_start']);
            if ($byStart !== 0) {
                return $byStart;
            }

            return strcmp((string)$a['period_key'], (string)$b['period_key']);
        });

        return $periods;
    }

    /**
     * Za traženo sidro sezone (`apr`/`oct`) pronalazi odgovarajuću opciju članarine.
     */
    private function resolveSeasonalOptionForAnchor(MembershipPaymentOption $selectedOption, string $anchor): MembershipPaymentOption
    {
        $normalizedAnchor = $anchor === 'apr' ? 'apr' : 'oct';

        $query = MembershipPaymentOption::query()
            ->where('period_type', 'seasonal');

        if ($this->supportsArchivedOptions()) {
            $query->where('is_archived', false);
        }

        $seasonalOptions = $query
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $matchByAnchor = $seasonalOptions->first(
            fn (MembershipPaymentOption $option): bool => $option->period_anchor === $normalizedAnchor
        );
        if ($matchByAnchor !== null) {
            return $matchByAnchor;
        }

        $matchByBoth = $seasonalOptions->first(
            fn (MembershipPaymentOption $option): bool => $option->period_anchor === 'both'
        );
        if ($matchByBoth !== null) {
            return $matchByBoth;
        }

        $selectedAnchor = (string)($selectedOption->period_anchor ?? '');
        if ($selectedAnchor === $normalizedAnchor || $selectedAnchor === 'both') {
            return $selectedOption;
        }

        $fallback = $seasonalOptions->first();
        return $fallback instanceof MembershipPaymentOption ? $fallback : $selectedOption;
    }

    /**
     * Generira skup podataka prema poslovnim pravilima.
     */
    private function generateAnnualPeriods(MembershipPaymentOption $option, Carbon $startDate, Carbon $horizonEnd): array
    {
        $periods = [];
        $startYear = (int)$startDate->copy()->subYear()->format('Y');
        $endYear = (int)$horizonEnd->copy()->addYear()->format('Y');

        for ($year = $startYear; $year <= $endYear; $year++) {
            if ($option->period_anchor === 'oct') {
                $periodStart = Carbon::create($year, 10)->startOfDay();
                $periodEnd = Carbon::create($year + 1, 9, 30)->endOfDay();
                $periodKey = 'annual-oct-' . $year;
                $title = 'Godišnja članarina ' . $year . '/' . ($year + 1) . ' (01.10.-30.09.)';
            } else {
                $periodStart = Carbon::create($year, 4)->startOfDay();
                $periodEnd = Carbon::create($year + 1, 3, 31)->endOfDay();
                $periodKey = 'annual-apr-' . $year;
                $title = 'Godišnja članarina ' . $year . '/' . ($year + 1) . ' (01.04.-31.03.)';
            }

            if ($periodEnd->lt($startDate) || $periodStart->gt($horizonEnd)) {
                continue;
            }

            $periods[] = [
                'period_key' => $periodKey,
                'period_start' => $periodStart->toDateString(),
                'period_end' => $periodEnd->toDateString(),
                'due_date' => $periodStart->toDateString(),
                'title' => $title,
                'description' => null,
            ];
        }

        return $periods;
    }

    /**
     * Vraća cijenu opcije koja je vrijedila na zadani datum.
     */
    private function resolvePriceForOptionDate(int $optionId, Carbon $date): float
    {
        $price = MembershipPaymentOptionPrice::query()
            ->where('membership_payment_option_id', $optionId)
            ->whereDate('valid_from', '<=', $date->toDateString())
            ->orderByDesc('valid_from')
            ->orderByDesc('id')
            ->first();

        if ($price === null) {
            return 0;
        }

        return (float)$price->amount;
    }

    /**
     * Vraća osnovni iznos stavke prije primjene varijanti (puna/podupirući).
     */
    private function baseAmountForCharge(ClanPaymentCharge $charge): float
    {
        $metadata = is_array($charge->metadata) ? $charge->metadata : [];
        if (isset($metadata['base_amount']) && is_numeric($metadata['base_amount'])) {
            return round((float)$metadata['base_amount'], 2);
        }

        return round((float)$charge->amount, 2);
    }

    /**
     * Validira traženu varijantu uplate i vraća važeću fallback varijantu ako je potrebno.
     */
    private function resolveVariantForCharge(ClanPaymentCharge $charge, ?string $variant): ?string
    {
        $availableValues = array_map(
            static fn (array $option): string => (string)($option['value'] ?? ''),
            $this->availableVariantsForCharge($charge)
        );

        if ($variant !== null && in_array($variant, $availableValues, true)) {
            return $variant;
        }

        if (in_array(self::VARIANT_FULL, $availableValues, true)) {
            return self::VARIANT_FULL;
        }

        return null;
    }

    /**
     * Izračunava vrijednosti prema definiranim formulama i pravilima.
     */
    private function calculateVariantAmount(float $baseAmount, ?string $variant): float
    {
        if ($variant === null) {
            return round($baseAmount, 2);
        }

        return match ($variant) {
            self::VARIANT_SUPPORTING,
            self::VARIANT_ANNUAL_SUPPORTING_BOTH => round($baseAmount * self::SUPPORTING_MEMBER_RATIO, 2),
            self::VARIANT_ANNUAL_SUPPORTING_INDOOR,
            self::VARIANT_ANNUAL_SUPPORTING_OUTDOOR => round($baseAmount * self::ANNUAL_MIXED_SUPPORTING_RATIO, 2),
            default => round($baseAmount, 2),
        };
    }

    /**
     * Sastavlja složeniju strukturu podataka iz više izvora.
     */
    private function buildChargeTitleForVariant(string $baseTitle, ?string $variant): string
    {
        if (!$this->isSupportingVariant($variant)) {
            return $baseTitle;
        }

        return str_contains($baseTitle, 'podupirući član')
            ? $baseTitle
            : $baseTitle . ' - podupirući član';
    }

    /**
     * Provjerava pripada li odabrana varijanta podupirućem članstvu.
     */
    private function isSupportingVariant(?string $variant): bool
    {
        return in_array($variant, [
            self::VARIANT_SUPPORTING,
            self::VARIANT_ANNUAL_SUPPORTING_INDOOR,
            self::VARIANT_ANNUAL_SUPPORTING_OUTDOOR,
            self::VARIANT_ANNUAL_SUPPORTING_BOTH,
        ], true);
    }

    /**
     * Provjerava naplaćuje li se odabrana opcija članarine gotovinom umjesto preko računa.
     */
    private function optionUsesCash(?MembershipPaymentOption $option): bool
    {
        if ($option === null) {
            return false;
        }

        return $this->supportsCollectionMethod()
            && $this->normalizeCollectionMethod($option->collection_method ?? null) === self::COLLECTION_CASH;
    }

    /**
     * Provjerava podržava li okruženje ili konfiguracija traženu mogućnost.
     */
    private function supportsCollectionMethod(): bool
    {
        return Schema::hasTable('membership_payment_options')
            && Schema::hasColumn('membership_payment_options', 'collection_method');
    }

    /**
     * Provjerava podržava li okruženje ili konfiguracija traženu mogućnost.
     */
    private function supportsArchivedOptions(): bool
    {
        if ($this->optionArchivedColumnSupported === null) {
            $this->optionArchivedColumnSupported = Schema::hasTable('membership_payment_options')
                && Schema::hasColumn('membership_payment_options', 'is_archived');
        }

        return $this->optionArchivedColumnSupported;
    }

    /**
     * Iz payload-a forme izvlači podatke za konkretnu opciju članarine (po ID-u ili ključu).
     */
    private function resolveOptionPayload(array $optionsPayload, MembershipPaymentOption $option): array
    {
        $payloadById = $optionsPayload[(string)$option->id] ?? $optionsPayload[$option->id] ?? null;
        if (is_array($payloadById)) {
            return $payloadById;
        }

        $payloadByKey = $optionsPayload[$option->key] ?? null;
        if (is_array($payloadByKey)) {
            return $payloadByKey;
        }

        return [];
    }

    /**
     * Normalizira tip razdoblja i sidro sezone za postavke modela članarine.
     */
    private function normalizePeriodSettings(string $periodType, mixed $periodAnchor): array
    {
        $normalizedPeriodType = trim($periodType);
        if (!in_array($normalizedPeriodType, ['monthly', 'seasonal', 'annual', 'exempt'], true)) {
            throw new InvalidArgumentException('Tip razdoblja nije ispravan.');
        }

        $normalizedPeriodAnchor = null;
        if ($normalizedPeriodType === 'seasonal') {
            $anchor = trim((string)$periodAnchor);
            $normalizedPeriodAnchor = $anchor === 'apr' ? 'apr' : 'oct';
        } elseif ($normalizedPeriodType === 'annual') {
            $anchor = trim((string)$periodAnchor);
            $normalizedPeriodAnchor = $anchor === 'oct' ? 'oct' : 'apr';
        }

        return [
            'period_type' => $normalizedPeriodType,
            'period_anchor' => $normalizedPeriodAnchor,
        ];
    }

    /**
     * Normalizira novčani iznos iz forme u decimalni broj s dvije decimale.
     */
    private function normalizeAmount(mixed $value): ?float
    {
        if ($value === null) {
            return null;
        }

        $normalized = trim((string)$value);
        if ($normalized === '') {
            return null;
        }

        $normalized = str_replace([' ', ','], ['', '.'], $normalized);
        if (!is_numeric($normalized)) {
            return null;
        }

        return round((float)$normalized, 2);
    }

    /**
     * Normalizira način naplate na podržane vrijednosti (`bank` ili `cash`).
     */
    private function normalizeCollectionMethod(mixed $value): string
    {
        $normalized = trim((string)$value);
        return $normalized === self::COLLECTION_CASH
            ? self::COLLECTION_CASH
            : self::COLLECTION_BANK;
    }

    /**
     * Normalizira datum iz forme u standardni format `Y-m-d`.
     */
    private function normalizeDate(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = trim($value);
        if ($normalized === '') {
            return null;
        }

        try {
            return Carbon::parse($normalized)->toDateString();
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Čisti tekstualni unos i pretvara prazne vrijednosti u `null`.
     */
    private function normalizeText(mixed $value): ?string
    {
        $normalized = trim((string)$value);

        return $normalized === '' ? null : $normalized;
    }

    /**
     * Čisti i validira OIB člana za potrebe HUB/poziva na broj.
     */
    private function normalizeOib(?string $oib): ?string
    {
        $normalized = preg_replace('/\D+/', '', (string)$oib);

        return strlen((string)$normalized) === 11 ? (string)$normalized : null;
    }

    /**
     * Sastavlja složeniju strukturu podataka iz više izvora.
     */
    private function buildHubDescription(Clanovi $clan, ClanPaymentCharge $charge, ?string $variant): string
    {
        $payer = trim(($clan->Ime ?? '') . ' ' . ($clan->Prezime ?? ''));
        $description = 'Plaćanje za: ' . $payer . ' za: ' . $this->resolveHubPeriodLabel($charge);

        if ($this->isSupportingVariant($variant)) {
            $description .= ' - članstvo';
        }

        return $description;
    }

    /**
     * Generira čitljiv naziv razdoblja (sezona/godina) koji ide u HUB opis plaćanja.
     */
    private function resolveHubPeriodLabel(ClanPaymentCharge $charge): string
    {
        $periodKey = (string)($charge->period_key ?? '');
        if (preg_match('/^season-apr-(\d{4})$/', $periodKey, $matches) === 1) {
            return 'Vanjska sezona za godinu ' . $matches[1] . '.';
        }

        if (preg_match('/^season-oct-(\d{4})$/', $periodKey, $matches) === 1) {
            $year = (int)$matches[1];
            return 'Dvoranska sezona za godinu ' . $year . '/' . ($year + 1) . '.';
        }

        if (preg_match('/^annual-(apr|oct)-(\d{4})$/', $periodKey, $matches) === 1) {
            $year = (int)$matches[2];
            return 'Godišnja članarina za godinu ' . $year . '/' . ($year + 1) . '.';
        }

        return $this->normalizeText($charge->title) ?? 'članarina';
    }

    /**
     * Validira ulaz i sprema promjene prema pravilima modula članarina članova.
     */
    private function splitAddress(string $address): array
    {
        $normalized = trim($address);
        if ($normalized === '') {
            return ['', ''];
        }

        $parts = array_values(array_filter(array_map('trim', explode(',', $normalized)), fn (string $part): bool => $part !== ''));
        if (count($parts) >= 2) {
            return [$parts[0], $parts[1]];
        }

        return [$normalized, ''];
    }

    /**
     * Normalizira IBAN (velika slova, bez razmaka) i čisti ga za HUB zapis.
     */
    private function normalizeIban(?string $iban): ?string
    {
        $normalized = strtoupper(str_replace(' ', '', (string)$iban));
        if ($normalized === '') {
            return null;
        }

        return $this->sanitizeHubLine($normalized, 34);
    }

    /**
     * Čisti jedan HUB red i skraćuje ga na dopuštenu duljinu.
     */
    private function sanitizeHubLine(string $value, int $maxLength): string
    {
        $normalized = trim(preg_replace('/\s+/', ' ', $value) ?? $value);

        if ($normalized === '') {
            return '';
        }

        if (mb_strlen($normalized) <= $maxLength) {
            return $normalized;
        }

        return mb_substr($normalized, 0, $maxLength);
    }

    /**
     * Čisti tekst HUB payload-a od nedopuštenih znakova i predugih vrijednosti.
     */
    private function sanitizeHubPayloadText(string $value): string
    {
        $normalized = str_replace(["\r\n", "\r", "\n"], ' ', $value);
        $normalized = trim((string)(preg_replace('/\s+/u', ' ', $normalized) ?? $normalized));
        if ($normalized === '') {
            return '';
        }

        return preg_replace('/[\x00-\x1F\x7F]/u', '', $normalized) ?? $normalized;
    }
}
