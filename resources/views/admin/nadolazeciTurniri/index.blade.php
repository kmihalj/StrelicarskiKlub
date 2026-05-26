{{-- Administratorski unos i popis nadolazećih turnira. --}}
@extends('layouts.app')

@section('content')
    @php
        $jeUredjivanje = isset($urediTurnir) && $urediTurnir !== null;
        $formaRuta = $jeUredjivanje
            ? route('admin.nadolazeci_turniri.update', $urediTurnir)
            : route('admin.nadolazeci_turniri.store');
        $tekucaGodina = (int) now()->year;
        $sljedecaGodina = $tekucaGodina + 1;
        $archeryImportReport = session('archery_import_report');
        $trebaOtvoritiImportModal = is_array($archeryImportReport) && array_key_exists('output', $archeryImportReport);
        $trebaOtvoritiNoviTurnirModal = $errors->any() && old('_form_context') === 'create_turnir';
    @endphp

    <div class="container-xxl bg-white shadow mb-3">
        <div class="row justify-content-center p-2 shadow bg-danger fw-bolder">
            <div class="col-lg-12 text-white d-flex flex-wrap justify-content-between align-items-center gap-2">
                <span>Administracija nadolazećih turnira</span>
                <div class="d-inline-flex flex-wrap align-items-center gap-2">
                    <button type="button" class="btn btn-sm btn-light" data-bs-toggle="modal" data-bs-target="#noviNadolazeciTurnirModal">
                        Novi nadolazeći turnir
                    </button>
                    <form action="{{ route('admin.nadolazeci_turniri.import_archery') }}" method="POST" class="m-0">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-light">
                            Uvezi turnire sa archery.hr ({{ $tekucaGodina }} i {{ $sljedecaGodina }})
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @if($jeUredjivanje)
    <div class="container-xxl bg-white shadow mb-3">
        <div class="row justify-content-center p-2 shadow bg-danger fw-bolder">
            <div class="col-lg-12 text-white d-flex flex-wrap justify-content-between align-items-center gap-2">
                <span>Uredi nadolazeći turnir</span>
                <a href="{{ route('admin.nadolazeci_turniri.index') }}" class="btn btn-sm btn-light">Odustani</a>
            </div>
        </div>
        <div class="row p-3">
            <div class="col-12">
                <form action="{{ $formaRuta }}" method="POST" enctype="multipart/form-data" class="row g-3">
                    @csrf
                    <input type="hidden" name="_form_context" value="edit_turnir">
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
                                Plaćanje preko računa / barkod
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
                        <div class="form-text">Vrijedi samo za plaćanje preko računa.</div>
                    </div>
                    <div class="col-lg-3">
                        <label class="form-label fw-semibold mb-1">Primatelj kotizacije</label>
                        @php
                            $kotizacijaPrimateljId = old('kotizacija_primatelj_funkcija_id', $urediTurnir->kotizacija_primatelj_funkcija_id ?? '');
                        @endphp
                        <select class="form-select" name="kotizacija_primatelj_funkcija_id">
                            <option value="" @selected((string)$kotizacijaPrimateljId === '')>Račun kluba (zadano)</option>
                            @foreach($kotizacijaPrimatelji as $primatelj)
                                @php
                                    $primateljClan = $primatelj->clan;
                                    $primateljNaziv = $primatelj->kotizacijaPrimateljLabel();
                                    $primateljOpis = $primatelj->funkcija . ' - ' . ($primateljClan ? $primateljClan->Prezime . ' ' . $primateljClan->Ime : $primateljNaziv);
                                    $imaPodatke = $primatelj->imaPodatkeZaKotizacije();
                                @endphp
                                <option value="{{ $primatelj->id }}"
                                    @selected((string)$kotizacijaPrimateljId === (string)$primatelj->id)
                                    @disabled(!$imaPodatke)>
                                    {{ $primateljOpis }}{{ $imaPodatke ? '' : ' (nedostaje IBAN)' }}
                                </option>
                            @endforeach
                        </select>
                        <div class="form-text">Dodatne račune upišite na stranici Podaci o klubu.</div>
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
                            Spremi promjene
                        </button>
                        <a href="{{ route('admin.nadolazeci_turniri.index') }}" class="btn btn-secondary">Odustani</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

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

                                    return [
                                        'naziv' => trim((string)$clan->Ime . ' ' . (string)$clan->Prezime),
                                        'url' => route('javno.clanovi.prikaz_clana', (int)$clan->id),
                                    ];
                                })
                                ->filter()
                                ->sortBy('naziv')
                                ->values();
                            $datumTurnira = $turnir->datum?->copy()->startOfDay();
                            $lijecnickiUpozorenjaClanovima = collect($turnir->prijave ?? [])
                                ->map(function ($prijava) use ($datumTurnira) {
                                    $clan = $prijava->clan;
                                    if (!$clan || !$datumTurnira) {
                                        return null;
                                    }

                                    $naziv = trim((string)$clan->Ime . ' ' . (string)$clan->Prezime);
                                    $url = route('javno.clanovi.prikaz_clana', (int)$clan->id);

                                    if (empty($clan->lijecnicki_do)) {
                                        return [
                                            'naziv' => $naziv,
                                            'tip' => 'neevidentiran',
                                            'url' => $url,
                                        ];
                                    }

                                    try {
                                        $vrijediDo = \Carbon\Carbon::parse((string)$clan->lijecnicki_do)->endOfDay();
                                    } catch (\Throwable) {
                                        return [
                                            'naziv' => $naziv,
                                            'tip' => 'neevidentiran',
                                            'url' => $url,
                                        ];
                                    }

                                    if (!$vrijediDo->lt($datumTurnira)) {
                                        return null;
                                    }

                                    return [
                                        'naziv' => $naziv,
                                        'tip' => 'nevazeci',
                                        'url' => $url,
                                    ];
                                })
                                ->filter()
                                ->sortBy('naziv')
                                ->values();
                            $lijecnickiImaNevazece = $lijecnickiUpozorenjaClanovima
                                ->contains(fn ($clan) => ($clan['tip'] ?? null) === 'nevazeci');
                            $lijecnickiImaNeevidentirane = $lijecnickiUpozorenjaClanovima
                                ->contains(fn ($clan) => ($clan['tip'] ?? null) === 'neevidentiran');
                            $lijecnickiNaslov = $lijecnickiImaNevazece && $lijecnickiImaNeevidentirane
                                ? 'Liječnički nije važeći/evidentiran:'
                                : ($lijecnickiImaNevazece
                                    ? 'Liječnički nije važeći:'
                                    : 'Liječnički nije evidentiran:');
                            $rowspan = $lijecnickiUpozorenjaClanovima->isNotEmpty() ? 3 : 2;
                        @endphp
                        <tr>
                            <td rowspan="{{ $rowspan }}" class="align-middle">{{ $turnir->datumRasponLabel() }}</td>
                            <td rowspan="{{ $rowspan }}" class="align-middle text-break">
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
                                    Plaćanje preko računa / barkod
                                    @if($turnir->kotizacija_iznos !== null)
                                        - {{ number_format((float)$turnir->kotizacija_iznos, 2, ',', '.') }} EUR
                                    @else
                                        - iznos nije definiran
                                    @endif
                                    @php $primateljKotizacije = $turnir->kotizacijaPrimateljFunkcija; @endphp
                                    <div class="small text-muted">
                                        Primatelj:
                                        @if($primateljKotizacije && $primateljKotizacije->imaPodatkeZaKotizacije())
                                            {{ $primateljKotizacije->kotizacijaPrimateljLabel() }}
                                        @else
                                            račun kluba
                                        @endif
                                    </div>
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
                            <td rowspan="{{ $rowspan }}" class="text-end align-middle text-nowrap">
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
                                    @foreach($prijavljeniClanovi as $prijavljeniClan)
                                        <a href="{{ $prijavljeniClan['url'] }}" class="link-primary text-decoration-underline">{{ $prijavljeniClan['naziv'] }}</a>@if(!$loop->last), @endif
                                    @endforeach
                                @else
                                    nema prijava
                                @endif
                            </td>
                        </tr>
                        @if($lijecnickiUpozorenjaClanovima->isNotEmpty())
                            <tr>
                                <td colspan="6" class="small text-start align-middle">
                                    <span class="text-danger">
                                        {{ $lijecnickiNaslov }}
                                        @foreach($lijecnickiUpozorenjaClanovima as $clanLijecnicki)
                                            <a href="{{ $clanLijecnicki['url'] }}" class="link-danger text-decoration-underline">{{ $clanLijecnicki['naziv'] }}</a>@if(!$loop->last), @endif
                                        @endforeach
                                    </span>
                                </td>
                            </tr>
                        @endif
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

                                    return [
                                        'naziv' => trim((string)$clan->Ime . ' ' . (string)$clan->Prezime),
                                        'url' => route('javno.clanovi.prikaz_clana', (int)$clan->id),
                                    ];
                                })
                                ->filter()
                                ->sortBy('naziv')
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
                                    Plaćanje preko računa / barkod
                                    @if($turnir->kotizacija_iznos !== null)
                                        - {{ number_format((float)$turnir->kotizacija_iznos, 2, ',', '.') }} EUR
                                    @else
                                        - iznos nije definiran
                                    @endif
                                    @php $primateljKotizacije = $turnir->kotizacijaPrimateljFunkcija; @endphp
                                    <div class="small text-muted">
                                        Primatelj:
                                        @if($primateljKotizacije && $primateljKotizacije->imaPodatkeZaKotizacije())
                                            {{ $primateljKotizacije->kotizacijaPrimateljLabel() }}
                                        @else
                                            račun kluba
                                        @endif
                                    </div>
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
                                    <form action="{{ route('admin.nadolazeci_turniri.kreiraj_rezultate', $turnir) }}" method="POST" class="m-0">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-warning">Kreiraj rezultate</button>
                                    </form>
                                    <a href="{{ route('admin.nadolazeci_turniri.index', ['uredi' => $turnir->id]) }}" class="btn btn-sm btn-success">Uredi</a>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="5" class="small text-start align-middle">
                                Prijavljeni članovi:
                                @if($prijavljeniClanovi->count() > 0)
                                    @foreach($prijavljeniClanovi as $prijavljeniClan)
                                        <a href="{{ $prijavljeniClan['url'] }}" class="link-primary text-decoration-underline">{{ $prijavljeniClan['naziv'] }}</a>@if(!$loop->last), @endif
                                    @endforeach
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

    <div class="modal fade" id="noviNadolazeciTurnirModal" tabindex="-1" aria-labelledby="noviNadolazeciTurnirTitle" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-danger">
                    <h5 class="modal-title text-white" id="noviNadolazeciTurnirTitle">Novi nadolazeći turnir</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Zatvori"></button>
                </div>
                <form action="{{ route('admin.nadolazeci_turniri.store') }}" method="POST" enctype="multipart/form-data" class="m-0">
                    @csrf
                    <input type="hidden" name="_form_context" value="create_turnir">
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-lg-4">
                                <label class="form-label fw-semibold mb-1">Naziv turnira</label>
                                <input type="text" class="form-control" name="naziv" value="{{ old('naziv') }}" required>
                            </div>
                            <div class="col-lg-4">
                                <label class="form-label fw-semibold mb-1">Organizator</label>
                                <input type="text" class="form-control" name="organizator" value="{{ old('organizator') }}">
                            </div>
                            <div class="col-lg-4">
                                <label class="form-label fw-semibold mb-1">Mjesto</label>
                                <input type="text" class="form-control" name="mjesto" value="{{ old('mjesto') }}" required>
                            </div>
                            <div class="col-lg-12">
                                <label class="form-label fw-semibold mb-1">Napomena</label>
                                <input type="text"
                                       class="form-control"
                                       name="napomena"
                                       value="{{ old('napomena') }}"
                                       placeholder="Npr. Turnir traje dva dana...">
                            </div>
                            <div class="col-lg-2">
                                <label class="form-label fw-semibold mb-1">Datum</label>
                                <input type="date" class="form-control" name="datum" value="{{ old('datum') }}" required>
                            </div>
                            <div class="col-lg-2">
                                <label class="form-label fw-semibold mb-1">Datum do</label>
                                <input type="date" class="form-control" name="datum_do" value="{{ old('datum_do') }}">
                            </div>
                            <div class="col-lg-3">
                                <label class="form-label fw-semibold mb-1">Tip turnira</label>
                                <select class="form-select" name="tipovi_turnira_id" required>
                                    <option value="">Odaberi tip turnira</option>
                                    @foreach($tipoviTurnira as $tip)
                                        <option value="{{ $tip->id }}" @selected((int) old('tipovi_turnira_id') === (int) $tip->id)>
                                            {{ $tip->naziv }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-lg-2">
                                <label class="form-label fw-semibold mb-1">Rok prijava do</label>
                                <input type="date" class="form-control" name="prijave_otvorene_do" value="{{ old('prijave_otvorene_do') }}">
                            </div>
                            <div class="col-lg-3">
                                <label class="form-label fw-semibold mb-1">Poziv (PDF)</label>
                                <input type="file" class="form-control" name="poziv_pdf" accept="application/pdf">
                            </div>
                            <div class="col-lg-3">
                                <label class="form-label fw-semibold mb-1">Kotizacija</label>
                                @php
                                    $kotizacijaNacinNovi = old('kotizacija_nacin', 'undefined');
                                    $kotizacijaNacinNovi = $kotizacijaNacinNovi === null || $kotizacijaNacinNovi === '' ? 'undefined' : $kotizacijaNacinNovi;
                                @endphp
                                <select class="form-select" name="kotizacija_nacin">
                                    <option value="undefined" @selected($kotizacijaNacinNovi === 'undefined')>Nije još definirano</option>
                                    <option value="bank" @selected($kotizacijaNacinNovi === 'bank')>Plaćanje preko računa / barkod</option>
                                    <option value="cash" @selected($kotizacijaNacinNovi === 'cash')>Plaćanje gotovinom</option>
                                </select>
                            </div>
                            <div class="col-lg-3">
                                <label class="form-label fw-semibold mb-1">Iznos kotizacije (EUR)</label>
                                <input type="text"
                                       class="form-control"
                                       name="kotizacija_iznos"
                                       value="{{ old('kotizacija_iznos') }}"
                                       placeholder="npr. 15.00">
                            </div>
                            <div class="col-lg-3">
                                <label class="form-label fw-semibold mb-1">Rok uplate</label>
                                <input type="date" class="form-control" name="kotizacija_rok_uplate" value="{{ old('kotizacija_rok_uplate') }}">
                                <div class="form-text">Vrijedi samo za plaćanje preko računa.</div>
                            </div>
                            <div class="col-lg-3">
                                <label class="form-label fw-semibold mb-1">Primatelj kotizacije</label>
                                @php
                                    $kotizacijaPrimateljNoviId = old('kotizacija_primatelj_funkcija_id', '');
                                @endphp
                                <select class="form-select" name="kotizacija_primatelj_funkcija_id">
                                    <option value="" @selected((string)$kotizacijaPrimateljNoviId === '')>Račun kluba (zadano)</option>
                                    @foreach($kotizacijaPrimatelji as $primatelj)
                                        @php
                                            $primateljClan = $primatelj->clan;
                                            $primateljNaziv = $primatelj->kotizacijaPrimateljLabel();
                                            $primateljOpis = $primatelj->funkcija . ' - ' . ($primateljClan ? $primateljClan->Prezime . ' ' . $primateljClan->Ime : $primateljNaziv);
                                            $imaPodatke = $primatelj->imaPodatkeZaKotizacije();
                                        @endphp
                                        <option value="{{ $primatelj->id }}"
                                            @selected((string)$kotizacijaPrimateljNoviId === (string)$primatelj->id)
                                            @disabled(!$imaPodatke)>
                                            {{ $primateljOpis }}{{ $imaPodatke ? '' : ' (nedostaje IBAN)' }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="form-text">Dodatne račune upišite na stranici Podaci o klubu.</div>
                            </div>
                            <div class="col-lg-3 d-flex align-items-end">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="1" id="is_zakljucan_novi" name="is_zakljucan"
                                        @checked((bool) old('is_zakljucan'))>
                                    <label class="form-check-label" for="is_zakljucan_novi">Ručno zaključaj prijave</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Odustani</button>
                        <button type="submit" class="btn btn-danger">Spremi turnir</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="archeryImportReportModal" tabindex="-1" aria-labelledby="archeryImportReportTitle" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-danger">
                    <h5 class="modal-title text-white" id="archeryImportReportTitle">Izvještaj importa turnira</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Zatvori"></button>
                </div>
                <div class="modal-body">
                    @if(is_array($archeryImportReport))
                        @php
                            $importOk = (bool) ($archeryImportReport['ok'] ?? false);
                            $importOutput = trim((string) ($archeryImportReport['output'] ?? ''));
                            $importYears = $archeryImportReport['years'] ?? [];
                            $importYearsText = is_array($importYears) ? implode(', ', array_map('strval', $importYears)) : '';
                        @endphp
                        <div class="alert {{ $importOk ? 'alert-success' : 'alert-danger' }} py-2">
                            <div class="fw-semibold mb-1">
                                {{ $importOk ? 'Import je uspješno završen.' : 'Import je završio s greškom.' }}
                            </div>
                            <div class="small mb-0">
                                Godine: {{ $importYearsText !== '' ? $importYearsText : '-' }}
                                @if(!empty($archeryImportReport['generated_at']))
                                    , vrijeme: {{ $archeryImportReport['generated_at'] }}
                                @endif
                            </div>
                        </div>
                        <pre class="small mb-0" style="white-space: pre-wrap; word-break: break-word;">{{ $importOutput !== '' ? $importOutput : 'Nema izlaza komande.' }}</pre>
                    @else
                        <p class="mb-0">Nema dostupnog izvještaja importa.</p>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Zatvori</button>
                </div>
            </div>
        </div>
    </div>

    @if($trebaOtvoritiImportModal)
        <button type="button"
                id="archeryImportReportAutoOpen"
                class="d-none"
                data-bs-toggle="modal"
                data-bs-target="#archeryImportReportModal"
                aria-hidden="true"></button>
        <script>
            window.addEventListener('load', function () {
                const trigger = document.getElementById('archeryImportReportAutoOpen');
                if (trigger instanceof HTMLButtonElement) {
                    trigger.click();
                }
            });
        </script>
    @endif

    @if($trebaOtvoritiNoviTurnirModal)
        <button type="button"
                id="noviTurnirAutoOpen"
                class="d-none"
                data-bs-toggle="modal"
                data-bs-target="#noviNadolazeciTurnirModal"
                aria-hidden="true"></button>
        <script>
            window.addEventListener('load', function () {
                const trigger = document.getElementById('noviTurnirAutoOpen');
                if (trigger instanceof HTMLButtonElement) {
                    trigger.click();
                }
            });
        </script>
    @endif
@endsection
