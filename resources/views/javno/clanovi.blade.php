{{-- Popis članova kluba s osnovnim podacima i poveznicama na profile. --}}
﻿@extends('layouts.app')
@auth()
    @if(auth()->user()->imaPravoAdminOrMember())
        @section('content')
            @php
                $mozeNapredniPrikaz = auth()->user()->imaPravoAdminOrMember();
                $jeAdmin = (int)auth()->user()->rola === 1;
                $showPaymentColumn = ($showPaymentColumn ?? false) && $jeAdmin;
                $brojStupaca = 3 + ($mozeNapredniPrikaz ? 2 : 0) + ($jeAdmin ? 1 : 0) + ($showPaymentColumn ? 1 : 0);
            @endphp
            <style>
                /*noinspection CssUnusedSymbol*/
                .clanovi-header-control {
                    appearance: none;
                    background: transparent;
                    border: 0;
                    padding: 0;
                    margin: 0;
                    color: inherit;
                    font: inherit;
                    line-height: inherit;
                    cursor: pointer;
                    text-align: left;
                }

                .clanovi-header-control:hover {
                    text-decoration: underline;
                    text-underline-offset: 2px;
                }

                .clanovi-header-control.is-active {
                    font-weight: 700;
                }

                /*noinspection CssUnusedSymbol*/
                .clanovi-sort-icon {
                    display: inline-flex;
                    width: 1rem;
                    min-width: 1rem;
                    height: 1rem;
                    align-items: center;
                    justify-content: center;
                    vertical-align: middle;
                }

                /*noinspection CssUnusedSymbol*/
                .clanovi-sort-icon .sort-icon-svg {
                    display: none;
                    width: 1rem;
                    height: 1rem;
                    fill: currentColor;
                }

                /*noinspection CssUnusedSymbol*/
                .clanovi-sort-icon[data-sort-state='both'] {
                    color: #6c757d;
                }

                /*noinspection CssUnusedSymbol*/
                .clanovi-sort-icon[data-sort-state='both'] .sort-icon-both,
                .clanovi-sort-icon[data-sort-state='asc'] .sort-icon-asc,
                .clanovi-sort-icon[data-sort-state='desc'] .sort-icon-desc {
                    display: block;
                }

                /*noinspection CssUnusedSymbol*/
                .clanovi-sort-icon[data-sort-state='asc'],
                .clanovi-sort-icon[data-sort-state='desc'] {
                    color: #212529;
                }

                .clanovi-header-control:focus-visible {
                    outline: 2px solid rgba(33, 37, 41, 0.35);
                    outline-offset: 2px;
                    border-radius: 2px;
                }

            </style>

            <div class="container-xxl bg-white shadow">
                <div class="row justify-content-center pt-3 shadow">
                    @if(auth()->user()->rola == 1)
                        <div class="col-12 m-1">
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <button class="btn btn-danger me-md-2" type="button" data-bs-toggle="modal" data-bs-target="#UnosClana_modal">
                                    Dodaj člana
                                </button>
                            </div>
                        </div>
                        @include('admin.clanovi.modal_za_unos')
                    @endif

                    @php
                        $brojAktivnihClanova = $clanovi->filter(fn ($clan) => (int)$clan->aktivan === 1)->count();
                    @endphp

                    <div class="col-lg-12 justify-content-center m-3 js-clanovi-table-wrap">
                        <div class="row g-2 align-items-end mb-3 js-clanovi-controls">
                            <div class="col-12 col-lg-4">
                                <label for="pretraga-clanova-aktivni" class="form-label mb-1">Pretraga (ime/prezime)</label>
                                <input type="text" id="pretraga-clanova-aktivni" class="form-control form-control-sm js-clanovi-search"
                                       placeholder="Upišite ime ili prezime">
                            </div>
                            <div class="col-12 col-lg-8">
                                <div class="d-flex flex-wrap justify-content-lg-end align-items-end gap-2">
                                    <span class="fw-semibold">Br. članova: {{ $brojAktivnihClanova }}</span>
                                    @if($jeAdmin)
                                        <button type="button" class="btn btn-outline-success btn-sm"
                                                data-bs-toggle="modal" data-bs-target="#CsvExportClanova_modal">
                                            CSV Export
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0 border js-clanovi-table">
                                <thead class="table-warning">
                                <tr>
                                    @if($jeAdmin)
                                        <th class="text-center" style="width: 44px;"></th>
                                    @endif
                                    <th>
                                        <div class="d-flex flex-wrap align-items-center gap-1">
                                            <button type="button" class="clanovi-header-control js-clanovi-sort-btn is-active" data-sort-field="name">
                                                <span class="me-1">@include('javno.partials.sortArrowIcon', ['state' => 'asc'])</span>
                                                <span class="js-sort-label js-clanovi-name-header-label">Prezime i ime</span>
                                            </button>
                                            <button type="button"
                                                    class="clanovi-header-control js-clanovi-name-order-btn ms-1"
                                                    title="Prebaci na Ime Prezime"
                                                    aria-label="Prebaci na Ime Prezime">
                                                <span class="fw-bold">↔</span>
                                            </button>
                                        </div>
                                    </th>
                                    @if($mozeNapredniPrikaz)
                                        <th>Telefon</th>
                                        <th>
                                            <button type="button" class="clanovi-header-control js-clanovi-sort-btn" data-sort-field="datum_rodjenja">
                                                <span class="me-1">@include('javno.partials.sortArrowIcon', ['state' => 'both'])</span>
                                                <span class="js-sort-label">Datum rođenja</span>
                                            </button>
                                        </th>
                                    @endif
                                    <th>
                                        <button type="button" class="clanovi-header-control js-clanovi-sort-btn" data-sort-field="godina_registracije">
                                            <span class="me-1">@include('javno.partials.sortArrowIcon', ['state' => 'both'])</span>
                                            <span class="js-sort-label">Godina registracije</span>
                                        </button>
                                        @if($mozeNapredniPrikaz)
                                            <br>Broj licence
                                        @endif
                                    </th>
                                    <th>
                                        <button type="button" class="clanovi-header-control js-clanovi-sort-btn" data-sort-field="lijecnicki">
                                            <span class="me-1">@include('javno.partials.sortArrowIcon', ['state' => 'both'])</span>
                                            <span class="js-sort-label">Trajanje liječničkog</span>
                                        </button>
                                    </th>
                                    @if($showPaymentColumn)
                                        <th>Plaćanja</th>
                                    @endif
                                </tr>
                                </thead>
                                <tbody class="js-clanovi-body">
                                @if($clanovi->count() == 0)
                                    <tr>
                                        <td colspan="{{ $brojStupaca }}" class="text-center">
                                            <div class="ms-3">
                                                <p class="fw-bold mb-1">Nema unešenih članova</p>
                                            </div>
                                        </td>
                                    </tr>
                                @else
                                    @foreach($clanovi as $clan)
                                        @if($clan->aktivan)
                                            @include('javno.clanovi_redak_u_popisu')
                                        @endif
                                    @endforeach
                                @endif
                                </tbody>
                            </table>
                        </div>
                        <div class="small text-muted mt-2 js-clanovi-no-results d-none">Nema rezultata za traženu pretragu.</div>
                    </div>
                </div>
            </div>

            @if($jeAdmin)
                @include('javno.partials.csvExportClanovaModal')
            @endif

            @if(auth()->user()->rola <= 1)
                <div class="container-xxl shadow mt-3">
                    <div class="row justify-content-center p-2 shadow bg-danger fw-bolder">
                        <div class="col-lg-12 text-white">
                            Neaktivni članovi
                            <span id="skrivanje_podataka"
                                  class="text-white d-none js-neaktivni-clanovi-toggle"
                                  style="float: right; cursor: pointer;"
                                  data-show-panel="0">_</span>
                            <span id="pokazivanje_podataka"
                                  class="text-white js-neaktivni-clanovi-toggle"
                                  style="float: right; cursor: pointer;"
                                  data-show-panel="1">+</span>
                        </div>
                    </div>
                </div>

                <div id="popis-neaktivnih" class="container-xxl bg-secondary-subtle shadow d-none">
                    <div class="row justify-content-center pt-3 shadow">
                        <div class="col-lg-12 justify-content-center m-3 js-clanovi-table-wrap">
                            <div class="row g-2 align-items-end mb-3 js-clanovi-controls">
                                <div class="col-12 col-lg-4">
                                    <label for="pretraga-clanova-neaktivni" class="form-label mb-1">Pretraga (ime/prezime)</label>
                                    <input type="text" id="pretraga-clanova-neaktivni" class="form-control form-control-sm js-clanovi-search"
                                           placeholder="Upišite ime ili prezime">
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0 border js-clanovi-table">
                                    <thead class="table-warning">
                                    <tr>
                                        @if($jeAdmin)
                                            <th class="text-center" style="width: 44px;"></th>
                                        @endif
                                        <th>
                                        <div class="d-flex flex-wrap align-items-center gap-1">
                                            <button type="button" class="clanovi-header-control js-clanovi-sort-btn is-active" data-sort-field="name">
                                                    <span class="me-1">@include('javno.partials.sortArrowIcon', ['state' => 'asc'])</span>
                                                    <span class="js-sort-label js-clanovi-name-header-label">Prezime i ime</span>
                                                </button>
                                                <button type="button"
                                                        class="clanovi-header-control js-clanovi-name-order-btn ms-1"
                                                        title="Prebaci na Ime Prezime"
                                                        aria-label="Prebaci na Ime Prezime">
                                                    <span class="fw-bold">↔</span>
                                                </button>
                                            </div>
                                        </th>
                                        <th>Telefon</th>
                                        <th>
                                            <button type="button" class="clanovi-header-control js-clanovi-sort-btn" data-sort-field="datum_rodjenja">
                                                <span class="me-1">@include('javno.partials.sortArrowIcon', ['state' => 'both'])</span>
                                                <span class="js-sort-label">Datum rođenja</span>
                                            </button>
                                        </th>
                                        <th>
                                            <button type="button" class="clanovi-header-control js-clanovi-sort-btn" data-sort-field="godina_registracije">
                                                <span class="me-1">@include('javno.partials.sortArrowIcon', ['state' => 'both'])</span>
                                                <span class="js-sort-label">Godina registracije</span>
                                            </button>
                                            <br>Broj licence
                                        </th>
                                        <th>
                                            <button type="button" class="clanovi-header-control js-clanovi-sort-btn" data-sort-field="lijecnicki">
                                                <span class="me-1">@include('javno.partials.sortArrowIcon', ['state' => 'both'])</span>
                                                <span class="js-sort-label">Trajanje liječničkog</span>
                                            </button>
                                        </th>
                                        @if($showPaymentColumn)
                                            <th>Plaćanja</th>
                                        @endif
                                    </tr>
                                    </thead>
                                    <tbody class="js-clanovi-body">
                                    @if($clanovi->count() == 0)
                                        <tr>
                                            <td colspan="{{ $brojStupaca }}" class="text-center">
                                                <div class="ms-3">
                                                    <p class="fw-bold mb-1">Nema unešenih članova</p>
                                                </div>
                                            </td>
                                        </tr>
                                    @else
                                        @foreach($clanovi as $clan)
                                            @if(!($clan->aktivan))
                                                @include('javno.clanovi_redak_u_popisu')
                                            @endif
                                        @endforeach
                                    @endif
                                    </tbody>
                                </table>
                            </div>
                            <div class="small text-muted mt-2 js-clanovi-no-results d-none">Nema rezultata za traženu pretragu.</div>
                        </div>
                    </div>
                </div>
            @endif

            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    const panel = /** @type {HTMLElement|null} */ (document.getElementById('popis-neaktivnih'));
                    const hideIcon = /** @type {HTMLElement|null} */ (document.getElementById('skrivanje_podataka'));
                    const showIcon = /** @type {HTMLElement|null} */ (document.getElementById('pokazivanje_podataka'));
                    const toggleNeaktivniClanovi = function (shouldShow) {
                        if (!panel || !hideIcon || !showIcon) {
                            return;
                        }

                        panel.classList.toggle('d-none', !shouldShow);
                        hideIcon.classList.toggle('d-none', !shouldShow);
                        showIcon.classList.toggle('d-none', shouldShow);
                    };

                    document.querySelectorAll('.js-neaktivni-clanovi-toggle').forEach(function (toggleElement) {
                        toggleElement.addEventListener('click', function () {
                            const shouldShow = toggleElement.getAttribute('data-show-panel') === '1';
                            toggleNeaktivniClanovi(shouldShow);
                        });
                    });

                    const csvYearSelect = /** @type {HTMLSelectElement|null} */ (document.getElementById('csv-export-stat-years'));
                    const csvYearHelp = /** @type {HTMLElement|null} */ (document.getElementById('csv-export-stat-year-help'));
                    const csvYearFields = Array.from(document.querySelectorAll('.js-csv-year-field'));
                    const updateCsvYearControl = function () {
                        if (!csvYearSelect || csvYearFields.length === 0) {
                            return;
                        }

                        const hasYearBasedField = csvYearFields.some((field) => field instanceof HTMLInputElement && field.checked);
                        csvYearSelect.disabled = !hasYearBasedField;
                        csvYearSelect.required = hasYearBasedField;

                        if (csvYearHelp) {
                            csvYearHelp.classList.toggle('text-danger', hasYearBasedField);
                            csvYearHelp.classList.toggle('fw-semibold', hasYearBasedField);
                        }
                    };
                    csvYearFields.forEach((field) => {
                        field.addEventListener('change', updateCsvYearControl);
                    });
                    updateCsvYearControl();

                    const tableWrappers = Array.from(document.querySelectorAll('.js-clanovi-table-wrap'));

                    const normalize = (value) => (value || '').toString().trim().toLocaleLowerCase('hr-HR');

                    const compareText = (a, b, direction) => {
                        const comparison = a.localeCompare(b, 'hr-HR', {sensitivity: 'base'});
                        return direction === 'desc' ? -comparison : comparison;
                    };

                    const compareNumeric = (a, b, direction) => {
                        const aMissing = a === null;
                        const bMissing = b === null;

                        if (aMissing && bMissing) {
                            return 0;
                        }
                        if (aMissing) {
                            return 1;
                        }
                        if (bMissing) {
                            return -1;
                        }

                        const comparison = a - b;
                        return direction === 'desc' ? -comparison : comparison;
                    };

                    const parseNumericData = (row, key) => {
                        const raw = row.dataset[key];

                        if (raw === undefined || raw === null || raw === '') {
                            return null;
                        }

                        const parsed = Number(raw);
                        return Number.isFinite(parsed) ? parsed : null;
                    };

                    const compareByName = (rowA, rowB, direction, order) => {
                        const firstKey = order === 'ime_prezime' ? 'ime' : 'prezime';
                        const secondKey = firstKey === 'ime' ? 'prezime' : 'ime';

                        const firstComparison = compareText(
                            normalize(rowA.dataset[firstKey]),
                            normalize(rowB.dataset[firstKey]),
                            direction
                        );
                        if (firstComparison !== 0) {
                            return firstComparison;
                        }

                        return compareText(
                            normalize(rowA.dataset[secondKey]),
                            normalize(rowB.dataset[secondKey]),
                            direction
                        );
                    };

                    tableWrappers.forEach((wrapper) => {
                        const controls = wrapper.querySelector('.js-clanovi-controls');
                        const searchInput = wrapper.querySelector('.js-clanovi-search');
                        const sortButtons = Array.from(wrapper.querySelectorAll('.js-clanovi-sort-btn'));
                        const nameOrderButton = wrapper.querySelector('.js-clanovi-name-order-btn');
                        const tableBody = wrapper.querySelector('.js-clanovi-body');
                        const noResults = wrapper.querySelector('.js-clanovi-no-results');
                        const nameHeaderLabel = wrapper.querySelector('.js-clanovi-name-header-label');

                        if (!searchInput || sortButtons.length === 0 || !nameOrderButton || !tableBody) {
                            return;
                        }

                        const allRows = Array.from(tableBody.querySelectorAll('.js-clan-row'));
                        const state = {
                            sortField: 'name',
                            sortDirection: 'asc',
                            nameOrder: 'prezime_ime',
                        };

                        if (allRows.length === 0) {
                            if (controls) {
                                controls.classList.add('d-none');
                            }
                            return;
                        }

                        const updateSortButtons = () => {
                            sortButtons.forEach((button) => {
                                const isActive = button.dataset.sortField === state.sortField;
                                const arrow = button.querySelector('.js-sort-arrow');

                                button.classList.toggle('is-active', isActive);

                                if (arrow) {
                                    arrow.dataset.sortState = isActive
                                        ? (state.sortDirection === 'asc' ? 'asc' : 'desc')
                                        : 'both';
                                }
                            });
                        };

                        const applyNameOrder = () => {
                            const isImePrezime = state.nameOrder === 'ime_prezime';

                            allRows.forEach((row) => {
                                const link = row.querySelector('.js-ime-prezime-link');

                                if (!link) {
                                    return;
                                }

                                if (isImePrezime) {
                                    link.textContent = link.dataset.imePrezime || link.textContent;
                                } else {
                                    link.textContent = link.dataset.prezimeIme || link.textContent;
                                }
                            });

                            if (nameHeaderLabel) {
                                nameHeaderLabel.textContent = isImePrezime ? 'Ime i prezime' : 'Prezime i ime';
                            }

                            const sljedeciPrikaz = isImePrezime ? 'Prezime Ime' : 'Ime Prezime';
                            nameOrderButton.setAttribute('title', `Prebaci na ${sljedeciPrikaz}`);
                            nameOrderButton.setAttribute('aria-label', `Prebaci na ${sljedeciPrikaz}`);
                        };

                        const sortRows = () => {
                            const sortedRows = [...allRows].sort((rowA, rowB) => {
                                let comparison = 0;

                                if (state.sortField === 'name') {
                                    comparison = compareByName(rowA, rowB, state.sortDirection, state.nameOrder);
                                } else if (state.sortField === 'datum_rodjenja') {
                                    comparison = compareNumeric(
                                        parseNumericData(rowA, 'datumRodjenja'),
                                        parseNumericData(rowB, 'datumRodjenja'),
                                        state.sortDirection
                                    );
                                } else if (state.sortField === 'godina_registracije') {
                                    comparison = compareNumeric(
                                        parseNumericData(rowA, 'godinaRegistracije'),
                                        parseNumericData(rowB, 'godinaRegistracije'),
                                        state.sortDirection
                                    );
                                } else if (state.sortField === 'lijecnicki') {
                                    comparison = compareNumeric(
                                        parseNumericData(rowA, 'lijecnickiDo'),
                                        parseNumericData(rowB, 'lijecnickiDo'),
                                        state.sortDirection
                                    );
                                }

                                if (comparison === 0) {
                                    comparison = compareByName(rowA, rowB, state.sortDirection, state.nameOrder);
                                }

                                return comparison;
                            });

                            sortedRows.forEach((row) => tableBody.appendChild(row));
                        };

                        const applySearch = () => {
                            const term = normalize(searchInput.value);
                            let visibleRows = 0;

                            allRows.forEach((row) => {
                                const ime = normalize(row.dataset.ime);
                                const prezime = normalize(row.dataset.prezime);
                                const prezimeIme = `${prezime} ${ime}`.trim();
                                const imePrezime = `${ime} ${prezime}`.trim();

                                const isMatch = term === ''
                                    || ime.includes(term)
                                    || prezime.includes(term)
                                    || prezimeIme.includes(term)
                                    || imePrezime.includes(term);

                                row.classList.toggle('d-none', !isMatch);
                                if (isMatch) {
                                    visibleRows++;
                                }
                            });

                            if (noResults) {
                                noResults.classList.toggle('d-none', term === '' || visibleRows > 0);
                            }
                        };

                        const renderTable = () => {
                            applyNameOrder();
                            sortRows();
                            applySearch();
                            updateSortButtons();
                        };

                        searchInput.addEventListener('input', applySearch);
                        sortButtons.forEach((button) => {
                            button.addEventListener('click', () => {
                                const clickedField = button.dataset.sortField || 'name';

                                if (state.sortField === clickedField) {
                                    state.sortDirection = state.sortDirection === 'asc' ? 'desc' : 'asc';
                                } else {
                                    state.sortField = clickedField;
                                    state.sortDirection = 'asc';
                                }

                                updateSortButtons();
                                sortRows();
                                applySearch();
                            });
                        });
                        nameOrderButton.addEventListener('click', () => {
                            state.nameOrder = state.nameOrder === 'prezime_ime' ? 'ime_prezime' : 'prezime_ime';
                            applyNameOrder();
                            sortRows();
                            applySearch();
                        });

                        renderTable();
                    });
                });
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
