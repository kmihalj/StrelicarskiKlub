{{-- Pregled treninga odabranog člana za admina/roditelja/člana s pravom pristupa. --}}
@extends('layouts.app')
@section('content')
    @php
        $mozeUredjivati = (bool)($mozeUredjivati ?? false);
    @endphp

    <div class="container-xxl">
        <div class="row justify-content-center p-2 mb-3 shadow bg-danger fw-bolder">
            <div class="col-lg-12 text-white d-flex flex-wrap align-items-center justify-content-between gap-2">
                <span>Pregled treninga</span>
                <button class="btn btn-sm btn-warning" type="button"
                        onclick="location.href='{{ route('javno.clanovi.prikaz_clana', $clan) }}'">
                    Povratak na člana
                </button>
            </div>
        </div>
    </div>

    <div class="container-xxl">
        <div class="row justify-content-center pt-3 pb-3 mb-3 shadow bg-white">
            <div class="col-lg-12">
                <p class="fw-bold mb-0">
                    Član:
                    <a class="link-primary link-offset-2 link-underline-opacity-0 link-underline-opacity-0-hover" href="{{ route('javno.clanovi.prikaz_clana', $clan) }}">
                        {{ trim((string)$clan->Ime) }} {{ trim((string)$clan->Prezime) }}
                    </a>
                </p>
            </div>
        </div>
    </div>

    @include('javno.partials.treningTipSekcija', [
        'sekcijaId' => 'admin-dvoranski-' . $clan->id,
        'naslov' => 'Dvoranski trening',
        'konfig' => ['tip' => 'dvoranski', 'broj_strijela_u_seriji' => 3, 'ima_x_kolonu' => false],
        'treninziPrikaz' => $dvoranskiPrikaz,
        'grafPodaci' => $grafDvoranski,
        'createRoute' => null,
        'destroyRouteName' => $mozeUredjivati ? 'admin.treninzi.dvoranski.destroy' : null,
        'destroyRouteExtraParams' => $mozeUredjivati ? ['clan' => $clan] : [],
    ])

    @include('javno.partials.treningTipSekcija', [
        'sekcijaId' => 'admin-vanjski-' . $clan->id,
        'naslov' => 'Vanjski trening',
        'konfig' => ['tip' => 'vanjski', 'broj_strijela_u_seriji' => 6, 'ima_x_kolonu' => true],
        'treninziPrikaz' => $vanjskiPrikaz,
        'grafPodaci' => $grafVanjski,
        'createRoute' => null,
        'destroyRouteName' => $mozeUredjivati ? 'admin.treninzi.vanjski.destroy' : null,
        'destroyRouteExtraParams' => $mozeUredjivati ? ['clan' => $clan] : [],
    ])

    @include('javno.partials.treningGrafAssets')
@endsection
