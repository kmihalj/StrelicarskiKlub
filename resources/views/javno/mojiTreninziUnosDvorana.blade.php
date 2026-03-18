{{-- Forma za unos dvoranskog treninga po rundama, serijama i strijelama. --}}
@extends('layouts.app')
@section('content')
    <div class="container-xxl">
        <div class="row justify-content-center p-2 mb-3 shadow bg-danger fw-bolder">
            <div class="col-lg-12 text-white d-flex flex-wrap align-items-center justify-content-between gap-2">
                <span>Unos dvoranskog treninga</span>
                <span>
                    <button class="btn btn-sm btn-warning" type="button"
                            onclick="location.href='{{ route('javno.treninzi.index') }}'">
                        Moji treninzi
                    </button>
                </span>
            </div>
        </div>
    </div>

    <div class="container-xxl">
        <div class="row justify-content-center pt-3 pb-3 mb-3 shadow bg-white">
            <div class="col-lg-12">
                <p class="fw-bold mb-2">
                    Član:
                    <a class="link-primary link-offset-2 link-underline-opacity-0 link-underline-opacity-0-hover" href="{{ route('javno.clanovi.prikaz_clana', $clanKorisnika) }}">
                        {{ trim((string)$clanKorisnika->Ime) }} {{ trim((string)$clanKorisnika->Prezime) }}
                    </a>
                </p>

                <form id="spremi_dvoranski_trening" action="{{ route('javno.treninzi.dvoranski.store') }}" method="POST">
                    @csrf
                    <div class="row g-2 align-items-end mb-3">
                        <div class="col-lg-3 col-md-4 col-sm-6">
                            <label for="datum_treninga" class="form-label">Datum treninga</label>
                            <input type="date" class="form-control" id="datum_treninga" name="datum" value="{{ $zadaniDatum }}" required>
                        </div>
                        <div class="col-lg-9 col-md-8 col-sm-6">
                            <input type="hidden" id="unos_json" name="unos_json">
                            <div class="btn-group" role="group" aria-label="Odabir runde">
                                <button type="button" class="btn btn-outline-danger js-round-btn active" data-round="0">Runda 1</button>
                                <button type="button" class="btn btn-outline-danger js-round-btn" data-round="1">Runda 2</button>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive mb-2">
                        <table class="table table-hover align-middle mb-0 border">
                            <thead class="theme-thead-accent">
                            <tr>
                                <th class="text-white">Serija</th>
                                <th class="text-white">1</th>
                                <th class="text-white">2</th>
                                <th class="text-white">3</th>
                                <th class="text-white">Zbroj</th>
                                <th class="text-white">Total</th>
                                <th class="text-white">9</th>
                                <th class="text-white">10</th>
                            </tr>
                            </thead>
                            <tbody id="dvorana-round-body"></tbody>
                            <tfoot>
                            <tr class="table-light fw-bold">
                                <td colspan="4">Aktivna runda ukupno</td>
                                <td id="active-round-total">-</td>
                                <td id="active-round-total-2">-</td>
                                <td id="active-round-nine">-</td>
                                <td id="active-round-ten">-</td>
                            </tr>
                            </tfoot>
                        </table>
                    </div>

                    <div class="card border-0 bg-light-subtle mb-3">
                        <div class="card-body py-2">
                            <p class="mb-2 small text-muted">Klikni polje u tablici pa odaberi pogodak:</p>
                            <div id="dvorana-keypad" class="d-flex flex-wrap gap-2 mb-2">
                                @foreach(['X', '10', '9', '8', '7', '6', '5', '4', '3', '2', '1', 'M'] as $pogodak)
                                    <button type="button" class="btn btn-sm btn-outline-secondary js-hit-key" data-value="{{ $pogodak }}">{{ $pogodak }}</button>
                                @endforeach
                                <button type="button" class="btn btn-sm btn-outline-dark js-hit-key" data-value="CLEAR">Obriši polje</button>
                            </div>
                            <p class="mb-0 small text-muted">
                                `X` i `10` vrijede 10 bodova, `M` je promašaj (0 bodova). Nakon unosa ide automatski na sljedeću strijelu.
                            </p>
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-lg-4 col-md-6">
                            <div class="border rounded p-2 h-100">
                                <p class="fw-bold mb-1">Runda 1</p>
                                <p class="mb-0">Total: <span id="sum-r1-total">-</span></p>
                                <p class="mb-0">9: <span id="sum-r1-nine">-</span></p>
                                <p class="mb-0">10: <span id="sum-r1-ten">-</span></p>
                            </div>
                        </div>
                        <div class="col-lg-4 col-md-6">
                            <div class="border rounded p-2 h-100">
                                <p class="fw-bold mb-1">Runda 2</p>
                                <p class="mb-0">Total: <span id="sum-r2-total">-</span></p>
                                <p class="mb-0">9: <span id="sum-r2-nine">-</span></p>
                                <p class="mb-0">10: <span id="sum-r2-ten">-</span></p>
                            </div>
                        </div>
                        <div class="col-lg-4 col-md-12">
                            <div class="border rounded p-2 h-100 bg-light">
                                <p class="fw-bold mb-1">Ukupno trening</p>
                                <p class="mb-0 fw-bold">Total: <span id="sum-all-total">-</span></p>
                                <p class="mb-0 fw-bold">9: <span id="sum-all-nine">-</span></p>
                                <p class="mb-0 fw-bold">10: <span id="sum-all-ten">-</span></p>
                            </div>
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-lg-12">
                            <div class="border rounded p-2">
                                <p class="fw-bold mb-1">Statistika treninga</p>
                                <p class="mb-0">Prosjek pogodaka: <span id="stat-prosjek">-</span></p>
                                <p class="mb-0">Najčešći pogodak: <span id="stat-najcesci">-</span></p>
                                <p class="mb-0">Najbolja serija: <span id="stat-najbolja">-</span></p>
                                <p class="mb-0">Najlošija serija: <span id="stat-najlosija">-</span></p>
                            </div>
                        </div>
                    </div>

                    <div class="text-end">
                        <button type="submit" class="btn btn-danger">Spremi trening</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <style>
        /*noinspection CssUnusedSymbol*/
        #dvorana-round-body .js-shot-cell,
        #dvorana-keypad .js-hit-key {
            -webkit-tap-highlight-color: transparent;
            touch-action: manipulation;
        }
    </style>

    <script>
        (function () {
            // noinspection JSUnresolvedVariable,JSValidateTypes
            const initialStateRaw = @json($inicijalniUnos);
            const initialState = (initialStateRaw && typeof initialStateRaw === 'object') ? initialStateRaw : {};
            const roundButtons = /** @type {HTMLButtonElement[]} */ (Array.from(document.querySelectorAll('.js-round-btn')));
            const tableBody = /** @type {HTMLTableSectionElement|null} */ (document.getElementById('dvorana-round-body'));
            const keypad = /** @type {HTMLElement|null} */ (document.getElementById('dvorana-keypad'));
            const hiddenInput = /** @type {HTMLInputElement|null} */ (document.getElementById('unos_json'));
            const form = /** @type {HTMLFormElement|null} */ (document.getElementById('spremi_dvoranski_trening'));

            if (!tableBody || !keypad || !hiddenInput || !form) {
                return;
            }

            const state = {
                activeRound: 0,
                activeRow: 0,
                activeCol: 0,
                rounds: [
                    normalizeRound(initialState['runda1']),
                    normalizeRound(initialState['runda2']),
                ],
            };

            const scoreToLabel = (score) => score === 0 ? 'M' : String(score);

            function normalizeToken(token) {
                if (token === null || token === undefined) {
                    return null;
                }

                const value = String(token).trim().toUpperCase();
                if (value === '') {
                    return null;
                }

                if (value === 'X' || value === 'M') {
                    return value;
                }

                if (/^\d+$/.test(value)) {
                    const num = parseInt(value, 10);
                    if (num >= 1 && num <= 10) {
                        return String(num);
                    }
                }

                return null;
            }

            function normalizeRound(roundInput) {
                const normalized = [];
                for (let row = 0; row < 10; row++) {
                    const sourceRow = Array.isArray(roundInput) && Array.isArray(roundInput[row]) ? roundInput[row] : [];
                    normalized[row] = [
                        normalizeToken(sourceRow[0]),
                        normalizeToken(sourceRow[1]),
                        normalizeToken(sourceRow[2]),
                    ];
                }
                return normalized;
            }

            function tokenToScore(token) {
                if (token === null) {
                    return null;
                }
                if (token === 'X' || token === '10') {
                    return 10;
                }
                if (token === 'M') {
                    return 0;
                }
                const asNum = Number(token);
                if (!Number.isNaN(asNum) && asNum >= 1 && asNum <= 9) {
                    return asNum;
                }
                return null;
            }

            function calculateRound(roundData) {
                let cumulative = 0;
                let total = 0;
                let nines = 0;
                let tens = 0;
                let hitsCount = 0;
                let hitsSum = 0;
                const values = [];
                const rows = [];

                for (let rowIndex = 0; rowIndex < 10; rowIndex++) {
                    const row = roundData[rowIndex];
                    const scores = row.map(tokenToScore);
                    const enteredScores = scores.filter((score) => score !== null);
                    const hasInput = enteredScores.length > 0;

                    let rowSum = null;
                    let rowNines = null;
                    let rowTens = null;
                    let rowTotal = null;

                    if (hasInput) {
                        rowSum = enteredScores.reduce((sum, score) => sum + score, 0);
                        rowNines = enteredScores.filter((score) => score === 9).length;
                        rowTens = enteredScores.filter((score) => score === 10).length;
                        cumulative += rowSum;
                        rowTotal = cumulative;
                        total = cumulative;
                        nines += rowNines;
                        tens += rowTens;
                        hitsCount += enteredScores.length;
                        hitsSum += rowSum;
                        values.push(...enteredScores);
                    }

                    rows.push({
                        rowNumber: rowIndex + 1,
                        shots: row,
                        hasInput,
                        sum: rowSum,
                        total: rowTotal,
                        nines: rowNines,
                        tens: rowTens,
                    });
                }

                return {
                    rows,
                    hasInput: hitsCount > 0,
                    total,
                    nines,
                    tens,
                    hitsCount,
                    hitsSum,
                    values,
                };
            }

            function calculateStats(roundOne, roundTwo) {
                const allValues = [...roundOne['values'], ...roundTwo['values']];

                let average = null;
                if (allValues.length > 0) {
                    const sum = allValues.reduce((acc, value) => acc + value, 0);
                    average = sum / allValues.length;
                }

                let mostCommon = null;
                if (allValues.length > 0) {
                    const freq = new Map();
                    allValues.forEach((value) => {
                        freq.set(value, (freq.get(value) || 0) + 1);
                    });

                    const maxCount = Math.max(...freq.values());
                    const labels = Array.from(freq.entries())
                        .filter((entry) => entry[1] === maxCount)
                        .map((entry) => scoreToLabel(entry[0]));

                    mostCommon = {
                        label: labels.join(', '),
                        count: maxCount,
                    };
                }

                const series = [];
                [roundOne['rows'], roundTwo['rows']].forEach((roundRows, roundIndex) => {
                    roundRows.forEach((row, rowIndex) => {
                        if (row['hasInput'] && row['sum'] !== null) {
                            series.push({
                                label: `R${roundIndex + 1}/S${rowIndex + 1}`,
                                sum: row['sum'],
                            });
                        }
                    });
                });

                let best = null;
                let worst = null;
                if (series.length > 0) {
                    const maxSum = Math.max(...series.map((row) => Number(row['sum'] ?? 0)));
                    const minSum = Math.min(...series.map((row) => Number(row['sum'] ?? 0)));

                    best = {
                        label: series.filter((row) => row['sum'] === maxSum).map((row) => row['label']).join(', '),
                        sum: maxSum,
                    };
                    worst = {
                        label: series.filter((row) => row['sum'] === minSum).map((row) => row['label']).join(', '),
                        sum: minSum,
                    };
                }

                return { average, mostCommon, best, worst };
            }

            function displayValue(value) {
                return value === null || value === undefined ? '-' : String(value);
            }

            function displayNineOrTen(value) {
                if (value === null || value === undefined || value === 0) {
                    return '-';
                }
                return String(value);
            }

            function renderRoundButtons() {
                roundButtons.forEach((button, index) => {
                    const isActive = index === state.activeRound;
                    button.classList.toggle('active', isActive);
                    button.classList.toggle('btn-danger', isActive);
                    button.classList.toggle('btn-outline-danger', !isActive);
                });
            }

            function renderTable(roundData) {
                tableBody.innerHTML = '';

                roundData['rows'].forEach((row, rowIndex) => {
                    const tr = document.createElement('tr');

                    const rowLabel = document.createElement('td');
                    rowLabel.className = 'fw-semibold';
                    rowLabel.textContent = String(row['rowNumber']);
                    tr.appendChild(rowLabel);

                    for (let colIndex = 0; colIndex < 3; colIndex++) {
                        const cell = document.createElement('td');
                        const button = document.createElement('button');
                        button.type = 'button';
                        button.className = 'btn btn-sm w-100 js-shot-cell';
                        const isActiveCell = state.activeRow === rowIndex && state.activeCol === colIndex;
                        button.classList.add(isActiveCell ? 'btn-danger' : 'btn-outline-secondary');
                        button.setAttribute('data-row', String(rowIndex));
                        button.setAttribute('data-col', String(colIndex));
                        button.textContent = row['shots'][colIndex] ?? '-';
                        cell.appendChild(button);
                        tr.appendChild(cell);
                    }

                    const sumCell = document.createElement('td');
                    sumCell.textContent = displayValue(row['sum']);
                    tr.appendChild(sumCell);

                    const totalCell = document.createElement('td');
                    totalCell.textContent = displayValue(row['total']);
                    tr.appendChild(totalCell);

                    const nineCell = document.createElement('td');
                    nineCell.textContent = displayNineOrTen(row['nines']);
                    tr.appendChild(nineCell);

                    const tenCell = document.createElement('td');
                    tenCell.textContent = displayNineOrTen(row['tens']);
                    tr.appendChild(tenCell);

                    tableBody.appendChild(tr);
                });
            }

            function renderSummary(roundOne, roundTwo, stats) {
                const activeRound = state.activeRound === 0 ? roundOne : roundTwo;

                document.getElementById('active-round-total').textContent = activeRound['hasInput'] ? activeRound['total'] : '-';
                document.getElementById('active-round-total-2').textContent = activeRound['hasInput'] ? activeRound['total'] : '-';
                document.getElementById('active-round-nine').textContent = activeRound['hasInput'] ? activeRound['nines'] : '-';
                document.getElementById('active-round-ten').textContent = activeRound['hasInput'] ? activeRound['tens'] : '-';

                document.getElementById('sum-r1-total').textContent = roundOne['hasInput'] ? roundOne['total'] : '-';
                document.getElementById('sum-r1-nine').textContent = roundOne['hasInput'] ? roundOne['nines'] : '-';
                document.getElementById('sum-r1-ten').textContent = roundOne['hasInput'] ? roundOne['tens'] : '-';

                document.getElementById('sum-r2-total').textContent = roundTwo['hasInput'] ? roundTwo['total'] : '-';
                document.getElementById('sum-r2-nine').textContent = roundTwo['hasInput'] ? roundTwo['nines'] : '-';
                document.getElementById('sum-r2-ten').textContent = roundTwo['hasInput'] ? roundTwo['tens'] : '-';

                const totalAll = roundOne['total'] + roundTwo['total'];
                const nineAll = roundOne['nines'] + roundTwo['nines'];
                const tenAll = roundOne['tens'] + roundTwo['tens'];
                const hasAll = roundOne['hasInput'] || roundTwo['hasInput'];

                document.getElementById('sum-all-total').textContent = hasAll ? totalAll : '-';
                document.getElementById('sum-all-nine').textContent = hasAll ? nineAll : '-';
                document.getElementById('sum-all-ten').textContent = hasAll ? tenAll : '-';

                document.getElementById('stat-prosjek').textContent = stats['average'] === null ? '-' : stats['average'].toFixed(2).replace('.', ',');
                document.getElementById('stat-najcesci').textContent = stats['mostCommon'] === null ? '-' : `${stats['mostCommon']['label']} (${stats['mostCommon']['count']}x)`;
                document.getElementById('stat-najbolja').textContent = stats['best'] === null ? '-' : `${stats['best']['label']} (${stats['best']['sum']})`;
                document.getElementById('stat-najlosija').textContent = stats['worst'] === null ? '-' : `${stats['worst']['label']} (${stats['worst']['sum']})`;
            }

            function moveToNextCell() {
                if (state.activeCol < 2) {
                    state.activeCol += 1;
                    return;
                }

                if (state.activeRow < 9) {
                    state.activeRow += 1;
                    state.activeCol = 0;
                }
            }

            function serializeState() {
                hiddenInput.value = JSON.stringify({
                    runda1: state.rounds[0],
                    runda2: state.rounds[1],
                });
            }

            function render() {
                renderRoundButtons();
                const roundOne = calculateRound(state.rounds[0]);
                const roundTwo = calculateRound(state.rounds[1]);
                const activeRoundData = state.activeRound === 0 ? roundOne : roundTwo;
                const stats = calculateStats(roundOne, roundTwo);

                renderTable(activeRoundData);
                renderSummary(roundOne, roundTwo, stats);
                serializeState();
            }

            roundButtons.forEach((button) => {
                button.addEventListener('click', function () {
                    state.activeRound = Number(button.getAttribute('data-round') || 0);
                    state.activeRow = 0;
                    state.activeCol = 0;
                    render();
                });
            });

            tableBody.addEventListener('click', function (event) {
                const target = event.target;
                if (!(target instanceof Element)) {
                    return;
                }
                const button = target.closest('.js-shot-cell');
                if (!(button instanceof HTMLButtonElement)) {
                    return;
                }

                state.activeRow = Number(button.getAttribute('data-row') || 0);
                state.activeCol = Number(button.getAttribute('data-col') || 0);
                button.blur();
                render();
            });

            keypad.addEventListener('click', function (event) {
                const target = event.target;
                if (!(target instanceof Element)) {
                    return;
                }
                const button = target.closest('.js-hit-key');
                if (!(button instanceof HTMLButtonElement)) {
                    return;
                }

                const value = button.getAttribute('data-value') || '';
                state.rounds[state.activeRound][state.activeRow][state.activeCol] = value === 'CLEAR' ? null : normalizeToken(value);

                if (value !== 'CLEAR') {
                    moveToNextCell();
                }

                button.blur();
                render();
            });

            form.addEventListener('submit', function () {
                serializeState();
            });

            render();
        })();
    </script>
@endsection
