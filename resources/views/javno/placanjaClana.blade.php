@extends('layouts.app')

@section('content')
    @php
        $profile = $paymentSummary['profile'] ?? null;
        $charges = $paymentSummary['charges'] ?? collect();
        $unpaidCharges = $paymentSummary['unpaidCharges'] ?? collect();
        $paidCharges = $paymentSummary['paidCharges'] ?? collect();
        $currentCharges = $paymentSummary['currentCharges'] ?? collect();
        $currentUnpaidCharges = $paymentSummary['currentUnpaidCharges'] ?? collect();
        $pastDueCharges = $paymentSummary['pastDueCharges'] ?? collect();
        $isExempt = (bool)($paymentSummary['isExempt'] ?? false);
        $nextCharge = $nextCharge ?? ($paymentSummary['nextUnpaidCharge'] ?? null);
        $hubPayload = $paymentHubData['payload'] ?? null;
        $hubFields = $paymentHubData['fields'] ?? null;
        $nextChargeVariants = $nextChargeVariants ?? [];
        $nextChargeSelectedVariant = $nextChargeSelectedVariant ?? null;
        $nextChargeRestrictionNote = $nextChargeRestrictionNote ?? null;
        $nextChargeEffectiveAmount = $nextChargeEffectiveAmount ?? null;
        $nextChargeIsCashCollection = (bool)($nextChargeIsCashCollection ?? false);
        $selectedChargeId = isset($selectedChargeId) ? (int)$selectedChargeId : ($nextCharge?->id ?? null);
        $hasConfiguredPaymentProfile = !empty($profile) && !empty($profile->paymentOption);
        $hasAnyCharges = $charges->count() > 0;
        $showPaymentDetails = $hasConfiguredPaymentProfile || $hasAnyCharges;
        $paymentService = app(\App\Services\PaymentTrackingService::class);
        $hubEmptyDefaultMessage = $nextChargeIsCashCollection
            ? 'Za ovu stavku uplata ide gotovinom treneru. Barkod nije dostupan.'
            : 'Odaberite neplaćenu stavku za prikaz podataka i barkoda.';
    @endphp

    <div class="container-xxl">
        <div class="row g-3">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-danger text-white fw-bolder d-flex justify-content-between align-items-center">
                        <span>Plaćanja člana: {{ $clan->Ime }} {{ $clan->Prezime }}</span>
                        <a class="btn btn-sm btn-outline-light" href="{{ route('javno.clanovi.prikaz_clana', $clan) }}">Povratak na profil</a>
                    </div>
                    <div class="card-body bg-secondary-subtle">
                        @if(!empty($paymentNotice))
                            <div class="alert alert-{{ $paymentNotice['variant'] ?? 'secondary' }} mb-3">
                                <div class="fw-bold">{{ $paymentNotice['title'] ?? 'Status plaćanja' }}</div>
                                <div>{{ $paymentNotice['message'] ?? '' }}</div>
                            </div>
                        @endif

                        @if($hasConfiguredPaymentProfile)
                            <div class="row g-2 mb-3">
                                <div class="col-lg-5">
                                    <span class="fw-semibold">Model plaćanja:</span>
                                    @php
                                        $profileOptionType = (string)($profile->paymentOption->period_type ?? '');
                                        $profileOptionAnchor = (string)($profile->paymentOption->period_anchor ?? '');
                                        $profileOptionCollection = (string)($profile->paymentOption->collection_method ?? 'bank');
                                        $profileOptionLabel = match (true) {
                                            $profileOptionType === 'exempt' => 'Oslobođen',
                                            $profileOptionType === 'monthly' && $profileOptionCollection === 'cash' => 'Mjesečno (gotovina treneru)',
                                            $profileOptionType === 'monthly' => 'Mjesečno',
                                            $profileOptionType === 'seasonal' => 'Sezonski',
                                            $profileOptionType === 'annual' && $profileOptionAnchor === 'apr' => 'Godišnje od 01.04.',
                                            $profileOptionType === 'annual' && $profileOptionAnchor === 'oct' => 'Godišnje od 01.10.',
                                            default => $profile->paymentOption->name,
                                        };
                                    @endphp
                                    {{ $profileOptionLabel }}
                                </div>
                                <div class="col-lg-3">
                                    <span class="fw-semibold">Početak praćenja:</span>
                                    {{ optional($profile->start_date)->format('d.m.Y.') ?? '-' }}
                                </div>
                                <div class="col-lg-4 text-lg-end">
                                    <span class="fw-semibold">Otvoreno za uplatu:</span>
                                    {{ number_format((float)($paymentSummary['totalOpenAmount'] ?? 0), 2, ',', '.') }} EUR
                                </div>
                            </div>
                        @endif

                        @if(!$showPaymentDetails)
                            <p class="mb-0">Administrator još nije postavio model plaćanja za ovog člana.</p>
                        @elseif($isExempt && !$hasAnyCharges)
                            <p class="mb-0">Član je oslobođen plaćanja članarine.</p>
                        @else
                            <div class="row g-3">
                                <div class="col-lg-8">
                                    <div class="card h-100">
                                        <div class="card-header bg-light fw-bold">Popis uplata i dugovanja</div>
                                        <div class="card-body p-0">
                                            @if($charges->count() === 0)
                                                <p class="p-3 mb-0">Nema evidentiranih stavki plaćanja.</p>
                                            @else
                                                <div class="table-responsive">
                                                    <table class="table table-sm table-hover align-middle mb-0">
                                                        <thead class="table-warning">
                                                        <tr>
                                                            <th>Naziv</th>
                                                            <th>Razdoblje / datum</th>
                                                            <th>Iznos</th>
                                                            <th>Status</th>
                                                            <th>Plaćeno</th>
                                                        </tr>
                                                        </thead>
                                                        <tbody>
                                                        @foreach($charges as $charge)
                                                            @php
                                                                $isPaid = $charge->status === \App\Services\PaymentTrackingService::STATUS_PAID;
                                                                $paidVariant = $paymentService->selectedVariantForCharge($charge);
                                                                $paidVariantLabel = $paymentService->variantLabelForCharge($charge, $paidVariant);
                                                                $paidVariantNote = $paymentService->restrictionNoteForCharge($charge, $paidVariant);
                                                            @endphp
                                                            <tr>
                                                                <td>
                                                                    {{ $charge->title }}
                                                                    @if($isPaid && !empty($paidVariantLabel) && $paidVariant !== \App\Services\PaymentTrackingService::VARIANT_FULL)
                                                                        <div class="small text-warning-emphasis">{{ $paidVariantLabel }}</div>
                                                                    @endif
                                                                    @if($isPaid && !empty($paidVariantNote))
                                                                        <div class="small text-warning">{{ $paidVariantNote }}</div>
                                                                    @endif
                                                                </td>
                                                                <td>
                                                                    @if($charge->period_start && $charge->period_end)
                                                                        {{ $charge->period_start->format('d.m.Y.') }} - {{ $charge->period_end->format('d.m.Y.') }}
                                                                    @elseif($charge->due_date)
                                                                        {{ $charge->due_date->format('d.m.Y.') }}
                                                                    @else
                                                                        -
                                                                    @endif
                                                                </td>
                                                                <td>{{ number_format((float)$charge->amount, 2, ',', '.') }} EUR</td>
                                                                <td>
                                                                    @if($isPaid)
                                                                        <span class="badge bg-success">Plaćeno</span>
                                                                    @else
                                                                        <span class="badge bg-danger">Nije plaćeno</span>
                                                                    @endif
                                                                </td>
                                                                <td>
                                                                    @if($charge->paid_at)
                                                                        {{ $charge->paid_at->format('d.m.Y.') }}
                                                                    @else
                                                                        -
                                                                    @endif
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <div class="col-lg-4">
                                    <div class="card mb-3">
                                        <div class="card-header bg-light fw-bold">Status</div>
                                        <div class="card-body small">
                                            <div class="mb-2">Tekuće razdoblje neplaćeno: <span class="fw-bold">{{ $currentUnpaidCharges->count() }}</span></div>
                                            <div class="mb-2">Dugovanja iz prošlih razdoblja: <span class="fw-bold">{{ $pastDueCharges->count() }}</span></div>
                                            <div class="mb-2">Otvorene stavke: <span class="fw-bold">{{ $unpaidCharges->count() }}</span></div>
                                            <div>Plaćene stavke: <span class="fw-bold">{{ $paidCharges->count() }}</span></div>
                                            @if($currentCharges->count() > 0)
                                                <hr class="my-2">
                                                <div class="fw-semibold mb-1">Tekuće razdoblje:</div>
                                                <ul class="mb-0 ps-3">
                                                    @foreach($currentCharges as $currentCharge)
                                                        <li>
                                                            {{ $currentCharge->title }}
                                                            @if($currentCharge->status === \App\Services\PaymentTrackingService::STATUS_PAID)
                                                                <span class="text-success">(plaćeno)</span>
                                                            @else
                                                                <span class="text-danger">(nije plaćeno)</span>
                                                            @endif
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="card">
                                        <div id="payment-hub-title" class="card-header bg-light fw-bold">
                                            {{ $nextChargeIsCashCollection ? 'Uplata (gotovina treneru)' : 'Uplata i barkod' }}
                                        </div>
                                        <div class="card-body">
                                            @if($unpaidCharges->count() > 0)
                                                <form id="charge-select-form" action="{{ route('javno.clanovi.placanja', $clan) }}" method="GET" class="mb-3">
                                                    <label for="selected_charge" class="form-label form-label-sm fw-semibold mb-1">Odaberi dug za uplatu</label>
                                                    <div>
                                                        <select id="selected_charge" name="charge" class="form-select form-select-sm">
                                                            @foreach($unpaidCharges as $unpaidCharge)
                                                                @php
                                                                    $chargePeriodLabel = '-';
                                                                    if ($unpaidCharge->period_start && $unpaidCharge->period_end) {
                                                                        $chargePeriodLabel = $unpaidCharge->period_start->format('d.m.Y.') . ' - ' . $unpaidCharge->period_end->format('d.m.Y.');
                                                                    } elseif ($unpaidCharge->due_date) {
                                                                        $chargePeriodLabel = $unpaidCharge->due_date->format('d.m.Y.');
                                                                    }
                                                                    $chargeOptionAmount = $paymentService->resolvedChargeAmount($unpaidCharge, true);
                                                                @endphp
                                                                <option value="{{ $unpaidCharge->id }}" @selected($selectedChargeId === (int)$unpaidCharge->id)>
                                                                    {{ $unpaidCharge->title }} ({{ $chargePeriodLabel }}) - {{ number_format((float)$chargeOptionAmount, 2, ',', '.') }} EUR
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                        <noscript>
                                                            <button type="submit" class="btn btn-sm btn-outline-primary text-nowrap mt-2">Prikaži</button>
                                                        </noscript>
                                                    </div>
                                                </form>
                                            @endif

                                            <div id="selected-charge-info" @class(['mb-2', 'd-none' => !$nextCharge])>
                                                <p class="small mb-2"><span class="fw-semibold">Odabrana stavka:</span> <span id="selected-charge-title">{{ $nextCharge?->title }}</span></p>
                                                <p class="small mb-2"><span class="fw-semibold">Iznos:</span> <span id="selected-charge-amount">{{ $nextCharge ? number_format((float)($nextChargeEffectiveAmount ?? $nextCharge->amount), 2, ',', '.') . ' EUR' : '-' }}</span></p>
                                            </div>

                                            @if($nextCharge && count($nextChargeVariants) > 0)
                                                <form id="payment-variant-form" action="{{ route('javno.clanovi.placanja.odabir', [$clan, $nextCharge]) }}" method="POST" class="mb-3">
                                                    @csrf
                                                    <label for="payment_variant" class="form-label form-label-sm fw-semibold mb-1">Odabir vrste uplate</label>
                                                    <div>
                                                        <select id="payment_variant" name="payment_variant" class="form-select form-select-sm">
                                                            @foreach($nextChargeVariants as $variant)
                                                                @php
                                                                    $variantValue = $variant['value'] ?? '';
                                                                    $variantLabel = $variant['label'] ?? $variantValue;
                                                                    $variantAmount = isset($variant['amount']) ? (float)$variant['amount'] : null;
                                                                @endphp
                                                                <option value="{{ $variantValue }}" @selected($nextChargeSelectedVariant === $variantValue)>
                                                                    {{ $variantLabel }}
                                                                    @if($variantAmount !== null)
                                                                        ({{ number_format($variantAmount, 2, ',', '.') }} EUR)
                                                                    @endif
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                        <noscript>
                                                            <button type="submit" class="btn btn-sm btn-outline-primary mt-2">Primijeni</button>
                                                        </noscript>
                                                    </div>
                                                </form>
                                            @else
                                                <form id="payment-variant-form" class="mb-3 d-none"></form>
                                            @endif

                                            @if(!empty($nextChargeRestrictionNote))
                                                <div id="next-charge-restriction" class="alert alert-warning small py-2 mb-3">
                                                    {{ $nextChargeRestrictionNote }}
                                                </div>
                                            @else
                                                <div id="next-charge-restriction" class="alert alert-warning small py-2 mb-3 d-none"></div>
                                            @endif

                                            @if($nextChargeIsCashCollection)
                                                <p id="hub-empty-message" class="small text-muted mb-0">{{ $hubEmptyDefaultMessage }}</p>
                                                <div id="hub-fields" class="small mb-2 d-none">
                                                    <div><span class="fw-semibold">Primatelj:</span> <span id="hub-field-primatelj"></span></div>
                                                    <div><span class="fw-semibold">Adresa:</span> <span id="hub-field-adresa"></span></div>
                                                    <div><span class="fw-semibold">IBAN:</span> <span id="hub-field-iban"></span></div>
                                                    <div><span class="fw-semibold">Model/Poziv:</span> <span id="hub-field-model"></span> / <span id="hub-field-poziv"></span></div>
                                                    <div><span class="fw-semibold">Opis plaćanja:</span> <span id="hub-field-opis"></span></div>
                                                </div>
                                                <div id="hub-canvas-wrap" class="w-100 overflow-hidden d-none">
                                                    <canvas id="payment-hub-barcode" class="border d-block mx-auto" style="background: #fff; image-rendering: pixelated; max-width: 100%; height: auto;"></canvas>
                                                </div>
                                            @elseif($hubFields)
                                                <div id="hub-fields" class="small mb-2">
                                                    <div><span class="fw-semibold">Primatelj:</span> <span id="hub-field-primatelj">{{ $hubFields['primatelj'] }}</span></div>
                                                    <div><span class="fw-semibold">Adresa:</span> <span id="hub-field-adresa">{{ $hubFields['adresa'] ?? '-' }}</span></div>
                                                    <div><span class="fw-semibold">IBAN:</span> <span id="hub-field-iban">{{ $hubFields['iban'] }}</span></div>
                                                    <div><span class="fw-semibold">Model/Poziv:</span> <span id="hub-field-model">{{ $hubFields['model'] }}</span> / <span id="hub-field-poziv">{{ $hubFields['poziv_na_broj'] }}</span></div>
                                                    <div><span class="fw-semibold">Opis plaćanja:</span> <span id="hub-field-opis">{{ $hubFields['opis'] }}</span></div>
                                                </div>
                                                <div id="hub-canvas-wrap" class="w-100 overflow-hidden">
                                                    <canvas id="payment-hub-barcode" class="border d-block mx-auto" style="background: #fff; image-rendering: pixelated; max-width: 100%; height: auto;"></canvas>
                                                </div>
                                                <p id="hub-empty-message" class="small text-muted mb-0 d-none">Odaberite neplaćenu stavku za prikaz podataka i barkoda.</p>
                                            @else
                                                <div id="hub-fields" class="small mb-2 d-none">
                                                    <div><span class="fw-semibold">Primatelj:</span> <span id="hub-field-primatelj"></span></div>
                                                    <div><span class="fw-semibold">Adresa:</span> <span id="hub-field-adresa"></span></div>
                                                    <div><span class="fw-semibold">IBAN:</span> <span id="hub-field-iban"></span></div>
                                                    <div><span class="fw-semibold">Model/Poziv:</span> <span id="hub-field-model"></span> / <span id="hub-field-poziv"></span></div>
                                                    <div><span class="fw-semibold">Opis plaćanja:</span> <span id="hub-field-opis"></span></div>
                                                </div>
                                                <div id="hub-canvas-wrap" class="w-100 overflow-hidden d-none">
                                                    <canvas id="payment-hub-barcode" class="border d-block mx-auto" style="background: #fff; image-rendering: pixelated; max-width: 100%; height: auto;"></canvas>
                                                </div>
                                                <p id="hub-empty-message" class="small text-muted mb-0">{{ $hubEmptyDefaultMessage }}</p>
                                            @endif

                                            @if(!empty($paymentInfoClanakId))
                                                <a href="{{ route('javno.clanci.prikaz_clanka', $paymentInfoClanakId) }}" class="btn btn-outline-primary btn-sm mt-3">
                                                    Upute za plaćanje
                                                </a>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if($unpaidCharges->count() > 0 || !empty($hubPayload))
        <script src="https://cdn.jsdelivr.net/gh/pkoretic/pdf417-generator@master/lib/libbcmath.js"></script>
        <script src="https://cdn.jsdelivr.net/gh/pkoretic/pdf417-generator@master/lib/bcmath.js"></script>
        <script src="https://cdn.jsdelivr.net/gh/bkuzmic/pdf417-js@master/pdf417.js"></script>
        <script>
            (function () {
                const initialPayload = @json($hubPayload);
                const memberPaymentsUrl = @json(route('javno.clanovi.placanja', $clan));
                const variantActionTemplate = @json(route('javno.clanovi.placanja.odabir', ['clan' => $clan, 'charge' => '__CHARGE__']));
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

                const chargeSelect = document.getElementById('selected_charge');
                const chargeForm = document.getElementById('charge-select-form');
                const selectedChargeInfo = document.getElementById('selected-charge-info');
                const selectedChargeTitle = document.getElementById('selected-charge-title');
                const selectedChargeAmount = document.getElementById('selected-charge-amount');
                const variantForm = document.getElementById('payment-variant-form');
                const variantSelect = document.getElementById('payment_variant');
                const restrictionBox = document.getElementById('next-charge-restriction');
                const paymentHubTitle = document.getElementById('payment-hub-title');
                const hubFields = document.getElementById('hub-fields');
                const hubCanvasWrap = document.getElementById('hub-canvas-wrap');
                const hubEmptyMessage = document.getElementById('hub-empty-message');
                const canvas = document.getElementById('payment-hub-barcode');

                function formatEur(amount) {
                    const numeric = Number(amount ?? 0);
                    return numeric.toLocaleString('hr-HR', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' EUR';
                }

                function setVisible(element, visible) {
                    if (!element) {
                        return;
                    }
                    element.classList.toggle('d-none', !visible);
                }

                function renderBarcode(payload) {
                    if (!canvas || !payload || typeof PDF417 === 'undefined' || typeof PDF417.init !== 'function' || typeof PDF417.getBarcodeArray !== 'function') {
                        return false;
                    }

                    try {
                        PDF417.init(payload);
                    } catch (error) {
                        return false;
                    }

                    const barcode = PDF417.getBarcodeArray();
                    if (!barcode || !barcode.num_cols || !barcode.num_rows || !barcode.bcode) {
                        return false;
                    }

                    canvas.width = 2 * barcode.num_cols;
                    canvas.height = 2 * barcode.num_rows;

                    const context = canvas.getContext('2d');
                    if (!context) {
                        return false;
                    }

                    context.fillStyle = '#ffffff';
                    context.fillRect(0, 0, canvas.width, canvas.height);
                    context.fillStyle = '#000000';

                    let y = 0;
                    for (let row = 0; row < barcode.num_rows; ++row) {
                        let x = 0;
                        for (let col = 0; col < barcode.num_cols; ++col) {
                            const value = barcode.bcode[row][col];
                            if (value === 1 || value === '1') {
                                context.fillRect(x, y, 2, 2);
                            }
                            x += 2;
                        }
                        y += 2;
                    }

                    return true;
                }

                function updateVariantOptions(variants, selectedVariant) {
                    if (!variantSelect) {
                        return;
                    }

                    variantSelect.innerHTML = '';
                    (variants || []).forEach((variant) => {
                        const option = document.createElement('option');
                        const value = variant?.value ?? '';
                        const label = variant?.label ?? value;
                        const amount = variant?.amount;

                        option.value = value;
                        option.textContent = (amount === null || amount === undefined)
                            ? label
                            : `${label} (${Number(amount).toLocaleString('hr-HR', {minimumFractionDigits: 2, maximumFractionDigits: 2})} EUR)`;

                        if (selectedVariant === value) {
                            option.selected = true;
                        }

                        variantSelect.appendChild(option);
                    });
                }

                function updateUi(data) {
                    if (!data || typeof data !== 'object') {
                        return;
                    }

                    const selectedId = data.selectedChargeId ?? null;
                    const nextCharge = data.nextCharge ?? null;
                    const nextChargeVariants = data.nextChargeVariants ?? [];
                    const selectedVariant = data.nextChargeSelectedVariant ?? null;
                    const restrictionNote = data.nextChargeRestrictionNote ?? null;
                    const effectiveAmount = data.nextChargeEffectiveAmount ?? null;
                    const isCashCollection = !!data.isCashCollection;
                    const hubData = data.paymentHubData ?? null;
                    const hubPayload = hubData?.payload ?? null;
                    const hub = hubData?.fields ?? null;

                    if (chargeSelect && selectedId !== null) {
                        chargeSelect.value = String(selectedId);
                    }

                    setVisible(selectedChargeInfo, !!nextCharge);
                    if (selectedChargeTitle) {
                        selectedChargeTitle.textContent = nextCharge?.title ?? '';
                    }
                    if (selectedChargeAmount) {
                        selectedChargeAmount.textContent = nextCharge ? formatEur(effectiveAmount) : '-';
                    }

                    if (variantForm) {
                        const hasVariants = !!nextCharge && Array.isArray(nextChargeVariants) && nextChargeVariants.length > 0;
                        setVisible(variantForm, hasVariants);
                        if (hasVariants) {
                            variantForm.action = variantActionTemplate.replace('__CHARGE__', String(nextCharge.id));
                            updateVariantOptions(nextChargeVariants, selectedVariant);
                        }
                    }

                    if (restrictionBox) {
                        restrictionBox.textContent = restrictionNote || '';
                        setVisible(restrictionBox, !!restrictionNote);
                    }

                    if (paymentHubTitle) {
                        paymentHubTitle.textContent = isCashCollection ? 'Uplata (gotovina treneru)' : 'Uplata i barkod';
                    }

                    if (hubEmptyMessage) {
                        hubEmptyMessage.textContent = isCashCollection
                            ? 'Za ovu stavku uplata ide gotovinom treneru. Barkod nije dostupan.'
                            : 'Odaberite neplaćenu stavku za prikaz podataka i barkoda.';
                    }

                    if (hub) {
                        const primatelj = document.getElementById('hub-field-primatelj');
                        const adresa = document.getElementById('hub-field-adresa');
                        const iban = document.getElementById('hub-field-iban');
                        const model = document.getElementById('hub-field-model');
                        const poziv = document.getElementById('hub-field-poziv');
                        const opis = document.getElementById('hub-field-opis');

                        if (primatelj) primatelj.textContent = hub.primatelj ?? '';
                        if (adresa) adresa.textContent = hub.adresa ?? '-';
                        if (iban) iban.textContent = hub.iban ?? '';
                        if (model) model.textContent = hub.model ?? '';
                        if (poziv) poziv.textContent = hub.poziv_na_broj ?? '';
                        if (opis) opis.textContent = hub.opis ?? '';
                    }

                    const drawn = !isCashCollection && renderBarcode(hubPayload);
                    setVisible(hubFields, !isCashCollection && !!hub);
                    setVisible(hubCanvasWrap, drawn);
                    setVisible(hubEmptyMessage, !drawn);
                }

                async function fetchJson(url, options) {
                    const response = await fetch(url, options);
                    if (!response.ok) {
                        throw new Error('HTTP ' + response.status);
                    }
                    return response.json();
                }

                if (chargeSelect && chargeForm) {
                    chargeSelect.addEventListener('change', async function () {
                        const selectedId = this.value;
                        if (!selectedId) {
                            return;
                        }

                        this.disabled = true;
                        if (variantSelect) variantSelect.disabled = true;

                        try {
                            const data = await fetchJson(`${chargeForm.action}?charge=${encodeURIComponent(selectedId)}&ajax=1`, {
                                method: 'GET',
                                headers: {
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest'
                                }
                            });
                            updateUi(data);
                            window.history.replaceState(null, '', `${memberPaymentsUrl}?charge=${encodeURIComponent(selectedId)}`);
                        } catch (error) {
                            chargeForm.submit();
                        } finally {
                            this.disabled = false;
                            if (variantSelect) variantSelect.disabled = false;
                        }
                    });
                }

                if (variantSelect && variantForm) {
                    variantSelect.addEventListener('change', async function () {
                        const selectedVariant = this.value;
                        this.disabled = true;
                        if (chargeSelect) chargeSelect.disabled = true;

                        try {
                            const body = new URLSearchParams();
                            body.append('payment_variant', selectedVariant);
                            body.append('ajax', '1');

                            const data = await fetchJson(variantForm.action, {
                                method: 'POST',
                                headers: {
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'X-CSRF-TOKEN': csrfToken,
                                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
                                },
                                body: body.toString()
                            });
                            updateUi(data);
                        } catch (error) {
                            variantForm.submit();
                        } finally {
                            this.disabled = false;
                            if (chargeSelect) chargeSelect.disabled = false;
                        }
                    });
                }

                if (initialPayload) {
                    renderBarcode(initialPayload);
                }
            })();
        </script>
    @endif
@endsection
