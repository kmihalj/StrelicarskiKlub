{{-- Ekran rezultata kluba: medalje po godinama, statistika i popis turnira s rezultatima. --}}
@extends('layouts.app')
@section('content')
    <div class="container-xxl bg-white shadow">
        @php
            $statistikaTekuca = $statistikaGodine[$trenutnaGodina] ?? ['ukupno' => 0, 'zlato' => 0, 'srebro' => 0, 'bronca' => 0];
            $detaljna = $detaljnaStatistikaGodine ?? [
                'ukupno' => 0,
                'zlato' => 0,
                'srebro' => 0,
                'bronca' => 0,
                'broj_turnira' => 0,
                'najvise_medalja' => ['label' => '-', 'vrijednost' => 0],
                'najvise_zlatnih' => ['label' => '-', 'vrijednost' => 0],
                'najvise_srebrnih' => ['label' => '-', 'vrijednost' => 0],
                'najvise_broncanih' => ['label' => '-', 'vrijednost' => 0],
                'najvise_turnira' => ['label' => '-', 'vrijednost' => 0],
            ];
        @endphp

        <div class="row g-3 p-3 mb-3">
            <div class="col-xxl-3 col-lg-4 col-md-6">
                <div class="card h-100 border">
                    <div class="card-header bg-white fw-bold">{{ $trenutnaGodina }}. godina</div>
                    <div class="card-body py-2">
                        <p class="fw-bold mb-2">Ukupno medalja: {{ $statistikaTekuca['ukupno'] }}</p>
                        <p class="mb-0">
                            @include('admin.SVG.gold') - {{ $statistikaTekuca['zlato'] }}<br>
                            @include('admin.SVG.silver') - {{ $statistikaTekuca['srebro'] }}<br>
                            @include('admin.SVG.bronze') - {{ $statistikaTekuca['bronca'] }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="col-xxl-3 col-lg-4 col-md-6">
                <div class="card h-100 border">
                    <div class="card-header bg-white fw-bold d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <span>Statistika za {{ $odabranaGodinaStatistike }}.</span>
                        <form method="GET" action="{{ route('javno.rezultati') }}" class="d-flex align-items-center gap-2">
                            <label for="godina" class="small mb-0">Godina:</label>
                            <select id="godina" name="godina" class="form-select form-select-sm" onchange="this.form.submit()">
                                @foreach($dostupneGodineStatistike as $godina)
                                    <option value="{{ $godina }}" @selected((int)$godina === (int)$odabranaGodinaStatistike)>
                                        {{ $godina }}
                                    </option>
                                @endforeach
                            </select>
                            <noscript>
                                <button type="submit" class="btn btn-sm btn-warning">Prikaži</button>
                            </noscript>
                        </form>
                    </div>
                    <div class="card-body py-2">
                        <p class="fw-bold mb-2">Ukupno medalja: {{ $detaljna['ukupno'] }}</p>
                        <p class="mb-0">
                            @include('admin.SVG.gold') - {{ $detaljna['zlato'] }}<br>
                            @include('admin.SVG.silver') - {{ $detaljna['srebro'] }}<br>
                            @include('admin.SVG.bronze') - {{ $detaljna['bronca'] }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="col-xxl-6 col-lg-4 col-md-12">
                <div class="card h-100 border">
                    <div class="card-header bg-white fw-bold">
                        Detaljna statistika za {{ $odabranaGodinaStatistike }}.
                    </div>
                    <div class="card-body py-2">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="mb-2">
                                    <small class="text-muted d-block">Broj turnira s nastupom kluba</small>
                                    <span class="fw-semibold">{{ $detaljna['broj_turnira'] }}</span>
                                </div>
                                <div class="mb-2">
                                    <small class="text-muted d-block">Najviše turnira odradio član</small>
                                    <span class="fw-semibold">{{ $detaljna['najvise_turnira']['label'] }}</span>
                                    <span class="small">({{ $detaljna['najvise_turnira']['vrijednost'] }})</span>
                                </div>
                                <div>
                                    <small class="text-muted d-block">Najviše osvojenih medalja</small>
                                    <span class="fw-semibold">{{ $detaljna['najvise_medalja']['label'] }}</span>
                                    <span class="small">({{ $detaljna['najvise_medalja']['vrijednost'] }})</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-2">
                                    <small class="text-muted d-block">Najviše zlatnih medalja</small>
                                    <span class="fw-semibold">{{ $detaljna['najvise_zlatnih']['label'] }}</span>
                                    <span class="small">({{ $detaljna['najvise_zlatnih']['vrijednost'] }})</span>
                                </div>
                                <div class="mb-2">
                                    <small class="text-muted d-block">Najviše srebrnih medalja</small>
                                    <span class="fw-semibold">{{ $detaljna['najvise_srebrnih']['label'] }}</span>
                                    <span class="small">({{ $detaljna['najvise_srebrnih']['vrijednost'] }})</span>
                                </div>
                                <div>
                                    <small class="text-muted d-block">Najviše brončanih medalja</small>
                                    <span class="fw-semibold">{{ $detaljna['najvise_broncanih']['label'] }}</span>
                                    <span class="small">({{ $detaljna['najvise_broncanih']['vrijednost'] }})</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            @auth()
                @if(auth()->user()->rola <= 1)
                    <div class="col-12 text-end">
                        <button class="btn btn-sm btn-warning" type="button" data-bs-toggle="modal" data-bs-target="#UnosTurnira_modal">Dodaj turnir</button>
                        <button class="btn btn-sm btn-warning" type="button" onclick="location.href='{{ route('admin.rezultati.popisTurnira') }}'">Popis turnira</button>
                    </div>
                    @include('admin.rezultati.modal_za_unos')
                @endif
            @endauth
        </div>
    </div>


    @if($turniri->count() == 0)
        {{-- Ako nema unesenih turnira --}}
        <div class="row justify-content-center">
            <div class="col-12 mb-2 mt-2">
                <div class="ms-3">
                    <p class="fw-bold mb-1">Nema unešenih turnira</p>
                </div>
            </div>
        </div>
    @else
        {{-- Prikaz turnira --}}
        @include('layouts.paginationBlok', ['paginator' => $turniri, 'isTop' => true])

        <div class="container-xxl">
        @include('admin.rezultati.prikazRezultata')
        </div>


        @include('layouts.paginationBlok', ['paginator' => $turniri])
    @endif

@endsection
