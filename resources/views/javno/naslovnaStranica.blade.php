@extends('layouts.app')
@section('content')
    <div class="container-xxl">
        <div class="row">
            <!-- Prikaz na desktop browseru -->
            <div class="d-none d-xxl-block col-lg-3">
                <!-- Kontakt podaci -->
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
                <!-- Članci za naslovnicu -->
                @if($clanciNaslovnica->count() != 0)
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


            <!-- Prikaz na smanjenom prozoru -->
            <div class="d-xxl-none col-lg-6 pe-lg-4">
                <!-- Kontakt podaci -->
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
                        <div class="d-xxl-none col-lg-6">
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


            <!-- Zadnjih 5 rezultata -->
            <div class="col-xxl-9 col-xl-12 col-lg-12 col-md-12 col-sm-12">
                @include('javno.naslovnaRodjendani', ['rodendaniDanas' => $rodendaniDanas])
                @include('javno.naslovnaLijecnickiStatus', ['statusLijecnickiKorisnika' => $statusLijecnickiKorisnika])
                @include('javno.naslovnaSkolaStatus', ['statusSkolaKorisnika' => $statusSkolaKorisnika ?? null])
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
                <div class="row justify-content-center p-2 shadow bg-danger fw-bolder">
                    <div class="col-lg-12 text-white">
                        Rezultati zadnjih 5 turnira i ostali članci
                    </div>
                </div>
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
