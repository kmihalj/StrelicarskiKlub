{{-- Pregled pojedinog članka zajedno s pripadajućim medijima. --}}
@extends('layouts.app')
@section('content')

    <div class="container-xxl bg-white shadow">
        <div class="row justify-content-center p-2 shadow bg-danger fw-bolder">
            <div class="col-lg-12 text-white d-flex justify-content-between align-items-center">
                <span>{{ $clanak->vrsta }}</span>
                @auth()
                    @if(auth()->user()->rola <= 1)
                        <span>
                            <button class="btn btn-sm btn-warning" onclick="location.href='{{ route('admin.clanci.popisClanaka') }}'" type="button">Popis članaka</button>
                            <button class="btn btn-sm btn-warning" type="button" onclick="location.href='{{ route('admin.clanci.unos') }}'">Dodaj članak</button>
                        </span>
                    @endif
                @endauth
            </div>
        </div>
    </div>
    <div class="container-xxl bg-white shadow">
        @include('admin.clanci.clanak')
    </div>

@endsection
