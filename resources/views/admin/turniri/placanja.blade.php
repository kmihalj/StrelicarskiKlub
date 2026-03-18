{{-- Administratorski ekran postavki praćenja plaćanja. --}}
@php
    $trackingEnabled = (bool)($paymentSetup['paymentTrackingEnabled'] ?? false);
    $paymentOptions = $paymentSetup['paymentOptions'] ?? collect();
    $paymentOptionsArchivedCount = (int)($paymentSetup['paymentOptionsArchivedCount'] ?? 0);
    $schoolTuitionAdultAmount = old('school_tuition_adult_amount', number_format((float)($paymentSetup['schoolTuitionAdultAmount'] ?? 100), 2, '.', ''));
    $schoolTuitionMinorAmount = old('school_tuition_minor_amount', number_format((float)($paymentSetup['schoolTuitionMinorAmount'] ?? 70), 2, '.', ''));
    $otvoriPlacanjaSetup = request()->boolean('open_payment_setup');
@endphp

<div class="card shadow-sm">
    <div class="card-header bg-danger text-white fw-bolder d-flex justify-content-between align-items-center">
        <span>Praćenje plaćanja članarina</span>
        <span>
            <span id="pokazivanje_setup_placanja"
                  class="text-white fw-bold{{ $otvoriPlacanjaSetup ? ' d-none' : '' }}"
                  title="Otvori"
                  style="cursor: pointer;">+</span>
            <span id="skrivanje_setup_placanja"
                  class="text-white fw-bold{{ $otvoriPlacanjaSetup ? '' : ' d-none' }}"
                  title="Zatvori"
                  style="cursor: pointer;">&minus;</span>
        </span>
    </div>
    <div id="setup_placanja_dropdown" class="card-body bg-secondary-subtle{{ $otvoriPlacanjaSetup ? '' : ' d-none' }}">
        <form action="{{ route('admin.placanja.setup.update') }}" method="POST">
            @csrf
            <div class="row g-2">
                <div class="col-12 d-flex flex-nowrap justify-content-between align-items-end gap-3">
                    <div>
                        <label for="payment_tracking_enabled" class="form-label fw-bold mb-1">Praćenje plaćanja</label>
                        <div class="form-check form-switch">
                            <input type="hidden" name="payment_tracking_enabled" value="0">
                            <input class="form-check-input" type="checkbox" role="switch" id="payment_tracking_enabled"
                                   name="payment_tracking_enabled" value="1" @checked($trackingEnabled)>
                            <label class="form-check-label" for="payment_tracking_enabled">
                                @if($trackingEnabled)
                                    Uključeno
                                @else
                                    Isključeno
                                @endif
                            </label>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">Spremi promjene</button>
                </div>
                <div class="col-lg-3 col-md-6">
                    <label for="school_tuition_adult_amount" class="form-label fw-semibold mb-1">Školarina punoljetni (EUR)</label>
                    <input type="text"
                           class="form-control form-control-sm"
                           id="school_tuition_adult_amount"
                           name="school_tuition_adult_amount"
                           value="{{ $schoolTuitionAdultAmount }}"
                           placeholder="100.00">
                </div>
                <div class="col-lg-3 col-md-6">
                    <label for="school_tuition_minor_amount" class="form-label fw-semibold mb-1">Školarina maloljetni (EUR)</label>
                    <input type="text"
                           class="form-control form-control-sm"
                           id="school_tuition_minor_amount"
                           name="school_tuition_minor_amount"
                           value="{{ $schoolTuitionMinorAmount }}"
                           placeholder="70.00">
                </div>
                <div class="col-12">
                    <div class="form-text">Ako je isključeno, korisnici ne vide nikakve indikatore plaćanja.</div>
                </div>
            </div>

            <hr>

            <div class="alert alert-secondary py-2 mb-3 small fw-semibold" style="color: #212529;">
                Ukupno modela u bazi: <strong>{{ $paymentOptions->count() + $paymentOptionsArchivedCount }}</strong>,
                prikazano u ovoj formi: <strong>{{ $paymentOptions->count() }}</strong>,
                arhivirano (skriveno): <strong>{{ $paymentOptionsArchivedCount }}</strong>.
            </div>

            <div class="d-flex flex-column gap-3">
                @foreach($paymentOptions as $option)
                    @php
                        $optionPathById = 'options.' . $option->id;
                        $optionPathByKey = 'options.' . $option->key;
                        $nameOld = old($optionPathById . '.name', old($optionPathByKey . '.name'));
                        $descriptionOld = old($optionPathById . '.description', old($optionPathByKey . '.description'));
                        $periodTypeOld = old($optionPathById . '.period_type', old($optionPathByKey . '.period_type'));
                        $periodAnchorOld = old($optionPathById . '.period_anchor', old($optionPathByKey . '.period_anchor'));
                        $collectionMethodOld = old($optionPathById . '.collection_method', old($optionPathByKey . '.collection_method'));
                        $enabledOld = old($optionPathById . '.enabled', old($optionPathByKey . '.enabled'));
                        $amountOld = old($optionPathById . '.amount', old($optionPathByKey . '.amount'));
                        $validFromOld = old($optionPathById . '.valid_from', old($optionPathByKey . '.valid_from'));
                        $nameValue = $nameOld ?? $option->name;
                        $descriptionValue = $descriptionOld ?? $option->description;
                        $periodTypeValue = $periodTypeOld ?? $option->period_type;
                        $periodAnchorValue = $periodAnchorOld ?? $option->period_anchor;
                        $collectionMethodValue = $collectionMethodOld ?? ($option->collection_method ?? 'bank');
                        $enabledValue = $enabledOld !== null ? (string)$enabledOld === '1' : (bool)$option->is_enabled;
                        $amountValue = ($periodTypeValue === 'exempt')
                            ? '0.00'
                            : ($amountOld ?? ($option->latest_price_amount ?? '0.00'));
                        $validFromValue = $validFromOld ?? ($option->latest_price_valid_from ?? now()->toDateString());
                    @endphp
                    <div class="card shadow-sm border border-secondary-subtle" data-option-key="{{ $option->key }}">
                        <div class="card-body py-3">
                            <div class="row g-2 align-items-end fw-semibold">
                                <div class="col-lg-4 col-12">
                                    <label class="form-label mb-1">Naziv</label>
                                    <input type="text" class="form-control form-control-sm"
                                           name="options[{{ $option->id }}][name]"
                                           value="{{ $nameValue }}"
                                           placeholder="Naziv modela plaćanja">
                                </div>
                                <div class="col-lg-2 col-12">
                                    <label class="form-label mb-1">Tip razdoblja</label>
                                    <select class="form-select form-select-sm js-existing-period-type"
                                            data-option-id="{{ $option->id }}"
                                            name="options[{{ $option->id }}][period_type]">
                                        <option value="monthly" @selected($periodTypeValue === 'monthly')>Mjesečno</option>
                                        <option value="seasonal" @selected($periodTypeValue === 'seasonal')>Sezonski</option>
                                        <option value="annual" @selected($periodTypeValue === 'annual')>Godišnje</option>
                                        <option value="exempt" @selected($periodTypeValue === 'exempt')>Oslobođen</option>
                                    </select>
                                </div>
                                <div class="col-lg-2 col-12">
                                    <label class="form-label mb-1">Sidro sezone</label>
                                    <select class="form-select form-select-sm js-existing-period-anchor"
                                            data-option-id="{{ $option->id }}"
                                            name="options[{{ $option->id }}][period_anchor]">
                                        <option value="">-- nije primjenjivo --</option>
                                        <option value="oct"
                                                data-label-seasonal="Sezona dvoranska (01.10.-31.03.)"
                                                data-label-annual="Godišnje sidro (01.10.-30.09.)"
                                                @selected($periodAnchorValue === 'oct')>
                                            Sezona dvoranska (01.10.-31.03.)
                                        </option>
                                        <option value="apr"
                                                data-label-seasonal="Sezona vanjska (01.04.-30.09.)"
                                                data-label-annual="Godišnje sidro (01.04.-31.03.)"
                                                @selected($periodAnchorValue === 'apr')>
                                            Sezona vanjska (01.04.-30.09.)
                                        </option>
                                    </select>
                                </div>
                                <div class="col-lg-2 col-12">
                                    <label class="form-label mb-1">Naplata</label>
                                    <select class="form-select form-select-sm"
                                            name="options[{{ $option->id }}][collection_method]">
                                        <option value="bank" @selected($collectionMethodValue === 'bank')>Račun + barkod</option>
                                        <option value="cash" @selected($collectionMethodValue === 'cash')>Gotovina treneru</option>
                                    </select>
                                </div>
                                <div class="col-lg-2 col-12">
                                    <label class="form-label mb-1 d-block">Dostupno</label>
                                    <input type="hidden" name="options[{{ $option->id }}][enabled]" value="0">
                                    <input class="form-check-input" type="checkbox"
                                           name="options[{{ $option->id }}][enabled]" value="1" @checked($enabledValue)>
                                </div>
                            </div>

                            <div class="row g-2 align-items-end fw-semibold mt-1">
                                <div class="col-lg-2 col-md-6 col-12">
                                    <label class="form-label mb-1">Vrijedi od</label>
                                    <input type="date" class="form-control form-control-sm"
                                           name="options[{{ $option->id }}][valid_from]"
                                           value="{{ $validFromValue }}">
                                </div>
                                <div class="col-lg-2 col-md-6 col-12">
                                    <label class="form-label mb-1">Iznos (EUR)</label>
                                    <input type="text" class="form-control form-control-sm js-existing-amount"
                                           data-option-id="{{ $option->id }}"
                                           data-option-key="{{ $option->key }}"
                                           data-option-period-type="{{ $periodTypeValue }}"
                                           data-option-period-anchor="{{ $periodAnchorValue }}"
                                           name="options[{{ $option->id }}][amount]"
                                           value="{{ $amountValue }}"
                                           placeholder="0.00">
                                </div>
                                <div class="col-lg-8 col-12">
                                    <label class="form-label mb-1">Opis za administratore</label>
                                    <input type="text" class="form-control form-control-sm"
                                           name="options[{{ $option->id }}][description]"
                                           value="{{ $descriptionValue }}"
                                           placeholder="Opis (opcionalno)">
                                </div>
                            </div>

                            <div class="row mt-3">
                                <div class="col-12 text-end">
                                    <button type="submit" class="btn btn-outline-danger btn-sm"
                                            name="delete_option_id" value="{{ $option->id }}"
                                            onclick="return confirm('Ukloniti ovu vrstu plaćanja iz admin popisa? Kod članova i dalje ostaje povijest plaćanja.');">
                                        Ukloni
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="text-end mt-3">
                <button type="submit" class="btn btn-primary">Spremi promjene</button>
            </div>
        </form>

        <hr>

        <div class="card mt-3">
            <div class="card-header bg-light fw-bold">Dodaj novu vrstu plaćanja</div>
            <div class="card-body">
                <form action="{{ route('admin.placanja.setup.option.add') }}" method="POST">
                    @csrf
                    <div class="row g-2 align-items-end">
                        <div class="col-lg-3">
                            <label for="new_payment_name" class="form-label">Naziv</label>
                            <input type="text" class="form-control" id="new_payment_name" name="name"
                                   placeholder="Npr. Tromjesečna članarina" required>
                        </div>
                        <div class="col-lg-2">
                            <label for="new_payment_period_type" class="form-label">Tip razdoblja</label>
                            <select class="form-select" id="new_payment_period_type" name="period_type" required>
                                <option value="monthly">Mjesečno</option>
                                <option value="seasonal">Sezonski</option>
                                <option value="annual">Godišnje</option>
                                <option value="exempt">Oslobođen</option>
                            </select>
                        </div>
                        <div class="col-lg-2">
                            <label for="new_payment_period_anchor" class="form-label">Sidro sezone</label>
                            <select class="form-select" id="new_payment_period_anchor" name="period_anchor">
                                <option value="oct"
                                        data-label-seasonal="Sezona dvoranska (01.10.-31.03.)"
                                        data-label-annual="Godišnje sidro (01.10.-30.09.)">
                                    Sezona dvoranska (01.10.-31.03.)
                                </option>
                                <option value="apr"
                                        data-label-seasonal="Sezona vanjska (01.04.-30.09.)"
                                        data-label-annual="Godišnje sidro (01.04.-31.03.)">
                                    Sezona vanjska (01.04.-30.09.)
                                </option>
                            </select>
                        </div>
                        <div class="col-lg-2">
                            <label for="new_payment_collection_method" class="form-label">Naplata</label>
                            <select class="form-select" id="new_payment_collection_method" name="collection_method">
                                <option value="bank" selected>Račun + barkod</option>
                                <option value="cash">Gotovina treneru</option>
                            </select>
                        </div>
                        <div class="col-lg-2">
                            <label for="new_payment_amount" class="form-label">Početni iznos (EUR)</label>
                            <input type="text" class="form-control" id="new_payment_amount" name="amount" placeholder="0.00">
                        </div>
                        <div class="col-lg-2">
                            <label for="new_payment_valid_from" class="form-label">Vrijedi od</label>
                            <input type="date" class="form-control" id="new_payment_valid_from" name="valid_from"
                                   value="{{ now()->toDateString() }}">
                        </div>
                        <div class="col-lg-1">
                            <label class="form-label d-block">Dostupno</label>
                            <input type="hidden" name="enabled" value="0">
                            <input class="form-check-input" type="checkbox" name="enabled" value="1" checked>
                        </div>
                        <div class="col-lg-1 text-end">
                            <button type="submit" class="btn btn-primary w-100">Dodaj</button>
                        </div>
                        <div class="col-12">
                            <div class="small text-muted mb-1">Opis za administratore</div>
                            <input type="text" class="form-control" id="new_payment_description" name="description"
                                   placeholder="Opis (opcionalno)">
                        </div>
                    </div>
        </form>
    </div>
</div>

    </div>
</div>

<script>
    (function () {
        const setupBody = document.getElementById('setup_placanja_dropdown');
        const hideIcon = document.getElementById('skrivanje_setup_placanja');
        const showIcon = document.getElementById('pokazivanje_setup_placanja');
        const toggleSetupPlacanja = function (shouldOpen) {
            if (!setupBody || !hideIcon || !showIcon) {
                return;
            }

            setupBody.classList.toggle('d-none', !shouldOpen);
            hideIcon.classList.toggle('d-none', !shouldOpen);
            showIcon.classList.toggle('d-none', shouldOpen);
        };

        if (showIcon) {
            showIcon.addEventListener('click', function () {
                toggleSetupPlacanja(true);
            });
        }
        if (hideIcon) {
            hideIcon.addEventListener('click', function () {
                toggleSetupPlacanja(false);
            });
        }

        const syncState = function (periodType, periodAnchor, amountInput) {
            if (!periodType || !periodAnchor || !amountInput) {
                return;
            }

            const type = periodType.value;
            const needsAnchor = type === 'seasonal' || type === 'annual';
            const anchorApr = periodAnchor.querySelector('option[value="apr"]');
            const anchorOct = periodAnchor.querySelector('option[value="oct"]');
            const setAnchorLabel = function (anchorOption) {
                if (!anchorOption) {
                    return;
                }

                const seasonalLabel = anchorOption.dataset.labelSeasonal || anchorOption.textContent;
                const annualLabel = anchorOption.dataset.labelAnnual || seasonalLabel;
                anchorOption.textContent = type === 'annual' ? annualLabel : seasonalLabel;
            };

            setAnchorLabel(anchorApr);
            setAnchorLabel(anchorOct);

            periodAnchor.disabled = !needsAnchor;
            if (anchorApr) {
                anchorApr.disabled = !needsAnchor;
            }
            if (anchorOct) {
                anchorOct.disabled = !needsAnchor;
            }

            if (!needsAnchor) {
                periodAnchor.value = '';
            } else if (periodAnchor.value === '') {
                periodAnchor.value = 'oct';
            }

            const isExempt = type === 'exempt';
            amountInput.readOnly = isExempt;
            if (isExempt && amountInput.value !== '0.00') {
                amountInput.value = '0.00';
            }
        };

        const periodType = document.getElementById('new_payment_period_type');
        const periodAnchor = document.getElementById('new_payment_period_anchor');
        const amountInput = document.getElementById('new_payment_amount');
        if (periodType && periodAnchor && amountInput) {
            const syncNewState = function () {
                syncState(periodType, periodAnchor, amountInput);
            };

            periodType.addEventListener('change', syncNewState);
            syncNewState();
        }

        document.querySelectorAll('.js-existing-period-type').forEach(function (typeInput) {
            const optionId = typeInput.getAttribute('data-option-id');
            const anchorInput = document.querySelector('.js-existing-period-anchor[data-option-id="' + optionId + '"]');
            const amountField = document.querySelector('.js-existing-amount[data-option-id="' + optionId + '"]');
            if (!anchorInput || !amountField) {
                return;
            }

            const syncExistingState = function () {
                syncState(typeInput, anchorInput, amountField);
                amountField.dataset.optionPeriodType = typeInput.value;
                amountField.dataset.optionPeriodAnchor = anchorInput.value || '';
            };

            typeInput.addEventListener('change', syncExistingState);
            anchorInput.addEventListener('change', syncExistingState);
            syncExistingState();
        });

        const seasonalIndoorAmountInput = document.querySelector('.js-existing-amount[data-option-period-type="seasonal"][data-option-period-anchor="oct"]');
        const seasonalOutdoorAmountInput = document.querySelector('.js-existing-amount[data-option-period-type="seasonal"][data-option-period-anchor="apr"]');
        const seasonalAmountInput = seasonalIndoorAmountInput || seasonalOutdoorAmountInput;
        const monthlyAmountInput = document.querySelector('.js-existing-amount[data-option-period-type="monthly"]');
        const annualAprAmountInput = document.querySelector('.js-existing-amount[data-option-period-type="annual"][data-option-period-anchor="apr"]');
        const annualOctAmountInput = document.querySelector('.js-existing-amount[data-option-period-type="annual"][data-option-period-anchor="oct"]');
        const autoCalculatedInputs = [monthlyAmountInput, annualAprAmountInput, annualOctAmountInput].filter(Boolean);
        let autoFlagsInitialized = false;

        const parseNumber = function (value) {
            if (typeof value !== 'string') {
                return NaN;
            }

            const normalized = value.replace(/\s+/g, '').replace(',', '.');
            const parsed = Number(normalized);
            return Number.isFinite(parsed) ? parsed : NaN;
        };

        autoCalculatedInputs.forEach(function (input) {
            input.addEventListener('input', function () {
                if (input.dataset.autoWrite === '1') {
                    return;
                }

                input.dataset.manualOverride = '1';
            });
        });

        const setAutoValue = function (input, value) {
            if (!input || input.dataset.manualOverride === '1') {
                return;
            }

            input.dataset.autoWrite = '1';
            input.value = value;
            delete input.dataset.autoWrite;
        };

        const updateDerivedAmounts = function () {
            if (!seasonalAmountInput) {
                return;
            }

            const seasonalAmount = Number.isFinite(parseNumber(seasonalIndoorAmountInput ? seasonalIndoorAmountInput.value : ''))
                ? parseNumber(seasonalIndoorAmountInput.value)
                : parseNumber(seasonalOutdoorAmountInput ? seasonalOutdoorAmountInput.value : '');
            if (!Number.isFinite(seasonalAmount)) {
                return;
            }

            if (!autoFlagsInitialized) {
                const monthlyValue = parseNumber(monthlyAmountInput ? monthlyAmountInput.value : '');
                const annualAprValue = parseNumber(annualAprAmountInput ? annualAprAmountInput.value : '');
                const annualOctValue = parseNumber(annualOctAmountInput ? annualOctAmountInput.value : '');
                const expectedMonthly = seasonalAmount / 6;
                const expectedAnnual = seasonalAmount * 2;

                if (monthlyAmountInput && Number.isFinite(monthlyValue) && Math.abs(monthlyValue - expectedMonthly) > 0.01) {
                    monthlyAmountInput.dataset.manualOverride = '1';
                }
                if (annualAprAmountInput && Number.isFinite(annualAprValue) && Math.abs(annualAprValue - expectedAnnual) > 0.01) {
                    annualAprAmountInput.dataset.manualOverride = '1';
                }
                if (annualOctAmountInput && Number.isFinite(annualOctValue) && Math.abs(annualOctValue - expectedAnnual) > 0.01) {
                    annualOctAmountInput.dataset.manualOverride = '1';
                }

                autoFlagsInitialized = true;
            }

            setAutoValue(monthlyAmountInput, (seasonalAmount / 6).toFixed(2));
            setAutoValue(annualAprAmountInput, (seasonalAmount * 2).toFixed(2));
            setAutoValue(annualOctAmountInput, (seasonalAmount * 2).toFixed(2));
        };

        if (seasonalAmountInput) {
            seasonalAmountInput.addEventListener('input', updateDerivedAmounts);
            if (seasonalOutdoorAmountInput && seasonalOutdoorAmountInput !== seasonalAmountInput) {
                seasonalOutdoorAmountInput.addEventListener('input', updateDerivedAmounts);
            }
            updateDerivedAmounts();
        }
    })();
</script>
