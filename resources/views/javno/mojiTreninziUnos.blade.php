{{-- Forma za unos ili uređivanje treninga prijavljenog člana. --}}
@extends('layouts.app')
@section('content')
    <div class="container-xxl">
        <div class="row justify-content-center p-2 mb-3 shadow bg-danger fw-bolder">
            <div class="col-lg-12 text-white d-flex flex-wrap align-items-center justify-content-between gap-2">
                <span>{{ $naslovForme }} - {{ $konfig['naziv'] }}</span>
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

                <form id="spremi_trening" action="{{ $formAction }}" method="POST">
                    @csrf
                    @if($formMethod !== 'POST')
                        @method($formMethod)
                    @endif
                    <input type="hidden" id="zatvori_nakon_spremanja" name="zatvori_nakon_spremanja" value="0">
                    <div id="trening-form-alert" class="alert alert-danger d-none py-2 px-3" role="alert"></div>
                    <div class="row g-2 align-items-end mb-3">
                        <div class="col-lg-3 col-md-4 col-sm-6">
                            <label for="datum_treninga" class="form-label">Datum treninga</label>
                            <input type="date" class="form-control" id="datum_treninga" name="datum" value="{{ $zadaniDatum }}" required>
                        </div>
                        <div class="col-lg-9 col-md-8 col-sm-6">
                            <input type="hidden" id="unos_json" name="unos_json">
                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                                <div class="btn-group" role="group" aria-label="Odabir runde">
                                    <button type="button" class="btn btn-outline-danger js-round-btn active" data-round="0">Runda 1</button>
                                    <button type="button" class="btn btn-outline-danger js-round-btn" data-round="1">Runda 2</button>
                                </div>
                                <button type="button" id="btn-spremi" class="btn btn-danger">Spremi</button>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive mb-2">
                        <table class="table table-hover align-middle mb-0 border trening-unos-table @if(in_array($konfig['tip'], ['dvoranski', 'vanjski'], true)) trening-unos-table-compact @endif">
                            <thead class="theme-thead-accent">
                            <tr id="trening-head-row">
                                <th class="text-white">Serija</th>
                                @for($i = 1; $i <= (int)$konfig['broj_strijela_u_seriji']; $i++)
                                    <th class="text-white">{{ $i }}</th>
                                @endfor
                                <th class="text-white">Zbroj</th>
                                <th class="text-white">Total</th>
                                <th class="text-white">9</th>
                                <th class="text-white">10</th>
                                @if($konfig['ima_x_kolonu'])
                                    <th class="text-white">X</th>
                                @endif
                            </tr>
                            </thead>
                            <tbody id="trening-round-body"></tbody>
                            <tfoot>
                            <tr class="table-light fw-bold">
                                <td id="active-round-label" colspan="{{ (int)$konfig['broj_strijela_u_seriji'] + 1 }}">Aktivna runda ukupno</td>
                                <td>-</td>
                                <td id="active-round-total">-</td>
                                <td id="active-round-nine">-</td>
                                <td id="active-round-ten">-</td>
                                @if($konfig['ima_x_kolonu'])
                                    <td id="active-round-x">-</td>
                                @endif
                            </tr>
                            </tfoot>
                        </table>
                    </div>

                    <div class="card border-0 bg-light-subtle mb-3">
                        <div class="card-body py-2">
                            <p class="mb-2 small text-muted">Klikni polje u tablici pa odaberi pogodak:</p>
                            <div id="trening-keypad" class="d-flex flex-wrap gap-2 mb-2">
                                @php
                                    $tipke = [
                                        ['oznaka' => 'X', 'klasa' => 'hit-key-gold'],
                                        ['oznaka' => '10', 'klasa' => 'hit-key-gold'],
                                        ['oznaka' => '9', 'klasa' => 'hit-key-gold'],
                                        ['oznaka' => '8', 'klasa' => 'hit-key-red'],
                                        ['oznaka' => '7', 'klasa' => 'hit-key-red'],
                                        ['oznaka' => '6', 'klasa' => 'hit-key-blue'],
                                        ['oznaka' => '5', 'klasa' => 'hit-key-blue'],
                                        ['oznaka' => '4', 'klasa' => 'hit-key-black'],
                                        ['oznaka' => '3', 'klasa' => 'hit-key-black'],
                                        ['oznaka' => '2', 'klasa' => 'hit-key-white'],
                                        ['oznaka' => '1', 'klasa' => 'hit-key-white'],
                                        ['oznaka' => 'M', 'klasa' => 'hit-key-green'],
                                    ];
                                @endphp
                                @foreach($tipke as $tipka)
                                    <button type="button"
                                            class="btn js-hit-key hit-key-btn {{ $tipka['klasa'] }}"
                                            data-value="{{ $tipka['oznaka'] }}">
                                        {{ $tipka['oznaka'] }}
                                    </button>
                                @endforeach
                                <button type="button" class="btn hit-key-btn hit-key-clear js-hit-key" data-value="CLEAR">Obri&#353;i</button>
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
                                @if($konfig['ima_x_kolonu'])
                                    <p class="mb-0">X: <span id="sum-r1-x">-</span></p>
                                @endif
                            </div>
                        </div>
                        <div class="col-lg-4 col-md-6">
                            <div class="border rounded p-2 h-100">
                                <p class="fw-bold mb-1">Runda 2</p>
                                <p class="mb-0">Total: <span id="sum-r2-total">-</span></p>
                                <p class="mb-0">9: <span id="sum-r2-nine">-</span></p>
                                <p class="mb-0">10: <span id="sum-r2-ten">-</span></p>
                                @if($konfig['ima_x_kolonu'])
                                    <p class="mb-0">X: <span id="sum-r2-x">-</span></p>
                                @endif
                            </div>
                        </div>
                        <div class="col-lg-4 col-md-12">
                            <div class="border rounded p-2 h-100 bg-light">
                                <p class="fw-bold mb-1">Ukupno trening</p>
                                <p class="mb-0 fw-bold">Total: <span id="sum-all-total">-</span></p>
                                <p class="mb-0 fw-bold">9: <span id="sum-all-nine">-</span></p>
                                <p class="mb-0 fw-bold">10: <span id="sum-all-ten">-</span></p>
                                @if($konfig['ima_x_kolonu'])
                                    <p class="mb-0 fw-bold">X: <span id="sum-all-x">-</span></p>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-lg-12">
                            <div class="border rounded p-2">
                                <p class="fw-bold mb-1">Statistika treninga</p>
                                <p class="mb-0">
                                    Prosjek pogodaka: <span id="stat-prosjek">-</span>;
                                    Naj&#269;e&#353;&#263;i pogodak: <span id="stat-najcesci">-</span>;
                                    Najbolja serija: <span id="stat-najbolja">-</span>;
                                    Najlo&#353;ija serija: <span id="stat-najlosija">-</span>
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="text-end">
                        <button type="button" id="btn-zatvori" class="btn btn-outline-secondary">Zatvori</button>
                    </div>
                </form>

                @if(session('saved_toast'))
                    <div id="trening-saved-toast" class="alert alert-success py-2 px-3 mb-0 mt-3" role="status" aria-live="polite">
                        {{ session('saved_toast') }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="modal fade" id="unsavedChangesModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Nespremljene promjene</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Zatvori"></button>
                </div>
                <div class="modal-body">
                    Trening nije spremljen. &#381;eli&#353; li spremiti prije zatvaranja?
                </div>
                <div class="modal-footer d-flex justify-content-between">
                    <button type="button" id="btn-zatvori-bez-spremanja" class="btn btn-outline-danger">Zatvori</button>
                    <div class="d-flex gap-2">
                        <button type="button" id="btn-odustani-zatvaranje" class="btn btn-outline-secondary" data-bs-dismiss="modal">Odustani</button>
                        <button type="button" id="btn-spremi-i-zatvori" class="btn btn-danger">Spremi i zatvori</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .shot-cell-btn {
            --bs-btn-color: #1a1a1a;
            --bs-btn-bg: #fff;
            --bs-btn-border-color: #adb5bd;
            --bs-btn-hover-color: var(--bs-btn-color);
            --bs-btn-hover-bg: var(--bs-btn-bg);
            --bs-btn-hover-border-color: var(--bs-btn-border-color);
            --bs-btn-active-color: var(--bs-btn-color);
            --bs-btn-active-bg: var(--bs-btn-bg);
            --bs-btn-active-border-color: var(--bs-btn-border-color);
            --bs-btn-focus-shadow-rgb: 220, 53, 69;
            min-height: 2.65rem;
            font-size: 1.05rem;
            font-weight: 700;
            border-width: 2px;
            border-color: #adb5bd;
            background: #fff;
            color: #1a1a1a;
            -webkit-tap-highlight-color: transparent;
            touch-action: manipulation;
        }

        .shot-cell-empty {
            --bs-btn-color: #6c757d;
            --bs-btn-bg: #fff;
            --bs-btn-border-color: #ced4da;
            background: #fff;
            color: #6c757d;
            border-color: #ced4da;
        }

        .shot-hit-gold {
            --bs-btn-color: #222;
            --bs-btn-bg: #ffd447;
            --bs-btn-border-color: #c9a31b;
            background: #ffd447;
            color: #222;
            border-color: #c9a31b;
        }

        .shot-hit-red {
            --bs-btn-color: #fff;
            --bs-btn-bg: #e33b3b;
            --bs-btn-border-color: #b11717;
            background: #e33b3b;
            color: #fff;
            border-color: #b11717;
        }

        .shot-hit-blue {
            --bs-btn-color: #fff;
            --bs-btn-bg: #1f65db;
            --bs-btn-border-color: #13449b;
            background: #1f65db;
            color: #fff;
            border-color: #13449b;
        }

        .shot-hit-black {
            --bs-btn-color: #fff;
            --bs-btn-bg: #20242a;
            --bs-btn-border-color: #000;
            background: #20242a;
            color: #fff;
            border-color: #000;
        }

        .shot-hit-black,
        .shot-hit-black:hover,
        .shot-hit-black:focus,
        .shot-hit-black:focus-visible,
        .shot-hit-black:active {
            color: #fff !important;
        }

        .shot-hit-white {
            --bs-btn-color: #1a1a1a;
            --bs-btn-bg: #fff;
            --bs-btn-border-color: #adb5bd;
            background: #fff;
            color: #1a1a1a;
            border-color: #adb5bd;
        }

        .shot-hit-green {
            --bs-btn-color: #fff;
            --bs-btn-bg: #22b259;
            --bs-btn-border-color: #12813d;
            background: #22b259;
            color: #fff;
            border-color: #12813d;
        }

        .shot-cell-active {
            border-color: #dc3545 !important;
            box-shadow: 0 0 0 .15rem rgba(220, 53, 69, .25);
        }

        #trening-saved-toast {
            position: fixed;
            top: 1rem;
            right: 1rem;
            z-index: 1085;
            box-shadow: 0 .35rem .85rem rgba(0, 0, 0, .15);
        }

        .trening-unos-table-compact > :not(caption) > * > * {
            padding: .25rem .35rem;
        }

        .trening-unos-table-compact .shot-cell-btn {
            min-height: 2rem;
            padding-top: .15rem;
            padding-bottom: .15rem;
            font-size: .95rem;
        }

        .hit-key-btn {
            --bs-btn-color: #1a1a1a;
            --bs-btn-bg: #fff;
            --bs-btn-border-color: #adb5bd;
            --bs-btn-hover-color: var(--bs-btn-color);
            --bs-btn-hover-bg: var(--bs-btn-bg);
            --bs-btn-hover-border-color: var(--bs-btn-border-color);
            --bs-btn-active-color: var(--bs-btn-color);
            --bs-btn-active-bg: var(--bs-btn-bg);
            --bs-btn-active-border-color: var(--bs-btn-border-color);
            min-width: 3rem;
            min-height: 3rem;
            font-size: 1.1rem;
            font-weight: 700;
            border-width: 2px;
            -webkit-tap-highlight-color: transparent;
            touch-action: manipulation;
        }

        .hit-key-gold {
            --bs-btn-color: #222;
            --bs-btn-bg: #ffd447;
            --bs-btn-border-color: #c9a31b;
            background: #ffd447;
            color: #222;
            border-color: #c9a31b;
        }

        .hit-key-red {
            --bs-btn-color: #fff;
            --bs-btn-bg: #e33b3b;
            --bs-btn-border-color: #b11717;
            background: #e33b3b;
            color: #fff;
            border-color: #b11717;
        }

        .hit-key-blue {
            --bs-btn-color: #fff;
            --bs-btn-bg: #1f65db;
            --bs-btn-border-color: #13449b;
            background: #1f65db;
            color: #fff;
            border-color: #13449b;
        }

        .hit-key-black {
            --bs-btn-color: #fff;
            --bs-btn-bg: #20242a;
            --bs-btn-border-color: #000;
            background: #20242a;
            color: #fff;
            border-color: #000;
        }

        .hit-key-black,
        .hit-key-black:hover,
        .hit-key-black:focus,
        .hit-key-black:focus-visible,
        .hit-key-black:active {
            color: #fff !important;
        }

        .hit-key-white {
            --bs-btn-color: #1a1a1a;
            --bs-btn-bg: #fff;
            --bs-btn-border-color: #adb5bd;
            background: #fff;
            color: #1a1a1a;
            border-color: #adb5bd;
        }

        .hit-key-green {
            --bs-btn-color: #fff;
            --bs-btn-bg: #22b259;
            --bs-btn-border-color: #12813d;
            background: #22b259;
            color: #fff;
            border-color: #12813d;
        }

        .hit-key-clear {
            --bs-btn-color: #fff;
            --bs-btn-bg: #6c757d;
            --bs-btn-border-color: #495057;
            background: #6c757d;
            color: #fff;
            border-color: #495057;
        }

        .hit-key-btn:hover,
        .hit-key-btn:focus,
        .hit-key-btn:focus-visible,
        .hit-key-btn:active {
            filter: brightness(0.95);
            color: var(--bs-btn-color);
        }

        @media (max-width: 767.98px) {
            .hit-key-btn {
                min-width: 3.35rem;
                min-height: 3.35rem;
                font-size: 1.2rem;
            }

            .shot-cell-btn {
                min-height: 3rem;
                font-size: 1.15rem;
            }

            .trening-unos-table-compact > :not(caption) > * > * {
                padding: .18rem .22rem;
                font-size: .9rem;
            }

            .trening-unos-table-compact .shot-cell-btn {
                min-height: 2.25rem;
                font-size: .95rem;
            }

            #trening-saved-toast {
                left: .75rem;
                right: .75rem;
                top: .75rem;
            }
        }
    </style>

    <script>
        (function () {
            const initialState = @json($inicijalniUnos);
            const config = {
                brojSerija: Number(@json((int)$konfig['broj_serija'])),
                brojStrijelaUSeriji: Number(@json((int)$konfig['broj_strijela_u_seriji'])),
                imaXKolonu: Boolean(@json((bool)$konfig['ima_x_kolonu'])),
            };

            const roundButtons = Array.from(document.querySelectorAll('.js-round-btn'));
            const tableBody = document.getElementById('trening-round-body');
            const keypad = document.getElementById('trening-keypad');
            const hiddenInput = document.getElementById('unos_json');
            const form = document.getElementById('spremi_trening');
            const formAlert = document.getElementById('trening-form-alert');
            const dateInput = document.getElementById('datum_treninga');
            const saveButton = document.getElementById('btn-spremi');
            const closeButton = document.getElementById('btn-zatvori');
            const closeAfterSaveInput = document.getElementById('zatvori_nakon_spremanja');
            const closeRoute = @json($closeRoute);
            const unsavedChangesModalEl = document.getElementById('unsavedChangesModal');
            const closeWithoutSaveBtn = document.getElementById('btn-zatvori-bez-spremanja');
            const saveAndCloseBtn = document.getElementById('btn-spremi-i-zatvori');
            const savedToast = document.getElementById('trening-saved-toast');
            const csrfTokenMeta = document.querySelector('meta[name="csrf-token"]');
            const csrfToken = csrfTokenMeta ? csrfTokenMeta.getAttribute('content') : '';
            const bootstrapModalCtor = window.bootstrap && window.bootstrap.Modal ? window.bootstrap.Modal : null;
            const unsavedChangesModal = (bootstrapModalCtor && unsavedChangesModalEl)
                ? new bootstrapModalCtor(unsavedChangesModalEl)
                : null;
            let modalBackdropEl = null;
            let isSubmitting = false;
            let ignoreBeforeUnload = false;
            let initialSnapshot = '';

            const hitOrder = new Map([
                ['X', 0],
                ['10', 1],
                ['9', 2],
                ['8', 3],
                ['7', 4],
                ['6', 5],
                ['5', 6],
                ['4', 7],
                ['3', 8],
                ['2', 9],
                ['1', 10],
                ['M', 11],
            ]);
            const state = {
                activeRound: 0,
                activeRow: 0,
                activeCol: 0,
                rounds: [
                    normalizeRound(initialState.runda1),
                    normalizeRound(initialState.runda2),
                ],
            };

            function buildSnapshot() {
                return JSON.stringify({
                    datum: dateInput ? dateInput.value : '',
                    runda1: state.rounds[0],
                    runda2: state.rounds[1],
                });
            }

            function hasUnsavedChanges() {
                return buildSnapshot() !== initialSnapshot;
            }

            function clearFormAlert() {
                if (!formAlert) {
                    return;
                }
                formAlert.classList.add('d-none');
                formAlert.textContent = '';
            }

            function showFormAlert(message) {
                if (!formAlert) {
                    return;
                }
                formAlert.textContent = message;
                formAlert.classList.remove('d-none');
            }

            function setSavingState(isSaving) {
                isSubmitting = isSaving;
                if (saveButton) {
                    saveButton.disabled = isSaving;
                }
                if (closeButton) {
                    closeButton.disabled = isSaving;
                }
                if (saveAndCloseBtn) {
                    saveAndCloseBtn.disabled = isSaving;
                }
                if (closeWithoutSaveBtn) {
                    closeWithoutSaveBtn.disabled = isSaving;
                }
            }

            function showSavedToast(message) {
                let toast = document.getElementById('trening-saved-toast');
                if (!toast) {
                    toast = document.createElement('div');
                    toast.id = 'trening-saved-toast';
                    toast.className = 'alert alert-success py-2 px-3 mb-0';
                    toast.setAttribute('role', 'status');
                    toast.setAttribute('aria-live', 'polite');
                    document.body.appendChild(toast);
                }

                toast.textContent = message;
                toast.classList.remove('d-none', 'fade');

                window.setTimeout(function () {
                    toast.classList.add('fade');
                    window.setTimeout(function () {
                        toast.remove();
                    }, 250);
                }, 1500);
            }

            function showUnsavedModal() {
                if (!unsavedChangesModalEl) {
                    return;
                }

                if (unsavedChangesModal) {
                    unsavedChangesModal.show();
                    return;
                }

                unsavedChangesModalEl.style.display = 'block';
                unsavedChangesModalEl.classList.add('show');
                unsavedChangesModalEl.removeAttribute('aria-hidden');
                unsavedChangesModalEl.setAttribute('aria-modal', 'true');
                document.body.classList.add('modal-open');

                modalBackdropEl = document.createElement('div');
                modalBackdropEl.className = 'modal-backdrop fade show';
                document.body.appendChild(modalBackdropEl);
            }

            function hideUnsavedModal() {
                if (!unsavedChangesModalEl) {
                    return;
                }

                if (unsavedChangesModal) {
                    unsavedChangesModal.hide();
                    return;
                }

                unsavedChangesModalEl.classList.remove('show');
                unsavedChangesModalEl.style.display = 'none';
                unsavedChangesModalEl.setAttribute('aria-hidden', 'true');
                unsavedChangesModalEl.removeAttribute('aria-modal');
                document.body.classList.remove('modal-open');

                if (modalBackdropEl) {
                    modalBackdropEl.remove();
                    modalBackdropEl = null;
                }
            }

            async function submitForm(closeAfterSave) {
                if (isSubmitting) {
                    return;
                }

                clearFormAlert();
                if (closeAfterSaveInput) {
                    closeAfterSaveInput.value = closeAfterSave ? '1' : '0';
                }

                serializeState();

                const formData = new FormData(form);

                try {
                    setSavingState(true);

                    const response = await fetch(form.action, {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                        },
                        credentials: 'same-origin',
                        body: formData,
                    });

                    if (response.status === 422) {
                        const payload = await response.json();
                        const poruke = payload && payload.errors
                            ? Object.values(payload.errors).flat()
                            : [];
                        showFormAlert(poruke.length ? poruke.join(' ') : 'Provjeri unesene podatke.');
                        return;
                    }

                    if (!response.ok) {
                        showFormAlert('Spremanje nije uspjelo. Pokušaj ponovno.');
                        return;
                    }

                    const payload = await response.json();
                    initialSnapshot = buildSnapshot();
                    showSavedToast(payload.message || 'Spremljeno.');

                    if (closeAfterSave && payload.redirect) {
                        ignoreBeforeUnload = true;
                        window.location.href = payload.redirect;
                    }
                } catch (error) {
                    showFormAlert('Spremanje nije uspjelo. Provjeri vezu i pokušaj ponovno.');
                } finally {
                    setSavingState(false);
                }
            }

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
                    const asNum = Number(value);
                    if (asNum >= 1 && asNum <= 10) {
                        return String(asNum);
                    }
                }
                return null;
            }

            function normalizeRound(roundInput) {
                const normalized = [];
                for (let row = 0; row < config.brojSerija; row++) {
                    const sourceRow = Array.isArray(roundInput) && Array.isArray(roundInput[row]) ? roundInput[row] : [];
                    normalized[row] = [];
                    for (let col = 0; col < config.brojStrijelaUSeriji; col++) {
                        normalized[row][col] = normalizeToken(sourceRow[col]);
                    }
                    normalized[row] = sortShotsInRow(normalized[row]);
                }
                return normalized;
            }

            function sortShotsInRow(rowData) {
                const entered = rowData
                    .map(normalizeToken)
                    .filter((token) => token !== null)
                    .sort((a, b) => (hitOrder.get(a) ?? 999) - (hitOrder.get(b) ?? 999));

                while (entered.length < config.brojStrijelaUSeriji) {
                    entered.push(null);
                }

                return entered.slice(0, config.brojStrijelaUSeriji);
            }

            function sortActiveRow(rowIndex) {
                state.rounds[state.activeRound][rowIndex] = sortShotsInRow(state.rounds[state.activeRound][rowIndex]);
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
                return Number.isNaN(asNum) ? null : asNum;
            }

            function tokenToCellClass(token) {
                if (token === null) {
                    return 'shot-cell-empty';
                }

                if (token === 'X' || token === '10' || token === '9') {
                    return 'shot-hit-gold';
                }
                if (token === '8' || token === '7') {
                    return 'shot-hit-red';
                }
                if (token === '6' || token === '5') {
                    return 'shot-hit-blue';
                }
                if (token === '4' || token === '3') {
                    return 'shot-hit-black';
                }
                if (token === '2' || token === '1') {
                    return 'shot-hit-white';
                }
                if (token === 'M') {
                    return 'shot-hit-green';
                }

                return 'shot-cell-empty';
            }

            function scoreToLabel(score) {
                return score === 0 ? 'M' : String(score);
            }

            function calculateRound(roundData) {
                let cumulative = 0;
                let total = 0;
                let nines = 0;
                let tens = 0;
                let xCount = 0;
                const rows = [];
                const values = [];

                for (let rowIndex = 0; rowIndex < config.brojSerija; rowIndex++) {
                    const row = roundData[rowIndex];
                    const scores = row.map(tokenToScore);
                    const enteredScores = scores.filter((score) => score !== null);
                    const hasInput = enteredScores.length > 0;

                    let rowSum = null;
                    let rowTotal = null;
                    let rowNines = null;
                    let rowTens = null;
                    let rowX = null;

                    if (hasInput) {
                        rowSum = enteredScores.reduce((sum, score) => sum + score, 0);
                        rowNines = enteredScores.filter((score) => score === 9).length;
                        rowTens = enteredScores.filter((score) => score === 10).length;
                        rowX = row.filter((token) => token === 'X').length;

                        cumulative += rowSum;
                        rowTotal = cumulative;
                        total = cumulative;
                        nines += rowNines;
                        tens += rowTens;
                        xCount += rowX;
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
                        x: rowX,
                    });
                }

                return {
                    rows,
                    hasInput: values.length > 0,
                    total,
                    nines,
                    tens,
                    x: xCount,
                    values,
                };
            }

            function calculateStats(roundOne, roundTwo) {
                const allValues = [...roundOne.values, ...roundTwo.values];

                let average = null;
                if (allValues.length > 0) {
                    average = allValues.reduce((acc, value) => acc + value, 0) / allValues.length;
                }

                let mostCommon = null;
                if (allValues.length > 0) {
                    const freq = new Map();
                    allValues.forEach((value) => freq.set(value, (freq.get(value) || 0) + 1));
                    const max = Math.max(...freq.values());
                    const labels = Array.from(freq.entries())
                        .filter((entry) => entry[1] === max)
                        .map((entry) => scoreToLabel(entry[0]));
                    mostCommon = { label: labels.join(', '), count: max };
                }

                const series = [];
                [roundOne.rows, roundTwo.rows].forEach((roundRows, roundIndex) => {
                    roundRows.forEach((row, rowIndex) => {
                        if (row.hasInput && row.sum !== null) {
                            const enteredShots = row.shots.filter((shot) => shot !== null);
                            const xCount = enteredShots.filter((shot) => shot === 'X').length;
                            const signature = enteredShots
                                .filter((shot) => shot !== 'X' && shot !== '10')
                                .slice()
                                .sort()
                                .join('|');

                            series.push({
                                label: `R${roundIndex + 1}/S${rowIndex + 1}`,
                                sum: row.sum,
                                xCount,
                                signature,
                            });
                        }
                    });
                });

                let best = null;
                let worst = null;
                if (series.length > 0) {
                    const max = Math.max(...series.map((row) => row.sum));
                    const min = Math.min(...series.map((row) => row.sum));

                    const pickBySignatureAndX = (candidates, mode) => {
                        const grouped = candidates.reduce((acc, row) => {
                            const key = row.signature;
                            if (!acc[key]) {
                                acc[key] = [];
                            }
                            acc[key].push(row);
                            return acc;
                        }, {});

                        const picked = [];
                        Object.values(grouped).forEach((group) => {
                            const targetX = mode === 'max'
                                ? Math.max(...group.map((row) => row.xCount))
                                : Math.min(...group.map((row) => row.xCount));

                            group.forEach((row) => {
                                if (row.xCount === targetX) {
                                    picked.push(row);
                                }
                            });
                        });

                        return picked;
                    };

                    const bestCandidates = series.filter((row) => row.sum === max);
                    const worstCandidates = series.filter((row) => row.sum === min);
                    const bestPicked = pickBySignatureAndX(bestCandidates, 'max');
                    const worstPicked = pickBySignatureAndX(worstCandidates, 'min');

                    best = { label: bestPicked.map((row) => row.label).join(', '), sum: max };
                    worst = { label: worstPicked.map((row) => row.label).join(', '), sum: min };
                }

                return { average, mostCommon, best, worst };
            }

            function displayCell(value, showZeroAsDash = false) {
                if (value === null || value === undefined) {
                    return '-';
                }
                if (showZeroAsDash && Number(value) === 0) {
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

                roundData.rows.forEach((row, rowIndex) => {
                    const tr = document.createElement('tr');

                    const rowLabel = document.createElement('td');
                    rowLabel.className = 'fw-semibold';
                    rowLabel.textContent = String(row.rowNumber);
                    tr.appendChild(rowLabel);

                    for (let colIndex = 0; colIndex < config.brojStrijelaUSeriji; colIndex++) {
                        const cell = document.createElement('td');
                        const button = document.createElement('button');
                        button.type = 'button';
                        button.className = 'btn w-100 js-shot-cell shot-cell-btn';
                        const token = row.shots[colIndex];
                        const isActiveCell = state.activeRow === rowIndex && state.activeCol === colIndex;
                        button.classList.add(tokenToCellClass(token));
                        if (isActiveCell) {
                            button.classList.add('shot-cell-active');
                        }
                        button.dataset.row = String(rowIndex);
                        button.dataset.col = String(colIndex);
                        button.textContent = token ?? '-';
                        cell.appendChild(button);
                        tr.appendChild(cell);
                    }

                    const sumCell = document.createElement('td');
                    sumCell.textContent = displayCell(row.sum);
                    tr.appendChild(sumCell);

                    const totalCell = document.createElement('td');
                    totalCell.textContent = displayCell(row.total);
                    tr.appendChild(totalCell);

                    const nineCell = document.createElement('td');
                    nineCell.textContent = displayCell(row.nines, true);
                    tr.appendChild(nineCell);

                    const tenCell = document.createElement('td');
                    tenCell.textContent = displayCell(row.tens, true);
                    tr.appendChild(tenCell);

                    if (config.imaXKolonu) {
                        const xCell = document.createElement('td');
                        xCell.textContent = displayCell(row.x, true);
                        tr.appendChild(xCell);
                    }

                    tableBody.appendChild(tr);
                });
            }

            function renderSummary(roundOne, roundTwo, stats) {
                const activeRound = state.activeRound === 0 ? roundOne : roundTwo;

                document.getElementById('active-round-total').textContent = activeRound.hasInput ? activeRound.total : '-';
                document.getElementById('active-round-nine').textContent = activeRound.hasInput ? activeRound.nines : '-';
                document.getElementById('active-round-ten').textContent = activeRound.hasInput ? activeRound.tens : '-';
                if (config.imaXKolonu) {
                    document.getElementById('active-round-x').textContent = activeRound.hasInput ? activeRound.x : '-';
                }

                document.getElementById('sum-r1-total').textContent = roundOne.hasInput ? roundOne.total : '-';
                document.getElementById('sum-r1-nine').textContent = roundOne.hasInput ? roundOne.nines : '-';
                document.getElementById('sum-r1-ten').textContent = roundOne.hasInput ? roundOne.tens : '-';
                if (config.imaXKolonu) {
                    document.getElementById('sum-r1-x').textContent = roundOne.hasInput ? roundOne.x : '-';
                }

                document.getElementById('sum-r2-total').textContent = roundTwo.hasInput ? roundTwo.total : '-';
                document.getElementById('sum-r2-nine').textContent = roundTwo.hasInput ? roundTwo.nines : '-';
                document.getElementById('sum-r2-ten').textContent = roundTwo.hasInput ? roundTwo.tens : '-';
                if (config.imaXKolonu) {
                    document.getElementById('sum-r2-x').textContent = roundTwo.hasInput ? roundTwo.x : '-';
                }

                const hasAll = roundOne.hasInput || roundTwo.hasInput;
                document.getElementById('sum-all-total').textContent = hasAll ? (roundOne.total + roundTwo.total) : '-';
                document.getElementById('sum-all-nine').textContent = hasAll ? (roundOne.nines + roundTwo.nines) : '-';
                document.getElementById('sum-all-ten').textContent = hasAll ? (roundOne.tens + roundTwo.tens) : '-';
                if (config.imaXKolonu) {
                    document.getElementById('sum-all-x').textContent = hasAll ? (roundOne.x + roundTwo.x) : '-';
                }

                document.getElementById('stat-prosjek').textContent = stats.average === null ? '-' : stats.average.toFixed(2).replace('.', ',');
                document.getElementById('stat-najcesci').textContent = stats.mostCommon === null ? '-' : `${stats.mostCommon.label} (${stats.mostCommon.count}x)`;
                document.getElementById('stat-najbolja').textContent = stats.best === null ? '-' : `${stats.best.label} (${stats.best.sum})`;
                document.getElementById('stat-najlosija').textContent = stats.worst === null ? '-' : `${stats.worst.label} (${stats.worst.sum})`;
            }

            function moveToNextEntryPosition(currentRow) {
                const currentRowData = state.rounds[state.activeRound][currentRow];
                const firstEmpty = currentRowData.findIndex((token) => token === null);

                if (firstEmpty !== -1) {
                    state.activeRow = currentRow;
                    state.activeCol = firstEmpty;
                    return;
                }

                if (currentRow < (config.brojSerija - 1)) {
                    state.activeRow = currentRow + 1;
                    state.activeCol = 0;
                    return;
                }

                state.activeRow = currentRow;
                state.activeCol = config.brojStrijelaUSeriji - 1;
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
                    state.activeRound = Number(this.dataset.round);
                    state.activeRow = 0;
                    state.activeCol = 0;
                    render();
                });
            });

            tableBody.addEventListener('click', function (event) {
                const button = event.target.closest('.js-shot-cell');
                if (!button) {
                    return;
                }

                state.activeRow = Number(button.dataset.row);
                state.activeCol = Number(button.dataset.col);
                button.blur();
                render();
            });

            keypad.addEventListener('click', function (event) {
                const button = event.target.closest('.js-hit-key');
                if (!button) {
                    return;
                }

                const value = button.dataset.value;
                const normalized = value === 'CLEAR' ? null : normalizeToken(value);
                const editedRow = state.activeRow;
                state.rounds[state.activeRound][state.activeRow][state.activeCol] = normalized;
                sortActiveRow(editedRow);

                if (value !== 'CLEAR') {
                    moveToNextEntryPosition(editedRow);
                }

                button.blur();
                render();
            });

            form.addEventListener('submit', function (event) {
                event.preventDefault();
                submitForm(false);
            });

            if (saveButton) {
                saveButton.addEventListener('click', function () {
                    submitForm(false);
                });
            }

            if (closeButton) {
                closeButton.addEventListener('click', function () {
                    if (!hasUnsavedChanges()) {
                        ignoreBeforeUnload = true;
                        window.location.href = closeRoute;
                        return;
                    }

                    showUnsavedModal();
                });
            }

            if (closeWithoutSaveBtn) {
                closeWithoutSaveBtn.addEventListener('click', function () {
                    ignoreBeforeUnload = true;
                    window.location.href = closeRoute;
                });
            }

            const cancelCloseBtn = document.getElementById('btn-odustani-zatvaranje');
            if (cancelCloseBtn) {
                cancelCloseBtn.addEventListener('click', function () {
                    hideUnsavedModal();
                });
            }

            if (saveAndCloseBtn) {
                saveAndCloseBtn.addEventListener('click', function () {
                    hideUnsavedModal();
                    submitForm(true);
                });
            }

            window.addEventListener('beforeunload', function (event) {
                if (ignoreBeforeUnload || isSubmitting || !hasUnsavedChanges()) {
                    return;
                }
                event.preventDefault();
                event.returnValue = '';
            });

            render();
            initialSnapshot = buildSnapshot();

            if (savedToast) {
                window.setTimeout(function () {
                    savedToast.classList.add('fade');
                    window.setTimeout(function () {
                        savedToast.remove();
                    }, 250);
                }, 1500);
            }
        })();
    </script>
@endsection
