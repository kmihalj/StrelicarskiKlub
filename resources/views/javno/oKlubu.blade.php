@extends('layouts.app')
@section('content')
    <div class="row">
        <div class="col-lg-12 col-md-12 col-sm-12">
            <div class="container-xxl bg-white shadow">
                <div class="row justify-content-center p-2 shadow bg-danger fw-bolder">
                    <div class="col-lg-12 text-white d-flex justify-content-between align-items-center">
                        <span>Podaci o klubu</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row mb-3">
        <div class="col-lg-12 col-md-12 col-sm-12">
            <div class="container-xxl bg-white shadow">
                <div class="row justify-content-start pt-3 shadow">
                    @if(is_null($klub))
                        <div class="col-lg-12 justify-content-start">
                            <p class="h3">Nema podataka o klubu</p>
                        </div>
                    @else
                        <div class="col-lg-3 justify-content-start">
                            <p class="h3">{{$klub->naziv}}</p>
                            <p class="fw-normal mb-1 mt-1">
                                {{$klub->adresa}}<br>OIB: 90882660766<br>
                                <a class="text-black" href="tel:{{ $klub->telefon }}"> {{ $klub->telefon }}</a>
                                <a aria-label="Chat on WhatsApp" href="https://wa.me/{{ $klub->telefon }}" target="_blank">@include('admin.SVG.whatsup')</a><br>
                                <a href="mailto:{{ $klub->email }}">{{ $klub->email }}</a><br>
                                <span class="link-primary" style="cursor: pointer;" data-bs-toggle="modal" data-bs-target="#QRCode">{{ $klub->racun }}</span>
                            </p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>


    @if(!is_null($klub))
        <div class="row">
            <div class="col-lg-12 col-md-12 col-sm-12">
                <div class="container-xxl bg-white shadow">
                    <div class="row justify-content-center p-2 shadow bg-danger fw-bolder">
                        <div class="col-lg-12 text-white d-flex justify-content-between align-items-center">
                            <span>Funkcije u klubu</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-lg-12 col-md-12 col-sm-12">
                <div class="container-xxl bg-white shadow">
                    <div class="row shadow">
                        <div class="col-lg-12 m-1">
                            <p style="text-align: justify; text-justify: inter-word;">Skupštinu Kluba sačinjavaju svi poslovno sposobni članovi Kluba, te
                                predstavnik pravne osobe članice Kluba kojeg imenuje osoba ovlaštena za zastupanje pravne osobe ako unutarnjim aktom pravne
                                osobe nije propisan
                                drukčiji uvjet imenovanja.</p>
                        </div>
                        <div class="col-lg-12 col-md-6 col-sm-6 mb-1 mt-1">
                            <span class="fw-bold">Predsjednik kluba:</span>
                            <span class="text-danger fw-bold">
                                 @foreach($clanovi as $clan)
                                    @if($clan->funkcija == "Predsjednik kluba")
                                        <a class="link-danger link-offset-2 link-underline-opacity-0 link-underline-opacity-50-hover fw-bold mb-1"
                                           href="{{ route('javno.clanovi.prikaz_clana', $clan->clan) }}">{{ $clan->clan->Ime }} {{ $clan->clan->Prezime }}</a>
                                    @endif
                                @endforeach
                            </span>
                        </div>

                        <div class="col-lg-12 col-md-6 col-sm-6 mb-1 mt-1">
                            <span class="fw-bold">Tajnik kluba:</span>
                            <span>
                                 @foreach($clanovi as $clan)
                                    @if($clan->funkcija == "Tajnik")
                                        <a class="link-danger link-offset-2 link-underline-opacity-0 link-underline-opacity-50-hover fw-bold mb-1"
                                           href="{{ route('javno.clanovi.prikaz_clana', $clan->clan) }}">{{ $clan->clan->Ime }} {{ $clan->clan->Prezime }}</a>
                                    @endif
                                @endforeach
                            </span>
                        </div>

                        @php $clanUO = 0; @endphp
                        <div class="col-lg-4 col-md-4 mb-1 mt-1">
                            <span class="fw-bold">Upravni odbor:</span><br>
                            <span>&#x2022;
                                 @foreach($clanovi as $clan)
                                    @if($clan->funkcija == "Predsjednik kluba")
                                        <a class="link-danger link-offset-2 link-underline-opacity-0 link-underline-opacity-50-hover fw-bold mb-1"
                                           href="{{ route('javno.clanovi.prikaz_clana', $clan->clan) }}">{{ $clan->clan->Ime }} {{ $clan->clan->Prezime }}</a>
                                    @endif
                                @endforeach
                            </span><br>
                            <span>&#x2022;
                                 @foreach($clanovi as $clan)
                                    @if($clan->funkcija == "Upravni odbor" && $clan->redniBroj == 1)
                                        <a class="link-danger link-offset-2 link-underline-opacity-0 link-underline-opacity-50-hover fw-bold mb-1"
                                           href="{{ route('javno.clanovi.prikaz_clana', $clan->clan) }}">{{ $clan->clan->Ime }} {{ $clan->clan->Prezime }}</a>
                                    @endif
                                @endforeach
                            </span><br>
                            <span>&#x2022;
                                 @foreach($clanovi as $clan)
                                    @if($clan->funkcija == "Upravni odbor" && $clan->redniBroj == 2)
                                        <a class="link-danger link-offset-2 link-underline-opacity-0 link-underline-opacity-50-hover fw-bold mb-1"
                                           href="{{ route('javno.clanovi.prikaz_clana', $clan->clan) }}">{{ $clan->clan->Ime }} {{ $clan->clan->Prezime }}</a>
                                    @endif
                                @endforeach
                            </span>
                        </div>

                        <div class="col-lg-4 col-md-4 mb-1 mt-1">
                            <span class="fw-bold">Nadzorni odbor (predsjednik + 2 člana):</span><br>
                            <span>&#x2022;
                                 @foreach($clanovi as $clan)
                                    @if($clan->funkcija == "Nadzorni odbor" && $clan->redniBroj == 1)
                                        <a class="link-danger link-offset-2 link-underline-opacity-0 link-underline-opacity-50-hover fw-bold mb-1"
                                           href="{{ route('javno.clanovi.prikaz_clana', $clan->clan) }}">{{ $clan->clan->Ime }} {{ $clan->clan->Prezime }}</a>
                                    @endif
                                @endforeach
                            </span><br>
                            <span>&#x2022;
                                 @foreach($clanovi as $clan)
                                    @if($clan->funkcija == "Nadzorni odbor" && $clan->redniBroj == 2)
                                        <a class="link-danger link-offset-2 link-underline-opacity-0 link-underline-opacity-50-hover fw-bold mb-1"
                                           href="{{ route('javno.clanovi.prikaz_clana', $clan->clan) }}">{{ $clan->clan->Ime }} {{ $clan->clan->Prezime }}</a>
                                    @endif
                                @endforeach
                            </span><br>
                            <span>&#x2022;
                                 @foreach($clanovi as $clan)
                                    @if($clan->funkcija == "Nadzorni odbor" && $clan->redniBroj == 3)
                                        <a class="link-danger link-offset-2 link-underline-opacity-0 link-underline-opacity-50-hover fw-bold mb-1"
                                           href="{{ route('javno.clanovi.prikaz_clana', $clan->clan) }}">{{ $clan->clan->Ime }} {{ $clan->clan->Prezime }}</a>
                                    @endif
                                @endforeach
                            </span>
                        </div>

                        <div class="col-lg-4 col-md-4 mb-1 mt-1">

                            <span class="fw-bold">Arbitražno vijeće (3 člana):</span><br>
                            <span>&#x2022;
                                 @foreach($clanovi as $clan)
                                    @if($clan->funkcija == "Arbitražno vijeće" && $clan->redniBroj == 1)
                                        <a class="link-danger link-offset-2 link-underline-opacity-0 link-underline-opacity-50-hover fw-bold mb-1"
                                           href="{{ route('javno.clanovi.prikaz_clana', $clan->clan) }}">{{ $clan->clan->Ime }} {{ $clan->clan->Prezime }}</a>
                                    @endif
                                @endforeach
                            </span><br>
                            <span>&#x2022;
                                 @foreach($clanovi as $clan)
                                    @if($clan->funkcija == "Arbitražno vijeće" && $clan->redniBroj == 2)
                                        <a class="link-danger link-offset-2 link-underline-opacity-0 link-underline-opacity-50-hover fw-bold mb-1"
                                           href="{{ route('javno.clanovi.prikaz_clana', $clan->clan) }}">{{ $clan->clan->Ime }} {{ $clan->clan->Prezime }}</a>
                                    @endif
                                @endforeach
                            </span><br>
                            <span>&#x2022;
                                 @foreach($clanovi as $clan)
                                    @if($clan->funkcija == "Arbitražno vijeće" && $clan->redniBroj == 3)
                                        <a class="link-danger link-offset-2 link-underline-opacity-0 link-underline-opacity-50-hover fw-bold mb-1"
                                           href="{{ route('javno.clanovi.prikaz_clana', $clan->clan) }}">{{ $clan->clan->Ime }} {{ $clan->clan->Prezime }}</a>
                                    @endif
                                @endforeach
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>


        <div class="row">
            <div class="col-lg-12 col-md-12 col-sm-12">
                <div class="container-xxl bg-white shadow">
                    <div class="row justify-content-center p-2 shadow bg-danger fw-bolder">
                        <div class="col-lg-12 text-white d-flex justify-content-between align-items-center">
                            <span>Dokumenti kluba</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-lg-12 col-md-12 col-sm-12">
                <div class="container-xxl bg-white shadow">
                    <div class="row justify-content-start shadow">
                        @if($klub->dokumenti->count() == 0)
                            <div class="col-lg-12 justify-content-start pt-3">
                                <p style="text-align: justify; text-justify: inter-word;">Nema spremljenih dokumenata</p>
                            </div>
                        @else
                            @foreach($klub->dokumenti as $dokument)
                                @if($dokument->javno == 1 )
                                    <div class="col-12 m-2 align-self-center">
                                        <span>{{ $dokument->opis }}</span>
                                        <span>&nbsp;&nbsp;&nbsp;&nbsp;</span>
                                        <span class="text-danger fw-bold"><a class="link-info link-offset-2 link-underline-opacity-0 link-underline-opacity-50-hover fw-bold mb-1" href="{{ asset('storage/klub/' . $dokument->link_text) }}" target="_blank">Preuzimanje / pregled dokumenta</a></span>
                                    </div>
                                @endif
                            @endforeach
                        @endif
                    </div>
                </div>
            </div>
        </div>
        @include('admin.klub.QRCodeModal')
    @endif

@endsection
