{{-- Naslovnica aplikacije s personaliziranim blokovima (statusi, rezultati, članci, rođendani). --}}
@extends('layouts.app')
@section('content')
    @php
        $korisnikPrijavljen = auth()->check();
        $mozePisatiKlupskiZid = $korisnikPrijavljen && auth()->user()->imaPravoAdminMemberOrSchool();
        $mozeModeriratiKlupskiZid = $korisnikPrijavljen && (int)auth()->user()->rola === 1;
    @endphp
    <div class="container-xxl">
        <div class="row">
            <!-- Prikaz na desktop browseru -->
            <div class="d-none d-xxl-block col-lg-3">
                <!-- Kontakt podaci -->
                {{-- Lijevi stupac (desktop): fiksni kontakt podaci kluba + statični članci naslovnice. --}}
                <div class="row justify-content-center p-2 shadow bg-danger fw-bolder me-lg-1">
                    <div class="col-lg-12 text-white">
                        Kontakt
                    </div>
                </div>
                <div class="row justify-content-start mb-3 pt-3 shadow bg-white me-lg-1">
                    @if(is_null($klub))
                        <div class="col-lg-12 justify-content-start">
                            <p class="h3">Unesite podatke o klubu</p>
                        </div>
                    @else
                        <div class="col-lg-12 justify-content-start">
                            <p class="h3">{{$klub->naziv}}</p>
                            <p class="fw-normal mb-1">
                                {{$klub->adresa}}<br>OIB: 90882660766<br>
                                <a class="text-black" href="tel:{{ $klub->telefon }}"> {{ $klub->telefon }}</a>&nbsp;
                                <a aria-label="Chat on WhatsApp" href="https://wa.me/{{ $klub->telefon }}" target="_blank">@include('admin.SVG.whatsup')</a>
                                <br>
                                <a href="mailto:{{ $klub->email }}">{{ $klub->email }}</a><br>
                                <span class="link-primary" style="cursor: pointer;" data-bs-toggle="modal" data-bs-target="#QRCode">{{ $klub->racun }}</span>
                            </p>
                        </div>
                    @endif
                </div>

                @include('javno.partials.klupskiZid', [
                    'headerClass' => 'row justify-content-center p-2 shadow bg-danger fw-bolder me-lg-1',
                    'bodyClass' => 'row justify-content-start mb-3 pt-3 pb-2 shadow bg-white me-lg-1',
                    'mozePisatiKlupskiZid' => $mozePisatiKlupskiZid,
                    'mozeModeriratiKlupskiZid' => $mozeModeriratiKlupskiZid,
                ])

                <!-- Članci za naslovnicu -->
                @if($clanciNaslovnica->count() != 0)
                    {{-- "Škola streličarstva" ide prva jer je najčešće ključna informacija za nove korisnike. --}}
                    @foreach($clanciNaslovnica as $clanak)
                        @if($clanak->naslov == "Škola streličarstva")
                            <div class="row justify-content-center p-2 shadow bg-danger fw-bolder me-lg-1">
                                <div class="col-lg-12 text-white">
                                    {{ $clanak->naslov }}
                                </div>
                            </div>
                            <div class="row justify-content-start mb-3 pt-2 shadow bg-white me-lg-1">
                                <div class="col-lg-12 justify-content-start ck-content">
                                    {!! $clanak->sadrzaj !!}
                                </div>
                            </div>
                            @break
                        @endif
                    @endforeach
                    {{-- Ostali naslovni članci slijede nakon škole, redoslijedom iz baze. --}}
                    @foreach($clanciNaslovnica as $clanak)
                        @if($clanak->naslov != "Škola streličarstva")
                            <div class="row justify-content-center p-2 shadow bg-danger fw-bolder me-lg-1">
                                <div class="col-lg-12 text-white">
                                    {{ $clanak->naslov }}
                                </div>
                            </div>
                            <div class="row justify-content-start mb-3 pt-2 shadow bg-white me-lg-1">
                                <div class="col-lg-12 justify-content-start ck-content">
                                    {!! $clanak->sadrzaj !!}
                                </div>
                            </div>

                        @endif
                    @endforeach
                @endif
            </div>


            <!-- Prikaz na srednjem ekranu -->
            <div class="d-none d-lg-flex d-xxl-none col-lg-4 mb-3 ps-lg-0 pe-lg-1">
                <div class="d-flex flex-column w-100">
                    <div class="row justify-content-center p-2 shadow bg-danger fw-bolder mb-0 mx-0">
                        <div class="col-lg-12 text-white">
                            Kontakt
                        </div>
                    </div>
                    <div class="row justify-content-start pt-3 pb-2 shadow bg-white flex-grow-1 mb-0 mx-0">
                        @if(is_null($klub))
                            <div class="col-lg-12 justify-content-start">
                                <p class="h3">Unesite podatke o klubu</p>
                            </div>
                        @else
                            <div class="col-lg-12 justify-content-start">
                                <p class="h3">{{$klub->naziv}}</p>
                                <p class="fw-normal mb-1">
                                    {{$klub->adresa}}<br>OIB: 90882660766<br>
                                    <a class="text-black" href="tel:{{ $klub->telefon }}"> {{ $klub->telefon }}</a>&nbsp;
                                    <a aria-label="Chat on WhatsApp" href="https://wa.me/{{ $klub->telefon }}" target="_blank">@include('admin.SVG.whatsup')</a>
                                    <br>
                                    <a href="mailto:{{ $klub->email }}">{{ $klub->email }}</a><br>
                                    <span class="link-primary" style="cursor: pointer;" data-bs-toggle="modal" data-bs-target="#QRCode">{{ $klub->racun }}</span>
                                </p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Mobilni prikaz -->
            <div class="d-lg-none col-12">
                <div class="row justify-content-center p-2 shadow bg-danger fw-bolder">
                    <div class="col-lg-12 text-white">
                        Kontakt
                    </div>
                </div>
                <div class="row justify-content-start mb-3 pt-3 shadow bg-white">
                    @if(is_null($klub))
                        <div class="col-lg-12 justify-content-start">
                            <p class="h3">Unesite podatke o klubu</p>
                        </div>
                    @else
                        <div class="col-lg-12 justify-content-start">
                            <p class="h3">{{$klub->naziv}}</p>
                            <p class="fw-normal mb-1">
                                {{$klub->adresa}}<br>OIB: 90882660766<br>
                                <a class="text-black" href="tel:{{ $klub->telefon }}"> {{ $klub->telefon }}</a>&nbsp;
                                <a aria-label="Chat on WhatsApp" href="https://wa.me/{{ $klub->telefon }}" target="_blank">@include('admin.SVG.whatsup')</a>
                                <br>
                                <a href="mailto:{{ $klub->email }}">{{ $klub->email }}</a><br>
                                <span class="link-primary" style="cursor: pointer;" data-bs-toggle="modal" data-bs-target="#QRCode">{{ $klub->racun }}</span>
                            </p>
                        </div>
                    @endif
                </div>
            </div>
            @include('admin.klub.QRCodeModal')
            <!-- Članci za naslovnicu -->
            @if($clanciNaslovnica->count() != 0)
                @foreach($clanciNaslovnica as $clanak)
                    @if($clanak->naslov == "Škola streličarstva")
                        <div class="d-none d-lg-flex d-xxl-none col-lg-4 mb-3 px-lg-2">
                            <div class="d-flex flex-column w-100">
                                <div class="row justify-content-center p-2 shadow bg-danger fw-bolder mb-0 mx-0">
                                    <div class="col-lg-12 text-white">
                                        {{ $clanak->naslov }}
                                    </div>
                                </div>
                                <div class="row justify-content-start pt-2 shadow bg-white flex-grow-1 mb-0 mx-0">
                                    <div class="col-lg-12 justify-content-start ck-content">
                                        {!! $clanak->sadrzaj !!}
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="d-lg-none col-12">
                            <div class="row justify-content-center p-2 shadow bg-danger fw-bolder">
                                <div class="col-lg-12 text-white">
                                    {{ $clanak->naslov }}
                                </div>
                            </div>
                            <div class="row justify-content-start mb-3 pt-2 shadow bg-white">
                                <div class="col-lg-12 justify-content-start ck-content">
                                    {!! $clanak->sadrzaj !!}
                                </div>
                            </div>
                        </div>
                        @break
                    @endif
                @endforeach
            @endif

            <div class="d-none d-lg-flex d-xxl-none col-lg-4 mb-3 ps-lg-1 pe-lg-0">
                @include('javno.partials.klupskiZid', [
                    'headerClass' => 'row justify-content-center p-2 shadow bg-danger fw-bolder mb-0 mx-0',
                    'bodyClass' => 'row justify-content-start pt-3 pb-2 shadow bg-white flex-grow-1 mb-0 mx-0',
                    'bodyColumnClass' => 'col-lg-12 justify-content-start d-flex flex-column',
                    'widgetClass' => 'd-flex flex-column w-100',
                    'mozePisatiKlupskiZid' => $mozePisatiKlupskiZid,
                    'mozeModeriratiKlupskiZid' => $mozeModeriratiKlupskiZid,
                ])
            </div>


            <!-- Zadnjih 5 rezultata -->
            <div class="col-xxl-9 col-xl-12 col-lg-12 col-md-12 col-sm-12">
                {{-- Informativni status blokovi (rođendani, liječnički, škola, status djece) prikazuju se kontekstualno po ulozi korisnika. --}}
                @include('javno.naslovnaRodjendani', ['rodendaniDanas' => $rodendaniDanas])

                @if($korisnikPrijavljen)
                    <div class="d-none d-lg-block">
                        @include('javno.partials.naslovnaMojiPodaci', [
                            'statusLijecnickiKorisnika' => $statusLijecnickiKorisnika,
                            'statusSkolaKorisnika' => $statusSkolaKorisnika ?? null,
                        ])
                    </div>
                @endif

                @if(isset($statusLijecnickiDjeca))
                    @foreach($statusLijecnickiDjeca as $statusLijecnickiDijete)
                        @include('javno.naslovnaRoditeljLijecnickiStatus', ['statusLijecnickiDijete' => $statusLijecnickiDijete])
                    @endforeach
                @endif
                @if(isset($statusSkolaDjeca))
                    @foreach($statusSkolaDjeca as $statusSkolaDijete)
                        @include('javno.naslovnaRoditeljSkolaStatus', ['statusSkolaDijete' => $statusSkolaDijete])
                    @endforeach
                @endif
                <div class="d-lg-none">
                    @include('javno.partials.klupskiZid', [
                        'mozePisatiKlupskiZid' => $mozePisatiKlupskiZid,
                        'mozeModeriratiKlupskiZid' => $mozeModeriratiKlupskiZid,
                    ])
                </div>
                @if($korisnikPrijavljen)
                    <div class="d-lg-none">
                        @include('javno.partials.naslovnaMojiPodaci', [
                            'statusLijecnickiKorisnika' => $statusLijecnickiKorisnika,
                            'statusSkolaKorisnika' => $statusSkolaKorisnika ?? null,
                        ])
                    </div>
                @endif
                <div class="row justify-content-center p-2 shadow bg-danger fw-bolder">
                    <div class="col-lg-12 text-white">
                        Rezultati zadnjih 5 turnira i ostali članci
                    </div>
                </div>
                {{-- Kombinirani feed rezultata i članaka predstavlja glavni dinamički sadržaj naslovnice. --}}
                @include('javno.naslovnaRezultatiClanci', ['stavkeRezultataIClanaka' => $stavkeRezultataIClanaka])
            </div>

            <!-- Prikaz na desktop browseru -->
            <!-- Članci za naslovnicu -->
            @if($clanciNaslovnica->count() != 0)
                @foreach($clanciNaslovnica as $clanak)
                    @if($clanak->naslov != "Škola streličarstva")
                        <div class="d-xxl-none col-lg-12">
                            <div class="row justify-content-center p-2 shadow bg-danger fw-bolder">
                                <div class="col-lg-12 text-white">
                                    {{ $clanak->naslov }}
                                </div>
                            </div>
                            <div class="row justify-content-start mb-3 pt-2 shadow bg-white">
                                <div class="col-lg-12 justify-content-start ck-content">
                                    {!! $clanak->sadrzaj !!}
                                </div>
                            </div>
                        </div>
                    @endif
                @endforeach
            @endif

        </div>
    </div>
@endsection
