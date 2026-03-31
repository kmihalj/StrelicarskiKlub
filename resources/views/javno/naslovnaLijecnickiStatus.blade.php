{{-- Blok statusa liječničkog pregleda za prijavljenog člana. --}}
@if(isset($statusLijecnickiKorisnika))
    @php
        $placanjeNotice = $statusLijecnickiKorisnika['paymentNotice'] ?? null;
        $vlastitiClanId = (int)($statusLijecnickiKorisnika['clan']->id ?? 0);
        $prijaveTurniraKorisnika = collect($prijaveTurniraKorisnika ?? collect())
            ->where('clan_id', $vlastitiClanId)
            ->values();
        $prijavljeniClanoviPoTurniru = $prijavljeniClanoviPoTurniru ?? [];
        $lijecnickiUpozorenjaTurniraKorisnika = $lijecnickiUpozorenjaTurniraKorisnika ?? [];
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
            @if($prijaveTurniraKorisnika->count() > 0)
                <div class="mt-3 pt-3 border-top">
                    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2">
                        <div class="fw-bold">Prijave na turnire</div>
                        <a href="{{ route('javno.prijave_turnira.index') }}" class="btn btn-sm btn-outline-primary">Otvori prijave na turnire</a>
                    </div>
                    <ul class="mb-0 mt-2">
                        @foreach($prijaveTurniraKorisnika as $prijava)
                            @php
                                $turnirId = (int)($prijava->nadolazeci_turnir_id ?? 0);
                                $prijavljeniClanovi = collect($prijavljeniClanoviPoTurniru[$turnirId] ?? []);
                                $charge = $prijava->paymentCharge;
                                $nacinKotizacije = $prijava->turnir?->kotizacija_nacin;
                                $iznosKotizacije = $prijava->turnir?->kotizacija_iznos;
                                $jePlaceno = $charge && $charge->status === \App\Services\PaymentTrackingService::STATUS_PAID;
                                $nijePlaceno = $charge && $charge->status === \App\Services\PaymentTrackingService::STATUS_OPEN;
                                $urlPlacanja = null;
                                if ($nijePlaceno && $prijava->clan) {
                                    $urlPlacanja = route('javno.clanovi.placanja', [
                                        'clan' => (int) $prijava->clan->id,
                                        'charge' => (int) $charge->id,
                                    ]);
                                }
                                $warning = $lijecnickiUpozorenjaTurniraKorisnika[(int) ($prijava->id ?? 0)] ?? null;
                            @endphp
                            <li class="mb-1">
                                <a href="{{ route('javno.prijave_turnira.show', $prijava) }}" class="link-primary text-decoration-underline">
                                    {{ $prijava->turnir?->datum?->format('d.m.Y.') ?? '-' }}; {{ $prijava->turnir?->naziv ?? '-' }};
                                    {{ $prijava->turnir?->mjesto ?? '-' }}
                                </a>
                                <span class="ms-1">|</span>
                                @if($nacinKotizacije === 'bank')
                                    @if($jePlaceno)
                                        <span class="badge bg-success">
                                            Plaćeno
                                            @if($iznosKotizacije !== null)
                                                {{ number_format((float)$iznosKotizacije, 2, ',', '.') }} EUR
                                            @endif
                                        </span>
                                    @elseif($nijePlaceno && $urlPlacanja)
                                        <a href="{{ $urlPlacanja }}" class="badge bg-danger text-white text-decoration-none">
                                            Nije plaćeno
                                            @if($iznosKotizacije !== null)
                                                {{ number_format((float)$iznosKotizacije, 2, ',', '.') }} EUR
                                            @endif
                                        </a>
                                    @else
                                        <span class="badge bg-secondary">
                                            Plaćanje preko računa kluba
                                            @if($iznosKotizacije !== null)
                                                {{ number_format((float)$iznosKotizacije, 2, ',', '.') }} EUR
                                            @else
                                                - iznos nije definiran
                                            @endif
                                        </span>
                                    @endif
                                @elseif($nacinKotizacije === 'cash')
                                    <span class="badge bg-secondary">
                                        Gotovina
                                        @if($iznosKotizacije !== null)
                                            {{ number_format((float)$iznosKotizacije, 2, ',', '.') }} EUR
                                        @else
                                            - iznos nije definiran
                                        @endif
                                    </span>
                                @else
                                    <span class="badge bg-secondary">Nije još definirano</span>
                                @endif
                                @if(!empty($warning))
                                    <div class="alert alert-danger py-1 px-2 mt-1 mb-1 small">{{ $warning }}</div>
                                @endif
                                @if($prijavljeniClanovi->count() > 0)
                                    <div class="small mt-1">
                                        Prijavljeni članovi:
                                        @foreach($prijavljeniClanovi as $prijavljeniClan)
                                            <a href="{{ $prijavljeniClan['url'] }}" class="link-primary text-decoration-underline">{{ $prijavljeniClan['naziv'] }}</a>@if(!$loop->last), @endif
                                        @endforeach
                                    </div>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    </div>
@endif
