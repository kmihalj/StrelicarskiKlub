{{-- Administratorski popis korisničkih računa s rolama i povezivanjem na člana/polaznika. --}}
@extends('layouts.app')

@section('content')
    <style>
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

        .clanovi-sort-icon {
            display: inline-flex;
            width: 1rem;
            min-width: 1rem;
            height: 1rem;
            align-items: center;
            justify-content: center;
            vertical-align: middle;
        }

        .clanovi-sort-icon .sort-icon-svg {
            display: none;
            width: 1rem;
            height: 1rem;
            fill: currentColor;
        }

        .clanovi-sort-icon[data-sort-state='both'] {
            color: #6c757d;
        }

        .clanovi-sort-icon[data-sort-state='both'] .sort-icon-both,
        .clanovi-sort-icon[data-sort-state='asc'] .sort-icon-asc,
        .clanovi-sort-icon[data-sort-state='desc'] .sort-icon-desc {
            display: block;
        }

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
            <div class="col-lg-12 m-3 js-korisnici-table-wrap">
                <div class="row g-2 align-items-end mb-3 js-korisnici-controls">
                    <div class="col-12 col-lg-4">
                        <label for="pretraga-korisnika" class="form-label mb-1">Pretraga (ime/prezime)</label>
                        <input type="text" id="pretraga-korisnika" class="form-control form-control-sm js-korisnici-search"
                               placeholder="Upišite ime ili prezime">
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 border js-korisnici-table">
                        <thead class="table-warning">
                        <tr>
                            <th>
                                <div class="d-flex flex-wrap align-items-center gap-1">
                                    <button type="button" class="clanovi-header-control js-korisnici-sort-btn is-active" data-sort-field="name">
                                        <span class="me-1">@include('javno.partials.sortArrowIcon', ['state' => 'asc'])</span>
                                        <span class="js-sort-label js-korisnici-name-header-label">Prezime i ime</span>
                                    </button>
                                    <button type="button"
                                            class="clanovi-header-control js-korisnici-name-order-btn ms-1"
                                            title="Prebaci na Ime Prezime"
                                            aria-label="Prebaci na Ime Prezime">
                                        <span class="fw-bold">↔</span>
                                    </button>
                                </div>
                            </th>
                            <th>E-mail</th>
                            <th>Telefon</th>
                            <th>OIB</th>
                            <th>
                                <button type="button" class="clanovi-header-control js-korisnici-sort-btn" data-sort-field="rola">
                                    <span class="me-1">@include('javno.partials.sortArrowIcon', ['state' => 'both'])</span>
                                    <span class="js-sort-label">Rola</span>
                                </button>
                            </th>
                            <th>Roditelj</th>
                        </tr>
                        </thead>
                        <tbody class="js-korisnici-body">
                        @foreach($users as $korisnik)
                            @php
                                $dijeloviImena = preg_split('/\s+/u', trim((string)$korisnik->name)) ?: [];
                                $ime = $dijeloviImena[0] ?? '';
                                $prezime = count($dijeloviImena) > 1 ? implode(' ', array_slice($dijeloviImena, 1)) : '';
                                $imePrezime = trim(($ime . ' ' . $prezime));
                                $prezimeIme = trim(($prezime !== '' ? ($prezime . ' ') : '') . $ime);

                                if ($imePrezime === '') {
                                    $imePrezime = (string)$korisnik->name;
                                }
                                if ($prezimeIme === '') {
                                    $prezimeIme = (string)$korisnik->name;
                                }

                                $rolaLabel = match ((int)$korisnik->rola) {
                                    1 => '1 - Admin',
                                    2 => '2 - Član',
                                    3 => '3 - Korisnik',
                                    4 => '4 - Polaznik škole',
                                    default => (string)$korisnik->rola,
                                };
                            @endphp
                            <tr class="js-korisnik-row"
                                data-ime="{{ mb_strtolower($ime) }}"
                                data-prezime="{{ mb_strtolower($prezime) }}"
                                data-rola="{{ (int)$korisnik->rola }}">
                                <td>
                                    <a class="js-ime-prezime-link link-primary link-offset-2 link-underline-opacity-0 link-underline-opacity-0-hover fw-bold"
                                       data-prezime-ime="{{ $prezimeIme }}"
                                       data-ime-prezime="{{ $imePrezime }}"
                                       href="{{ route('admin.korisnici.edit', $korisnik) }}">{{ $prezimeIme }}</a>
                                </td>
                                <td>
                                    @if(!empty($korisnik->email))
                                        <a href="mailto:{{ $korisnik->email }}">{{ $korisnik->email }}</a>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    @if(!empty($korisnik->br_telefona))
                                        <a href="tel:{{ $korisnik->br_telefona }}">{{ $korisnik->br_telefona }}</a>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>{{ !empty($korisnik->oib) ? $korisnik->oib : '-' }}</td>
                                <td>{{ $rolaLabel }}</td>
                                <td>@if((bool)$korisnik->je_roditelj) DA @else - @endif</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="small text-muted mt-2 js-korisnici-no-results d-none">Nema rezultata za traženu pretragu.</div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const wrapper = document.querySelector('.js-korisnici-table-wrap');
            const tableBody = document.querySelector('.js-korisnici-body');
            const sortButtons = Array.from(document.querySelectorAll('.js-korisnici-sort-btn'));
            const nameOrderButton = document.querySelector('.js-korisnici-name-order-btn');
            const nameHeaderLabel = document.querySelector('.js-korisnici-name-header-label');
            const controls = document.querySelector('.js-korisnici-controls');
            const searchInput = document.querySelector('.js-korisnici-search');
            const noResults = document.querySelector('.js-korisnici-no-results');

            if (!wrapper || !tableBody || sortButtons.length === 0 || !nameOrderButton || !searchInput) {
                return;
            }

            const allRows = Array.from(tableBody.querySelectorAll('.js-korisnik-row'));
            if (allRows.length === 0) {
                if (controls) {
                    controls.classList.add('d-none');
                }
                return;
            }

            const state = {
                sortField: 'name',
                sortDirection: 'asc',
                nameOrder: 'prezime_ime',
            };

            const normalize = function (value) {
                return (value || '').toString().trim().toLocaleLowerCase('hr-HR');
            };

            const compareText = function (a, b, direction) {
                const comparison = a.localeCompare(b, 'hr-HR', {sensitivity: 'base'});
                return direction === 'desc' ? -comparison : comparison;
            };

            const compareNumeric = function (a, b, direction) {
                const comparison = a - b;
                return direction === 'desc' ? -comparison : comparison;
            };

            const compareByName = function (rowA, rowB, direction, order) {
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

            const updateSortButtons = function () {
                sortButtons.forEach(function (button) {
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

            const applyNameOrder = function () {
                const isImePrezime = state.nameOrder === 'ime_prezime';

                allRows.forEach(function (row) {
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

            const sortRows = function () {
                const sortedRows = [...allRows].sort(function (rowA, rowB) {
                    let comparison;

                    if (state.sortField === 'rola') {
                        comparison = compareNumeric(
                            Number(rowA.dataset.rola || 0),
                            Number(rowB.dataset.rola || 0),
                            state.sortDirection
                        );
                    } else {
                        comparison = compareByName(rowA, rowB, state.sortDirection, state.nameOrder);
                    }

                    if (comparison === 0) {
                        comparison = compareByName(rowA, rowB, state.sortDirection, state.nameOrder);
                    }

                    return comparison;
                });

                sortedRows.forEach(function (row) {
                    tableBody.appendChild(row);
                });
            };

            const applySearch = function () {
                const term = normalize(searchInput.value);
                let visibleRows = 0;

                allRows.forEach(function (row) {
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

            const renderTable = function () {
                applyNameOrder();
                sortRows();
                applySearch();
                updateSortButtons();
            };

            searchInput.addEventListener('input', applySearch);

            sortButtons.forEach(function (button) {
                button.addEventListener('click', function () {
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

            nameOrderButton.addEventListener('click', function () {
                state.nameOrder = state.nameOrder === 'prezime_ime' ? 'ime_prezime' : 'prezime_ime';
                applyNameOrder();
                sortRows();
                applySearch();
            });

            renderTable();
        });
    </script>
@endsection
