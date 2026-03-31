{{-- Administratorski unos i popis nadolazećih turnira. --}}
@extends('layouts.app')

@section('content')
    @php
        $jeUredjivanje = isset($urediTurnir) && $urediTurnir !== null;
        $formaRuta = $jeUredjivanje
            ? route('admin.nadolazeci_turniri.update', $urediTurnir)
            : route('admin.nadolazeci_turniri.store');
    @endphp

    <div class="container-xxl bg-white shadow mb-3">
        <div class="row justify-content-center p-2 shadow bg-danger fw-bolder">
            <div class="col-lg-12 text-white">
                {{ $jeUredjivanje ? 'Uredi nadolazeći turnir' : 'Novi nadolazeći turnir' }}
            </div>
        </div>
        <div class="row p-3">
            <div class="col-12">
                <form action="{{ $formaRuta }}" method="POST" enctype="multipart/form-data" class="row g-3">
                    @csrf
                    <div class="col-lg-4">
                        <label class="form-label fw-semibold mb-1">Naziv turnira</label>
                        <input type="text" class="form-control" name="naziv"
                               value="{{ old('naziv', $urediTurnir->naziv ?? '') }}" required>
                    </div>
                    <div class="col-lg-4">
                        <label class="form-label fw-semibold mb-1">Organizator</label>
                        <input type="text" class="form-control" name="organizator"
                               value="{{ old('organizator', $urediTurnir->organizator ?? '') }}">
                    </div>
                    <div class="col-lg-4">
                        <label class="form-label fw-semibold mb-1">Mjesto</label>
                        <input type="text" class="form-control" name="mjesto"
                               value="{{ old('mjesto', $urediTurnir->mjesto ?? '') }}" required>
                    </div>
                    <div class="col-lg-12">
                        <label class="form-label fw-semibold mb-1">Napomena</label>
                        <input type="text" class="form-control" name="napomena"
                               value="{{ old('napomena', $urediTurnir->napomena ?? '') }}"
                               placeholder="Npr. Turnir traje dva dana...">
                    </div>

                    <div class="col-lg-2">
                        <label class="form-label fw-semibold mb-1">Datum</label>
                        <input type="date" class="form-control" name="datum"
                               value="{{ old('datum', isset($urediTurnir?->datum) ? $urediTurnir->datum->format('Y-m-d') : '') }}" required>
                    </div>
                    <div class="col-lg-2">
                        <label class="form-label fw-semibold mb-1">Datum do</label>
                        <input type="date" class="form-control" name="datum_do"
                               value="{{ old('datum_do', isset($urediTurnir?->datum_do) ? $urediTurnir->datum_do->format('Y-m-d') : '') }}">
                    </div>
                    <div class="col-lg-3">
                        <label class="form-label fw-semibold mb-1">Tip turnira</label>
                        <select class="form-select" name="tipovi_turnira_id" required>
                            <option value="">Odaberi tip turnira</option>
                            @foreach($tipoviTurnira as $tip)
                                <option value="{{ $tip->id }}"
                                    @selected((int)old('tipovi_turnira_id', $urediTurnir->tipovi_turnira_id ?? 0) === (int)$tip->id)>
                                    {{ $tip->naziv }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-2">
                        <label class="form-label fw-semibold mb-1">Rok prijava do</label>
                        <input type="date" class="form-control" name="prijave_otvorene_do"
                               value="{{ old('prijave_otvorene_do', isset($urediTurnir?->prijave_otvorene_do) ? $urediTurnir->prijave_otvorene_do->format('Y-m-d') : '') }}">
                    </div>
                    <div class="col-lg-3">
                        <label class="form-label fw-semibold mb-1">Poziv (PDF)</label>
                        <input type="file" class="form-control" name="poziv_pdf" accept="application/pdf">
                    </div>

                    <div class="col-lg-3">
                        <label class="form-label fw-semibold mb-1">Kotizacija</label>
                        @php
                            $kotizacijaNacin = old('kotizacija_nacin', $urediTurnir->kotizacija_nacin ?? 'undefined');
                            $kotizacijaNacin = $kotizacijaNacin === null || $kotizacijaNacin === '' ? 'undefined' : $kotizacijaNacin;
                        @endphp
                        <select class="form-select" name="kotizacija_nacin">
                            <option value="undefined"
                                @selected($kotizacijaNacin === 'undefined')>
                                Nije još definirano
                            </option>
                            <option value="bank"
                                @selected($kotizacijaNacin === 'bank')>
                                Plaćanje preko računa kluba
                            </option>
                            <option value="cash"
                                @selected($kotizacijaNacin === 'cash')>
                                Plaćanje gotovinom
                            </option>
                        </select>
                    </div>
                    <div class="col-lg-3">
                        <label class="form-label fw-semibold mb-1">Iznos kotizacije (EUR)</label>
                        <input type="text" class="form-control" name="kotizacija_iznos"
                               value="{{ old('kotizacija_iznos', isset($urediTurnir?->kotizacija_iznos) ? number_format((float)$urediTurnir->kotizacija_iznos, 2, '.', '') : '') }}"
                               placeholder="npr. 15.00">
                    </div>
                    <div class="col-lg-3">
                        <label class="form-label fw-semibold mb-1">Rok uplate</label>
                        <input type="date" class="form-control" name="kotizacija_rok_uplate"
                               value="{{ old('kotizacija_rok_uplate', isset($urediTurnir?->kotizacija_rok_uplate) ? $urediTurnir->kotizacija_rok_uplate->format('Y-m-d') : '') }}">
                        <div class="form-text">Vrijedi samo za "Plaćanje preko računa kluba".</div>
                    </div>
                    <div class="col-lg-3 d-flex align-items-end">
                        @if(!empty($urediTurnir?->poziv_pdf_path))
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="1" id="obrisi_poziv_pdf" name="obrisi_poziv_pdf">
                                <label class="form-check-label" for="obrisi_poziv_pdf">
                                    Obriši postojeći PDF poziv
                                </label>
                            </div>
                        @endif
                    </div>
                    <div class="col-lg-12 d-flex align-items-end">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="1" id="is_zakljucan" name="is_zakljucan"
                                @checked((bool)old('is_zakljucan', $urediTurnir->is_zakljucan ?? false))>
                            <label class="form-check-label" for="is_zakljucan">Ručno zaključaj prijave</label>
                        </div>
                    </div>

                    @if(!empty($urediTurnir?->poziv_pdf_path))
                        <div class="col-12">
                            <a href="{{ asset('storage/' . $urediTurnir->poziv_pdf_path) }}" target="_blank" class="btn btn-sm btn-primary">
                                Trenutni PDF poziv
                            </a>
                        </div>
                    @endif

                    <div class="col-12 d-flex gap-2">
                        <button type="submit" class="btn btn-danger">
                            {{ $jeUredjivanje ? 'Spremi promjene' : 'Spremi turnir' }}
                        </button>
                        @if($jeUredjivanje)
                            <a href="{{ route('admin.nadolazeci_turniri.index') }}" class="btn btn-secondary">Odustani</a>
                        @endif
                    </div>
                </form>
            </div>
        </div>
    </div>

    @include('layouts.paginationBlok', ['paginator' => $nadolazeciTurniri, 'isTop' => true])
    <div class="container-xxl bg-white shadow mb-3">
        <div class="row justify-content-center p-2 shadow bg-danger fw-bolder">
            <div class="col-lg-12 text-white">Popis nadolazećih turnira</div>
        </div>
        <div class="row p-3">
            <div class="col-12 table-responsive">
                <table class="table table-hover align-middle mb-0 border" style="min-width: 1080px;">
                    <thead class="table-warning">
                    <tr>
                        <th>Datum</th>
                        <th>Naziv</th>
                        <th>Mjesto</th>
                        <th>Tip turnira</th>
                        <th>Rok prijava</th>
                        <th>Prijave</th>
                        <th>Kotizacija</th>
                        <th>Poziv</th>
                        <th class="text-nowrap text-end" style="width: 1%;"></th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($nadolazeciTurniri as $turnir)
                        @php
                            $zakljucano = $turnir->prijaveZakljucane();
                            $prijavljeniClanovi = collect($turnir->prijave ?? [])
                                ->map(function ($prijava) {
                                    $clan = $prijava->clan;
                                    if (!$clan) {
                                        return null;
                                    }

                                    return trim((string)$clan->Prezime . ' ' . (string)$clan->Ime);
                                })
                                ->filter()
                                ->sort()
                                ->values();
                            $datumTurnira = $turnir->datum?->copy()->startOfDay();
                            $lijecnickiIsticuClanovima = collect($turnir->prijave ?? [])
                                ->map(function ($prijava) use ($datumTurnira) {
                                    $clan = $prijava->clan;
                                    if (!$clan || !$datumTurnira || empty($clan->lijecnicki_do)) {
                                        return null;
                                    }

                                    try {
                                        $vrijediDo = \Carbon\Carbon::parse((string)$clan->lijecnicki_do)->endOfDay();
                                    } catch (\Throwable) {
                                        return null;
                                    }

                                    if (!$vrijediDo->lt($datumTurnira)) {
                                        return null;
                                    }

                                    return trim((string)$clan->Prezime . ' ' . (string)$clan->Ime)
                                        . ' - '
                                        . $vrijediDo->format('d.m.Y.');
                                })
                                ->filter()
                                ->sort()
                                ->values();
                        @endphp
                        <tr>
                            <td rowspan="3" class="align-middle">{{ $turnir->datumRasponLabel() }}</td>
                            <td rowspan="3" class="align-middle text-break">
                                <div class="fw-semibold">{{ $turnir->naziv }}</div>
                                @if($turnir->organizator)
                                    <div class="small text-muted">{{ $turnir->organizator }}</div>
                                @endif
                                @if(!empty($turnir->napomena))
                                    <div class="small text-warning-emphasis">{{ $turnir->napomena }}</div>
                                @endif
                            </td>
                            <td>{{ $turnir->mjesto }}</td>
                            <td>{{ $turnir->tipTurnira->naziv ?? '-' }}</td>
                            <td>
                                @if($turnir->prijave_otvorene_do)
                                    {{ $turnir->prijave_otvorene_do->format('d.m.Y.') }}
                                @else
                                    -
                                @endif
                                <div>
                                    @if($zakljucano)
                                        <span class="badge bg-danger">Zaključano</span>
                                    @else
                                        <span class="badge bg-success">Otvoreno</span>
                                    @endif
                                </div>
                            </td>
                            <td>{{ (int)$turnir->aktivne_prijave_count }}</td>
                            <td>
                                @if($turnir->kotizacija_nacin === 'bank')
                                    Plaćanje preko računa kluba
                                    @if($turnir->kotizacija_iznos !== null)
                                        - {{ number_format((float)$turnir->kotizacija_iznos, 2, ',', '.') }} EUR
                                    @else
                                        - iznos nije definiran
                                    @endif
                                @elseif($turnir->kotizacija_nacin === 'cash')
                                    Plaćanje gotovinom
                                    @if($turnir->kotizacija_iznos !== null)
                                        - {{ number_format((float)$turnir->kotizacija_iznos, 2, ',', '.') }} EUR
                                    @else
                                        - iznos nije definiran
                                    @endif
                                @else
                                    Nije još definirano
                                @endif
                            </td>
                            <td>
                                @if(!empty($turnir->poziv_pdf_path))
                                    <a href="{{ asset('storage/' . $turnir->poziv_pdf_path) }}" target="_blank">PDF</a>
                                @else
                                    -
                                @endif
                            </td>
                            <td rowspan="3" class="text-end align-middle text-nowrap">
                                <div class="d-inline-flex align-items-center justify-content-end flex-nowrap gap-1">
                                    <a href="{{ route('admin.nadolazeci_turniri.show', $turnir) }}" class="btn btn-sm btn-primary">Prijave</a>
                                    <a href="{{ route('admin.nadolazeci_turniri.index', ['uredi' => $turnir->id]) }}" class="btn btn-sm btn-success">Uredi</a>
                                    <form action="{{ route('admin.nadolazeci_turniri.destroy', $turnir) }}" method="POST" class="m-0">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-danger"
                                                onclick="return confirm('Obrisati turnir i sve prijave?')">Obriši</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="6" class="small text-start align-middle">
                                Prijavljeni članovi:
                                @if($prijavljeniClanovi->count() > 0)
                                    {{ $prijavljeniClanovi->implode(', ') }}
                                @else
                                    nema prijava
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td colspan="6" class="small text-start align-middle">
                                @if($lijecnickiIsticuClanovima->count() > 0)
                                    <span class="text-danger">Liječnički ističe članovima: {{ $lijecnickiIsticuClanovima->implode(', ') }}</span>
                                @else
                                    <span class="text-muted">Liječnički je važeći za sve prijavljene članove.</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center">Nema unesenih nadolazećih turnira.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @include('layouts.paginationBlok', ['paginator' => $nadolazeciTurniri])

    @include('layouts.paginationBlok', ['paginator' => $prosliTurniri, 'isTop' => true])
    <div class="container-xxl bg-white shadow mb-3">
        <div class="row justify-content-center p-2 shadow bg-danger fw-bolder">
            <div class="col-lg-12 text-white">Prošli turniri</div>
        </div>
        <div class="row p-3">
            <div class="col-12 table-responsive">
                <table class="table table-hover align-middle mb-0 border" style="min-width: 1080px;">
                    <thead class="table-warning">
                    <tr>
                        <th>Datum</th>
                        <th>Naziv</th>
                        <th>Mjesto</th>
                        <th>Tip turnira</th>
                        <th>Prijave</th>
                        <th>Kotizacija</th>
                        <th>Poziv</th>
                        <th class="text-nowrap text-end" style="width: 1%;"></th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($prosliTurniri as $turnir)
                        @php
                            $prijavljeniClanovi = collect($turnir->prijave ?? [])
                                ->map(function ($prijava) {
                                    $clan = $prijava->clan;
                                    if (!$clan) {
                                        return null;
                                    }

                                    return trim((string)$clan->Prezime . ' ' . (string)$clan->Ime);
                                })
                                ->filter()
                                ->sort()
                                ->values();
                        @endphp
                        <tr>
                            <td rowspan="2" class="align-middle">{{ $turnir->datumRasponLabel() }}</td>
                            <td rowspan="2" class="align-middle text-break">
                                <div class="fw-semibold">{{ $turnir->naziv }}</div>
                                @if($turnir->organizator)
                                    <div class="small text-muted">{{ $turnir->organizator }}</div>
                                @endif
                                @if(!empty($turnir->napomena))
                                    <div class="small text-warning-emphasis">{{ $turnir->napomena }}</div>
                                @endif
                            </td>
                            <td>{{ $turnir->mjesto }}</td>
                            <td>{{ $turnir->tipTurnira->naziv ?? '-' }}</td>
                            <td>{{ (int)$turnir->aktivne_prijave_count }}</td>
                            <td>
                                @if($turnir->kotizacija_nacin === 'bank')
                                    Plaćanje preko računa kluba
                                    @if($turnir->kotizacija_iznos !== null)
                                        - {{ number_format((float)$turnir->kotizacija_iznos, 2, ',', '.') }} EUR
                                    @else
                                        - iznos nije definiran
                                    @endif
                                @elseif($turnir->kotizacija_nacin === 'cash')
                                    Plaćanje gotovinom
                                    @if($turnir->kotizacija_iznos !== null)
                                        - {{ number_format((float)$turnir->kotizacija_iznos, 2, ',', '.') }} EUR
                                    @else
                                        - iznos nije definiran
                                    @endif
                                @else
                                    Nije još definirano
                                @endif
                            </td>
                            <td>
                                @if(!empty($turnir->poziv_pdf_path))
                                    <a href="{{ asset('storage/' . $turnir->poziv_pdf_path) }}" target="_blank">PDF</a>
                                @else
                                    -
                                @endif
                            </td>
                            <td rowspan="2" class="text-end align-middle text-nowrap">
                                <div class="d-inline-flex align-items-center justify-content-end flex-nowrap gap-1">
                                    <a href="{{ route('admin.nadolazeci_turniri.show', $turnir) }}" class="btn btn-sm btn-primary">Prijave</a>
                                    <a href="{{ route('admin.nadolazeci_turniri.index', ['uredi' => $turnir->id]) }}" class="btn btn-sm btn-success">Uredi</a>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="5" class="small text-start align-middle">
                                Prijavljeni članovi:
                                @if($prijavljeniClanovi->count() > 0)
                                    {{ $prijavljeniClanovi->implode(', ') }}
                                @else
                                    nema prijava
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center">Nema prošlih turnira.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @include('layouts.paginationBlok', ['paginator' => $prosliTurniri])
@endsection
