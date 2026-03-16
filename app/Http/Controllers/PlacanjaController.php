<?php

namespace App\Http\Controllers;

use App\Models\ClanPaymentCharge;
use App\Models\Clanovi;
use App\Models\PolaznikPaymentCharge;
use App\Services\PaymentTrackingService;
use App\Services\SchoolPaymentService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Admin i korisnički kontroler za praćenje članarina, dodatnih zaduženja, izvještaje i generiranje barkoda.
 */
class PlacanjaController extends Controller
{
    /**
     * Učitava servise za članarine, školarine i generiranje barkoda uplata.
     */
    public function __construct(private readonly PaymentTrackingService $paymentTrackingService)
    {
    }

    /**
     * Prikazuje admin nadzor plaćanja: setup, filtere, statistiku i tablice dugovanja/uplata.
     */
    public function adminIndex(Request $request): View
    {
        return view('admin.placanja.index', $this->buildAdminReportData($request));
    }

    /**
     * Generira i vraća CSV izvještaj plaćanja prema trenutno odabranim filterima i opsegu.
     */
    public function exportCsv(Request $request): StreamedResponse
    {
        $scope = trim((string)$request->query('scope', 'rows'));
        if (!in_array($scope, ['rows', 'debtors', 'persons'], true)) {
            $scope = 'rows';
        }

        $reportData = $this->buildAdminReportData($request, false);
        $rows = collect();
        $headers = [];
        $filenamePrefix = 'placanja_stavke';

        if ($scope === 'debtors') {
            $filenamePrefix = 'placanja_duznici';
            $rows = collect($reportData['debtorsSummary'] ?? []);
            $headers = ['Osoba', 'Tip', 'Račun (€)', 'Gotovina (€)', 'Ukupno (€)', 'Stavki'];
        } elseif ($scope === 'persons') {
            $filenamePrefix = 'placanja_sazetak_po_osobi';
            $rows = collect($reportData['personsSummary'] ?? []);
            $headers = ['Osoba', 'Tip', 'Uplaćeno (€)', 'Otvoreno (€)', 'Uplaćeno račun (€)', 'Uplaćeno gotovina (€)', 'Stavki'];
        } else {
            $rows = collect($reportData['reportRows'] ?? []);
            $headers = ['Datum', 'Osoba', 'Tip', 'Model', 'Naziv stavke', 'Razdoblje', 'Naplata', 'Iznos (€)', 'Status'];
        }

        $filename = $filenamePrefix . '_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($rows, $headers, $scope) {
            $output = fopen('php://output', 'wb');
            if ($output === false) {
                return;
            }

            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, $headers, ';');

            foreach ($rows as $row) {
                if ($scope === 'debtors') {
                    fputcsv($output, [
                        (string)($row['person_name'] ?? ''),
                        (string)(($row['entity_type'] ?? '') === 'school' ? 'Polaznik škole' : 'Član'),
                        number_format((float)($row['open_bank'] ?? 0), 2, ',', '.'),
                        number_format((float)($row['open_cash'] ?? 0), 2, ',', '.'),
                        number_format((float)($row['open_total'] ?? 0), 2, ',', '.'),
                        (int)($row['items_count'] ?? 0),
                    ], ';');
                    continue;
                }

                if ($scope === 'persons') {
                    fputcsv($output, [
                        (string)($row['person_name'] ?? ''),
                        (string)(($row['entity_type'] ?? '') === 'school' ? 'Polaznik škole' : 'Član'),
                        number_format((float)($row['paid_total'] ?? 0), 2, ',', '.'),
                        number_format((float)($row['open_total'] ?? 0), 2, ',', '.'),
                        number_format((float)($row['paid_bank'] ?? 0), 2, ',', '.'),
                        number_format((float)($row['paid_cash'] ?? 0), 2, ',', '.'),
                        (int)($row['items_count'] ?? 0),
                    ], ';');
                    continue;
                }

                fputcsv($output, [
                    (string)($row['reference_date_label'] ?? ''),
                    (string)($row['person_name'] ?? ''),
                    (string)(($row['entity_type'] ?? '') === 'school' ? 'Polaznik škole' : 'Član'),
                    (string)($row['model_name'] ?? ''),
                    (string)($row['title'] ?? ''),
                    (string)($row['period_label'] ?? ''),
                    (string)(($row['channel'] ?? '') === PaymentTrackingService::COLLECTION_CASH ? 'Gotovina' : 'Račun'),
                    number_format((float)($row['amount'] ?? 0), 2, ',', '.'),
                    (string)($row['status_label'] ?? ''),
                ], ';');
            }

            fclose($output);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Sprema globalne postavke praćenja članarina i školarine.
     */
    public function updateSetup(Request $request): RedirectResponse
    {
        if ($request->filled('delete_option_id')) {
            $deleteValidated = $request->validate([
                'delete_option_id' => ['required', 'integer', 'exists:membership_payment_options,id'],
            ], [
                'delete_option_id.exists' => 'Odabrana vrsta plaćanja nije pronađena.',
            ]);

            try {
                $this->paymentTrackingService->archiveOption((int)$deleteValidated['delete_option_id']);
            } catch (\RuntimeException|\InvalidArgumentException $exception) {
                return redirect()
                    ->route('admin.placanja.index', ['open_payment_setup' => 1])
                    ->with('error', $exception->getMessage());
            }

            return redirect()
                ->route('admin.placanja.index', ['open_payment_setup' => 1])
                ->with('success', 'Vrsta plaćanja je uklonjena iz admin popisa.');
        }

        $validated = $request->validate([
            'payment_tracking_enabled' => ['nullable', 'boolean'],
            'school_tuition_adult_amount' => ['nullable', 'string', 'max:32'],
            'school_tuition_minor_amount' => ['nullable', 'string', 'max:32'],
            'options' => ['nullable', 'array'],
            'options.*.name' => ['nullable', 'string', 'max:191'],
            'options.*.description' => ['nullable', 'string', 'max:2000'],
            'options.*.period_type' => ['nullable', 'in:monthly,seasonal,annual,exempt'],
            'options.*.period_anchor' => ['nullable', 'in:oct,apr'],
            'options.*.collection_method' => ['nullable', 'in:bank,cash'],
            'options.*.enabled' => ['nullable', 'boolean'],
            'options.*.amount' => ['nullable', 'string', 'max:32'],
            'options.*.valid_from' => ['nullable', 'date'],
        ], [
            'options.*.period_type.in' => 'Tip razdoblja nije ispravan.',
            'options.*.period_anchor.in' => 'Sidro razdoblja mora biti dvoranska ili vanjska sezona.',
            'options.*.collection_method.in' => 'Način naplate mora biti račun ili gotovina.',
            'options.*.valid_from.date' => 'Datum cijene nije ispravan.',
        ]);

        $validated['payment_tracking_enabled'] = $request->boolean('payment_tracking_enabled');

        try {
            $this->paymentTrackingService->updateSetup($validated, (int)auth()->id());
        } catch (\InvalidArgumentException $exception) {
            return redirect()
                ->route('admin.placanja.index', ['open_payment_setup' => 1])
                ->with('error', $exception->getMessage())
                ->withInput();
        }

        return redirect()
            ->route('admin.placanja.index', ['open_payment_setup' => 1])
            ->with('success', 'Postavke praćenja plaćanja su spremljene.');
    }

    /**
     * Validira ulaz i sprema promjene prema pravilima modula praćenja članarina i ostalih uplata.
     */
    public function saveClanProfile(Request $request, Clanovi $clan): RedirectResponse
    {
        $validated = $request->validate([
            'membership_payment_option_id' => ['nullable', 'integer', 'exists:membership_payment_options,id'],
            'start_date' => ['required', 'date'],
        ], [
            'membership_payment_option_id.exists' => 'Odabrani model plaćanja nije pronađen.',
            'start_date.required' => 'Potrebno je unijeti datum početka praćenja.',
            'start_date.date' => 'Datum početka praćenja nije ispravan.',
        ]);

        $this->paymentTrackingService->assignProfileToClan($clan, $validated, (int)auth()->id());

        return redirect()
            ->route('admin.clanovi.prikaz_clana', ['clan' => $clan, 'open_payments' => 1])
            ->with('success', 'Podaci o plaćanjima člana su spremljeni.');
    }

    /**
     * Validira ulaz i sprema promjene prema pravilima modula praćenja članarina i ostalih uplata.
     */
    public function addOption(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:191'],
            'description' => ['nullable', 'string', 'max:2000'],
            'period_type' => ['required', 'in:monthly,seasonal,annual,exempt'],
            'period_anchor' => ['nullable', 'in:oct,apr'],
            'collection_method' => ['nullable', 'in:bank,cash'],
            'enabled' => ['nullable', 'boolean'],
            'amount' => ['nullable', 'string', 'max:32'],
            'valid_from' => ['nullable', 'date'],
        ], [
            'name.required' => 'Naziv vrste plaćanja je obavezan.',
            'period_type.required' => 'Potrebno je odabrati tip razdoblja.',
            'period_type.in' => 'Tip razdoblja nije ispravan.',
            'period_anchor.in' => 'Sidro razdoblja mora biti dvoranska ili vanjska sezona.',
            'collection_method.in' => 'Način naplate mora biti račun ili gotovina.',
            'valid_from.date' => 'Datum važenja nije ispravan.',
        ]);

        $validated['enabled'] = $request->boolean('enabled');

        if (($validated['period_type'] ?? '') !== 'exempt' && empty(trim((string)($validated['amount'] ?? '')))) {
            return redirect()
                ->route('admin.placanja.index', ['open_payment_setup' => 1])
                ->with('error', 'Za novu vrstu plaćanja potrebno je unijeti početni iznos.')
                ->withInput();
        }

        try {
            $this->paymentTrackingService->createOption($validated, (int)auth()->id());
        } catch (\InvalidArgumentException $exception) {
            return redirect()
                ->route('admin.placanja.index', ['open_payment_setup' => 1])
                ->with('error', $exception->getMessage())
                ->withInput();
        }

        return redirect()
            ->route('admin.placanja.index', ['open_payment_setup' => 1])
            ->with('success', 'Nova vrsta plaćanja je dodana.');
    }

    /**
     * Validira ulaz i sprema promjene prema pravilima modula praćenja članarina i ostalih uplata.
     */
    public function addManualCharge(Request $request, Clanovi $clan): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:191'],
            'description' => ['nullable', 'string', 'max:2000'],
            'amount' => ['required', 'string', 'max:32'],
            'due_date' => ['nullable', 'date'],
        ], [
            'title.required' => 'Naziv dodatnog plaćanja je obavezan.',
            'amount.required' => 'Iznos dodatnog plaćanja je obavezan.',
            'due_date.date' => 'Datum zaduženja nije ispravan.',
        ]);

        try {
            $this->paymentTrackingService->createManualCharge($clan, $validated, (int)auth()->id());
        } catch (\InvalidArgumentException $exception) {
            return redirect()
                ->route('admin.clanovi.prikaz_clana', ['clan' => $clan, 'open_payments' => 1])
                ->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('admin.clanovi.prikaz_clana', ['clan' => $clan, 'open_payments' => 1])
            ->with('success', 'Dodatno plaćanje je spremljeno.');
    }

    /**
     * Potvrđuje ili poništava uplatu za odabrano zaduženje člana.
     */
    public function updateChargeStatus(Request $request, Clanovi $clan, ClanPaymentCharge $charge): RedirectResponse
    {
        if ((int)$charge->clan_id !== (int)$clan->id) {
            abort(404);
        }

        $validated = $request->validate([
            'is_paid' => ['nullable', 'boolean'],
            'paid_at' => ['nullable', 'date'],
            'payment_variant' => ['nullable', 'string', 'max:64'],
            'amount' => ['nullable', 'string', 'max:32'],
        ], [
            'paid_at.date' => 'Datum uplate nije ispravan.',
            'amount.max' => 'Iznos je predugačak.',
        ]);

        $isPaid = (bool)($validated['is_paid'] ?? false);
        $paidAt = is_string($validated['paid_at'] ?? null) ? $validated['paid_at'] : null;
        $paymentVariant = is_string($validated['payment_variant'] ?? null)
            ? trim($validated['payment_variant'])
            : null;
        $amount = is_string($validated['amount'] ?? null)
            ? trim($validated['amount'])
            : null;

        if ($isPaid && $amount !== null && $amount !== '') {
            $normalizedAmount = str_replace([' ', ','], ['', '.'], $amount);
            if (!is_numeric($normalizedAmount) || (float)$normalizedAmount <= 0) {
                return redirect()
                    ->route('admin.clanovi.prikaz_clana', ['clan' => $clan, 'open_payments' => 1])
                    ->with('error', 'Iznos uplate mora biti broj veći od 0.');
            }
        }

        $this->paymentTrackingService->updateChargeStatus($charge, $isPaid, $paidAt, (int)auth()->id(), $paymentVariant, $amount);

        return redirect()
            ->route('admin.clanovi.prikaz_clana', ['clan' => $clan, 'open_payments' => 1])
            ->with('success', 'Status plaćanja je ažuriran.');
    }

    /**
     * Sprema odabranu varijantu uplate (članarina/podupirući član) za konkretan dug.
     */
    public function updatePreferredVariant(Request $request, Clanovi $clan, ClanPaymentCharge $charge): RedirectResponse|JsonResponse
    {
        if (!auth()->check() || !auth()->user()->mozePregledavatiClana((int)$clan->id)) {
            abort(403);
        }

        if ((int)$charge->clan_id !== (int)$clan->id) {
            abort(404);
        }

        $validated = $request->validate([
            'payment_variant' => ['nullable', 'string', 'max:64'],
        ]);

        $variant = is_string($validated['payment_variant'] ?? null)
            ? trim($validated['payment_variant'])
            : null;

        $this->paymentTrackingService->setPreferredVariantForCharge($charge, $variant);

        if ($request->boolean('ajax') || $request->expectsJson() || $request->ajax()) {
            $charge->refresh();

            return response()->json(
                $this->memberPaymentsUiPayload($clan, $charge)
            );
        }

        return redirect()
            ->route('javno.clanovi.placanja', ['clan' => $clan, 'charge' => $charge->id]);
    }

    /**
     * Briše ručno uneseno zaduženje člana iz evidencije plaćanja.
     */
    public function destroyCharge(Clanovi $clan, ClanPaymentCharge $charge): RedirectResponse
    {
        if ((int)$charge->clan_id !== (int)$clan->id) {
            abort(404);
        }

        try {
            $this->paymentTrackingService->deleteCharge($charge, (int)auth()->id());
        } catch (\InvalidArgumentException $exception) {
            return redirect()
                ->route('admin.clanovi.prikaz_clana', ['clan' => $clan, 'open_payments' => 1])
                ->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('admin.clanovi.prikaz_clana', ['clan' => $clan, 'open_payments' => 1])
            ->with('success', 'Stavka plaćanja je obrisana.');
    }

    /**
     * Dohvaća detalje jedne stavke i priprema ih za prikaz.
     */
    public function showMemberPayments(Request $request, Clanovi $clan): View|JsonResponse
    {
        if (!auth()->check() || !auth()->user()->mozePregledavatiClana((int)$clan->id)) {
            abort(403);
        }

        $summary = $this->paymentTrackingService->memberSummary($clan);
        if (!($summary['enabled'] ?? false)) {
            abort(404);
        }

        $notice = $this->paymentTrackingService->noticeForClan($clan);
        $unpaidCharges = $summary['unpaidCharges'] ?? collect();
        $selectedChargeId = (int)$request->query('charge', 0);
        $nextCharge = $unpaidCharges->first(
            fn ($charge): bool => (int)$charge->id === $selectedChargeId
        );
        if (!$nextCharge instanceof ClanPaymentCharge) {
            $nextCharge = $summary['nextUnpaidCharge'] ?? null;
        }
        $selectedChargeId = $nextCharge instanceof ClanPaymentCharge ? (int)$nextCharge->id : null;

        $uiPayload = $this->memberPaymentsUiPayload($clan, $nextCharge);

        if ($request->boolean('ajax') || $request->expectsJson() || $request->ajax()) {
            return response()->json($uiPayload);
        }

        return view('javno.placanjaClana', [
            'clan' => $clan,
            'paymentSummary' => $summary,
            'paymentNotice' => $notice,
            'nextCharge' => $nextCharge,
            'paymentHubData' => $uiPayload['paymentHubData'] ?? null,
            'nextChargeIsCashCollection' => (bool)($uiPayload['isCashCollection'] ?? false),
            'paymentInfoClanakId' => $this->paymentTrackingService->paymentInfoArticleId(),
            'nextChargeVariants' => $uiPayload['nextChargeVariants'] ?? [],
            'nextChargeSelectedVariant' => $uiPayload['nextChargeSelectedVariant'] ?? null,
            'nextChargeRestrictionNote' => $uiPayload['nextChargeRestrictionNote'] ?? null,
            'nextChargeEffectiveAmount' => $uiPayload['nextChargeEffectiveAmount'] ?? null,
            'selectedChargeId' => $selectedChargeId,
        ]);
    }

    /**
     * Priprema sve podatke za korisnički ekran „Moja plaćanja” (stavka, varijante, barkod, napomene).
     */
    private function memberPaymentsUiPayload(Clanovi $clan, ?ClanPaymentCharge $nextCharge): array
    {
        $hubData = $nextCharge instanceof ClanPaymentCharge
            ? $this->paymentTrackingService->buildHubPayloadForCharge($clan, $nextCharge)
            : null;
        $nextChargeVariants = $nextCharge instanceof ClanPaymentCharge
            ? $this->paymentTrackingService->availableVariantsForCharge($nextCharge)
            : [];
        $nextChargeSelectedVariant = $nextCharge instanceof ClanPaymentCharge
            ? $this->paymentTrackingService->selectedVariantForCharge($nextCharge, true)
            : null;
        $nextChargeRestrictionNote = $nextCharge instanceof ClanPaymentCharge
            ? $this->paymentTrackingService->restrictionNoteForCharge($nextCharge, $nextChargeSelectedVariant)
            : null;
        $nextChargeEffectiveAmount = $nextCharge instanceof ClanPaymentCharge
            ? $this->paymentTrackingService->resolvedChargeAmount($nextCharge, true)
            : null;

        return [
            'selectedChargeId' => $nextCharge instanceof ClanPaymentCharge ? (int)$nextCharge->id : null,
            'nextCharge' => $nextCharge instanceof ClanPaymentCharge ? [
                'id' => (int)$nextCharge->id,
                'title' => (string)$nextCharge->title,
            ] : null,
            'isCashCollection' => $nextCharge instanceof ClanPaymentCharge
                ? $this->paymentTrackingService->isCashCollectionForCharge($nextCharge)
                : false,
            'paymentHubData' => $hubData,
            'nextChargeVariants' => $nextChargeVariants,
            'nextChargeSelectedVariant' => $nextChargeSelectedVariant,
            'nextChargeRestrictionNote' => $nextChargeRestrictionNote,
            'nextChargeEffectiveAmount' => $nextChargeEffectiveAmount,
        ];
    }

    /**
     * Sastavlja složeniju strukturu podataka iz više izvora.
     */
    private function buildAdminReportData(Request $request, bool $applyRowsLimit = true): array
    {
        $paymentSetup = $this->paymentTrackingService->setupViewData();
        $schoolPaymentEnabled = app(SchoolPaymentService::class)->isEnabled();

        $periodPreset = (string)$request->query('period_preset', 'current_season');
        $statusFilter = (string)$request->query('status', 'all');
        $targetFilter = (string)$request->query('target', 'all');
        $channelFilter = (string)$request->query('channel', 'all');
        $modelTypeFilter = (string)$request->query('model_type', 'all');
        $rowsLimit = (int)$request->query('rows_limit', 500);
        if ($rowsLimit < 50) {
            $rowsLimit = 50;
        } elseif ($rowsLimit > 1000) {
            $rowsLimit = 1000;
        }

        [$dateFrom, $dateTo] = $this->resolveReportDateRange(
            $periodPreset,
            $request->query('date_from'),
            $request->query('date_to')
        );

        $clanRows = ClanPaymentCharge::query()
            ->with([
                'clan:id,Ime,Prezime',
                'paymentOption',
            ])
            ->where('status', '!=', PaymentTrackingService::STATUS_DELETED)
            ->get()
            ->map(fn (ClanPaymentCharge $charge): array => $this->mapClanChargeForReport($charge));

        $schoolRows = PolaznikPaymentCharge::query()
            ->with(['polaznik:id,Ime,Prezime'])
            ->where('status', '!=', SchoolPaymentService::STATUS_DELETED)
            ->get()
            ->map(fn (PolaznikPaymentCharge $charge): array => $this->mapSchoolChargeForReport($charge));

        $allRows = $clanRows->concat($schoolRows)->values();

        $filteredRows = $allRows->filter(function (array $row) use (
            $statusFilter,
            $targetFilter,
            $channelFilter,
            $modelTypeFilter,
            $dateFrom,
            $dateTo
        ): bool {
            if ($statusFilter !== 'all' && ($row['status'] ?? '') !== $statusFilter) {
                return false;
            }

            if ($targetFilter !== 'all' && ($row['entity_type'] ?? '') !== $targetFilter) {
                return false;
            }

            if ($channelFilter !== 'all' && ($row['channel'] ?? '') !== $channelFilter) {
                return false;
            }

            if ($modelTypeFilter !== 'all' && ($row['model_type'] ?? '') !== $modelTypeFilter) {
                return false;
            }

            $referenceDate = (string)($row['reference_date'] ?? '');
            if ($dateFrom !== null && ($referenceDate === '' || $referenceDate < $dateFrom)) {
                return false;
            }

            return !($dateTo !== null && ($referenceDate === '' || $referenceDate > $dateTo));
        })->values();

        $sortedRows = $filteredRows
            ->sortByDesc(function (array $row): string {
                $dateKey = (string)($row['reference_date'] ?? '0000-00-00');
                $idKey = str_pad((string)($row['id'] ?? 0), 10, '0', STR_PAD_LEFT);
                return $dateKey . '-' . $idKey;
            })
            ->values();

        $reportRows = $applyRowsLimit
            ? $sortedRows->take($rowsLimit)->values()
            : $sortedRows->values();

        $totalPaid = round($sortedRows
            ->filter(fn (array $row): bool => ($row['status'] ?? '') === PaymentTrackingService::STATUS_PAID)
            ->sum(fn (array $row): float => (float)($row['amount'] ?? 0)), 2);
        $totalOpen = round($sortedRows
            ->filter(fn (array $row): bool => ($row['status'] ?? '') === PaymentTrackingService::STATUS_OPEN)
            ->sum(fn (array $row): float => (float)($row['amount'] ?? 0)), 2);
        $totalPaidBank = round($sortedRows
            ->filter(fn (array $row): bool => ($row['status'] ?? '') === PaymentTrackingService::STATUS_PAID && ($row['channel'] ?? '') === PaymentTrackingService::COLLECTION_BANK)
            ->sum(fn (array $row): float => (float)($row['amount'] ?? 0)), 2);
        $totalPaidCash = round($sortedRows
            ->filter(fn (array $row): bool => ($row['status'] ?? '') === PaymentTrackingService::STATUS_PAID && ($row['channel'] ?? '') === PaymentTrackingService::COLLECTION_CASH)
            ->sum(fn (array $row): float => (float)($row['amount'] ?? 0)), 2);

        $debtorsSummary = $sortedRows
            ->filter(fn (array $row): bool => ($row['status'] ?? '') === PaymentTrackingService::STATUS_OPEN)
            ->groupBy(fn (array $row): string => (string)($row['person_key'] ?? 'unknown'))
            ->map(function (Collection $rows): array {
                $first = $rows->first();
                return [
                    'person_name' => (string)($first['person_name'] ?? ''),
                    'entity_type' => (string)($first['entity_type'] ?? ''),
                    'profile_url' => (string)($first['profile_url'] ?? ''),
                    'open_total' => round($rows->sum(fn (array $row): float => (float)($row['amount'] ?? 0)), 2),
                    'open_bank' => round($rows
                        ->filter(fn (array $row): bool => ($row['channel'] ?? '') === PaymentTrackingService::COLLECTION_BANK)
                        ->sum(fn (array $row): float => (float)($row['amount'] ?? 0)), 2),
                    'open_cash' => round($rows
                        ->filter(fn (array $row): bool => ($row['channel'] ?? '') === PaymentTrackingService::COLLECTION_CASH)
                        ->sum(fn (array $row): float => (float)($row['amount'] ?? 0)), 2),
                    'items_count' => $rows->count(),
                ];
            })
            ->sortByDesc('open_total')
            ->values();

        $personsSummary = $sortedRows
            ->groupBy(fn (array $row): string => (string)($row['person_key'] ?? 'unknown'))
            ->map(function (Collection $rows): array {
                $first = $rows->first();
                $paidRows = $rows->filter(fn (array $row): bool => ($row['status'] ?? '') === PaymentTrackingService::STATUS_PAID);
                $openRows = $rows->filter(fn (array $row): bool => ($row['status'] ?? '') === PaymentTrackingService::STATUS_OPEN);

                return [
                    'person_name' => (string)($first['person_name'] ?? ''),
                    'entity_type' => (string)($first['entity_type'] ?? ''),
                    'profile_url' => (string)($first['profile_url'] ?? ''),
                    'paid_total' => round($paidRows->sum(fn (array $row): float => (float)($row['amount'] ?? 0)), 2),
                    'open_total' => round($openRows->sum(fn (array $row): float => (float)($row['amount'] ?? 0)), 2),
                    'paid_bank' => round($paidRows
                        ->filter(fn (array $row): bool => ($row['channel'] ?? '') === PaymentTrackingService::COLLECTION_BANK)
                        ->sum(fn (array $row): float => (float)($row['amount'] ?? 0)), 2),
                    'paid_cash' => round($paidRows
                        ->filter(fn (array $row): bool => ($row['channel'] ?? '') === PaymentTrackingService::COLLECTION_CASH)
                        ->sum(fn (array $row): float => (float)($row['amount'] ?? 0)), 2),
                    'items_count' => $rows->count(),
                ];
            })
            ->sortByDesc('paid_total')
            ->values();

        return [
            'paymentSetup' => $paymentSetup,
            'schoolPaymentEnabled' => $schoolPaymentEnabled,
            'reportFilters' => [
                'period_preset' => $periodPreset,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'status' => $statusFilter,
                'target' => $targetFilter,
                'channel' => $channelFilter,
                'model_type' => $modelTypeFilter,
                'rows_limit' => $rowsLimit,
            ],
            'reportStats' => [
                'rows_total' => $sortedRows->count(),
                'rows_shown' => $reportRows->count(),
                'total_paid' => $totalPaid,
                'total_open' => $totalOpen,
                'total_paid_bank' => $totalPaidBank,
                'total_paid_cash' => $totalPaidCash,
                'debtors_count' => $debtorsSummary->count(),
            ],
            'reportRows' => $reportRows,
            'debtorsSummary' => $debtorsSummary,
            'personsSummary' => $personsSummary,
        ];
    }

    /**
     * Pretvara zaduženje člana u red izvještaja za prikaz i CSV izvoz.
     */
    private function mapClanChargeForReport(ClanPaymentCharge $charge): array
    {
        $charge->loadMissing(['clan', 'paymentOption']);

        $clan = $charge->clan;
        $personName = trim((string)($clan->Prezime ?? '') . ' ' . (string)($clan->Ime ?? ''));
        if ($personName === '') {
            $personName = 'Član #' . (int)$charge->clan_id;
        }

        $periodStart = $charge->period_start?->format('Y-m-d');
        $periodEnd = $charge->period_end?->format('Y-m-d');
        $dueDate = $charge->due_date?->format('Y-m-d');
        $paidAt = $charge->paid_at?->format('Y-m-d');
        $referenceDate = $paidAt ?? $dueDate ?? $periodStart ?? $charge->created_at?->format('Y-m-d');

        $modelType = match ($charge->source) {
            PaymentTrackingService::SOURCE_AUTO => (string)($charge->paymentOption?->period_type ?? 'auto'),
            PaymentTrackingService::SOURCE_OPENING => 'opening',
            default => 'manual',
        };

        $channel = $this->paymentTrackingService->isCashCollectionForCharge($charge)
            ? PaymentTrackingService::COLLECTION_CASH
            : PaymentTrackingService::COLLECTION_BANK;

        $periodLabel = '-';
        if ($charge->period_start !== null && $charge->period_end !== null) {
            $periodLabel = $charge->period_start->format('d.m.Y.') . ' - ' . $charge->period_end->format('d.m.Y.');
        } elseif ($charge->due_date !== null) {
            $periodLabel = 'Zaduženje: ' . $charge->due_date->format('d.m.Y.');
        }

        return [
            'id' => (int)$charge->id,
            'entity_type' => 'member',
            'person_key' => 'member:' . (int)$charge->clan_id,
            'person_name' => $personName,
            'profile_url' => route('javno.clanovi.prikaz_clana', ['clan' => (int)$charge->clan_id, 'open_payments' => 1]),
            'title' => (string)$charge->title,
            'model_name' => (string)($charge->paymentOption?->name ?? ($charge->source === PaymentTrackingService::SOURCE_OPENING ? 'Početno dugovanje' : 'Dodatno plaćanje')),
            'model_type' => $modelType,
            'channel' => $channel,
            'amount' => round((float)$charge->amount, 2),
            'status' => (string)$charge->status,
            'status_label' => $charge->status === PaymentTrackingService::STATUS_PAID ? 'Plaćeno' : 'Otvoreno',
            'period_label' => $periodLabel,
            'reference_date' => $referenceDate,
            'reference_date_label' => $referenceDate ? Carbon::parse($referenceDate)->format('d.m.Y.') : '-',
            'paid_at' => $paidAt,
            'due_date' => $dueDate,
            'source' => (string)$charge->source,
        ];
    }

    /**
     * Pretvara zaduženje školarine u red izvještaja za prikaz i CSV izvoz.
     */
    private function mapSchoolChargeForReport(PolaznikPaymentCharge $charge): array
    {
        $charge->loadMissing('polaznik');
        $polaznik = $charge->polaznik;
        $personName = trim((string)($polaznik->Prezime ?? '') . ' ' . (string)($polaznik->Ime ?? ''));
        if ($personName === '') {
            $personName = 'Polaznik #' . (int)$charge->polaznik_skole_id;
        }

        $paidAt = $charge->paid_at?->format('Y-m-d');
        $referenceDate = $paidAt ?? $charge->created_at?->format('Y-m-d');
        $periodLabel = $charge->due_training_count !== null
            ? 'Nakon ' . (int)$charge->due_training_count . ' treninga'
            : 'Odmah';

        return [
            'id' => (int)$charge->id,
            'entity_type' => 'school',
            'person_key' => 'school:' . (int)$charge->polaznik_skole_id,
            'person_name' => $personName,
            'profile_url' => route('javno.skola.polaznici.show', ['polaznik' => (int)$charge->polaznik_skole_id, 'open_payments' => 1]),
            'title' => (string)$charge->title,
            'model_name' => 'Školarina',
            'model_type' => 'school',
            'channel' => PaymentTrackingService::COLLECTION_CASH,
            'amount' => round((float)$charge->amount, 2),
            'status' => (string)$charge->status,
            'status_label' => $charge->status === SchoolPaymentService::STATUS_PAID ? 'Plaćeno' : 'Otvoreno',
            'period_label' => $periodLabel,
            'reference_date' => $referenceDate,
            'reference_date_label' => $referenceDate ? Carbon::parse($referenceDate)->format('d.m.Y.') : '-',
            'paid_at' => $paidAt,
            'due_date' => null,
            'source' => (string)$charge->source,
        ];
    }

    /**
     * Određuje konačnu vrijednost prema pravilima modula članarina, školarina i ostalih plaćanja.
     */
    private function resolveReportDateRange(string $preset, mixed $rawFrom, mixed $rawTo): array
    {
        $normalizedPreset = trim($preset);
        $today = now()->startOfDay();
        $from = null;
        $to = null;

        if ($normalizedPreset === 'current_month') {
            $from = $today->copy()->startOfMonth()->toDateString();
            $to = $today->copy()->endOfMonth()->toDateString();
        } elseif ($normalizedPreset === 'current_year') {
            $from = $today->copy()->startOfYear()->toDateString();
            $to = $today->copy()->endOfYear()->toDateString();
        } elseif ($normalizedPreset === 'current_season') {
            $year = (int)$today->format('Y');
            $month = (int)$today->format('n');
            if ($month >= 10) {
                $from = Carbon::create($year, 10, 1)->toDateString();
                $to = Carbon::create($year + 1, 3, 31)->toDateString();
            } elseif ($month >= 4) {
                $from = Carbon::create($year, 4, 1)->toDateString();
                $to = Carbon::create($year, 9, 30)->toDateString();
            } else {
                $from = Carbon::create($year - 1, 10, 1)->toDateString();
                $to = Carbon::create($year, 3, 31)->toDateString();
            }
        } elseif ($normalizedPreset === 'custom') {
            $from = $this->normalizeFilterDate($rawFrom);
            $to = $this->normalizeFilterDate($rawTo);
        }

        if ($normalizedPreset !== 'custom' && $normalizedPreset !== 'all') {
            $rawFromDate = $this->normalizeFilterDate($rawFrom);
            $rawToDate = $this->normalizeFilterDate($rawTo);
            if ($rawFromDate !== null) {
                $from = $rawFromDate;
            }
            if ($rawToDate !== null) {
                $to = $rawToDate;
            }
        }

        if ($from !== null && $to !== null && $from > $to) {
            [$from, $to] = [$to, $from];
        }

        return [$from, $to];
    }

    /**
     * Normalizira datum filtera izvještaja plaćanja u valjan format `Y-m-d`.
     */
    private function normalizeFilterDate(mixed $value): ?string
    {
        $candidate = trim((string)$value);
        if ($candidate === '') {
            return null;
        }

        try {
            return Carbon::parse($candidate)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }
}
