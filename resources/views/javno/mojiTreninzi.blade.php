{{-- Korisnički pregled osobnih treninga (dvorana/vanjski) s filtrima i grafovima napretka. --}}
@extends('layouts.app')
@section('content')
    <div class="container-xxl">
        <div class="row justify-content-center p-2 mb-3 shadow bg-dark fw-bolder">
            <div class="col-lg-12 text-white">
                Moji treninzi
            </div>
        </div>
    </div>

    <div class="container-xxl">
        <div class="row justify-content-center pt-3 pb-3 mb-3 shadow bg-white">
            <div class="col-lg-12">
                <p class="fw-bold mb-0">
                    Član:
                    <a class="link-primary link-offset-2 link-underline-opacity-0 link-underline-opacity-0-hover" href="{{ route('javno.clanovi.prikaz_clana', $clanKorisnika) }}">
                        {{ trim((string)$clanKorisnika->Ime) }} {{ trim((string)$clanKorisnika->Prezime) }}
                    </a>
                </p>
            </div>
        </div>
    </div>

    @include('javno.partials.treningTipSekcija', [
        'sekcijaId' => 'moji-dvoranski',
        'naslov' => 'Dvoranski trening',
        'konfig' => ['tip' => 'dvoranski', 'broj_strijela_u_seriji' => 3, 'ima_x_kolonu' => false],
        'treninziPrikaz' => $dvoranskiPrikaz,
        'grafPodaci' => $grafDvoranski,
        'createRoute' => route('javno.treninzi.dvoranski.create'),
        'editRouteName' => 'javno.treninzi.dvoranski.edit',
        'destroyRouteName' => 'javno.treninzi.dvoranski.destroy',
    ])

    @include('javno.partials.treningTipSekcija', [
        'sekcijaId' => 'moji-vanjski',
        'naslov' => 'Vanjski trening',
        'konfig' => ['tip' => 'vanjski', 'broj_strijela_u_seriji' => 6, 'ima_x_kolonu' => true],
        'treninziPrikaz' => $vanjskiPrikaz,
        'grafPodaci' => $grafVanjski,
        'createRoute' => route('javno.treninzi.vanjski.create'),
        'editRouteName' => 'javno.treninzi.vanjski.edit',
        'destroyRouteName' => 'javno.treninzi.vanjski.destroy',
    ])

    @include('javno.partials.treningGrafAssets')
@endsection
