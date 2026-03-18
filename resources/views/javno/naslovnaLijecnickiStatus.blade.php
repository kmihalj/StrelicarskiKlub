{{-- Blok statusa liječničkog pregleda za prijavljenog člana. --}}
@if(isset($statusLijecnickiKorisnika))
    @php
        $placanjeNotice = $statusLijecnickiKorisnika['paymentNotice'] ?? null;
    @endphp
    <div class="row justify-content-center p-2 shadow bg-danger fw-bolder">
        <div class="col-lg-12 text-white">
            Moji podaci
        </div>
    </div>

    <div class="row justify-content-center pt-3 pb-3 mb-3 shadow bg-white">
        <div class="col-lg-12">
            <div class="d-flex flex-column flex-md-row align-items-md-start justify-content-between gap-2">
                <div>
                    <p class="h5 fw-bold mb-1 mb-md-0">
                        <a class="link-primary link-offset-2 link-underline-opacity-0 link-underline-opacity-0-hover"
                           href="{{ route('javno.clanovi.prikaz_clana', $statusLijecnickiKorisnika['clan']) }}">
                            {{ trim((string)$statusLijecnickiKorisnika['clan']->Ime) }} {{ trim((string)$statusLijecnickiKorisnika['clan']->Prezime) }}
                        </a>
                    </p>
                </div>

                <div class="text-md-end">
                    @if($statusLijecnickiKorisnika['istekao'])
                        <p class="mb-0 text-danger fw-bold">
                            Lije&#269;ni&#269;ki je istekao {{ $statusLijecnickiKorisnika['datum'] }}
                        </p>
                    @elseif(is_null($statusLijecnickiKorisnika['datum']))
                        <p class="mb-0">
                            Trajanje lije&#269;ni&#269;kog do: -
                        </p>
                    @else
                        <p class="mb-0 text-nowrap">
                            Trajanje lije&#269;ni&#269;kog do: {{ $statusLijecnickiKorisnika['datum'] }}
                            <span class="@if($statusLijecnickiKorisnika['manjeOdDvadesetDana']) text-danger fw-bold @endif">
                                ({{ $statusLijecnickiKorisnika['brojDana'] }} dana)
                            </span>
                        </p>
                    @endif
                    <p class="mb-0 mt-2">
                        <button type="button" class="btn btn-sm btn-danger"
                                onclick="location.href='{{ route('javno.treninzi.index') }}'">
                            Moji treninzi
                        </button>
                    </p>
                </div>
            </div>
            @if(!empty($placanjeNotice))
                <div class="alert alert-{{ $placanjeNotice['variant'] ?? 'secondary' }} mb-0 mt-3">
                    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2">
                        <div>
                            <div class="fw-bold">{{ $placanjeNotice['title'] ?? 'Status plaćanja' }}</div>
                            <div class="small">{{ $placanjeNotice['message'] ?? '' }}</div>
                        </div>
                        <a class="btn btn-sm btn-outline-primary text-nowrap"
                           href="{{ route('javno.clanovi.placanja', $statusLijecnickiKorisnika['clan']) }}">
                            Moja plaćanja
                        </a>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endif
