@extends('layouts.app')
@section('content')
    <div class="container-xxl bg-white shadow">
        <div class="row pt-3 mb-3 shadow @if($jeRodendanDanas ?? false) clan-birthday-hero @endif">
            @if($jeRodendanDanas ?? false)
                <div class="clan-birthday-balloons" aria-hidden="true">
                    <span class="clan-birthday-balloon clan-birthday-balloon-1"></span>
                    <span class="clan-birthday-balloon clan-birthday-balloon-2"></span>
                    <span class="clan-birthday-balloon clan-birthday-balloon-3"></span>
                    <span class="clan-birthday-balloon clan-birthday-balloon-4"></span>
                    <span class="clan-birthday-balloon clan-birthday-balloon-5"></span>
                </div>
            @endif
            {{-- slika člana --}}
            <div class="col-lg-2 col-md-2 col-sm-4 mb-2">
                @if((empty($clan->slika_link)))
                    <img
                        src="@if( $clan->spol == "M") {{ asset('storage/slike/avatar_m.png') }} @else {{ asset('storage/slike/avatar_f.png') }} @endif"
                        class="img-thumbnail" alt="">
                @else
                    <img src="{{ asset('storage/slike_clanova/' . $clan->slika_link) }}" class="img-thumbnail" alt="">
                @endif
            </div>

            {{-- Ime prezim i osobni rekordi --}}
            <div class="col-lg-6 mb-2">
                <p class="h3 fw-bold">{{ $clan->Ime }} {{ $clan->Prezime }}</p>
                @if(count($osobniRekordi) != 0)
                    <p class="fw-bold">Osobni rekordi:</p>
                    <div class="table-responsive-sm">
                        <table class="table table-sm table-hover align-middle table-borderless birthday-records-table">
                            @foreach($osobniRekordi as $rekord)
                                <tr>
                                    <td>
                                        {{ $rekord['tipTurnira'] }}
                                    </td>
                                    <td>
                                        {{ $rekord['stil'] }}
                                    </td>
                                    <td>
                                        {{ $rekord['kategorija'] }}
                                    </td>
                                    <td class="fw-bold">
                                        {{ $rekord['rezultat'] }}
                                    </td>
                                    <td>
                                        <a class="link" style="text-decoration: none"
                                           href="{{ route('javno.rezultati.prikaz_turnira', $rekord['turnir']) }}">{{ date('d.m.Y.',strtotime($rekord['turnir']->datum)) }}</a>
                                    </td>
                                </tr>
                            @endforeach
                        </table>
                    </div>
                @endif
            </div>

            {{-- sudjelovanje na turnirima --}}
            @if($turniri['ukupno'] != 0)
                <div class="col-md-2 md-2">
                    <p class="fw-bold">Sudjelovanje na turnirima: {{$turniri['ukupno']}}</p>
                    <ul>
                        <li><span class="fw-bold">{{$turniri['prva']}}</span> @include('admin.SVG.gold')</li>
                        <li><span class="fw-bold">{{$turniri['druga']}}</span> @include('admin.SVG.silver')</li>
                        <li><span class="fw-bold">{{$turniri['treca']}}</span> @include('admin.SVG.bronze')</li>
                    </ul>
                    <p class="fw-bold">Ukupno medalja: {{$turniri['medalje']}}</p>
                </div>
            @endif

            {{-- Članstvo od i trajanje liječničkog --}}
            <div class="col-md-2 mb-2">
                <p>
                    <span>Datum početka članstva:</span>
                    <span class="fw-bold">
                        @if(!empty($clan->datum_pocetka_clanstva))
                            {{ optional($clan->datum_pocetka_clanstva)->format('d.m.Y.') }}
                        @elseif(!empty($clan->clan_od))
                            {{ $clan->clan_od }}
                        @else
                            -
                        @endif
                    </span><br>
                @isset($clan->broj_licence)
                    @if($clan->broj_licence != "nema licencu")
                        <span>Br. licence:</span><span class="fw-bold"> {{ $clan->broj_licence }}</span>
                    @endif
                @endisset
                </p>
                @isset($clan->lijecnicki_do)
                    <p class="fw-normal mb-1">
                        @php
                            $from=date_create(date('Y-m-d'));
                            $to=date_create($clan->lijecnicki_do);
                            $diff=date_diff($from, $to);
                            if ($diff->format('%R') == "-") {
                                @endphp
                                <span class="fw-bolder text-danger">Liječnički istekao:</span><br><span
                                      class="fw-bold text-danger">{{ date('d.m.Y.', strtotime($clan->lijecnicki_do)) }}</span><br>
                                @php                                
                            }
                            else {
                                @endphp
                                    <span class="fw-bolder text-danger">Liječnički traje do:</span><br><span
                                          class="fw-bold">{{ date('d.m.Y.', strtotime($clan->lijecnicki_do)) }}</span><br>
                                @php
                                if ($diff->format('%a') < 30) {
                                    echo '<p class="text-danger fw-bold">' . $diff->format('%a dana') . '</p>';
                                }
                                else {
                                    echo '<p class="fw-bold">' . $diff->format('%a dana')  . '</p>';
                                }
                            }
                        @endphp
                    </p>
                @endisset
            </div>

            @auth
                @php
                    $jeVlastitiProfil = (int)auth()->user()->clan_id === (int)$clan->id;
                    $jeAdmin = (int)auth()->user()->rola <= 1;
                    $jeRoditeljPregled = (bool)($jeRoditeljPregled ?? false);
                @endphp

                @if($jeAdmin || $jeVlastitiProfil || $jeRoditeljPregled)
                    <div class="col-12 mt-2 pb-2">
                        @if($jeVlastitiProfil)
                            <div class="d-flex flex-wrap align-items-center gap-2">
                                @if(!($themeModeForced ?? false))
                                    <form action="{{ route('user.theme_mode.update') }}" method="POST"
                                          class="d-inline-flex align-items-center gap-2 mb-0">
                                        @csrf
                                        <label for="profil_tema_mod" class="small fw-semibold mb-0">Prikaz teme:</label>
                                        <select id="profil_tema_mod" name="theme_mode_preference" class="form-select form-select-sm"
                                                onchange="this.form.submit()">
                                            @php
                                                $mod = auth()->user()->theme_mode_preference ?? 'auto';
                                            @endphp
                                            <option value="auto" @if($mod === 'auto') selected @endif>Automatski (uređaj)</option>
                                            <option value="light" @if($mod === 'light') selected @endif>Svijetla</option>
                                            <option value="dark" @if($mod === 'dark') selected @endif>Tamna</option>
                                        </select>
                                    </form>
                                @endif
                                @if($jeAdmin)
                                    @if($paymentProfileConfigured ?? false)
                                        <button type="button" class="btn btn-sm btn-outline-primary"
                                                onclick="location.href='{{ route('javno.clanovi.placanja', $clan) }}'">
                                            Moja plaćanja
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-primary"
                                                onclick="location.href='{{ route('admin.clanovi.prikaz_clana', ['clan' => $clan, 'open_payments' => 1]) }}'">
                                            Pregled plaćanja
                                        </button>
                                    @endif
                                    <button type="button" class="btn btn-sm btn-outline-danger"
                                            onclick="location.href='{{ route('admin.clanovi.prikaz_clana', $clan) }}'">
                                        Uredi podatke
                                    </button>
                                @else
                                    @if($paymentProfileConfigured ?? false)
                                        <button type="button" class="btn btn-sm btn-outline-primary"
                                                onclick="location.href='{{ route('javno.clanovi.placanja', $clan) }}'">
                                            Moja plaćanja
                                        </button>
                                    @endif
                                @endif
                                <button type="button" class="btn btn-sm btn-danger ms-auto"
                                        onclick="location.href='{{ route('javno.treninzi.index') }}'">
                                    Moji treninzi
                                </button>
                            </div>
                        @elseif($jeAdmin)
                            <div class="text-end d-flex justify-content-end gap-2">
                                @if($paymentProfileConfigured ?? false)
                                    <button type="button" class="btn btn-sm btn-outline-primary"
                                            onclick="location.href='{{ route('admin.clanovi.prikaz_clana', ['clan' => $clan, 'open_payments' => 1]) }}'">
                                        Pregled plaćanja
                                    </button>
                                @endif
                                <button type="button" class="btn btn-sm btn-danger"
                                        onclick="location.href='{{ route('admin.treninzi.index', $clan) }}'">
                                    Pregled treninga
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger"
                                        onclick="location.href='{{ route('admin.clanovi.prikaz_clana', $clan) }}'">
                                    Uredi podatke
                                </button>
                            </div>
                        @elseif($jeRoditeljPregled)
                            <div class="text-end d-flex justify-content-end gap-2">
                                @if($paymentProfileConfigured ?? false)
                                    <button type="button" class="btn btn-sm btn-outline-primary"
                                            onclick="location.href='{{ route('javno.clanovi.placanja', $clan) }}'">
                                        Pregled plaćanja
                                    </button>
                                @endif
                                <button type="button" class="btn btn-sm btn-danger"
                                        onclick="location.href='{{ route('javno.treninzi.clan.index', $clan) }}'">
                                    Pregled treninga
                                </button>
                            </div>
                        @endif
                    </div>
                @endif
            @endauth
        </div>
    </div>

    @if(($paymentProfileConfigured ?? false) && !empty($paymentNotice))
        <div class="container-xxl shadow mt-3 bg-white">
            <div class="row p-3">
                <div class="col-12">
                    <div class="alert alert-{{ $paymentNotice['variant'] ?? 'secondary' }} mb-0">
                        <div class="fw-bold">{{ $paymentNotice['title'] ?? 'Status plaćanja' }}</div>
                        <div>{{ $paymentNotice['message'] ?? '' }}</div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @auth
        @if($mozeVidjetiDokumenteClana ?? false)
            <div class="mb-3">
                <div class="container-xxl shadow mt-3">
                    <div class="row justify-content-center p-2 shadow bg-danger fw-bolder">
                        <div class="col-lg-12 text-white">
                            Pregled dokumenata
                            <span id="skrivanje_admin_pregled_clana" class="text-white" style="float: right; cursor: pointer; display: none"
                                  onclick="document.getElementById('admin_pregled_clana_dropdown').style.display = 'none';document.getElementById('skrivanje_admin_pregled_clana').style.display = 'none';document.getElementById('pokazivanje_admin_pregled_clana').style.display = 'block';">_</span>
                            <span id="pokazivanje_admin_pregled_clana" class="text-white" style="float: right; cursor: pointer;"
                                  onclick="document.getElementById('admin_pregled_clana_dropdown').style.display = 'block';document.getElementById('skrivanje_admin_pregled_clana').style.display = 'block';document.getElementById('pokazivanje_admin_pregled_clana').style.display = 'none';">+</span>
                        </div>
                    </div>
                </div>
                <div id="admin_pregled_clana_dropdown" class="container-xxl bg-white shadow" style="display: none">
                    <div class="row p-3">
                        @if($adminPregled ?? false)
                            <div class="col-lg-12 mb-3">
                                <a class="btn btn-outline-primary" href="{{ route('admin.clanovi.prikaz_clana', ['clan' => $clan, 'open_documents' => 1]) }}">Uredi dokumente i liječničke</a>
                            </div>
                        @endif
                        <div class="col-lg-12 mb-3">
                            <div class="card">
                                <div class="card-header bg-danger text-white fw-bold">Podaci člana</div>
                                <div class="card-body">
                                    <div class="row g-2">
                                        <div class="col-lg-6">
                                            <label class="form-label mb-1">Prezime</label>
                                            <input type="text" class="form-control form-control-sm" value="{{ $clan->Prezime }}" disabled>
                                        </div>
                                        <div class="col-lg-6">
                                            <label class="form-label mb-1">Ime</label>
                                            <input type="text" class="form-control form-control-sm" value="{{ $clan->Ime }}" disabled>
                                        </div>
                                        <div class="col-lg-3">
                                            <label class="form-label mb-1">Datum rođenja</label>
                                            <input type="text" class="form-control form-control-sm"
                                                   value="@if(!empty($clan->datum_rodjenja)){{ date('d.m.Y.', strtotime((string)$clan->datum_rodjenja)) }}@endif"
                                                   disabled>
                                        </div>
                                        <div class="col-lg-3">
                                            <label class="form-label mb-1">OIB</label>
                                            <input type="text" class="form-control form-control-sm" value="{{ $clan->oib }}" disabled>
                                        </div>
                                        <div class="col-lg-3">
                                            <label class="form-label mb-1">Telefon</label>
                                            <input type="text" class="form-control form-control-sm" value="{{ $clan->br_telefona }}" disabled>
                                        </div>
                                        <div class="col-lg-3">
                                            <label class="form-label mb-1">E-mail</label>
                                            <input type="text" class="form-control form-control-sm" value="{{ $clan->email }}" disabled>
                                        </div>
                                        <div class="col-lg-3">
                                            <label class="form-label mb-1">Datum početka članstva</label>
                                            <input type="text" class="form-control form-control-sm"
                                                   value="@if(!empty($clan->datum_pocetka_clanstva)){{ optional($clan->datum_pocetka_clanstva)->format('d.m.Y.') }}@elseif(!empty($clan->clan_od)){{ $clan->clan_od }}@endif"
                                                   disabled>
                                        </div>
                                        <div class="col-lg-3">
                                            <label class="form-label mb-1">Spol</label>
                                            <input type="text" class="form-control form-control-sm"
                                                   value="{{ $clan->spol === 'M' ? 'Muško' : ($clan->spol === 'Ž' ? 'Žensko' : '') }}"
                                                   disabled>
                                        </div>
                                        <div class="col-lg-3">
                                            <label class="form-label mb-1">Status člana</label>
                                            <input type="text" class="form-control form-control-sm"
                                                   value="{{ $clan->aktivan ? 'Aktivan' : 'Neaktivan' }}" disabled>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6 mb-3">
                            <p class="fw-bold mb-2">Liječnički pregledi</p>
                            @if($clan->lijecnickiPregledi->count() == 0)
                                <p class="mb-0">Nema unosa.</p>
                            @else
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover align-middle mb-0 border">
                                        <thead class="table-warning">
                                        <tr>
                                            <th>Vrijedi do</th>
                                            <th>Dokument</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @foreach($clan->lijecnickiPregledi as $pregled)
                                            <tr>
                                                <td>{{ optional($pregled->vrijedi_do)->format('d.m.Y.') }}</td>
                                                <td>
                                                    @if(!empty($pregled->putanja))
                                                        <a class="link-success" href="{{ route('javno.clanovi.preuzmi_lijecnicki', [$clan, $pregled]) }}" target="_blank">Pregled</a>
                                                    @else
                                                        -
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>
                        <div class="col-lg-6 mb-3">
                            <p class="fw-bold mb-2">Dokumenti člana</p>
                            @if($clan->dokumenti->count() == 0)
                                <p class="mb-0">Nema unosa.</p>
                            @else
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover align-middle mb-0 border">
                                        <thead class="table-warning">
                                        <tr>
                                            <th>Naziv</th>
                                            <th>Dokument</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @foreach($clan->dokumenti as $dokument)
                                            <tr>
                                                <td>{{ $dokument->naziv }}</td>
                                                <td>
                                                    @if(!empty($dokument->putanja))
                                                        <a class="link-success" href="{{ route('javno.clanovi.preuzmi_dokument', [$clan, $dokument]) }}" target="_blank">Pregled</a>
                                                    @else
                                                        -
                                                    @endif
                                                </td>
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
        @endif
    @endauth

    @auth
        @if(($mozeVidjetiSkolaDolaske ?? false) && isset($evidencijeSkole) && $evidencijeSkole->count() > 0)
            <div class="mb-3">
                <div class="container-xxl shadow mt-3">
                    <div class="row justify-content-center p-2 shadow bg-danger fw-bolder">
                        <div class="col-lg-12 text-white">
                            Evidencija dolazaka - škola
                            <span id="skrivanje_skola_dolazaka" class="text-white" style="float: right; cursor: pointer; display: none"
                                  onclick="document.getElementById('skola_dolasci_dropdown').style.display = 'none';document.getElementById('skrivanje_skola_dolazaka').style.display = 'none';document.getElementById('pokazivanje_skola_dolazaka').style.display = 'block';">_</span>
                            <span id="pokazivanje_skola_dolazaka" class="text-white" style="float: right; cursor: pointer;"
                                  onclick="document.getElementById('skola_dolasci_dropdown').style.display = 'block';document.getElementById('skrivanje_skola_dolazaka').style.display = 'block';document.getElementById('pokazivanje_skola_dolazaka').style.display = 'none';">+</span>
                        </div>
                    </div>
                </div>
                <div id="skola_dolasci_dropdown" class="container-xxl bg-white shadow" style="display: none">
                    <div class="row p-3">
                        <div class="col-lg-12">
                            @foreach($evidencijeSkole as $evidencija)
                                <div class="mb-3">
                                    <p class="fw-bold mb-2">
                                        Evidencija polaznika: {{ $evidencija->Ime }} {{ $evidencija->Prezime }}
                                        @if(!empty($evidencija->prebacen_at))
                                            (prebačen {{ optional($evidencija->prebacen_at)->format('d.m.Y.') }})
                                        @endif
                                    </p>
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
                                                @php
                                                    $dolazak = $evidencija->dolasci->firstWhere('redni_broj', $i);
                                                @endphp
                                                <tr>
                                                    <td>{{ $i }}</td>
                                                    <td>{{ empty($dolazak?->datum) ? '-' : optional($dolazak->datum)->format('d.m.Y.') }}</td>
                                                </tr>
                                            @endfor
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        @endif
    @endauth

    @php
        $timskeMedaljePoTipu = (isset($timskeMedalje) && $timskeMedalje->count() > 0)
            ? $timskeMedalje->groupBy(fn ($tim) => (int)($tim->turnir?->tipTurnira?->id ?? 0))
            : collect();
    @endphp

    @if(count($turniriPopis) != 0 || $timskeMedaljePoTipu->count() > 0)
        @foreach($tipoviTurnira as $tip)
            @php
                $imaPojedinacno = $turniriPopis->contains(function ($turnirClan) use ($tip, $clan) {
                    if ((int)$turnirClan->tipTurnira->id !== (int)$tip->id) {
                        return false;
                    }

                    return $turnirClan->rezultatiOpci->contains('clan_id', $clan->id);
                });
            @endphp

            @if($imaPojedinacno)
                <div class="container-xxl bg-white shadow">
                    <div class="row pt-3 pb-3 mb-3 shadow">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0 border">
                                <thead>
                                <tr>
                                    <th class="border-0" colspan="@php echo(6 + $tip->polja->count());  @endphp">
                                        {{$tip->naziv}}
                                    </th>
                                </tr>
                                <tr style="--bs-table-bg:var(--bs-success);">
                                    <th class="text-white">Datum</th>
                                    <th class="text-white">Turnir</th>
                                    <th class="text-white">Stil</th>
                                    <th class="text-white">Kategorija</th>
                                    @foreach($tip->polja as $polje)
                                        <th class="text-white">{{ $polje->naziv }}</th>
                                    @endforeach
                                    <th class="text-white">Plasman (eliminacije)</th>
                                    <th class="text-white"></th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($turniriPopis as $turnirClan)
                                    @if((int)$turnirClan->tipTurnira->id === (int)$tip->id)
                                        @foreach($turnirClan->rezultatiOpci as $i => $rezultat)
                                            @if((int)$rezultat->clan_id === (int)$clan->id)
                                                <tr
                                                    @if($turnirClan->eliminacije && $rezultat->plasman_nakon_eliminacija <=3)
                                                        class="fw-bold"
                                                    @endif
                                                    @if(!($turnirClan->eliminacije) && $rezultat->plasman <=3)
                                                        class="fw-bold"
                                                    @endif>
                                                    <td>
                                                        <p class="mb-1">
                                                            <a class="@if(in_array($turnirClan->datum, $datumiRekorda)) link-danger @else link-dark @endif"
                                                               style="text-decoration: none"
                                                               href="{{ route('javno.rezultati.prikaz_turnira', $turnirClan) }}">{{ date('d.m.Y.',strtotime($turnirClan->datum)) }}</a>
                                                        </p>
                                                    </td>
                                                    <td class="@if(in_array($turnirClan->datum, $datumiRekorda)) text-danger @endif">
                                                        <p class="mb-1">
                                                            <a class="@if(in_array($turnirClan->datum, $datumiRekorda)) link-danger @else link-dark @endif"
                                                               style="text-decoration: none"
                                                               href="{{ route('javno.rezultati.prikaz_turnira', $turnirClan) }}">{{ $turnirClan->naziv }}</a>
                                                        </p>
                                                    </td>
                                                    <td class="@if(in_array($turnirClan->datum, $datumiRekorda)) text-danger @endif">
                                                        <p class="mb-1"> {{ $rezultat->stil->naziv }} </p>
                                                    </td>
                                                    <td class="@if(in_array($turnirClan->datum, $datumiRekorda)) text-danger @endif">
                                                        <p class="mb-1"> {{ $rezultat->kategorija->naziv }} </p>
                                                    </td>

                                                    @foreach($turnirClan->rezultatiPoTipuTurnira as $polje)
                                                        @if ($rezultat->clan_id == $polje['clan_id']  && $rezultat->stil->id == $polje['stil_id'])
                                                            <td class="@if(in_array($turnirClan->datum, $datumiRekorda)) text-danger @endif">
                                                                <p class="mb-1"> {{ $polje['rezultat'] }}  </p>
                                                            </td>
                                                        @endif
                                                    @endforeach
                                                    <td class="@if(in_array($turnirClan->datum, $datumiRekorda)) text-danger @endif">
                                                        <p class="mb-1">{{ $rezultat->plasman }}
                                                            @if($turnirClan->eliminacije)
                                                                ({{ $rezultat->plasman_nakon_eliminacija }})
                                                            @endif
                                                        </p>
                                                    </td>
                                                    <td class="@if(in_array($turnirClan->datum, $datumiRekorda)) text-danger align-text-bottom @endif">
                                                        <p class="mb-1">
                                                            @if(!($turnirClan->eliminacije))
                                                                @switch($rezultat->plasman)
                                                                    @case(1)
                                                                        <span class="float-end"> @include('admin.SVG.gold') </span>
                                                                        @break
                                                                    @case(2)
                                                                        <span class="float-end"> @include('admin.SVG.silver') </span>
                                                                        @break
                                                                    @case(3)
                                                                        <span class="float-end"> @include('admin.SVG.bronze') </span>
                                                                        @break
                                                                @endswitch
                                                            @endif
                                                            @if($turnirClan->eliminacije)
                                                                @switch($rezultat->plasman_nakon_eliminacija)
                                                                    @case(1)
                                                                        <span class="float-end"> @include('admin.SVG.gold') </span>
                                                                        @break
                                                                    @case(2)
                                                                        <span class="float-end"> @include('admin.SVG.silver') </span>
                                                                        @break
                                                                    @case(3)
                                                                        <span class="float-end"> @include('admin.SVG.bronze') </span>
                                                                        @break
                                                                @endswitch
                                                        </p>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endif
                                        @endforeach
                                    @endif
                                </tbody>
                                @endforeach
                            </table>
                        </div>
                    </div>
                </div>
            @endif

            @php
                $timoviTipa = $timskeMedaljePoTipu->get((int)$tip->id, collect());
            @endphp
            @if($timoviTipa->count() > 0)
                @include('javno.partials.timskeMedaljeTablica', ['timoviTipa' => $timoviTipa, 'tipNaziv' => $tip->naziv])
            @endif
        @endforeach
    @endif

    @once
        <style>
            .clan-birthday-hero {
                position: relative;
                overflow: hidden;
                color: var(--bs-body-color, #212529);
                background:
                    radial-gradient(circle at 14% 20%, rgba(255, 209, 102, .26), rgba(255, 255, 255, 0) 44%),
                    radial-gradient(circle at 86% 22%, rgba(86, 204, 242, .22), rgba(255, 255, 255, 0) 42%),
                    var(--bs-body-bg, #ffffff);
            }

            .clan-birthday-hero a {
                color: var(--theme-link-color, var(--bs-primary));
            }

            .clan-birthday-hero a:hover {
                color: var(--theme-link-hover-color, var(--bs-primary));
            }

            .clan-birthday-balloons {
                position: absolute;
                inset: 0;
                pointer-events: none;
                z-index: 0;
                opacity: .72;
            }

            .clan-birthday-hero > [class*="col-"] {
                position: relative;
                z-index: 1;
            }

            .clan-birthday-balloon {
                position: absolute;
                bottom: 2rem;
                width: 1.7rem;
                height: 2.1rem;
                border-radius: 48% 52% 44% 56% / 56% 56% 44% 44%;
                animation: clan-birthday-balloon-float 5.5s ease-in-out infinite;
            }

            .clan-birthday-balloon::after {
                content: "";
                position: absolute;
                left: 50%;
                top: 100%;
                width: 1px;
                height: 1.05rem;
                background: rgba(0, 0, 0, .22);
                transform: translateX(-50%);
            }

            .clan-birthday-balloon-1 { left: 6%; background: #ff6b81; animation-delay: .0s; }
            .clan-birthday-balloon-2 { left: 22%; background: #feca57; animation-delay: .9s; }
            .clan-birthday-balloon-3 { left: 58%; background: #1dd1a1; animation-delay: 1.3s; }
            .clan-birthday-balloon-4 { left: 76%; background: #54a0ff; animation-delay: .5s; }
            .clan-birthday-balloon-5 { left: 90%; background: #ff9f43; animation-delay: 1.8s; }

            .clan-birthday-hero .birthday-records-table,
            .clan-birthday-hero .birthday-records-table > :not(caption) > * > * {
                --bs-table-bg: transparent;
                --bs-table-striped-bg: transparent;
                --bs-table-active-bg: transparent;
                --bs-table-hover-bg: rgba(255, 255, 255, .18);
                background-color: transparent !important;
            }

            .theme-dark .clan-birthday-hero {
                background:
                    radial-gradient(circle at 14% 20%, rgba(255, 209, 102, .14), rgba(0, 0, 0, 0) 44%),
                    radial-gradient(circle at 86% 22%, rgba(86, 204, 242, .12), rgba(0, 0, 0, 0) 42%),
                    var(--bs-dark-bg-subtle, #1f2329);
            }

            .theme-dark .clan-birthday-balloon::after {
                background: rgba(255, 255, 255, .36);
            }

            .theme-dark .clan-birthday-hero .birthday-records-table,
            .theme-dark .clan-birthday-hero .birthday-records-table > :not(caption) > * > * {
                --bs-table-hover-bg: rgba(255, 255, 255, .08);
            }

            @keyframes clan-birthday-balloon-float {
                0%, 100% { transform: translateY(0) rotate(-2deg); }
                50% { transform: translateY(-.95rem) rotate(3deg); }
            }
        </style>
    @endonce
@endsection
