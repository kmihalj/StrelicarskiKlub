{{-- Glavni admin ekran za unos i uređivanje rezultata turnira (pojedinačno i timski). --}}
@extends('layouts.app')

@section('content')
    <div class="container-xxl">
        {{-- Zaglavlje ekrana: kontekst trenutnog turnira + brzi povratak na popis turnira. --}}
        <div class="row justify-content-center p-2 mb-3 shadow bg-danger fw-bolder">
            <div class="col-lg-12 text-white">
                <span class="align-middle" onclick="location.href='{{ route('javno.rezultati.prikaz_turnira', $turnir) }}'">Unos rezultata - {{ date('d.m.Y.', strtotime( $turnir->datum  )) }} - {{ $turnir->naziv  }} - {{ $turnir->lokacija  }} - {{ $turnir->tipTurnira->naziv }} @if($turnir->eliminacije)
                        - eliminacije
                    @endif </span>
                <span class="float-end align-middle">
                <button class="btn btn-warning" style="--bs-btn-padding-y: .1rem; --bs-btn-padding-x: .5rem; --bs-btn-font-size: .70rem;" onclick="location.href='{{ route('admin.rezultati.popisTurnira') }}'">Popis turnira</button>
                    </span>

            </div>
        </div>
    </div>

    <div class="container-xxl">
        {{-- Blok 1: tablica već unesenih rezultata (edit + delete po retku). --}}
        <div class="row justify-content-center p-2 shadow bg-success fw-bolder">
            <div class="col-lg-12 text-white">
                Uneseni rezultati
            </div>
        </div>
        <div class="row justify-content-center pt-2 pb-3 shadow bg-white fw-bolder">
            <div class="col-lg-12">
        @include('admin.rezultati.postojeciRezultati')
            </div>
        </div>
        {{-- Blok 2: forma za unos novog rezultata ili uređivanje postojećeg retka. --}}
        <div class="row justify-content-center p-2 shadow bg-dark-subtle fw-bolder">
            <div class="col-lg-12 text-danger">
               Unos rezultata
            </div>
        </div>
        <div class="row justify-content-center pt-2 shadow bg-white fw-bolder mb-3">
            <div class="col-lg-12">
                @include('admin.rezultati.noviRezultatUnos')
            </div>
        </div>

        {{-- Blok 3: timski rezultati; vidljiv i koristan samo ako je turnir označen da ima timove. --}}
        <div class="row justify-content-center p-2 shadow bg-success fw-bolder">
            <div class="col-lg-12 text-white">
                Timski rezultati
            </div>
        </div>
        <div class="row justify-content-center pt-2 pb-3 shadow bg-white fw-bolder mb-3">
            <div class="col-lg-12">
                @include('admin.rezultati.unosTimova')
            </div>
        </div>

        {{-- Blok 4: uvodni opis turnira (iznad galerije na javnom prikazu rezultata). --}}
        <div class="row justify-content-center p-2 shadow bg-success fw-bolder">
            <div class="col-lg-12 text-white">
                Opis
            </div>
        </div>
        <div class="row justify-content-center pt-2 mb-3 shadow bg-white">
            <div class="col-lg-12">
                @include('admin.rezultati.dodavanjeOpisa')
            </div>
        </div>

        {{-- Blok 5: upload medija turnira (slike/video) koji se prikazuju u galeriji. --}}
        <div class="row justify-content-center p-2 shadow bg-success fw-bolder">
            <div class="col-lg-12 text-white">
                Dodavanje slika <i>(.jpg, .jpeg, .png, .webp)</i> i/ili videa <i>(.mp4)</i>
            </div>
        </div>
        <div class="row justify-content-center pt-4 pb-3 mb-3 shadow bg-white fw-bolder">
            <div class="col-lg-12">
                @include('admin.rezultati.dodavanjeMedija')
            </div>
        </div>

        {{-- Blok 6: završni opis koji se prikazuje ispod galerije (npr. dodatne napomene i linkovi). --}}
        <div class="row justify-content-center p-2 shadow bg-success fw-bolder">
            <div class="col-lg-12 text-white">
                Opis ispod galerije
            </div>
        </div>
        <div class="row justify-content-center pt-2 mb-3 shadow bg-white">
            <div class="col-lg-12">
                @include('admin.rezultati.dodavanjeOpisa2')
            </div>
        </div>


    </div>


    {{--<div class="row justify-content-center">
        <div class="col-12 mb-2 mt-2">
            <div class="card">
                <div class="card-body bg-secondary-subtle shadow">
                    @include('admin.rezultati.dodavanjeLinkova')
                </div>
            </div>
        </div>
    </div>--}}
@endsection
