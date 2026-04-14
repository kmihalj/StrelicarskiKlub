{{-- Javni/admin popis članaka po vrsti sadržaja. --}}
@extends('layouts.app')
@section('content')
    @php
        $nazivPopisGumba = $vrsta === 'Obavijest' ? 'Popis obavijesti' : 'Popis članaka';
    @endphp

    <div class="container-xxl bg-white shadow mb-3">
        <div class="row justify-content-center p-2 shadow bg-danger fw-bolder">
            <div class="col-lg-12 text-white d-flex justify-content-between align-items-center">
                <span>{{ $vrsta }} - popis</span>
                <span class="d-flex align-items-center gap-2">
                    <button class="btn btn-sm btn-light" onclick="location.href='{{ route('javno.clanci.popisClanakaTablica', ['vrsta' => $vrsta]) }}'" type="button">
                        {{ $nazivPopisGumba }}
                    </button>
                    @auth()
                        @if(auth()->user()->rola <= 1)
                            <button class="btn btn-sm btn-warning" onclick="location.href='{{ route('admin.clanci.popisClanaka') }}'" type="button">Popis članaka</button>
                            <button class="btn btn-sm btn-warning" type="button" onclick="location.href='{{ route('admin.clanci.unos') }}'">Dodaj članak</button>
                        @endif
                    @endauth
                </span>
            </div>
        </div>
    </div>

    @include('layouts.paginationBlok', ['paginator' => $clanci, 'isTop' => true])

    @foreach($clanci as $clanak)
        <div class="container-xxl bg-white shadow mb-3">
            @include('admin.clanci.clanak')
        </div>
    @endforeach

    @include('layouts.paginationBlok', ['paginator' => $clanci])
@endsection
