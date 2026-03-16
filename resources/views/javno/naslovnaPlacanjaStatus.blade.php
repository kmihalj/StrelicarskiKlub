{{-- Blok statusa članarine (plaćeno/dugovanje) za prijavljenog člana. --}}
@php
    $statusPlacanjaKorisnika = $statusPlacanjaKorisnika ?? null;
    $placanjeNotice = $statusPlacanjaKorisnika['notice'] ?? null;
    $placanjeClan = $statusPlacanjaKorisnika['clan'] ?? null;
@endphp

@if(!empty($placanjeNotice) && !empty($placanjeClan))
    <div class="row justify-content-start mb-3 pt-2 shadow bg-white">
        <div class="col-lg-12 justify-content-start">
            <div class="alert alert-{{ $placanjeNotice['variant'] ?? 'secondary' }} mb-2 mt-2">
                <div class="fw-bold">{{ $placanjeNotice['title'] ?? 'Status plaćanja' }}</div>
                <div class="small">{{ $placanjeNotice['message'] ?? '' }}</div>
                <a class="btn btn-sm btn-outline-primary mt-2" href="{{ route('javno.clanovi.placanja', $placanjeClan) }}">
                    Moja plaćanja
                </a>
            </div>
        </div>
    </div>
@endif
