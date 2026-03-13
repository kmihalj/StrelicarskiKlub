@extends('layouts.app')
@section('content')
    {{--    statistika za tekuću i prošlu godinu--}}
    <div class="container-xxl bg-white shadow">
        <div class="row p-3 mb-3 shadow">
            <div class="col-lg-3 col-md-4 col-sm-6">
                <h5>{{date('Y')}}. ukupno: {{ $statistika[date('Y')][1] + $statistika[date('Y')][2] + $statistika[date('Y')][3] }}</h5>
                <p class="fw-bolder">
                    @include('admin.SVG.gold') - {{ $statistika[date('Y')][1] }}<br>
                    @include('admin.SVG.silver') - {{ $statistika[date('Y')][2] }}<br>
                    @include('admin.SVG.bronze') - {{ $statistika[date('Y')][3] }}
                </p>
            </div>
            <div class="col-lg-3 col-md-4 col-sm-6">
                <h5>{{date('Y') - 1}}. ukupno: {{ $statistika[date('Y') - 1][1] + $statistika[date('Y') - 1][2] + $statistika[date('Y') - 1][3] }}</h5>
                <p class="fw-bolder">
                    @include('admin.SVG.gold') - {{ $statistika[date('Y') - 1][1] }}<br>
                    @include('admin.SVG.silver') - {{ $statistika[date('Y') - 1][2] }}<br>
                    @include('admin.SVG.bronze') - {{ $statistika[date('Y') - 1][3] }}
                </p>
            </div>
            @auth()
                @if(auth()->user()->rola <= 1)
                    <div class="col-12 text-end">
                        <button class="btn btn-sm btn-warning" type="button" data-bs-toggle="modal" data-bs-target="#UnosTurnira_modal">Dodaj turnir</button>
                        <button class="btn btn-sm btn-warning" type="button" onclick="location.href='{{ route('admin.rezultati.popisTurnira') }}'">Popis turnira</button>
                    </div>
                    @include('admin.rezultati.modal_za_unos')
                @endif
            @endauth
        </div>
    </div>


    @if($turniri->count() == 0)
        {{-- Ako nema unesenih turnira --}}
        <div class="row justify-content-center">
            <div class="col-12 mb-2 mt-2">
                <div class="ms-3">
                    <p class="fw-bold mb-1">Nema unešenih turnira</p>
                </div>
            </div>
        </div>
    @else
        {{-- Prikaz turnira --}}
        @include('layouts.paginationBlok', ['paginator' => $turniri, 'isTop' => true])

        <div class="container-xxl">
        @include('admin.rezultati.prikazRezultata')
        </div>


        @include('layouts.paginationBlok', ['paginator' => $turniri])
    @endif
@endsection
