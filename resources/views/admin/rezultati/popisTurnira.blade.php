@extends('layouts.app')

@section('content')
    @include('layouts.paginationBlok', ['paginator' => $turniri, 'isTop' => true])

    <div class="container-xxl bg-white shadow mb-3">
        <div class="row justify-content-center p-2 shadow bg-danger fw-bolder">
            <div class="col-lg-12 text-white d-flex justify-content-between align-items-center">
                <span>Popis turnira</span>
                @auth()
                    @if(auth()->user()->rola <= 1)
                        <span>
                            <button class="btn btn-sm btn-warning" type="button" data-bs-toggle="modal" data-bs-target="#UnosTurnira_modal">Dodaj turnir</button>
                        </span>
                    @endif
                @endauth
            </div>
        </div>
        <div class="row justify-content-center p-2 shadow bg-white fw-bolder">
            <div class="col-lg-12 mt-3 mb-3 text-white">
                @include('admin.rezultati.tabelaTurnira')
            </div>
        </div>
    </div>


    {{-- Prikaz turnira --}}
    @include('layouts.paginationBlok', ['paginator' => $turniri])

    @auth()
        @if(auth()->user()->rola <= 1)
            @include('admin.rezultati.modal_za_unos')
            @isset($uredi_turnir)
                @include('admin.rezultati.modal_za_uredjivanje')
            @endisset
        @endif
    @endauth

@endsection
