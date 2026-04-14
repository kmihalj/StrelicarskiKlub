{{-- Tablični popis članaka po odabranoj vrsti za javni i admin prikaz. --}}
@extends('layouts.app')
@section('content')
    @php
        $isObavijest = $vrsta === 'Obavijest';
        $naslovPopisa = $isObavijest ? 'Popis obavijesti' : 'Popis članaka';
        $labelPovratka = $isObavijest ? 'Sve obavijesti' : 'Svi članci';
        $isAdmin = auth()->check() && (int) auth()->user()->rola <= 1;
        $colspan = $isAdmin ? 3 : 2;
    @endphp

    <div class="container-xxl bg-white shadow mb-3">
        <div class="row justify-content-center p-2 shadow bg-danger fw-bolder">
            <div class="col-lg-12 text-white d-flex justify-content-between align-items-center">
                <span>{{ $naslovPopisa }}</span>
                <span class="d-flex align-items-center gap-2">
                    <button class="btn btn-sm btn-light" type="button" onclick="location.href='{{ route('javno.clanci.popisClanaka', ['vrsta' => $vrsta]) }}'">{{ $labelPovratka }}</button>
                    @if($isAdmin)
                        <button class="btn btn-sm btn-warning" type="button" onclick="location.href='{{ route('admin.clanci.popisClanaka') }}'">Popis članaka</button>
                        <button class="btn btn-sm btn-warning" type="button" onclick="location.href='{{ route('admin.clanci.unos') }}'">Dodaj članak</button>
                    @endif
                </span>
            </div>
        </div>

        <div id="vrstaClanciTableWrapper" class="row p-2 shadow bg-white fw-bolder" data-default-sort-key="datum" data-default-sort-direction="desc">
            <div class="col-lg-6 col-md-8 col-12 pt-3 pb-2">
                <label for="vrstaClanciSearch" class="form-label mb-1">Pretraga po naslovu</label>
                <input id="vrstaClanciSearch" type="search" class="form-control" placeholder="Unesite naslov..." data-search-input>
            </div>

            <div class="col-lg-12 pt-2 pb-2 mb-3 justify-content-center">
                <div class="table-responsive">
                    <table id="vrstaClanciTable" class="table table-hover align-middle mb-0 border">
                        <thead class="table-warning">
                        <tr>
                            <th>
                                <button type="button" class="btn btn-link p-0 text-dark fw-bold text-decoration-none js-sort" data-sort-key="naslov">
                                    Naslov <span data-sort-indicator="naslov"></span>
                                </button>
                            </th>
                            <th>
                                <button type="button" class="btn btn-link p-0 text-dark fw-bold text-decoration-none js-sort" data-sort-key="datum">
                                    Datum <span data-sort-indicator="datum"></span>
                                </button>
                            </th>
                            @if($isAdmin)
                                <th class="text-end">Akcije</th>
                            @endif
                        </tr>
                        </thead>
                        <tbody>
                        @if($clanci->count() === 0)
                            <tr data-empty-row>
                                <td colspan="{{ $colspan }}" class="text-center">
                                    <p class="fw-bold mb-1">Nema unesenih članaka</p>
                                </td>
                            </tr>
                        @else
                            @foreach($clanci as $clanak)
                                @php
                                    $naslov = mb_strtolower((string) $clanak->naslov, 'UTF-8');
                                @endphp
                                <tr data-article-row="1"
                                    data-naslov="{{ $naslov }}"
                                    data-datum="{{ $clanak->datum }}"
                                    data-search-text="{{ $naslov }}">
                                    <td>
                                        <p class="fw-normal mb-0">
                                            <a class="link-dark link-offset-2 link-underline-opacity-0 link-underline-opacity-50-hover"
                                               href="{{ route('javno.clanci.prikaz_clanka', $clanak) }}">{{ $clanak->naslov }}</a>
                                        </p>
                                    </td>
                                    <td>
                                        <p class="fw-normal mb-0">{{ date('d.m.Y.', strtotime($clanak->datum)) }}</p>
                                    </td>
                                    @if($isAdmin)
                                        <td class="text-end">
                                            <form id="prikaz{{ $clanak->id }}" action="{{ route('admin.clanci.uredjivanje', $clanak->id) }}" method="POST">
                                                @csrf
                                            </form>
                                            <form id="brisanje{{ $clanak->id }}" action="{{ route('admin.clanci.brisanje', $clanak->id) }}" method="POST">
                                                @csrf
                                            </form>

                                            <button type="submit" form="prikaz{{ $clanak->id }}" class="btn text-success btn-rounded" title="Uređivanje">
                                                @include('admin.SVG.uredi')
                                            </button>
                                            <button type="submit" form="brisanje{{ $clanak->id }}" class="btn text-danger btn-rounded" title="Obriši"
                                                    onclick="return confirm('Da li ste sigurni da želite obrisati članak ?')">
                                                @include('admin.SVG.obrisi')
                                            </button>
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                            <tr class="d-none" data-no-results-row>
                                <td colspan="{{ $colspan }}" class="text-center">
                                    <p class="fw-bold mb-1">Nema rezultata za zadani pojam</p>
                                </td>
                            </tr>
                        @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        (() => {
            const wrapper = document.getElementById('vrstaClanciTableWrapper');
            if (!wrapper) {
                return;
            }

            const tableBody = wrapper.querySelector('tbody');
            const searchInput = wrapper.querySelector('[data-search-input]');
            const sortButtons = Array.from(wrapper.querySelectorAll('.js-sort'));
            const indicatorNodes = Array.from(wrapper.querySelectorAll('[data-sort-indicator]'));
            const noResultsRow = wrapper.querySelector('[data-no-results-row]');
            const rows = Array.from(wrapper.querySelectorAll('tr[data-article-row="1"]'));

            if (!tableBody || rows.length === 0) {
                return;
            }

            let currentSortKey = wrapper.dataset.defaultSortKey || 'datum';
            let currentSortDirection = wrapper.dataset.defaultSortDirection || 'desc';

            const normalize = (value) => String(value || '').toLocaleLowerCase('hr-HR');

            const compareRows = (rowA, rowB) => {
                const direction = currentSortDirection === 'asc' ? 1 : -1;

                if (currentSortKey === 'datum') {
                    const valueA = String(rowA.dataset.datum || '');
                    const valueB = String(rowB.dataset.datum || '');
                    return valueA.localeCompare(valueB) * direction;
                }

                const valueA = normalize(rowA.dataset[currentSortKey] || '');
                const valueB = normalize(rowB.dataset[currentSortKey] || '');
                return valueA.localeCompare(valueB, 'hr-HR') * direction;
            };

            const updateIndicators = () => {
                indicatorNodes.forEach((indicatorNode) => {
                    const key = indicatorNode.getAttribute('data-sort-indicator');
                    if (!key || key !== currentSortKey) {
                        indicatorNode.textContent = '';
                        return;
                    }

                    indicatorNode.textContent = currentSortDirection === 'asc' ? '▲' : '▼';
                });
            };

            const applyTableState = () => {
                const query = normalize(searchInput ? searchInput.value : '').trim();

                const filteredRows = rows.filter((row) => {
                    const haystack = normalize(row.dataset.searchText || '');
                    return query === '' || haystack.includes(query);
                });

                filteredRows.sort(compareRows);

                rows.forEach((row) => row.classList.add('d-none'));
                filteredRows.forEach((row) => {
                    row.classList.remove('d-none');
                    tableBody.appendChild(row);
                });

                if (noResultsRow) {
                    noResultsRow.classList.toggle('d-none', filteredRows.length !== 0);
                    if (filteredRows.length === 0) {
                        tableBody.appendChild(noResultsRow);
                    }
                }

                updateIndicators();
            };

            sortButtons.forEach((button) => {
                button.addEventListener('click', () => {
                    const key = button.getAttribute('data-sort-key');
                    if (!key) {
                        return;
                    }

                    if (currentSortKey === key) {
                        currentSortDirection = currentSortDirection === 'asc' ? 'desc' : 'asc';
                    } else {
                        currentSortKey = key;
                        currentSortDirection = key === 'datum' ? 'desc' : 'asc';
                    }

                    applyTableState();
                });
            });

            if (searchInput) {
                searchInput.addEventListener('input', applyTableState);
            }

            applyTableState();
        })();
    </script>
@endsection
