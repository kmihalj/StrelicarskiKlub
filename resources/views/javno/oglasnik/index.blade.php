{{-- Javni prikaz aktivnih oglasa opreme clanova kluba. --}}
@extends('layouts.app')

@section('content')
    @include('javno.oglasnik._styles')

    <div class="container-xxl bg-white shadow mb-3">
        <div class="row justify-content-center p-2 shadow bg-danger fw-bolder">
            <div class="col-lg-12 text-white d-flex flex-wrap justify-content-between align-items-center gap-2">
                <span>Oglasnik</span>
                @if($mozePredati)
                    <span class="d-inline-flex align-items-center gap-2">
                        <a href="{{ route('javno.oglasnik.create') }}" class="btn btn-sm btn-light">Predaja oglasa</a>
                        <a href="{{ route('javno.oglasnik.mine') }}" class="btn btn-sm btn-light">Moji oglasi</a>
                    </span>
                @endif
            </div>
        </div>

        <div class="row p-3 g-3">
            @forelse($oglasi as $oglas)
                <div class="col-12 col-md-6 col-xl-4">
                    @include('javno.oglasnik._card', [
                        'oglas' => $oglas,
                        'canManage' => (bool) ($upravljanjeMapa[(int) $oglas->id] ?? false),
                        'showStateBadge' => false,
                    ])
                </div>
            @empty
                <div class="col-12">
                    <div class="alert alert-secondary mb-0">Trenutno nema aktivnih oglasa.</div>
                </div>
            @endforelse
        </div>

        @if($oglasi->hasPages())
            <div class="row px-3 pb-3">
                <div class="col-12">
                    {{ $oglasi->links() }}
                </div>
            </div>
        @endif
    </div>
@endsection

