{{-- Profil člana: osobni podaci, rezultati po tipu turnira, timske medalje, treninzi i dokumenti. --}}
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
                            {{ $clan->datum_pocetka_clanstva?->format('d.m.Y.') }}
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
                            $from = date_create(date('Y-m-d'));
                            $to = date_create($clan->lijecnicki_do);
                            $diff = date_diff($from, $to);
                            $jeIstekao = $diff->format('%R') === '-';
                            $datumLijecnickog = date('d.m.Y.', strtotime((string)$clan->lijecnicki_do));
                            $preostaloDana = (int)$diff->format('%a');
                        @endphp
                        <span class="fw-bolder text-danger">{{ $jeIstekao ? 'Liječnički istekao:' : 'Liječnički traje do:' }}</span><br>
                        <span @class(['fw-bold', 'text-danger' => $jeIstekao])>{{ $datumLijecnickog }}</span><br>
                        @if(!$jeIstekao)
                            <span @class(['fw-bold', 'text-danger' => $preostaloDana < 30])>{{ $preostaloDana }} dana</span>
                        @endif
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
                            <span id="skrivanje_admin_pregled_clana" class="text-white float-end d-none" style="cursor: pointer;"
                                  data-toggle-panel
                                  data-panel-id="admin_pregled_clana_dropdown"
                                  data-show-id="pokazivanje_admin_pregled_clana"
                                  data-hide-id="skrivanje_admin_pregled_clana"
                                  data-expand="0">_</span>
                            <span id="pokazivanje_admin_pregled_clana" class="text-white float-end" style="cursor: pointer;"
                                  data-toggle-panel
                                  data-panel-id="admin_pregled_clana_dropdown"
                                  data-show-id="pokazivanje_admin_pregled_clana"
                                  data-hide-id="skrivanje_admin_pregled_clana"
                                  data-expand="1">+</span>
                        </div>
                    </div>
                </div>
                <div id="admin_pregled_clana_dropdown" class="container-xxl bg-white shadow d-none">
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
                                                   value="@if(!empty($clan->datum_pocetka_clanstva)){{ $clan->datum_pocetka_clanstva?->format('d.m.Y.') }}@elseif(!empty($clan->clan_od)){{ $clan->clan_od }}@endif"
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
                                                <td>{{ $pregled->vrijedi_do?->format('d.m.Y.') ?? '-' }}</td>
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
                            <span id="skrivanje_skola_dolazaka" class="text-white float-end d-none" style="cursor: pointer;"
                                  data-toggle-panel
                                  data-panel-id="skola_dolasci_dropdown"
                                  data-show-id="pokazivanje_skola_dolazaka"
                                  data-hide-id="skrivanje_skola_dolazaka"
                                  data-expand="0">_</span>
                            <span id="pokazivanje_skola_dolazaka" class="text-white float-end" style="cursor: pointer;"
                                  data-toggle-panel
                                  data-panel-id="skola_dolasci_dropdown"
                                  data-show-id="pokazivanje_skola_dolazaka"
                                  data-hide-id="skrivanje_skola_dolazaka"
                                  data-expand="1">+</span>
                        </div>
                    </div>
                </div>
                <div id="skola_dolasci_dropdown" class="container-xxl bg-white shadow d-none">
                    <div class="row p-3">
                        <div class="col-lg-12">
                            @foreach($evidencijeSkole as $evidencija)
                                <div class="mb-3">
                                    <p class="fw-bold mb-2">
                                        Evidencija polaznika: {{ $evidencija->Ime }} {{ $evidencija->Prezime }}
                                        @if(!empty($evidencija->prebacen_at))
                                            (prebačen {{ $evidencija->prebacen_at?->format('d.m.Y.') ?? '-' }})
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
                                                    <td>{{ $dolazak?->datum?->format('d.m.Y.') ?? '-' }}</td>
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
        use Illuminate\Support\Collection;

        $timskeMedaljePoTipu = (isset($timskeMedalje) && $timskeMedalje->count() > 0)
            ? $timskeMedalje->groupBy(fn ($tim) => (int)($tim->turnir?->tipTurnira?->id ?? 0))
            : collect();
    @endphp

    @if(count($turniriPopis) != 0 || $timskeMedaljePoTipu->count() > 0)
        @foreach($tipoviTurnira as $tip)
            @php
                $ukupnoPoljeTipa = $tip->polja->first(function ($poljeTipa) {
                    return mb_strtolower(trim((string)$poljeTipa->naziv)) === 'ukupno';
                });

                $grafGrupeTipa = [];
                if ($ukupnoPoljeTipa !== null) {
                    foreach ($turniriPopis as $turnirClan) {
                        if ((int)$turnirClan->tipTurnira->id !== (int)$tip->id) {
                            continue;
                        }

                        foreach ($turnirClan->rezultatiOpci as $rezultat) {
                            if ((int)$rezultat->clan_id !== (int)$clan->id) {
                                continue;
                            }

                            $ukupnoRezultat = $turnirClan->rezultatiPoTipuTurnira->first(function ($polje) use ($rezultat, $ukupnoPoljeTipa) {
                                return (int)$polje['clan_id'] === (int)$rezultat->clan_id
                                    && (int)$polje['stil_id'] === (int)$rezultat->stil->id
                                    && (int)$polje['polje_za_tipove_turnira_id'] === (int)$ukupnoPoljeTipa->id;
                            });

                            if ($ukupnoRezultat === null) {
                                continue;
                            }

                            $datumSort = !empty($turnirClan->datum) ? date('Y-m-d', strtotime((string)$turnirClan->datum)) : null;
                            if (empty($datumSort)) {
                                continue;
                            }

                            $grupaKey = (int)$rezultat->stil->id . '|' . (int)$rezultat->kategorija->id;
                            if (!array_key_exists($grupaKey, $grafGrupeTipa)) {
                                $grafGrupeTipa[$grupaKey] = [
                                    'stil' => (string)$rezultat->stil->naziv,
                                    'kategorija' => (string)$rezultat->kategorija->naziv,
                                    'zadnji_datum_sort' => $datumSort,
                                    'podaci' => [],
                                ];
                            }

                            if ($datumSort > ($grafGrupeTipa[$grupaKey]['zadnji_datum_sort'] ?? '')) {
                                $grafGrupeTipa[$grupaKey]['zadnji_datum_sort'] = $datumSort;
                            }

                            $grafGrupeTipa[$grupaKey]['podaci'][] = [
                                'datum_sort' => $datumSort,
                                'datum' => date('d.m.Y.', strtotime((string)$turnirClan->datum)),
                                'total' => (int)$ukupnoRezultat->rezultat,
                                'rezultat_opci_id' => (int)$rezultat->id,
                                'turnir_url' => route('javno.rezultati.prikaz_turnira', $turnirClan),
                                'turnir_naziv' => (string)$turnirClan->naziv,
                            ];
                        }
                    }
                }

                $grafGrupeTipa = collect($grafGrupeTipa)
                    ->map(function (array $grupa): array {
                        $podaci = collect($grupa['podaci'])
                            ->sortBy(function (array $stavka) {
                                return ($stavka['datum_sort'] ?? '') . '-' . str_pad((string)($stavka['rezultat_opci_id'] ?? 0), 10, '0', STR_PAD_LEFT);
                            })
                            ->values()
                            ->map(function (array $stavka): array {
                                return [
                                    'datum' => $stavka['datum'],
                                    'total' => $stavka['total'],
                                    'turnir_url' => $stavka['turnir_url'] ?? null,
                                    'turnir_naziv' => $stavka['turnir_naziv'] ?? null,
                                ];
                            })
                            ->values();

                        $grupa['podaci'] = $podaci;
                        $grupa['broj_tocaka'] = $podaci->count();

                        return $grupa;
                    })
                    ->filter(function (array $grupa): bool {
                        return (int)$grupa['broj_tocaka'] >= 2;
                    })
                    ->sortByDesc('zadnji_datum_sort')
                    ->values();

                $imaPojedinacno = $turniriPopis->contains(function ($turnirClan) use ($tip, $clan) {
                    $tipTurnira = $turnirClan->tipTurnira ?? null;
                    if ((int)($tipTurnira->id ?? 0) !== (int)$tip->id) {
                        return false;
                    }

                    $rezultatiOpci = $turnirClan->rezultatiOpci ?? null;
                    if (!($rezultatiOpci instanceof Collection)) {
                        return false;
                    }

                    return $rezultatiOpci->contains('clan_id', $clan->id);
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
                                <tr class="theme-thead-accent">
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

                        @if($grafGrupeTipa->count() > 0)
                            <div class="col-lg-12 mt-3">
                                @foreach($grafGrupeTipa as $grafGrupa)
                                    <div class="border rounded p-2 mb-3 bg-light-subtle">
                                        <p class="fw-bold mb-2">
                                            Graf napretka (Ukupno) - {{ $grafGrupa['stil'] }} / {{ $grafGrupa['kategorija'] }}
                                        </p>
                                        <div class="rezultat-chart-wrap">
                                            <svg class="js-clan-rezultat-chart"
                                                 data-points='@json($grafGrupa['podaci'])'
                                                 viewBox="0 0 1000 320"
                                                 preserveAspectRatio="none"
                                                 role="img"
                                                 aria-label="Graf napretka - {{ $tip->naziv }} - {{ $grafGrupa['stil'] }} - {{ $grafGrupa['kategorija'] }}"></svg>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
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
        <script>
            (function () {
                const togglePanelVisibility = (panelId, showId, hideId, expand) => {
                    const panel = document.getElementById(panelId);
                    const showHandle = document.getElementById(showId);
                    const hideHandle = document.getElementById(hideId);

                    if (panel) {
                        panel.classList.toggle('d-none', !expand);
                    }
                    if (showHandle) {
                        showHandle.classList.toggle('d-none', !!expand);
                    }
                    if (hideHandle) {
                        hideHandle.classList.toggle('d-none', !expand);
                    }
                };

                document.querySelectorAll('[data-toggle-panel]').forEach((handle) => {
                    handle.addEventListener('click', () => {
                        const panelId = handle.getAttribute('data-panel-id') || '';
                        const showId = handle.getAttribute('data-show-id') || '';
                        const hideId = handle.getAttribute('data-hide-id') || '';
                        const expand = handle.getAttribute('data-expand') === '1';

                        if (!panelId || !showId || !hideId) {
                            return;
                        }

                        togglePanelVisibility(panelId, showId, hideId, expand);
                    });
                });
            })();
        </script>
    @endonce

    @once
        <style>
            .rezultat-chart-wrap {
                width: 100%;
                height: 17rem;
                overflow-x: hidden;
                overflow-y: hidden;
            }

            .js-clan-rezultat-chart {
                width: 100%;
                height: 100%;
                display: block;
            }

            @media (max-width: 767px) {
                .rezultat-chart-wrap {
                    height: 14rem;
                }
            }

            @media (max-width: 479px) {
                .rezultat-chart-wrap {
                    height: 13rem;
                }
            }
        </style>
        <script>
            (function () {
                if (window['__clanRezultatChartsInit']) {
                    return;
                }
                window['__clanRezultatChartsInit'] = true;

                const createSvgElement = (name, attrs = {}) => {
                    const node = document.createElementNS('http://www.w3.org/2000/svg', name);
                    Object.entries(attrs).forEach(([key, value]) => node.setAttribute(key, String(value)));
                    return node;
                };

                const readPointValue = (point, key, fallback = null) => {
                    if (!point || typeof point !== 'object') {
                        return fallback;
                    }
                    return key in point ? point[key] : fallback;
                };

                const renderChart = (svg) => {
                    const rawPoints = svg.dataset.points || '[]';
                    let points;
                    try {
                        points = JSON.parse(rawPoints);
                    } catch (e) {
                        points = [];
                    }

                    while (svg.firstChild) {
                        svg.removeChild(svg.firstChild);
                    }

                    if (!Array.isArray(points) || points.length < 2) {
                        return;
                    }

                    const hostWidth = Math.max(260, Math.round(svg.parentElement?.clientWidth || 0));
                    const height = 320;
                    const compact = hostWidth < 576;
                    const paddingLeft = compact ? 40 : 56;
                    const paddingRight = 6;
                    const paddingTop = 16;
                    const paddingBottom = compact ? 34 : 38;
                    const width = hostWidth;
                    const plotWidth = width - paddingLeft - paddingRight;
                    const plotHeight = height - paddingTop - paddingBottom;

                    svg.setAttribute('viewBox', `0 0 ${width} ${height}`);
                    svg.setAttribute('preserveAspectRatio', 'none');
                    svg.style.width = '100%';
                    svg.style.height = '100%';

                    const rootStyles = getComputedStyle(document.documentElement);
                    const bodyStyles = getComputedStyle(document.body);
                    const fallbackSecondary = bodyStyles.getPropertyValue('--bs-secondary-color')?.trim() || '#6c757d';
                    const fallbackBody = bodyStyles.getPropertyValue('--bs-body-color')?.trim() || '#495057';
                    const primaryColor = rootStyles.getPropertyValue('--theme-primary')?.trim() || '#dc3545';
                    const gridColor = document.body.classList.contains('theme-dark') ? 'rgba(255,255,255,0.18)' : '#dee2e6';
                    const axisColor = document.body.classList.contains('theme-dark') ? 'rgba(255,255,255,0.45)' : '#adb5bd';

                    const totals = points.map((point) => Number(readPointValue(point, 'total', 0)) || 0);

                    let minTotal = 0;
                    let maxTotal = 1;
                    if (totals.length > 0) {
                        minTotal = Number(totals[0]) || 0;
                        maxTotal = Number(totals[0]) || 0;
                        totals.forEach((totalRaw) => {
                            const total = Number(totalRaw) || 0;
                            if (total < minTotal) {
                                minTotal = total;
                            }
                            if (total > maxTotal) {
                                maxTotal = total;
                            }
                        });
                    }

                    const minValue = Math.min(minTotal, 0);
                    const maxValue = Math.max(maxTotal, 1);
                    const range = Math.max(maxValue - minValue, 1);
                    const minIndex = totals.findIndex((value) => value === minTotal);
                    const maxIndex = totals.findIndex((value) => value === maxTotal);
                    const brojTocaka = points.length;
                    const labelStep = (() => {
                        if (brojTocaka <= 10) return 1;
                        if (brojTocaka <= 20) return 3;
                        if (brojTocaka <= 35) return 4;
                        if (brojTocaka <= 50) return 5;
                        return 6;
                    })();
                    const denseProximity = brojTocaka <= 10 ? 0 : (brojTocaka <= 20 ? 1 : 2);

                    const yToPx = (value) => paddingTop + plotHeight - ((value - minValue) / range) * plotHeight;

                    const gridCount = 5;
                    for (let i = 0; i <= gridCount; i++) {
                        const y = paddingTop + (plotHeight / gridCount) * i;
                        svg.appendChild(createSvgElement('line', {
                            x1: paddingLeft,
                            y1: y,
                            x2: width - paddingRight,
                            y2: y,
                            stroke: gridColor,
                            'stroke-width': 1
                        }));

                        const value = (maxValue - (range / gridCount) * i).toFixed(0);
                        const label = createSvgElement('text', {
                            x: paddingLeft - 8,
                            y: y + 4,
                            'text-anchor': 'end',
                            'font-size': 11,
                            fill: fallbackSecondary
                        });
                        label.textContent = value;
                        svg.appendChild(label);
                    }

                    svg.appendChild(createSvgElement('line', {
                        x1: paddingLeft,
                        y1: height - paddingBottom,
                        x2: width - paddingRight,
                        y2: height - paddingBottom,
                        stroke: axisColor,
                        'stroke-width': 1.5
                    }));

                    svg.appendChild(createSvgElement('line', {
                        x1: paddingLeft,
                        y1: paddingTop,
                        x2: paddingLeft,
                        y2: height - paddingBottom,
                        stroke: axisColor,
                        'stroke-width': 1.5
                    }));

                    const stepX = brojTocaka <= 1
                        ? 0
                        : Math.max(plotWidth, 1) / (brojTocaka - 1);
                    const startX = paddingLeft;
                    const resolveValueLabelY = (y, preferBelow = false) => {
                        const aboveY = y - 8;
                        const belowY = y + 14;
                        if (preferBelow && belowY <= (height - paddingBottom + 18)) {
                            return belowY;
                        }
                        return aboveY < (paddingTop + 10) ? belowY : aboveY;
                    };
                    const resolveValueLabelX = (x) => {
                        const rightLimit = width - paddingRight - 6;
                        const leftLimit = paddingLeft + 10;
                        if (x >= rightLimit) {
                            return { x: width - paddingRight - 2, anchor: 'end' };
                        }
                        if (x <= leftLimit) {
                            return { x: paddingLeft + 2, anchor: 'start' };
                        }
                        return { x, anchor: 'middle' };
                    };
                    const linePath = points.map((point, index) => {
                        const x = startX + stepX * index;
                        const y = yToPx(Number(readPointValue(point, 'total', 0)) || 0);
                        return `${index === 0 ? 'M' : 'L'}${x},${y}`;
                    }).join(' ');

                    svg.appendChild(createSvgElement('path', {
                        d: linePath,
                        fill: 'none',
                        stroke: primaryColor,
                        'stroke-width': 3,
                        'stroke-linecap': 'round',
                        'stroke-linejoin': 'round'
                    }));

                    points.forEach((point, index) => {
                        const x = startX + stepX * index;
                        const pointTotal = Number(readPointValue(point, 'total', 0)) || 0;
                        const y = yToPx(pointTotal);

                        const circle = createSvgElement('circle', {
                            cx: x,
                            cy: y,
                            r: 4,
                            fill: primaryColor
                        });

                        const turnirUrlRaw = readPointValue(point, 'turnir_url', null);
                        const turnirUrl = (typeof turnirUrlRaw === 'string' && turnirUrlRaw.length > 0)
                            ? turnirUrlRaw
                            : null;
                        const turnirNazivRaw = readPointValue(point, 'turnir_naziv', null);
                        const turnirNaziv = (typeof turnirNazivRaw === 'string' && turnirNazivRaw.length > 0)
                            ? turnirNazivRaw
                            : 'Turnir';
                        const datum = String(readPointValue(point, 'datum', '-'));

                        if (turnirUrl) {
                            const link = document.createElementNS('http://www.w3.org/2000/svg', 'a');
                            link.setAttribute('href', turnirUrl);
                            link.setAttributeNS('http://www.w3.org/1999/xlink', 'xlink:href', turnirUrl);
                            link.setAttribute('target', '_self');
                            link.style.cursor = 'pointer';

                            const naslov = createSvgElement('title');
                            naslov.textContent = `${turnirNaziv} (${datum}) - ${pointTotal}`;
                            link.appendChild(naslov);
                            link.appendChild(circle);
                            svg.appendChild(link);
                        } else {
                            svg.appendChild(circle);
                        }

                        const isMinOrMax = index === minIndex || index === maxIndex;
                        const shouldShowByStep = labelStep === 1
                            ? true
                            : (index % labelStep === 0);
                        const tooCloseToExtremes = !isMinOrMax && (
                            Math.abs(index - minIndex) <= denseProximity
                            || Math.abs(index - maxIndex) <= denseProximity
                        );

                        if (isMinOrMax || (shouldShowByStep && !tooCloseToExtremes)) {
                            const preferBelow = index === minIndex && minIndex !== maxIndex;
                            const valueLabelPos = resolveValueLabelX(x);
                            const valueLabel = createSvgElement('text', {
                                x: valueLabelPos.x,
                                y: resolveValueLabelY(y, preferBelow),
                                'text-anchor': valueLabelPos.anchor,
                                'font-size': brojTocaka > 35 ? 9 : 10,
                                'font-weight': isMinOrMax ? '700' : '600',
                                fill: fallbackBody
                            });
                            valueLabel.textContent = String(pointTotal);
                            svg.appendChild(valueLabel);
                        }
                    });
                };

                document.querySelectorAll('.js-clan-rezultat-chart').forEach(renderChart);
            })();
        </script>
    @endonce

    @once
        <style>
            .clan-birthday-hero {
                position: relative;
                overflow: hidden;
                color: #212529;
                background:
                    radial-gradient(circle at 14% 20%, rgba(255, 209, 102, .26), rgba(255, 255, 255, 0) 44%),
                    radial-gradient(circle at 86% 22%, rgba(86, 204, 242, .22), rgba(255, 255, 255, 0) 42%),
                    #ffffff;
            }

            .clan-birthday-hero a {
                color: #0d6efd;
            }

            .clan-birthday-hero a:hover {
                color: #0a58ca;
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
                color: #e9ecef;
                background:
                    radial-gradient(circle at 14% 20%, rgba(255, 209, 102, .14), rgba(0, 0, 0, 0) 44%),
                    radial-gradient(circle at 86% 22%, rgba(86, 204, 242, .12), rgba(0, 0, 0, 0) 42%),
                    #1f2329;
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
