@php
    $sekcijaDomId = strtolower((string)($sekcijaId ?? ('trening-' . ($konfig['tip'] ?? 'tip'))));
    $sekcijaDomId = preg_replace('/[^a-z0-9\-]+/', '-', $sekcijaDomId);
    $sekcijaDomId = trim((string)$sekcijaDomId, '-');
    if ($sekcijaDomId === '') {
        $sekcijaDomId = 'trening-sekcija';
    }
    $imaTablicu = $treninziPrikaz->count() > 0;
    $prikaziGraf = count($grafPodaci) >= 2;
    $editRouteName = $editRouteName ?? null;
    $editRouteExtraParams = $editRouteExtraParams ?? [];
    $destroyRouteName = $destroyRouteName ?? null;
    $destroyRouteExtraParams = $destroyRouteExtraParams ?? [];
    $imaAkcijuBrisanja = !empty($destroyRouteName);
@endphp

<div class="container-xxl">
    <div class="row justify-content-center p-2 mb-0 shadow bg-danger fw-bolder">
        <div class="col-lg-12 text-white d-flex flex-wrap align-items-center justify-content-between gap-2">
            <span>{{ $naslov }}</span>
            <span class="d-inline-flex align-items-center gap-2">
                @if(!empty($createRoute))
                    <button class="btn btn-sm btn-warning fw-bold px-3" type="button"
                            onclick="location.href='{{ $createRoute }}'" title="Dodaj trening">
                        +
                    </button>
                @endif
                @if($imaTablicu)
                    <button id="pregled-toggle-{{ $sekcijaDomId }}" class="btn btn-sm btn-outline-light"
                            type="button"
                            aria-expanded="false"
                            onclick="window.toggleTreningTablica('{{ $sekcijaDomId }}', null);">
                        Pregled
                    </button>
                @endif
            </span>
        </div>
    </div>
</div>

@once
    <style>
        .trening-th-r1 {
            background-color: #ffd447 !important;
            color: #1f1f1f !important;
        }

        .trening-th-r2 {
            background-color: #9fd76a !important;
            color: #1f1f1f !important;
        }

        .trening-th-total {
            background-color: var(--bs-primary) !important;
            color: var(--theme-on-primary, #ffffff) !important;
            border-color: rgba(var(--bs-primary-rgb), 0.65) !important;
        }

        .trening-td-runda {
            background-color: #f3f4f6 !important;
        }

        .theme-dark .trening-td-runda {
            background-color: var(--bs-secondary-bg-subtle) !important;
            color: var(--bs-body-color) !important;
        }

        .trening-mobile-card {
            border: 1px solid #dee2e6;
            border-radius: .5rem;
            padding: .65rem;
            background: #f8f9fa;
        }

        .theme-dark .trening-mobile-card {
            border-color: rgba(255, 255, 255, 0.2);
            background: var(--bs-dark-bg-subtle);
        }

        .trening-mobile-card + .trening-mobile-card {
            margin-top: .6rem;
        }

        .trening-mobile-summary {
            margin-bottom: .55rem;
        }

        .trening-mobile-summary > :not(caption) > * > * {
            padding: .3rem .35rem;
            font-size: .82rem;
        }

        .trening-mobile-summary th,
        .trening-mobile-summary td {
            white-space: nowrap;
            vertical-align: middle;
        }

        .trening-mobile-summary th:first-child,
        .trening-mobile-summary td:first-child {
            white-space: normal;
            min-width: 4.7rem;
        }

        .trening-mobile-stat-line {
            font-size: .8rem;
            line-height: 1.35;
        }
    </style>

    <script>
        if (typeof window.toggleTreningTablica !== 'function') {
            window.toggleTreningTablica = function (sekcijaDomId, shouldShow) {
                var tableWrap = document.getElementById(sekcijaDomId + '_tablica');
                var pregledToggle = document.getElementById('pregled-toggle-' + sekcijaDomId);
                var graphWrap = document.getElementById(sekcijaDomId + '_graf_okvir');

                if (!tableWrap) {
                    return;
                }

                var currentlyVisible = tableWrap.style.display !== 'none';
                var visible = typeof shouldShow === 'boolean' ? shouldShow : !currentlyVisible;

                tableWrap.style.display = visible ? 'block' : 'none';
                if (pregledToggle) {
                    pregledToggle.textContent = visible ? 'Sakrij' : 'Pregled';
                    pregledToggle.setAttribute('aria-expanded', visible ? 'true' : 'false');
                }

                if (graphWrap) {
                    graphWrap.classList.toggle('mb-0', visible);
                    graphWrap.classList.toggle('mb-3', !visible);
                }
            };
        }
    </script>
@endonce

@if($prikaziGraf || !$imaTablicu)
    <div class="container-xxl">
        <div id="{{ $sekcijaDomId }}_graf_okvir" class="row justify-content-center pt-3 pb-0 mb-3 shadow bg-white">
            <div class="col-lg-12">
                @if($prikaziGraf)
                    <div class="border rounded p-2 mb-3 bg-light-subtle">
                        <p class="fw-bold mb-2">Graf napretka (Total po datumu)</p>
                        <div class="trening-chart-wrap">
                            <svg class="js-trening-chart"
                                 data-points='@json($grafPodaci)'
                                 role="img"
                                 aria-label="Graf napretka"></svg>
                        </div>
                    </div>
                @endif

                @if(!$imaTablicu)
                    <p class="mb-0">Nema unesenih treninga.</p>
                @elseif(!$prikaziGraf)
                    <p class="mb-3 text-muted">Graf napretka prikazuje se od 2 treninga.</p>
                @endif
            </div>
        </div>
    </div>
@endif

@if($imaTablicu)
    <div id="{{ $sekcijaDomId }}_tablica" class="container-xxl" style="display: none;">
        <div class="row justify-content-center pt-0 pb-3 mb-3 shadow bg-white">
            <div class="col-lg-12">
                <div class="table-responsive d-none d-md-block">
                    <table class="table table-hover align-middle mb-0 border">
                        <thead>
                        <tr>
                            <th class="trening-th-total text-white">Datum</th>
                            <th class="trening-th-r1">Total</th>
                            <th class="trening-th-r1">9</th>
                            <th class="trening-th-r1">10</th>
                            @if($konfig['ima_x_kolonu'])
                                <th class="trening-th-r1">X</th>
                            @endif
                            <th class="trening-th-r2">Total</th>
                            <th class="trening-th-r2">9</th>
                            <th class="trening-th-r2">10</th>
                            @if($konfig['ima_x_kolonu'])
                                <th class="trening-th-r2">X</th>
                            @endif
                            <th class="trening-th-total text-white fw-bold">Total</th>
                            <th class="trening-th-total text-white fw-bold">9</th>
                            <th class="trening-th-total text-white fw-bold">10</th>
                            @if($konfig['ima_x_kolonu'])
                                <th class="trening-th-total text-white fw-bold">X</th>
                            @endif
                            <th class="trening-th-total text-white">Akcija</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($treninziPrikaz as $stavka)
                            @php
                                $trening = $stavka['trening'];
                                $runda1 = $stavka['runda1'];
                                $runda2 = $stavka['runda2'];
                                $ukupno = $stavka['ukupno'];
                                $statistika = $stavka['statistika'];
                                $collapsePrefix = $konfig['tip'] . '-' . $trening->id;
                                $editRouteParams = array_merge($editRouteExtraParams, ['trening' => $trening]);
                                $destroyRouteParams = array_merge($destroyRouteExtraParams, ['trening' => $trening]);
                                $desktopDetailsId = $collapsePrefix . '-detalji-desktop';
                            @endphp
                            <tr>
                                <td>{{ $trening->datum ? $trening->datum->format('d.m.Y.') : '-' }}</td>
                                <td class="trening-td-runda">{{ $runda1['imaUnosa'] ? $runda1['total'] : '-' }}</td>
                                <td class="trening-td-runda">{{ $runda1['imaUnosa'] ? $runda1['devetke'] : '-' }}</td>
                                <td class="trening-td-runda">{{ $runda1['imaUnosa'] ? $runda1['desetke'] : '-' }}</td>
                                @if($konfig['ima_x_kolonu'])
                                    <td class="trening-td-runda">{{ $runda1['imaUnosa'] ? $runda1['x'] : '-' }}</td>
                                @endif
                                <td class="trening-td-runda">{{ $runda2['imaUnosa'] ? $runda2['total'] : '-' }}</td>
                                <td class="trening-td-runda">{{ $runda2['imaUnosa'] ? $runda2['devetke'] : '-' }}</td>
                                <td class="trening-td-runda">{{ $runda2['imaUnosa'] ? $runda2['desetke'] : '-' }}</td>
                                @if($konfig['ima_x_kolonu'])
                                    <td class="trening-td-runda">{{ $runda2['imaUnosa'] ? $runda2['x'] : '-' }}</td>
                                @endif
                                <td class="fw-bold">{{ $ukupno['imaUnosa'] ? $ukupno['total'] : '-' }}</td>
                                <td class="fw-bold">{{ $ukupno['imaUnosa'] ? $ukupno['devetke'] : '-' }}</td>
                                <td class="fw-bold">{{ $ukupno['imaUnosa'] ? $ukupno['desetke'] : '-' }}</td>
                                @if($konfig['ima_x_kolonu'])
                                    <td class="fw-bold">{{ $ukupno['imaUnosa'] ? $ukupno['x'] : '-' }}</td>
                                @endif
                                <td>
                                    <div class="d-flex justify-content-between align-items-center gap-2">
                                        <div class="d-flex flex-nowrap gap-1">
                                            <button type="button" class="btn btn-sm btn-outline-danger"
                                                    data-bs-toggle="collapse"
                                                    data-bs-target="#{{ $desktopDetailsId }}"
                                                    aria-expanded="false">
                                                Pregled
                                            </button>
                                            @if(!empty($editRouteName))
                                                <button type="button" class="btn btn-sm btn-outline-primary"
                                                        onclick="location.href='{{ route($editRouteName, $editRouteParams) }}'">
                                                    Uredi
                                                </button>
                                            @endif
                                        </div>
                                        @if($imaAkcijuBrisanja)
                                            <div class="ms-auto">
                                                <form id="delete-{{ $collapsePrefix }}" action="{{ route($destroyRouteName, $destroyRouteParams) }}" method="POST">
                                                    @csrf
                                                </form>
                                                <button type="submit" form="delete-{{ $collapsePrefix }}"
                                                        class="btn btn-sm btn-outline-danger"
                                                        onclick="return confirm('&#381;eli&#353; obrisati trening?')">
                                                    Obri&#353;i
                                                </button>
                                            </div>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            <tr class="collapse" id="{{ $desktopDetailsId }}">
                                <td colspan="{{ $konfig['ima_x_kolonu'] ? 14 : 11 }}" class="bg-body-tertiary">
                                    <p class="mb-2 text-end">
                                        <span class="fw-semibold">Prosjek pogodaka:</span>
                                        @if(is_null($statistika['prosjek']))
                                            -
                                        @else
                                            {{ number_format($statistika['prosjek'], 2, ',', '.') }}
                                        @endif
                                         ;
                                        <span class="fw-semibold">Naj&#269;e&#353;&#263;i pogodak:</span>
                                        @if(is_null($statistika['najcesciPogodak']))
                                            -
                                        @else
                                            {{ $statistika['najcesciPogodak'] }} ({{ $statistika['najcesciPogodakBroj'] }}x)
                                        @endif
                                         ;
                                        <span class="fw-semibold">Najbolja serija:</span>
                                        @if(is_null($statistika['najboljeSerije']))
                                            -
                                        @else
                                            {{ $statistika['najboljeSerije'] }} ({{ $statistika['najboljiZbroj'] }})
                                        @endif
                                         ;
                                        <span class="fw-semibold">Najlo&#353;ija serija:</span>
                                        @if(is_null($statistika['najlosijeSerije']))
                                            -
                                        @else
                                            {{ $statistika['najlosijeSerije'] }} ({{ $statistika['najlosijiZbroj'] }})
                                        @endif
                                    </p>

                                    <p class="fw-bold mb-1">Runda 1</p>
                                    @include('javno.partials.treningRundaTablica', ['runda' => $runda1, 'konfig' => $konfig])

                                    <p class="fw-bold mt-2 mb-1">Runda 2</p>
                                    @include('javno.partials.treningRundaTablica', ['runda' => $runda2, 'konfig' => $konfig])
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="d-md-none">
                    @foreach($treninziPrikaz as $stavka)
                        @php
                            $trening = $stavka['trening'];
                            $runda1 = $stavka['runda1'];
                            $runda2 = $stavka['runda2'];
                            $ukupno = $stavka['ukupno'];
                            $statistika = $stavka['statistika'];
                            $collapsePrefix = $konfig['tip'] . '-' . $trening->id;
                            $editRouteParams = array_merge($editRouteExtraParams, ['trening' => $trening]);
                            $destroyRouteParams = array_merge($destroyRouteExtraParams, ['trening' => $trening]);
                            $mobileDetailsId = $collapsePrefix . '-detalji-mobile';
                        @endphp
                        <div class="trening-mobile-card">
                            <p class="fw-bold mb-2">{{ $trening->datum ? $trening->datum->format('d.m.Y.') : '-' }}</p>

                            <table class="table table-sm border align-middle mb-0 trening-mobile-summary">
                                <thead>
                                <tr>
                                    <th class="trening-th-total text-white">Runda</th>
                                    <th class="trening-th-total text-white">Total</th>
                                    <th class="trening-th-total text-white">9</th>
                                    <th class="trening-th-total text-white">10</th>
                                    @if($konfig['ima_x_kolonu'])
                                        <th class="trening-th-total text-white">X</th>
                                    @endif
                                </tr>
                                </thead>
                                <tbody>
                                <tr>
                                    <td class="fw-semibold">Runda 1</td>
                                    <td>{{ $runda1['imaUnosa'] ? $runda1['total'] : '-' }}</td>
                                    <td>{{ $runda1['imaUnosa'] ? $runda1['devetke'] : '-' }}</td>
                                    <td>{{ $runda1['imaUnosa'] ? $runda1['desetke'] : '-' }}</td>
                                    @if($konfig['ima_x_kolonu'])
                                        <td>{{ $runda1['imaUnosa'] ? $runda1['x'] : '-' }}</td>
                                    @endif
                                </tr>
                                <tr>
                                    <td class="fw-semibold">Runda 2</td>
                                    <td>{{ $runda2['imaUnosa'] ? $runda2['total'] : '-' }}</td>
                                    <td>{{ $runda2['imaUnosa'] ? $runda2['devetke'] : '-' }}</td>
                                    <td>{{ $runda2['imaUnosa'] ? $runda2['desetke'] : '-' }}</td>
                                    @if($konfig['ima_x_kolonu'])
                                        <td>{{ $runda2['imaUnosa'] ? $runda2['x'] : '-' }}</td>
                                    @endif
                                </tr>
                                <tr class="fw-bold">
                                    <td>Ukupno</td>
                                    <td>{{ $ukupno['imaUnosa'] ? $ukupno['total'] : '-' }}</td>
                                    <td>{{ $ukupno['imaUnosa'] ? $ukupno['devetke'] : '-' }}</td>
                                    <td>{{ $ukupno['imaUnosa'] ? $ukupno['desetke'] : '-' }}</td>
                                    @if($konfig['ima_x_kolonu'])
                                        <td>{{ $ukupno['imaUnosa'] ? $ukupno['x'] : '-' }}</td>
                                    @endif
                                </tr>
                                </tbody>
                            </table>

                            <div class="d-flex justify-content-between align-items-center gap-2 mt-2">
                                <div class="d-flex flex-wrap gap-1">
                                    <button type="button" class="btn btn-sm btn-outline-danger"
                                            data-bs-toggle="collapse"
                                            data-bs-target="#{{ $mobileDetailsId }}"
                                            aria-expanded="false">
                                        Pregled
                                    </button>
                                    @if(!empty($editRouteName))
                                        <button type="button" class="btn btn-sm btn-outline-primary"
                                                onclick="location.href='{{ route($editRouteName, $editRouteParams) }}'">
                                            Uredi
                                        </button>
                                    @endif
                                </div>
                                @if($imaAkcijuBrisanja)
                                    <div class="ms-auto">
                                        <form id="delete-mobile-{{ $collapsePrefix }}" action="{{ route($destroyRouteName, $destroyRouteParams) }}" method="POST">
                                            @csrf
                                        </form>
                                        <button type="submit" form="delete-mobile-{{ $collapsePrefix }}"
                                                class="btn btn-sm btn-outline-danger"
                                                onclick="return confirm('&#381;eli&#353; obrisati trening?')">
                                            Obri&#353;i
                                        </button>
                                    </div>
                                @endif
                            </div>

                            <div class="collapse mt-2" id="{{ $mobileDetailsId }}">
                                <div class="border rounded p-2 bg-white">
                                    <p class="mb-2 trening-mobile-stat-line">
                                        <span class="fw-semibold">Prosjek pogodaka:</span>
                                        @if(is_null($statistika['prosjek']))
                                            -
                                        @else
                                            {{ number_format($statistika['prosjek'], 2, ',', '.') }}
                                        @endif
                                         ;
                                        <span class="fw-semibold">Naj&#269;e&#353;&#263;i pogodak:</span>
                                        @if(is_null($statistika['najcesciPogodak']))
                                            -
                                        @else
                                            {{ $statistika['najcesciPogodak'] }} ({{ $statistika['najcesciPogodakBroj'] }}x)
                                        @endif
                                    </p>
                                    <p class="mb-2 trening-mobile-stat-line">
                                        <span class="fw-semibold">Najbolja serija:</span>
                                        @if(is_null($statistika['najboljeSerije']))
                                            -
                                        @else
                                            {{ $statistika['najboljeSerije'] }} ({{ $statistika['najboljiZbroj'] }})
                                        @endif
                                         ;
                                        <span class="fw-semibold">Najlo&#353;ija serija:</span>
                                        @if(is_null($statistika['najlosijeSerije']))
                                            -
                                        @else
                                            {{ $statistika['najlosijeSerije'] }} ({{ $statistika['najlosijiZbroj'] }})
                                        @endif
                                    </p>

                                    <p class="fw-bold mb-1">Runda 1</p>
                                    @include('javno.partials.treningRundaTablica', ['runda' => $runda1, 'konfig' => $konfig])

                                    <p class="fw-bold mt-2 mb-1">Runda 2</p>
                                    @include('javno.partials.treningRundaTablica', ['runda' => $runda2, 'konfig' => $konfig])
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
@endif
