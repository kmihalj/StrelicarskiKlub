@extends('layouts.app')

@auth()
    @if(auth()->user()->rola <= 1)
        @section('content')
            @php
                $filters = $reportFilters ?? [];
                $stats = $reportStats ?? [];
                $rows = $reportRows ?? collect();
                $debtors = $debtorsSummary ?? collect();
                $persons = $personsSummary ?? collect();
                $exportBaseQuery = [
                    'period_preset' => $filters['period_preset'] ?? 'current_season',
                    'date_from' => $filters['date_from'] ?? null,
                    'date_to' => $filters['date_to'] ?? null,
                    'status' => $filters['status'] ?? 'all',
                    'target' => $filters['target'] ?? 'all',
                    'channel' => $filters['channel'] ?? 'all',
                    'model_type' => $filters['model_type'] ?? 'all',
                    'rows_limit' => $filters['rows_limit'] ?? 500,
                ];
            @endphp

            <div class="row g-4">
                <div class="col-12">
                    @include('admin.turniri.placanja', ['paymentSetup' => $paymentSetup])
                </div>

                <div class="col-12">
                    <div class="card shadow-sm">
                        <div class="card-header bg-danger text-white fw-bolder">Izvještaji plaćanja</div>
                        <div class="card-body bg-secondary-subtle">
                            <form method="GET" action="{{ route('admin.placanja.index') }}" class="row g-2 align-items-end">
                                <div class="col-lg-2 col-md-4">
                                    <label for="period_preset" class="form-label mb-1">Razdoblje</label>
                                    <select class="form-select form-select-sm" id="period_preset" name="period_preset">
                                        <option value="current_season" @selected(($filters['period_preset'] ?? '') === 'current_season')>Tekuća sezona</option>
                                        <option value="current_month" @selected(($filters['period_preset'] ?? '') === 'current_month')>Tekući mjesec</option>
                                        <option value="current_year" @selected(($filters['period_preset'] ?? '') === 'current_year')>Tekuća godina</option>
                                        <option value="custom" @selected(($filters['period_preset'] ?? '') === 'custom')>Ručno od-do</option>
                                        <option value="all" @selected(($filters['period_preset'] ?? '') === 'all')>Sve</option>
                                    </select>
                                </div>
                                <div class="col-lg-2 col-md-4">
                                    <label for="date_from" class="form-label mb-1">Od datuma</label>
                                    <input type="date" class="form-control form-control-sm" id="date_from" name="date_from"
                                           value="{{ $filters['date_from'] ?? '' }}">
                                </div>
                                <div class="col-lg-2 col-md-4">
                                    <label for="date_to" class="form-label mb-1">Do datuma</label>
                                    <input type="date" class="form-control form-control-sm" id="date_to" name="date_to"
                                           value="{{ $filters['date_to'] ?? '' }}">
                                </div>
                                <div class="col-lg-2 col-md-4">
                                    <label for="status" class="form-label mb-1">Status</label>
                                    <select class="form-select form-select-sm" id="status" name="status">
                                        <option value="all" @selected(($filters['status'] ?? '') === 'all')>Svi</option>
                                        <option value="open" @selected(($filters['status'] ?? '') === 'open')>Otvoreno</option>
                                        <option value="paid" @selected(($filters['status'] ?? '') === 'paid')>Plaćeno</option>
                                    </select>
                                </div>
                                <div class="col-lg-2 col-md-4">
                                    <label for="target" class="form-label mb-1">Skupina</label>
                                    <select class="form-select form-select-sm" id="target" name="target">
                                        <option value="all" @selected(($filters['target'] ?? '') === 'all')>Svi</option>
                                        <option value="member" @selected(($filters['target'] ?? '') === 'member')>Članovi</option>
                                        <option value="school" @selected(($filters['target'] ?? '') === 'school')>Polaznici škole</option>
                                    </select>
                                </div>
                                <div class="col-lg-2 col-md-4">
                                    <label for="channel" class="form-label mb-1">Naplata</label>
                                    <select class="form-select form-select-sm" id="channel" name="channel">
                                        <option value="all" @selected(($filters['channel'] ?? '') === 'all')>Sve</option>
                                        <option value="bank" @selected(($filters['channel'] ?? '') === 'bank')>Račun</option>
                                        <option value="cash" @selected(($filters['channel'] ?? '') === 'cash')>Gotovina</option>
                                    </select>
                                </div>
                                <div class="col-lg-2 col-md-4">
                                    <label for="model_type" class="form-label mb-1">Tip modela</label>
                                    <select class="form-select form-select-sm" id="model_type" name="model_type">
                                        <option value="all" @selected(($filters['model_type'] ?? '') === 'all')>Svi</option>
                                        <option value="monthly" @selected(($filters['model_type'] ?? '') === 'monthly')>Mjesečno</option>
                                        <option value="seasonal" @selected(($filters['model_type'] ?? '') === 'seasonal')>Sezonski</option>
                                        <option value="annual" @selected(($filters['model_type'] ?? '') === 'annual')>Godišnje</option>
                                        <option value="manual" @selected(($filters['model_type'] ?? '') === 'manual')>Dodatno</option>
                                        <option value="opening" @selected(($filters['model_type'] ?? '') === 'opening')>Početni dug</option>
                                        <option value="school" @selected(($filters['model_type'] ?? '') === 'school')>Školarina</option>
                                    </select>
                                </div>
                                <div class="col-lg-2 col-md-4">
                                    <label for="rows_limit" class="form-label mb-1">Max redaka</label>
                                    <select class="form-select form-select-sm" id="rows_limit" name="rows_limit">
                                        <option value="100" @selected((int)($filters['rows_limit'] ?? 0) === 100)>100</option>
                                        <option value="250" @selected((int)($filters['rows_limit'] ?? 0) === 250)>250</option>
                                        <option value="500" @selected((int)($filters['rows_limit'] ?? 0) === 500)>500</option>
                                        <option value="1000" @selected((int)($filters['rows_limit'] ?? 0) === 1000)>1000</option>
                                    </select>
                                </div>
                                <div class="col-lg-3 col-md-8 d-flex gap-2">
                                    <button type="submit" class="btn btn-primary btn-sm w-100">Primijeni</button>
                                    <a href="{{ route('admin.placanja.index') }}" class="btn btn-outline-secondary btn-sm w-100">Reset</a>
                                    <button type="submit" formaction="{{ route('admin.placanja.export.csv') }}" name="scope" value="rows" class="btn btn-success btn-sm w-100">CSV</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-12">
                    <div class="row g-2">
                        <div class="col-lg-2 col-md-4 col-6">
                            <div class="card h-100">
                                <div class="card-body py-2">
                                    <div class="small text-muted">Uplaćeno</div>
                                    <div class="fw-bold text-success">{{ number_format((float)($stats['total_paid'] ?? 0), 2, ',', '.') }} €</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-2 col-md-4 col-6">
                            <div class="card h-100">
                                <div class="card-body py-2">
                                    <div class="small text-muted">Otvoreni dug</div>
                                    <div class="fw-bold text-danger">{{ number_format((float)($stats['total_open'] ?? 0), 2, ',', '.') }} €</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-2 col-md-4 col-6">
                            <div class="card h-100">
                                <div class="card-body py-2">
                                    <div class="small text-muted">Uplaćeno račun</div>
                                    <div class="fw-bold">{{ number_format((float)($stats['total_paid_bank'] ?? 0), 2, ',', '.') }} €</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-2 col-md-4 col-6">
                            <div class="card h-100">
                                <div class="card-body py-2">
                                    <div class="small text-muted">Uplaćeno gotovina</div>
                                    <div class="fw-bold">{{ number_format((float)($stats['total_paid_cash'] ?? 0), 2, ',', '.') }} €</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-2 col-md-4 col-6">
                            <div class="card h-100">
                                <div class="card-body py-2">
                                    <div class="small text-muted">Dužnici</div>
                                    <div class="fw-bold">{{ (int)($stats['debtors_count'] ?? 0) }}</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-2 col-md-4 col-6">
                            <div class="card h-100">
                                <div class="card-body py-2">
                                    <div class="small text-muted">Stavki (prikaz/ukupno)</div>
                                    <div class="fw-bold">{{ (int)($stats['rows_shown'] ?? 0) }} / {{ (int)($stats['rows_total'] ?? 0) }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-xl-6">
                    <div class="card h-100">
                        <div class="card-header bg-light fw-bold d-flex justify-content-between align-items-center">
                            <span>Popis dužnika</span>
                            <a href="{{ route('admin.placanja.export.csv', array_merge($exportBaseQuery, ['scope' => 'debtors'])) }}"
                               class="btn btn-sm btn-outline-secondary py-0 px-2"
                               title="CSV (dužnici)"
                               aria-label="CSV (dužnici)">⬇</a>
                        </div>
                        <div class="card-body p-0">
                            @if($debtors->count() === 0)
                                <p class="p-3 mb-0">Nema otvorenih dugovanja za odabrani filter.</p>
                            @else
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover align-middle mb-0 js-sortable-table">
                                        <thead class="table-warning">
                                        <tr>
                                            <th data-sort-type="string">Osoba</th>
                                            <th data-sort-type="string">Tip</th>
                                            <th data-sort-type="number">Račun</th>
                                            <th data-sort-type="number">Gotovina</th>
                                            <th data-sort-type="number">Ukupno</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @foreach($debtors as $row)
                                            <tr>
                                                <td>
                                                    @if(!empty($row['profile_url']))
                                                        <a href="{{ $row['profile_url'] }}">{{ $row['person_name'] }}</a>
                                                    @else
                                                        {{ $row['person_name'] }}
                                                    @endif
                                                </td>
                                                <td>{{ $row['entity_type'] === 'school' ? 'Polaznik škole' : 'Član' }}</td>
                                                <td>{{ number_format((float)$row['open_bank'], 2, ',', '.') }} €</td>
                                                <td>{{ number_format((float)$row['open_cash'], 2, ',', '.') }} €</td>
                                                <td class="text-danger fw-bold">{{ number_format((float)$row['open_total'], 2, ',', '.') }} €</td>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="col-12 col-xl-6">
                    <div class="card h-100">
                        <div class="card-header bg-light fw-bold d-flex justify-content-between align-items-center">
                            <span>Sažetak po osobi</span>
                            <a href="{{ route('admin.placanja.export.csv', array_merge($exportBaseQuery, ['scope' => 'persons'])) }}"
                               class="btn btn-sm btn-outline-secondary py-0 px-2"
                               title="CSV (sažetak po osobi)"
                               aria-label="CSV (sažetak po osobi)">⬇</a>
                        </div>
                        <div class="card-body p-0">
                            @if($persons->count() === 0)
                                <p class="p-3 mb-0">Nema podataka za odabrani filter.</p>
                            @else
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover align-middle mb-0 js-sortable-table">
                                        <thead class="table-warning">
                                        <tr>
                                            <th data-sort-type="string">Osoba</th>
                                            <th data-sort-type="string">Tip</th>
                                            <th data-sort-type="number">Uplaćeno</th>
                                            <th data-sort-type="number">Otvoreno</th>
                                            <th data-sort-type="number">Stavki</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @foreach($persons as $row)
                                            <tr>
                                                <td>
                                                    @if(!empty($row['profile_url']))
                                                        <a href="{{ $row['profile_url'] }}">{{ $row['person_name'] }}</a>
                                                    @else
                                                        {{ $row['person_name'] }}
                                                    @endif
                                                </td>
                                                <td>{{ $row['entity_type'] === 'school' ? 'Polaznik škole' : 'Član' }}</td>
                                                <td class="text-success">{{ number_format((float)$row['paid_total'], 2, ',', '.') }} €</td>
                                                <td class="@if((float)$row['open_total'] > 0) text-danger fw-bold @endif">
                                                    {{ number_format((float)$row['open_total'], 2, ',', '.') }} €
                                                </td>
                                                <td>{{ (int)$row['items_count'] }}</td>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="col-12">
                    <div class="card">
                        <div class="card-header bg-light fw-bold d-flex justify-content-between align-items-center">
                            <span>Sve stavke plaćanja</span>
                            <a href="{{ route('admin.placanja.export.csv', array_merge($exportBaseQuery, ['scope' => 'rows'])) }}"
                               class="btn btn-sm btn-outline-secondary py-0 px-2"
                               title="CSV (sve stavke)"
                               aria-label="CSV (sve stavke)">⬇</a>
                        </div>
                        <div class="card-body p-0">
                            @if($rows->count() === 0)
                                <p class="p-3 mb-0">Nema stavki za odabrani filter.</p>
                            @else
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover align-middle mb-0 js-sortable-table">
                                        <thead class="table-warning">
                                        <tr>
                                            <th data-sort-type="date">Datum</th>
                                            <th data-sort-type="string">Osoba</th>
                                            <th data-sort-type="string">Tip</th>
                                            <th data-sort-type="string">Model</th>
                                            <th data-sort-type="string">Naziv stavke</th>
                                            <th data-sort-type="string">Razdoblje</th>
                                            <th data-sort-type="string">Naplata</th>
                                            <th data-sort-type="number">Iznos</th>
                                            <th data-sort-type="string">Status</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @foreach($rows as $row)
                                            <tr>
                                                <td>{{ $row['reference_date_label'] }}</td>
                                                <td>
                                                    @if(!empty($row['profile_url']))
                                                        <a href="{{ $row['profile_url'] }}">{{ $row['person_name'] }}</a>
                                                    @else
                                                        {{ $row['person_name'] }}
                                                    @endif
                                                </td>
                                                <td>{{ $row['entity_type'] === 'school' ? 'Polaznik škole' : 'Član' }}</td>
                                                <td>{{ $row['model_name'] }}</td>
                                                <td>{{ $row['title'] }}</td>
                                                <td>{{ $row['period_label'] }}</td>
                                                <td>{{ $row['channel'] === 'cash' ? 'Gotovina' : 'Račun' }}</td>
                                                <td>{{ number_format((float)$row['amount'], 2, ',', '.') }} €</td>
                                                <td>
                                                    @if($row['status'] === 'paid')
                                                        <span class="badge bg-success">Plaćeno</span>
                                                    @else
                                                        <span class="badge bg-danger">Otvoreno</span>
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
            </div>

            <script>
                (function () {
                    const presetInput = document.getElementById('period_preset');
                    const dateFromInput = document.getElementById('date_from');
                    const dateToInput = document.getElementById('date_to');

                    const toIsoDate = function (date) {
                        const year = date.getFullYear();
                        const month = String(date.getMonth() + 1).padStart(2, '0');
                        const day = String(date.getDate()).padStart(2, '0');
                        return year + '-' + month + '-' + day;
                    };

                    const applyPresetDates = function (preset) {
                        const now = new Date();
                        const year = now.getFullYear();
                        const month = now.getMonth() + 1;

                        if (preset === 'current_month') {
                            dateFromInput.value = toIsoDate(new Date(year, now.getMonth(), 1));
                            dateToInput.value = toIsoDate(new Date(year, now.getMonth() + 1, 0));
                            return;
                        }

                        if (preset === 'current_year') {
                            dateFromInput.value = toIsoDate(new Date(year, 0, 1));
                            dateToInput.value = toIsoDate(new Date(year, 11, 31));
                            return;
                        }

                        if (preset === 'current_season') {
                            if (month >= 10) {
                                dateFromInput.value = toIsoDate(new Date(year, 9, 1));
                                dateToInput.value = toIsoDate(new Date(year + 1, 2, 31));
                                return;
                            }

                            if (month >= 4) {
                                dateFromInput.value = toIsoDate(new Date(year, 3, 1));
                                dateToInput.value = toIsoDate(new Date(year, 8, 30));
                                return;
                            }

                            dateFromInput.value = toIsoDate(new Date(year - 1, 9, 1));
                            dateToInput.value = toIsoDate(new Date(year, 2, 31));
                        }
                    };

                    const syncDateInputsState = function (isInitial) {
                        if (!presetInput || !dateFromInput || !dateToInput) {
                            return;
                        }

                        const preset = presetInput.value;
                        const isAll = preset === 'all';
                        dateFromInput.disabled = isAll;
                        dateToInput.disabled = isAll;

                        if (isAll) {
                            dateFromInput.value = '';
                            dateToInput.value = '';
                            return;
                        }

                        if (!isInitial && (preset === 'current_month' || preset === 'current_year' || preset === 'current_season')) {
                            applyPresetDates(preset);
                        }
                    };

                    const parseDateValue = function (raw) {
                        const trimmed = raw.trim();
                        const match = trimmed.match(/^(\d{1,2})\.(\d{1,2})\.(\d{4})/);
                        if (match) {
                            const day = match[1].padStart(2, '0');
                            const month = match[2].padStart(2, '0');
                            const year = match[3];
                            return Number(year + month + day);
                        }

                        const timestamp = Date.parse(trimmed);
                        return Number.isNaN(timestamp) ? Number.NEGATIVE_INFINITY : timestamp;
                    };

                    const parseNumberValue = function (raw) {
                        const normalized = raw
                            .replace(/\./g, '')
                            .replace(',', '.')
                            .replace(/[^\d.-]/g, '');
                        const parsed = Number(normalized);
                        return Number.isNaN(parsed) ? 0 : parsed;
                    };

                    const getComparableValue = function (cell, sortType) {
                        const raw = ((cell && cell.dataset && cell.dataset.sortValue) ? cell.dataset.sortValue : cell ? cell.textContent : '')
                            .toString()
                            .trim();
                        if (sortType === 'number') {
                            return parseNumberValue(raw);
                        }
                        if (sortType === 'date') {
                            return parseDateValue(raw);
                        }

                        return raw.toLocaleLowerCase('hr-HR');
                    };

                    const initSortableTables = function () {
                        document.querySelectorAll('table.js-sortable-table').forEach(function (table) {
                            const tbody = table.tBodies[0];
                            if (!tbody) {
                                return;
                            }

                            const headers = Array.from(table.querySelectorAll('thead th'));
                            headers.forEach(function (header, index) {
                                header.classList.add('user-select-none');
                                header.style.cursor = 'pointer';

                                header.addEventListener('click', function () {
                                    const currentDirection = header.dataset.sortDirection === 'asc' ? 'asc' : 'desc';
                                    const nextDirection = currentDirection === 'asc' ? 'desc' : 'asc';
                                    const sortType = header.dataset.sortType || 'string';

                                    headers.forEach(function (otherHeader) {
                                        if (otherHeader !== header) {
                                            delete otherHeader.dataset.sortDirection;
                                        }
                                    });
                                    header.dataset.sortDirection = nextDirection;

                                    const multiplier = nextDirection === 'asc' ? 1 : -1;
                                    const rows = Array.from(tbody.querySelectorAll('tr'));
                                    rows.sort(function (rowA, rowB) {
                                        const valueA = getComparableValue(rowA.cells[index], sortType);
                                        const valueB = getComparableValue(rowB.cells[index], sortType);

                                        if (sortType === 'number' || sortType === 'date') {
                                            return (valueA - valueB) * multiplier;
                                        }

                                        return valueA.localeCompare(valueB, 'hr', {sensitivity: 'base'}) * multiplier;
                                    });

                                    rows.forEach(function (row) {
                                        tbody.appendChild(row);
                                    });
                                });
                            });
                        });
                    };

                    if (presetInput) {
                        presetInput.addEventListener('change', function () {
                            syncDateInputsState(false);
                        });
                        syncDateInputsState(true);
                    }

                    initSortableTables();
                })();
            </script>
        @endsection
    @else
        @section('content')
            @include('layouts.neovlasteno')
        @endsection
    @endif
@endauth

@guest()
    @section('content')
        @include('layouts.neovlasteno')
    @endsection
@endguest
