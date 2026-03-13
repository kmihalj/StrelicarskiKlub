@extends('layouts.app')
@section('content')

    <div class="container-xxl bg-white shadow mb-3">
        <div class="row justify-content-center p-2 shadow bg-danger fw-bolder">
            <div class="col-lg-12 text-white d-flex justify-content-between align-items-center">
                <span>{{ $vrsta }} - popis</span>
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

    @include('layouts.paginationBlok', ['paginator' => $clanci, 'isTop' => true])

    @foreach($clanci as $clanak)
        <div class="container-xxl bg-white shadow mb-3">
            @include('admin.clanci.clanak')
        </div>
    @endforeach
    @include('layouts.paginationBlok', ['paginator' => $clanci])

@endsection
