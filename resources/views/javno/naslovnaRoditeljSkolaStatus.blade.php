@if(isset($statusSkolaDijete) && !is_null($statusSkolaDijete))
    @php
        $skolarinaNotice = $statusSkolaDijete['paymentNotice'] ?? null;
    @endphp
    <div class="row justify-content-center p-2 shadow bg-danger fw-bolder">
        <div class="col-lg-12 text-white">
            Podaci djeteta škole streličarstva
        </div>
    </div>

    <div class="row justify-content-center pt-3 pb-3 mb-3 shadow bg-white">
        <div class="col-lg-12">
            <div class="d-flex flex-column flex-md-row align-items-md-start justify-content-between gap-2">
                <div>
                    <p class="h5 fw-bold mb-1 mb-md-0">
                        <a class="link-primary link-offset-2 link-underline-opacity-0 link-underline-opacity-0-hover"
                           href="{{ route('javno.skola.polaznici.show', $statusSkolaDijete['polaznik']) }}">
                            {{ trim((string)$statusSkolaDijete['polaznik']->Ime) }} {{ trim((string)$statusSkolaDijete['polaznik']->Prezime) }}
                        </a>
                    </p>
                </div>

                <div class="text-md-end">
                    <p class="mb-0">Broj dolazaka: <span class="fw-bold">{{ $statusSkolaDijete['brojDolazaka'] }}</span></p>
                    <p class="mb-0">
                        Zadnji dolazak:
                        @if(empty($statusSkolaDijete['zadnjiDolazak']))
                            -
                        @else
                            {{ optional($statusSkolaDijete['zadnjiDolazak'])->format('d.m.Y.') }}
                        @endif
                    </p>
                    <p class="mb-0 mt-2">
                        <button type="button" class="btn btn-sm btn-danger"
                                onclick="location.href='{{ route('javno.skola.polaznici.show', $statusSkolaDijete['polaznik']) }}'">
                            Dolasci djeteta
                        </button>
                    </p>
                </div>
            </div>

            @if(!empty($skolarinaNotice))
                <div class="alert alert-{{ $skolarinaNotice['variant'] ?? 'secondary' }} mb-0 mt-3">
                    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2">
                        <div>
                            <div class="fw-bold">{{ $skolarinaNotice['title'] ?? 'Status školarine' }}</div>
                            <div class="small">{{ $skolarinaNotice['message'] ?? '' }}</div>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-primary text-nowrap"
                                onclick="location.href='{{ route('javno.skola.polaznici.show', ['polaznik' => $statusSkolaDijete['polaznik'], 'open_payments' => 1]) }}'">
                            Pregled školarine
                        </button>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endif
