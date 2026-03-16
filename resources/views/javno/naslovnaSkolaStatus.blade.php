{{-- Blok statusa školarine i dolazaka za prijavljenog polaznika škole. --}}
@if(isset($statusSkolaKorisnika) && !is_null($statusSkolaKorisnika))
    @php
        $skolarinaNotice = $statusSkolaKorisnika['paymentNotice'] ?? null;
    @endphp
    <div class="row justify-content-center p-2 shadow bg-danger fw-bolder">
        <div class="col-lg-12 text-white">
            Moji podaci škole streličarstva
        </div>
    </div>

    <div class="row justify-content-center pt-3 pb-3 mb-3 shadow bg-white">
        <div class="col-lg-12">
            <div class="d-flex flex-column flex-md-row align-items-md-start justify-content-between gap-2">
                <div>
                    <p class="h5 fw-bold mb-1 mb-md-0">
                        <a class="link-primary link-offset-2 link-underline-opacity-0 link-underline-opacity-0-hover"
                           href="{{ route('javno.skola.polaznici.show', $statusSkolaKorisnika['polaznik']) }}">
                            {{ trim((string)$statusSkolaKorisnika['polaznik']->Ime) }} {{ trim((string)$statusSkolaKorisnika['polaznik']->Prezime) }}
                        </a>
                    </p>
                </div>

                <div class="text-md-end">
                    <p class="mb-0">Broj dolazaka: <span class="fw-bold">{{ $statusSkolaKorisnika['brojDolazaka'] }}</span></p>
                    <p class="mb-0">
                        Zadnji dolazak:
                        @if(empty($statusSkolaKorisnika['zadnjiDolazak']))
                            -
                        @else
                            {{ optional($statusSkolaKorisnika['zadnjiDolazak'])->format('d.m.Y.') }}
                        @endif
                    </p>
                    <p class="mb-0 mt-2">
                        <button type="button" class="btn btn-sm btn-danger"
                                onclick="location.href='{{ route('javno.skola.polaznici.show', $statusSkolaKorisnika['polaznik']) }}'">
                            Moji dolasci
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
                                onclick="location.href='{{ route('javno.skola.polaznici.show', ['polaznik' => $statusSkolaKorisnika['polaznik'], 'open_payments' => 1]) }}'">
                            Moja školarina
                        </button>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endif
