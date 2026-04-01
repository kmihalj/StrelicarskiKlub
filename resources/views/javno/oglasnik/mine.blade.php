{{-- Pregled "Mojih oglasa" s odvojenim aktivnim i deaktiviranim oglasima. --}}
@extends('layouts.app')

@section('content')
    @include('javno.oglasnik._styles')

    <div class="container-xxl bg-white shadow mb-3">
        <div class="row justify-content-center p-2 shadow bg-danger fw-bolder">
            <div class="col-lg-12 text-white d-flex flex-wrap justify-content-between align-items-center gap-2">
                <span>Moji oglasi</span>
                <span class="d-inline-flex align-items-center gap-2">
                    <a href="{{ route('javno.oglasnik.create') }}" class="btn btn-sm btn-light">Predaja oglasa</a>
                    <a href="{{ route('javno.oglasnik.index') }}" class="btn btn-sm btn-light">Oglasnik</a>
                </span>
            </div>
        </div>

        <div class="row p-3">
            <div class="col-12">
                <h6 class="fw-bold mb-3">Aktivni oglasi</h6>
                <div class="row g-3">
                    @forelse($aktivniOglasi as $oglas)
                        <div class="col-12 col-md-6 col-xl-4">
                            @include('javno.oglasnik._card', [
                                'oglas' => $oglas,
                                'canManage' => true,
                                'showStateBadge' => true,
                            ])
                        </div>
                    @empty
                        <div class="col-12">
                            <div class="alert alert-secondary mb-0">Nema aktivnih oglasa.</div>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <div class="container-xxl bg-white shadow mb-3">
        <div class="row justify-content-center p-2 shadow bg-danger fw-bolder">
            <div class="col-lg-12 text-white">Deaktivirani oglasi</div>
        </div>
        <div class="row p-3">
            <div class="col-12">
                <div class="row g-3">
                    @forelse($deaktiviraniOglasi as $oglas)
                        <div class="col-12 col-md-6 col-xl-4">
                            @include('javno.oglasnik._card', [
                                'oglas' => $oglas,
                                'canManage' => true,
                                'showStateBadge' => true,
                            ])
                        </div>
                    @empty
                        <div class="col-12">
                            <div class="alert alert-secondary mb-0">Nema deaktiviranih oglasa.</div>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    @if(($jeAdmin ?? false) === true)
        <div class="container-xxl bg-white shadow mb-3">
            <div class="row justify-content-center p-2 shadow bg-danger fw-bolder">
                <div class="col-lg-12 text-white">Deaktivirani oglasi drugih članova</div>
            </div>
            <div class="row p-3">
                <div class="col-12">
                    <div class="row g-3">
                        @forelse(($deaktiviraniDrugiOglasi ?? collect()) as $oglas)
                            <div class="col-12 col-md-6 col-xl-4">
                                @include('javno.oglasnik._card', [
                                    'oglas' => $oglas,
                                    'canManage' => true,
                                    'showStateBadge' => true,
                                ])
                            </div>
                        @empty
                            <div class="col-12">
                                <div class="alert alert-secondary mb-0">Nema deaktiviranih oglasa drugih članova.</div>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    @endif
@endsection
