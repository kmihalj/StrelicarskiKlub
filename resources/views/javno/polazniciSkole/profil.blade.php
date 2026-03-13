@extends('layouts.app')

@section('content')
    @php
        $otvoriDokumente = request()->boolean('open_documents');
        $otvoriPlacanja = (bool)($otvoriPlacanja ?? request()->boolean('open_payments'));
        $schoolPaymentEnabled = (bool)($schoolPaymentEnabled ?? false);
        $schoolPaymentSummary = $schoolPaymentSummary ?? null;
        $schoolPaymentNotice = $schoolPaymentNotice ?? null;
        $schoolPaymentProfile = $schoolPaymentSummary['profile'] ?? null;
        $schoolPaymentCharges = $schoolPaymentSummary['charges'] ?? collect();
        $schoolPaymentOpenCharges = $schoolPaymentSummary['openCharges'] ?? collect();
        $schoolPaymentPaidCharges = $schoolPaymentSummary['paidCharges'] ?? collect();
        $schoolAttendanceCount = (int)($schoolPaymentSummary['attendanceCount'] ?? 0);
        $schoolPaymentService = app(\App\Services\SchoolPaymentService::class);
    @endphp

    <div class="container-xxl bg-white shadow">
        <div class="row justify-content-center p-2 shadow bg-danger fw-bolder">
            <div class="col-lg-12 text-white">
                Polaznik škole: {{ $polaznik->Ime }} {{ $polaznik->Prezime }}
            </div>
        </div>

        @auth
            @if((int)auth()->user()->polaznik_id === (int)$polaznik->id)
                <div class="row p-2 bg-secondary-subtle border-bottom">
                    @if(!($themeModeForced ?? false))
                        <div class="col-12 text-start">
                            <form action="{{ route('user.theme_mode.update') }}" method="POST" class="d-inline-flex align-items-center gap-2">
                                @csrf
                                <label for="profil_tema_mod_skola" class="small fw-semibold mb-0">Prikaz teme:</label>
                                <select id="profil_tema_mod_skola" name="theme_mode_preference" class="form-select form-select-sm"
                                        onchange="this.form.submit()">
                                    @php
                                        $mod = auth()->user()->theme_mode_preference ?? 'auto';
                                    @endphp
                                    <option value="auto" @if($mod === 'auto') selected @endif>Automatski (uređaj)</option>
                                    <option value="light" @if($mod === 'light') selected @endif>Svijetla</option>
                                    <option value="dark" @if($mod === 'dark') selected @endif>Tamna</option>
                                </select>
                            </form>
                        </div>
                    @endif
                </div>
            @endif
        @endauth

        <div class="row p-3 bg-secondary-subtle">
            <div class="col-12">
                @if($mozeUredjivati)
                    <form id="uredjivanje_polaznika" action="{{ route('admin.skola.polaznici.update', $polaznik) }}" method="POST">
                        @csrf
                    </form>
                @endif

                @if($mozeVidjetiPunePodatke ?? false)
                    <div class="row">
                        <div class="col-lg-6 mb-2">
                            <label for="Prezime">Prezime:</label>
                            <input type="text" class="form-control" name="Prezime" id="Prezime"
                                   value="{{ $polaznik->Prezime }}" @if($mozeUredjivati) form="uredjivanje_polaznika" @else disabled @endif>
                        </div>
                        <div class="col-lg-6 mb-2">
                            <label for="Ime">Ime:</label>
                            <input type="text" class="form-control" name="Ime" id="Ime"
                                   value="{{ $polaznik->Ime }}" @if($mozeUredjivati) form="uredjivanje_polaznika" @else disabled @endif>
                        </div>
                        <div class="col-lg-3 mb-2">
                            <label for="datum_rodjenja">Datum rođenja:</label>
                            <input type="date" class="form-control" name="datum_rodjenja" id="datum_rodjenja"
                                   value="{{ empty($polaznik->datum_rodjenja) ? '' : optional($polaznik->datum_rodjenja)->format('Y-m-d') }}"
                                   @if($mozeUredjivati) form="uredjivanje_polaznika" @else disabled @endif>
                        </div>
                        <div class="col-lg-3 mb-2">
                            <label for="oib">OIB:</label>
                            <input type="text" class="form-control" name="oib" id="oib" maxlength="11"
                                   value="{{ $polaznik->oib }}" @if($mozeUredjivati) form="uredjivanje_polaznika" @else disabled @endif>
                        </div>
                        <div class="col-lg-3 mb-2">
                            <label for="br_telefona">Br. telefona:</label>
                            <input type="text" class="form-control" name="br_telefona" id="br_telefona"
                                   value="{{ $polaznik->br_telefona }}" @if($mozeUredjivati) form="uredjivanje_polaznika" @else disabled @endif>
                        </div>
                        <div class="col-lg-3 mb-2">
                            <label for="email">E-mail:</label>
                            <input type="email" class="form-control" name="email" id="email"
                                   value="{{ $polaznik->email }}" @if($mozeUredjivati) form="uredjivanje_polaznika" @else disabled @endif>
                        </div>
                        <div class="col-lg-3 mb-2">
                            <label for="datum_upisa">Datum upisa:</label>
                            <input type="date" class="form-control" name="datum_upisa" id="datum_upisa"
                                   value="{{ empty($polaznik->datum_upisa) ? '' : optional($polaznik->datum_upisa)->format('Y-m-d') }}"
                                   @if($mozeUredjivati) form="uredjivanje_polaznika" @else disabled @endif>
                        </div>
                        <div class="col-lg-3 mb-2">
                            <label for="spol">Spol:</label>
                            <select class="form-select" id="spol" name="spol" @if($mozeUredjivati) form="uredjivanje_polaznika" @else disabled @endif>
                                <option value="" @if(empty($polaznik->spol)) selected @endif></option>
                                <option value="M" @if($polaznik->spol === 'M') selected @endif>Muško</option>
                                <option value="Ž" @if($polaznik->spol === 'Ž') selected @endif>Žensko</option>
                            </select>
                        </div>
                        <div class="col-lg-3 mb-2 align-self-end">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="u_skoli" name="u_skoli" value="1"
                                       @if($polaznik->u_skoli) checked @endif
                                       @if($mozeUredjivati) form="uredjivanje_polaznika" @else disabled @endif>
                                <label class="form-check-label" for="u_skoli">Aktivan polaznik škole</label>
                            </div>
                        </div>
                        <div class="col-lg-3 mb-2">
                            <label class="fw-bold">Povezani korisnik:</label>
                            <input type="text" class="form-control" value="{{ $polaznik->povezaniKorisnik->name ?? '-' }}" disabled>
                        </div>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle mb-0 border">
                            <tbody>
                            <tr>
                                <th style="width: 220px">Ime i prezime</th>
                                <td>{{ trim((string)$polaznik->Ime) }} {{ trim((string)$polaznik->Prezime) }}</td>
                            </tr>
                            <tr>
                                <th>Br. telefona</th>
                                <td>
                                    @if(!empty($polaznik->br_telefona))
                                        <a href="tel:{{ $polaznik->br_telefona }}">{{ $polaznik->br_telefona }}</a>
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>E-mail</th>
                                <td>
                                    @if(!empty($polaznik->email))
                                        <a href="mailto:{{ $polaznik->email }}">{{ $polaznik->email }}</a>
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>

    @if(($mozeVidjetiPunePodatke ?? false) && $schoolPaymentEnabled)
        <div class="container-xxl shadow mt-3">
            <div class="row justify-content-center p-2 shadow bg-danger fw-bolder">
                <div class="col-lg-12 text-white">
                    Školarina polaznika
                    <span id="skrivanje_skola_placanja" class="text-white" style="float: right; cursor: pointer; @if($otvoriPlacanja) display: block; @else display: none; @endif"
                          onclick="document.getElementById('skola_placanja_dropdown').style.display = 'none';document.getElementById('skrivanje_skola_placanja').style.display = 'none';document.getElementById('pokazivanje_skola_placanja').style.display = 'block';">_</span>
                    <span id="pokazivanje_skola_placanja" class="text-white" style="float: right; cursor: pointer; @if($otvoriPlacanja) display: none; @endif"
                          onclick="document.getElementById('skola_placanja_dropdown').style.display = 'block';document.getElementById('skrivanje_skola_placanja').style.display = 'block';document.getElementById('pokazivanje_skola_placanja').style.display = 'none';">+</span>
                </div>
            </div>
        </div>
        <div id="skola_placanja_dropdown" class="container-xxl bg-secondary-subtle shadow" style="@if($otvoriPlacanja) display: block; @else display: none; @endif">
            <div class="row p-3">
                <div class="col-12">
                    @if(!empty($schoolPaymentNotice))
                        <div class="alert alert-{{ $schoolPaymentNotice['variant'] ?? 'secondary' }} mb-3">
                            <div class="fw-bold">{{ $schoolPaymentNotice['title'] ?? 'Status školarine' }}</div>
                            <div>{{ $schoolPaymentNotice['message'] ?? '' }}</div>
                        </div>
                    @endif

                    @if($mozeUredjivati)
                        <div class="card mb-3">
                            <div class="card-header bg-warning text-dark fw-bold">Model školarine polaznika</div>
                            <div class="card-body">
                                <form action="{{ route('admin.skola.polaznici.placanja.profil', $polaznik) }}" method="POST">
                                    @csrf
                                    <div class="row g-2 align-items-end">
                                        <div class="col-lg-4">
                                            <label for="payment_mode" class="form-label">Model plaćanja</label>
                                            @php
                                                $selectedMode = old('payment_mode', $schoolPaymentProfile->payment_mode ?? \App\Services\SchoolPaymentService::MODE_FULL);
                                            @endphp
                                            <select class="form-select" id="payment_mode" name="payment_mode">
                                                <option value="{{ \App\Services\SchoolPaymentService::MODE_FULL }}" @selected($selectedMode === \App\Services\SchoolPaymentService::MODE_FULL)>
                                                    U cijelosti
                                                </option>
                                                <option value="{{ \App\Services\SchoolPaymentService::MODE_INSTALLMENTS }}" @selected($selectedMode === \App\Services\SchoolPaymentService::MODE_INSTALLMENTS)>
                                                    U dvije rate
                                                </option>
                                                <option value="{{ \App\Services\SchoolPaymentService::MODE_EXEMPT }}" @selected($selectedMode === \App\Services\SchoolPaymentService::MODE_EXEMPT)>
                                                    Oslobođen školarine
                                                </option>
                                            </select>
                                        </div>
                                        <div class="col-lg-3">
                                            <label class="form-label">Broj evidentiranih dolazaka</label>
                                            <input type="text" class="form-control" value="{{ $schoolAttendanceCount }} / 16" disabled>
                                        </div>
                                        <div class="col-lg-3">
                                            <label class="form-label">Ukupan iznos školarine</label>
                                            <input type="text" class="form-control"
                                                   value="{{ number_format((float)($schoolPaymentProfile->tuition_amount ?? 0), 2, ',', '.') }} EUR"
                                                   disabled>
                                        </div>
                                        <div class="col-lg-2 text-end">
                                            <button type="submit" class="btn btn-primary text-nowrap px-3">Spremi</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    @endif

                    <div class="card">
                        <div class="card-header bg-light fw-bold">Popis stavki školarine</div>
                        <div class="card-body">
                            @if($schoolPaymentCharges->count() === 0)
                                <p class="mb-0">Nema stavki školarine za ovog polaznika.</p>
                            @else
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover align-middle mb-0">
                                        <thead class="table-warning">
                                        <tr>
                                            <th>Naziv</th>
                                            <th>Iznos</th>
                                            <th>Rok / uvjet</th>
                                            <th>Status</th>
                                            <th>Datum uplate</th>
                                            @if($mozeUredjivati)
                                                <th class="text-end">Akcija</th>
                                            @endif
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @foreach($schoolPaymentCharges as $charge)
                                            @php
                                                $statusFormId = 'skolarina_status_' . $charge->id;
                                                $isPaid = $charge->status === \App\Services\SchoolPaymentService::STATUS_PAID;
                                                $dueTrainingCount = (int)($charge->due_training_count ?? 0);
                                                $hasTrainingCondition = $dueTrainingCount > 0;
                                                $conditionReached = $hasTrainingCondition && $schoolAttendanceCount >= $dueTrainingCount;
                                                $settlementOptions = $schoolPaymentService->settlementOptionsForCharge($charge);
                                            @endphp

                                            @if($mozeUredjivati)
                                                <form id="{{ $statusFormId }}" action="{{ route('admin.skola.polaznici.placanja.status', [$polaznik, $charge]) }}" method="POST">
                                                    @csrf
                                                    <input type="hidden" name="is_paid" value="{{ $isPaid ? 0 : 1 }}">
                                                </form>
                                            @endif

                                            <tr>
                                                <td>{{ $charge->title }}</td>
                                                <td>{{ number_format((float)$charge->amount, 2, ',', '.') }} EUR</td>
                                                <td>
                                                    @if($hasTrainingCondition)
                                                        Nakon {{ $dueTrainingCount }} treninga
                                                        <span class="@if($conditionReached) text-danger fw-semibold @else text-success fw-semibold @endif">
                                                            (trenutno {{ $schoolAttendanceCount }}/{{ $dueTrainingCount }})
                                                        </span>
                                                    @else
                                                        Odmah
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($isPaid)
                                                        <span class="badge bg-success">Plaćeno</span>
                                                    @elseif($hasTrainingCondition && !$conditionReached)
                                                        <span class="badge bg-success">Čeka {{ $dueTrainingCount }}. trening</span>
                                                    @else
                                                        <span class="badge bg-danger">Nije plaćeno</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($charge->paid_at)
                                                        {{ optional($charge->paid_at)->format('d.m.Y.') }}
                                                    @elseif($mozeUredjivati)
                                                        <input type="date" class="form-control form-control-sm"
                                                               name="paid_at"
                                                               form="{{ $statusFormId }}"
                                                               value="{{ now()->toDateString() }}">
                                                    @else
                                                        -
                                                    @endif
                                                </td>
                                                @if($mozeUredjivati)
                                                    <td class="text-end">
                                                        <div class="d-inline-flex flex-nowrap justify-content-end align-items-center gap-2">
                                                            @if(!$isPaid && count($settlementOptions) > 0)
                                                                <select class="form-select form-select-sm"
                                                                        name="settlement_type"
                                                                        form="{{ $statusFormId }}">
                                                                    @foreach($settlementOptions as $settlementOption)
                                                                        <option value="{{ $settlementOption['value'] ?? '' }}"
                                                                                @selected(($settlementOption['value'] ?? '') === \App\Services\SchoolPaymentService::SETTLEMENT_FULL)>
                                                                            {{ $settlementOption['label'] ?? ($settlementOption['value'] ?? '') }}
                                                                        </option>
                                                                    @endforeach
                                                                </select>
                                                            @endif
                                                            @if($isPaid)
                                                                <button type="submit" form="{{ $statusFormId }}" class="btn btn-outline-secondary btn-sm text-nowrap">
                                                                    Vrati na neplaćeno
                                                                </button>
                                                            @else
                                                                <button type="submit" form="{{ $statusFormId }}" class="btn btn-outline-success btn-sm text-nowrap">
                                                                    Potvrdi uplatu
                                                                </button>
                                                            @endif
                                                        </div>
                                                    </td>
                                                @endif
                                            </tr>
                                        @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                <div class="small text-muted mt-2">
                                    Otvorene stavke: <strong>{{ $schoolPaymentOpenCharges->count() }}</strong>,
                                    podmirene stavke: <strong>{{ $schoolPaymentPaidCharges->count() }}</strong>.
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if($mozeVidjetiEvidencijuDolasaka ?? false)
        <div class="container-xxl bg-white shadow mt-3">
            <div class="row justify-content-center p-2 shadow bg-danger fw-bolder">
                <div class="col-lg-12 text-white">Evidencija dolazaka - škola (1-16)</div>
            </div>
            <div class="row p-3 bg-secondary-subtle">
                <div class="col-12">
                    @if(auth()->check() && (int)auth()->user()->rola === 1)
                        <div class="text-end mb-2">
                            <button type="button" class="btn btn-sm btn-outline-danger"
                                    onclick="location.href='{{ route('javno.skola.evidencija.index') }}'">
                                Otvori evidenciju svih polaznika
                            </button>
                        </div>
                    @endif

                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle mb-0 border">
                            <thead>
                            <tr>
                                <th>Dolazak</th>
                                <th>Datum</th>
                            </tr>
                            </thead>
                            <tbody>
                            @for($i = 1; $i <= 16; $i++)
                                <tr>
                                    <td>{{ $i }}</td>
                                    <td>{{ empty($dolasci[$i]) ? '-' : optional($dolasci[$i])->format('d.m.Y.') }}</td>
                                </tr>
                            @endfor
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if($mozeVidjetiDokumente ?? false)
        <div class="container-xxl shadow mt-3">
            <div class="row justify-content-center p-2 shadow bg-danger fw-bolder">
                <div class="col-lg-12 text-white">
                    Dokumenti polaznika škole
                    <span id="skrivanje_skola_dokumenata" class="text-white" style="float: right; cursor: pointer; @if($otvoriDokumente) display: block; @else display: none; @endif"
                          onclick="document.getElementById('skola_dokumenti_dropdown').style.display = 'none';document.getElementById('skrivanje_skola_dokumenata').style.display = 'none';document.getElementById('pokazivanje_skola_dokumenata').style.display = 'block';">_</span>
                    <span id="pokazivanje_skola_dokumenata" class="text-white" style="float: right; cursor: pointer; @if($otvoriDokumente) display: none; @endif"
                          onclick="document.getElementById('skola_dokumenti_dropdown').style.display = 'block';document.getElementById('skrivanje_skola_dokumenata').style.display = 'block';document.getElementById('pokazivanje_skola_dokumenata').style.display = 'none';">+</span>
                </div>
            </div>
        </div>
        <div id="skola_dokumenti_dropdown" class="container-xxl bg-secondary-subtle shadow" style="@if($otvoriDokumente) display: block; @else display: none; @endif">
            <div class="row p-3">
                <div class="col-12">
                    @if($mozeUredjivatiDokumente ?? false)
                        <div class="card mb-3">
                            <div class="card-header bg-warning text-dark fw-bold">Novi dokument polaznika</div>
                            <div class="card-body">
                                <form action="{{ route('admin.skola.polaznici.spremi_dokument', $polaznik) }}" enctype="multipart/form-data" method="POST">
                                    @csrf
                                    <div class="row align-items-end">
                                        <div class="col-lg-2 mb-2">
                                            <label for="vrsta_novi_dokument">Vrsta:</label>
                                            <select class="form-select" id="vrsta_novi_dokument" name="vrsta" required>
                                                <option value="" disabled selected>Odaberi</option>
                                                <option value="Upisnica">Upisnica</option>
                                                <option value="GDPR">GDPR</option>
                                                <option value="Slika">Slika</option>
                                                <option value="Ostalo">Ostalo</option>
                                            </select>
                                        </div>
                                        <div class="col-lg-2 mb-2">
                                            <label for="naziv_novi_dokument">Naziv:</label>
                                            <input type="text" class="form-control" id="naziv_novi_dokument" name="naziv" readonly>
                                        </div>
                                        <div class="col-lg-2 mb-2">
                                            <label for="datum_novi_dokument">Datum dokumenta:</label>
                                            <input type="date" class="form-control" id="datum_novi_dokument" name="datum_dokumenta">
                                        </div>
                                        <div class="col-lg-3 mb-2">
                                            <label for="napomena_novi_dokument">Napomena:</label>
                                            <input type="text" class="form-control" id="napomena_novi_dokument" name="napomena">
                                        </div>
                                        <div class="col-lg-2 mb-2">
                                            <label for="datoteka_novi_dokument">Datoteka:</label>
                                            <input type="file" class="form-control" id="datoteka_novi_dokument" name="dokument" required>
                                        </div>
                                        <div class="col-lg-1 mb-2 text-end">
                                            <button type="submit" class="btn btn-primary">Spremi</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    @endif

                    <div class="card mb-2">
                        <div class="card-header bg-light fw-bold">Popis dokumenata polaznika</div>
                        <div class="card-body">
                            @if($polaznik->dokumenti->count() === 0)
                                <p class="mb-0">Nema spremljenih dokumenata.</p>
                            @else
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover align-middle mb-0">
                                        <thead class="table-warning">
                                        <tr>
                                            <th>Naziv</th>
                                            <th>Datum</th>
                                            <th>Napomena</th>
                                            <th>Datoteka</th>
                                            @if($mozeUredjivatiDokumente ?? false)
                                                <th class="text-end">Akcije</th>
                                            @endif
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @foreach($polaznik->dokumenti as $dokument)
                                            @if($mozeUredjivatiDokumente ?? false)
                                                <form id="obrisi_dokument_polaznika_{{ $dokument->id }}" action="{{ route('admin.skola.polaznici.obrisi_dokument', [$polaznik, $dokument]) }}" method="POST">
                                                    @csrf
                                                </form>
                                            @endif
                                            <tr>
                                                <td>{{ $dokument->naziv }}</td>
                                                <td>{{ optional($dokument->datum_dokumenta)->format('d.m.Y.') }}</td>
                                                <td>{{ $dokument->napomena ?: '-' }}</td>
                                                <td>
                                                    @if(!empty($dokument->putanja))
                                                        <a class="link-success" href="{{ route('javno.skola.polaznici.preuzmi_dokument', [$polaznik, $dokument]) }}" target="_blank">Pregled</a>
                                                    @else
                                                        -
                                                    @endif
                                                </td>
                                                @if($mozeUredjivatiDokumente ?? false)
                                                    <td class="text-end">
                                                        <button type="submit" form="obrisi_dokument_polaznika_{{ $dokument->id }}" class="btn text-danger btn-rounded" title="Obriši"
                                                                onclick="return confirm('Da li ste sigurni da želite obrisati dokument ?')">
                                                            @include('admin.SVG.obrisi')
                                                        </button>
                                                    </td>
                                                @endif
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
        </div>
    @endif

    <div class="container-xxl bg-white shadow mt-3">
        <div class="row p-3">
            <div class="col-12">
                <div class="d-grid gap-2 d-md-flex justify-content-between align-items-center">
                    <div class="d-grid gap-2 d-md-flex">
                        @if($mozeUredjivati)
                            <form action="{{ route('admin.skola.polaznici.destroy', $polaznik) }}" method="POST" class="d-inline-block">
                                @csrf
                                <button type="submit" class="btn btn-outline-danger"
                                        onclick="return confirm('Da li ste sigurni da želite obrisati polaznika škole?')">
                                    Obriši polaznika
                                </button>
                            </form>
                        @endif
                    </div>

                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        @if($mozeUredjivati)
                            <button class="btn btn-primary me-md-2" type="submit" form="uredjivanje_polaznika">Spremi</button>
                            @if($polaznik->u_skoli)
                                <form action="{{ route('admin.skola.polaznici.prebaci_u_clana', $polaznik) }}" method="POST" class="d-inline-block">
                                    @csrf
                                    <button type="submit" class="btn btn-warning"
                                            onclick="return confirm('Polaznik će biti prebačen u članove kluba. Nastaviti?')">
                                        Prebaci u članove
                                    </button>
                                </form>
                            @elseif(!empty($polaznik->prebacen_u_clana_id))
                                <button class="btn btn-outline-success" type="button"
                                        onclick="location.href='{{ route('javno.clanovi.prikaz_clana', $polaznik->prebacenClan) }}'">
                                    Profil člana
                                </button>
                            @endif
                        @endif
                        <button class="btn btn-outline-secondary" type="button" onclick="location.href='{{ route('javno.skola.polaznici.index') }}'">Popis polaznika</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if($mozeUredjivatiDokumente ?? false)
        <script>
            (function () {
                const vrsta = document.getElementById('vrsta_novi_dokument');
                const naziv = document.getElementById('naziv_novi_dokument');
                if (!vrsta || !naziv) {
                    return;
                }

                const syncNaziv = function () {
                    if (!vrsta.value) {
                        naziv.value = '';
                        naziv.readOnly = true;
                        naziv.required = false;
                        return;
                    }

                    if (vrsta.value === 'Ostalo') {
                        naziv.readOnly = false;
                        naziv.required = true;
                        naziv.value = '';
                        return;
                    }

                    naziv.value = vrsta.value;
                    naziv.readOnly = true;
                    naziv.required = false;
                };

                vrsta.addEventListener('change', syncNaziv);
                syncNaziv();
            })();
        </script>
    @endif
@endsection
