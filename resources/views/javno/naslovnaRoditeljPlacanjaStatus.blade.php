{{-- Roditeljski blok: pregled statusa članarine djece članova. --}}
@php
    $statusPlacanjaDijete = $statusPlacanjaDijete ?? null;
    $placanjeNotice = $statusPlacanjaDijete['notice'] ?? null;
    $placanjeClan = $statusPlacanjaDijete['clan'] ?? null;
@endphp

@if(!empty($placanjeNotice) && !empty($placanjeClan))
    <div class="row justify-content-start mb-3 pt-2 shadow bg-white">
        <div class="col-lg-12 justify-content-start">
            <div class="alert alert-{{ $placanjeNotice['variant'] ?? 'secondary' }} mb-2 mt-2">
                <div class="fw-bold">Plaćanje - {{ $placanjeClan->Ime }} {{ $placanjeClan->Prezime }}</div>
                <div class="small">{{ $placanjeNotice['message'] ?? '' }}</div>
                <a class="btn btn-sm btn-outline-primary mt-2" href="{{ route('javno.clanovi.placanja', $placanjeClan) }}">
                    Pregled plaćanja
                </a>
            </div>
        </div>
    </div>
@endif
