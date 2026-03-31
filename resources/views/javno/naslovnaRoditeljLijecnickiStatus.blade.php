{{-- Roditeljski blok: pregled liječničkog statusa djece članova. --}}
@if(isset($statusLijecnickiDijete))
    @php
        $placanjeNotice = $statusLijecnickiDijete['paymentNotice'] ?? null;
        $placanjeImaDugovanje = in_array(($placanjeNotice['variant'] ?? null), ['danger', 'warning'], true);
        $placanjePrikaziObavijest = !empty($placanjeNotice)
            && str_starts_with((string)($placanjeNotice['title'] ?? ''), 'Potrebna uplata');
        $dijeteClanId = (int)($statusLijecnickiDijete['clan']->id ?? 0);
        $prijaveTurniraDijete = collect($prijaveTurniraKorisnika ?? collect())
            ->where('clan_id', $dijeteClanId)
            ->values();
        $prijavljeniClanoviPoTurniru = $prijavljeniClanoviPoTurniru ?? [];
        $lijecnickiUpozorenjaTurniraKorisnika = $lijecnickiUpozorenjaTurniraKorisnika ?? [];
    @endphp
    <div class="row justify-content-center p-2 shadow bg-danger fw-bolder">
        <div class="col-lg-12 text-white">
            Podaci djeteta (član)
        </div>
    </div>

    <div class="row justify-content-center pt-3 pb-3 mb-3 shadow bg-white">
        <div class="col-lg-12">
            <div class="d-flex flex-column flex-md-row align-items-md-start justify-content-between gap-2">
                <div>
                    <p class="h5 fw-bold mb-1 mb-md-0">
                        <a class="link-primary link-offset-2 link-underline-opacity-0 link-underline-opacity-0-hover"
                           href="{{ route('javno.clanovi.prikaz_clana', $statusLijecnickiDijete['clan']) }}">
                            {{ trim((string)$statusLijecnickiDijete['clan']->Ime) }} {{ trim((string)$statusLijecnickiDijete['clan']->Prezime) }}
                        </a>
                    </p>
                </div>

                <div class="text-md-end">
                    @if($statusLijecnickiDijete['istekao'])
                        <p class="mb-0 text-danger fw-bold">
                            Lije&#269;ni&#269;ki je istekao {{ $statusLijecnickiDijete['datum'] }}
                        </p>
                    @elseif(is_null($statusLijecnickiDijete['datum']))
                        <p class="mb-0">Trajanje lije&#269;ni&#269;kog do: -</p>
                    @else
                        <p class="mb-0 text-nowrap">
                            Trajanje lije&#269;ni&#269;kog do: {{ $statusLijecnickiDijete['datum'] }}
                            <span class="@if($statusLijecnickiDijete['manjeOdDvadesetDana']) text-danger fw-bold @endif">
                                ({{ $statusLijecnickiDijete['brojDana'] }} dana)
                            </span>
                        </p>
                    @endif
                </div>
            </div>
            <div class="mt-2 d-flex flex-wrap align-items-center justify-content-between gap-2">
                @if($placanjePrikaziObavijest)
                    <div class="alert alert-danger mb-0 py-1 px-2 small text-start text-md-nowrap">
                        <span class="fw-bold">{{ $placanjeNotice['title'] }}</span>
                        @if(!empty($placanjeNotice['message']))
                            <span> - {{ $placanjeNotice['message'] }}</span>
                        @endif
                    </div>
                @endif
                <div class="d-flex flex-wrap align-items-center gap-2 ms-auto">
                    @if(!empty($placanjeNotice))
                        <a class="btn btn-sm {{ $placanjeImaDugovanje ? 'btn-danger' : 'btn-success' }} d-inline-flex align-items-center justify-content-center"
                           href="{{ route('javno.clanovi.placanja', $statusLijecnickiDijete['clan']) }}"
                           title="Plaćanja"
                           aria-label="Plaćanja">
                            @include('admin.SVG.cashcoin')
                        </a>
                    @endif
                    <button type="button" class="btn btn-sm btn-danger"
                            onclick="location.href='{{ route('javno.treninzi.clan.index', $statusLijecnickiDijete['clan']) }}'">
                        Pregled treninga
                    </button>
                </div>
            </div>
            @if($prijaveTurniraDijete->count() > 0)
                <div class="mt-3 pt-3 border-top">
                    <a href="{{ route('javno.prijave_turnira.index') }}"
                       class="fw-bold link-primary link-offset-2 link-underline-opacity-25 link-underline-opacity-100-hover text-decoration-underline">
                        Prijava na turnire
                    </a>
                    <ul class="mb-0 mt-2">
                        @foreach($prijaveTurniraDijete as $prijava)
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
                                    {{ $prijava->turnir?->datumRasponLabel() ?? '-' }}; {{ $prijava->turnir?->naziv ?? '-' }};
                                    {{ $prijava->turnir?->mjesto ?? '-' }}
                                </a>
                                <span class="ms-1">|</span>
                                @if($nacinKotizacije === 'bank')
                                    @if($jePlaceno)
                                        <span class="badge bg-success align-middle ms-1">
                                            Plaćeno
                                            @if($iznosKotizacije !== null)
                                                : {{ number_format((float)$iznosKotizacije, 2, ',', '.') }} €
                                            @endif
                                        </span>
                                    @elseif($nijePlaceno && $urlPlacanja)
                                        <a href="{{ $urlPlacanja }}" class="badge bg-danger text-white text-decoration-none align-middle ms-1">
                                            Nije plaćeno
                                            @if($iznosKotizacije !== null)
                                                : {{ number_format((float)$iznosKotizacije, 2, ',', '.') }} €
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
