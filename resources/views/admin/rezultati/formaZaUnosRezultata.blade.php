@extends('layouts.app')

@section('content')
    <div class="container-xxl">
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
