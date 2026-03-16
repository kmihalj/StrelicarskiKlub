{{-- Prikaz rezultata turnira (tablice, medalje, opisi i mediji) na javnom/admin pogledu. --}}
@foreach($turniri as $turnir)
    @php
        $timoviTurnira = $turnir->relationLoaded('rezultatiTimovi') ? $turnir->rezultatiTimovi : collect();
    @endphp
    @auth()
        @if(auth()->user()->rola == 1)
            <form id="unosRezultata{{ $turnir->id }}" action="{{ route('admin.rezultati.unosRezultata', $turnir->id) }}" method="POST">
                @csrf
            </form>
        @endif
    @endauth
    <div class="row justify-content-center mb-3 pt-3 shadow bg-white">
        <div class="col-lg-12">
            <h3 class="fw-semibold" onclick="location.href='{{ route('javno.rezultati.prikaz_turnira', $turnir) }}'" style="cursor: pointer">
                {{ date('d.m.Y.', strtotime( $turnir->datum  )) }} - {{ $turnir->naziv  }} - {{ $turnir->lokacija  }}
                - {{ $turnir->tipTurnira->naziv }}
                @if($turnir->eliminacije)
                    - eliminacije
                @endif
                @auth()
                    @if(auth()->user()->rola == 1)
                        <span class="float-end">
                            <button type="submit" form="unosRezultata{{ $turnir->id }}" class="btn text-success btn-rounded" title="Unos rezultata">
                                @include('admin.SVG.unos')
                            </button>
                        </span>
                    @endif
                @endauth
            </h3>
        </div>
        <div class="col-lg-12">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 border">
                    <thead class="h5 theme-thead-accent">
                    <tr>
                        <th class="text-white">Ime i prezime</th>
                        <th class="text-white">Stil</th>
                        <th class="text-white">Kategorija</th>
                        @foreach($turnir->tipTurnira->polja as $polje)
                            <th class="text-white">{{ $polje->naziv }}</th>
                        @endforeach
                        <th class="text-white">Plasman</th>
                        @if($turnir->eliminacije)
                            <th class="text-white">Plasman nakon eliminacija</th>
                        @endif
                        <th class="text-white"></th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($turnir->rezultatiOpci as $i => $rezultat)
                        <tr
                            @if($turnir->eliminacije && $rezultat->plasman_nakon_eliminacija <=3)
                                class="fw-bold"
                            @endif
                            @if(!($turnir->eliminacije) && $rezultat->plasman <=3)
                                class="fw-bold"
                            @endif >
                            <td>
                                <p class="mb-1">
                                    <a class="link-primary link-offset-2 link-underline-opacity-0 link-underline-opacity-0-hover"
                                       href="{{ route('javno.clanovi.prikaz_clana', $rezultat->clan) }}">{{ $rezultat->clan->Ime }} {{ $rezultat->clan->Prezime }}</a>
                                </p>
                            </td>
                            <td>
                                <p class="mb-1"> {{ $rezultat->stil->naziv }} </p>
                            </td>
                            <td>
                                <p class="mb-1"> {{ $rezultat->kategorija->naziv }} </p>
                            </td>
                            @foreach($turnir->rezultatiPoTipuTurnira as $polje)
                                @if ($rezultat->clan->id == $polje['clan_id']  && $rezultat->stil->id == $polje['stil_id'])
                                    <td>
                                        <p class="mb-1"> @if($polje['rezultat'] == 0)
                                                -
                                            @else
                                                {{ $polje['rezultat'] }}
                                            @endif </p>
                                    </td>
                                @endif
                            @endforeach
                            <td>
                                <p class="mb-1"> {{ $rezultat->plasman }}</p>
                            </td>
                            @if(!($turnir->eliminacije))
                                <td>
                                    @switch($rezultat->plasman)
                                        @case(1)
                                            <span class="float-end"> @include('admin.SVG.gold') </span>
                                            @break
                                        @case(2)
                                            <span class="float-end"> @include('admin.SVG.silver') </span>
                                            @break
                                        @case(3)
                                            <span class="float-end"> @include('admin.SVG.bronze') </span>
                                            @break
                                    @endswitch
                                </td>
                            @else
                                <td>
                                    <p class="mb-1"> {{ $rezultat->plasman_nakon_eliminacija }} </p>
                                </td>
                                <td>
                                    @switch($rezultat->plasman_nakon_eliminacija)
                                        @case(1)
                                            <span class="float-end"> @include('admin.SVG.gold') </span>
                                            @break
                                        @case(2)
                                            <span class="float-end"> @include('admin.SVG.silver') </span>
                                            @break
                                        @case(3)
                                            <span class="float-end"> @include('admin.SVG.bronze') </span>
                                            @break
                                    @endswitch
                                </td>
                            @endif
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        @if($timoviTurnira->count() > 0)
            <div class="col-lg-12 mt-3">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 border">
                        <thead class="h5 theme-thead-accent">
                        <tr>
                            <th class="text-white">Član</th>
                            @foreach($turnir->tipTurnira->polja as $polje)
                                <th class="text-white">{{ $polje->naziv }}</th>
                            @endforeach
                            <th class="text-white">Stil</th>
                            <th class="text-white">Kategorija</th>
                            <th class="text-white">Ukupno</th>
                            <th class="text-white">Plasman</th>
                            <th class="text-white"></th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($timoviTurnira->sortBy([['plasman', 'asc'], ['id', 'asc']]) as $tim)
                            @php
                                $timStavke = $tim->clanoviStavke
                                    ->filter(fn ($stavka) => $stavka->rezultatOpci !== null && $stavka->rezultatOpci->clan !== null)
                                    ->sortBy([['redni_broj', 'asc'], ['id', 'asc']])
                                    ->values();
                                $brojRedakaTima = max($timStavke->count(), 1);
                            @endphp
                            @if($timStavke->count() === 0)
                                <tr @if((int)$tim->plasman <= 3) class="fw-bold" @endif>
                                    <td>-</td>
                                    @foreach($turnir->tipTurnira->polja as $polje)
                                        <td>-</td>
                                    @endforeach
                                    <td>{{ $tim->stil?->naziv ?? 'Mix / više stilova' }}</td>
                                    <td>{{ $tim->kategorija?->naziv ?? 'Mix / više kategorija' }}</td>
                                    <td>{{ $tim->rezultat }}</td>
                                    <td>{{ $tim->plasman }}</td>
                                    <td>
                                        @switch((int)$tim->plasman)
                                            @case(1)
                                                @include('admin.SVG.gold')
                                                @break
                                            @case(2)
                                                @include('admin.SVG.silver')
                                                @break
                                            @case(3)
                                                @include('admin.SVG.bronze')
                                                @break
                                        @endswitch
                                    </td>
                                </tr>
                            @else
                                @foreach($timStavke as $index => $stavka)
                                    @php
                                        $rezultatOpci = $stavka->rezultatOpci;
                                        $rezultatiPolja = $turnir->rezultatiPoTipuTurnira
                                            ->where('clan_id', $rezultatOpci->clan_id)
                                            ->where('stil_id', $rezultatOpci->stil_id)
                                            ->where('kategorija_id', $rezultatOpci->kategorija_id)
                                            ->keyBy('polje_za_tipove_turnira_id');
                                    @endphp
                                    <tr @if((int)$tim->plasman <= 3) class="fw-bold" @endif>
                                        <td>
                                            <a class="link-primary link-offset-2 link-underline-opacity-0 link-underline-opacity-0-hover"
                                               href="{{ route('javno.clanovi.prikaz_clana', $rezultatOpci->clan) }}">
                                                {{ $rezultatOpci->clan->Ime }} {{ $rezultatOpci->clan->Prezime }}
                                            </a>
                                        </td>
                                        @foreach($turnir->tipTurnira->polja as $polje)
                                            @php
                                                $vrijednostPolja = $rezultatiPolja->get($polje->id)?->rezultat;
                                            @endphp
                                            <td>{{ $vrijednostPolja === null ? '-' : $vrijednostPolja }}</td>
                                        @endforeach
                                        @if($index === 0)
                                            <td rowspan="{{ $brojRedakaTima }}">{{ $tim->stil?->naziv ?? 'Mix / više stilova' }}</td>
                                            <td rowspan="{{ $brojRedakaTima }}">{{ $tim->kategorija?->naziv ?? 'Mix / više kategorija' }}</td>
                                            <td rowspan="{{ $brojRedakaTima }}">{{ $tim->rezultat }}</td>
                                            <td rowspan="{{ $brojRedakaTima }}">{{ $tim->plasman }}</td>
                                            <td rowspan="{{ $brojRedakaTima }}">
                                                @switch((int)$tim->plasman)
                                                    @case(1)
                                                        @include('admin.SVG.gold')
                                                        @break
                                                    @case(2)
                                                        @include('admin.SVG.silver')
                                                        @break
                                                    @case(3)
                                                        @include('admin.SVG.bronze')
                                                        @break
                                                @endswitch
                                            </td>
                                        @endif
                                    </tr>
                                @endforeach
                            @endif
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        <div class="col-lg-12 p-3 ck-content">
            {!! $turnir->opis !!}
        </div>

        @if($turnir->mediji->count() != 0)
            @php
                $slikeTurnira = $turnir->mediji
                    ->where('vrsta', 'slika')
                    ->filter(fn ($medij) => Storage::disk('public')->exists('turniri/' . $turnir->id . '/' . $medij->link))
                    ->values();
                $videoTurnira = $turnir->mediji
                    ->where('vrsta', 'video')
                    ->filter(fn ($medij) => Storage::disk('public')->exists('turniri/' . $turnir->id . '/' . $medij->link))
                    ->values();
            @endphp

            @if($slikeTurnira->count() != 0)
                <div class="justified-gallery js-justified-gallery mb-2" data-row-height="165" data-gap="8">
                    @foreach($slikeTurnira as $i => $medij)
                        <button type="button"
                                class="btn justified-gallery-item js-gallery-thumb"
                                data-bs-toggle="modal"
                                data-bs-target="#Galerija{{$turnir->id}}"
                                data-galerija-slide-to="{{ $i }}"
                                data-carousel-id="carousel{{$turnir->id}}">
                            <img src="{{ asset('storage/turniri/' . $turnir->id . '/' . $medij->link) }}"
                                 class="justified-gallery-img"
                                 loading="lazy"
                                 alt="{{ $turnir->naziv }} - slika {{ $i + 1 }}">
                        </button>
                    @endforeach
                </div>

                <div class="modal fade js-gallery-modal" id="Galerija{{$turnir->id}}" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-fullscreen-md-down modal-xl modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header bg-danger">
                                <h4 class="modal-title text-white">{{ date('d.m.Y.', strtotime( $turnir->datum  )) }} - {{ $turnir->naziv  }}</h4>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body bg-secondary-subtle">
                                <div id="carousel{{$turnir->id}}" class="carousel slide" data-bs-ride="false" data-bs-interval="false">
                                    <div class="carousel-indicators">
                                        @foreach($slikeTurnira as $i => $medij)
                                            <button type="button"
                                                    data-bs-target="#carousel{{$turnir->id}}"
                                                    data-bs-slide-to="{{ $i }}"
                                                    @if($i === 0) class="active" aria-current="true" @endif
                                                    aria-label="Slika {{ $i + 1 }}"></button>
                                        @endforeach
                                    </div>
                                    <div class="carousel-inner">
                                        @foreach($slikeTurnira as $i => $medij)
                                            <div class="carousel-item @if($i === 0) active @endif">
                                                <div class="d-flex align-items-center justify-content-center galerija-slide-box">
                                                    <img src="{{ asset('storage/turniri/' . $turnir->id . '/' . $medij->link) }}"
                                                         class="d-block w-100 galerija-slide-img"
                                                         alt="{{ $turnir->naziv }} - slika {{ $i + 1 }}">
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                    <button class="carousel-control-prev" type="button" data-bs-target="#carousel{{$turnir->id}}" data-bs-slide="prev">
                                        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                        <span class="visually-hidden">Prethodna</span>
                                    </button>
                                    <button class="carousel-control-next" type="button" data-bs-target="#carousel{{$turnir->id}}" data-bs-slide="next">
                                        <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                        <span class="visually-hidden">Sljedeća</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            @if($videoTurnira->count() != 0)
                <div class="justified-video-gallery pt-2">
                    @foreach($videoTurnira as $medij)
                        <div class="justified-video-item pb-3">
                            <video controls class="justified-video-player">
                                <source src="{{ asset('storage/turniri/' . $turnir->id . '/' . $medij->link) }}" type="video/mp4">
                                Vaš browser ne podržava video.
                            </video>
                        </div>
                    @endforeach
                </div>
            @endif
        @endif

        <div class="col-lg-12 ck-content">
            {!! $turnir->opis2 !!}
        </div>

    </div>
@endforeach

@once
    <style>
        .justified-gallery {
            display: flex;
            flex-wrap: wrap;
            align-items: flex-start;
            justify-content: space-between;
            gap: 8px;
            width: 100%;
            margin-left: auto;
            margin-right: auto;
        }
        .justified-gallery-item {
            height: 165px;
            flex: 0 0 auto;
            display: block;
            padding: 0 !important;
            margin: 0 !important;
            border: 0 !important;
            border-radius: 0;
            background: #dee2e6 !important;
            overflow: hidden;
            line-height: 0;
            text-decoration: none;
            box-sizing: border-box;
        }
        .justified-gallery-img {
            display: block;
            height: 100%;
            width: auto;
            max-width: none;
            object-fit: contain;
            background: transparent;
        }
        .justified-video-gallery {
            display: flex;
            flex-wrap: wrap;
            align-items: flex-start;
            justify-content: space-between;
            gap: 8px;
            width: 100%;
            margin-left: auto;
            margin-right: auto;
        }
        .justified-video-item {
            flex: 0 1 calc(33.333% - 6px);
            max-width: calc(33.333% - 6px);
            text-align: center;
        }
        .justified-video-player {
            display: block;
            width: 100%;
            aspect-ratio: 16 / 9;
            background: #000;
            border-radius: .4rem;
            border: 1px solid #dee2e6;
        }
        .galerija-slide-box {
            min-height: 70vh;
            padding: .5rem;
            background: #dee2e6;
            border-radius: .5rem;
        }
        .galerija-slide-img {
            max-height: 85vh;
            object-fit: contain;
        }
        @media (max-width: 767.98px) {
            .justified-gallery-item {
                height: 120px;
            }
            .justified-video-item {
                flex-basis: 100%;
                max-width: 100%;
            }
            .galerija-slide-box {
                min-height: 62vh;
            }
        }
        @media (min-width: 768px) and (max-width: 991.98px) {
            .justified-gallery-item {
                height: 135px;
            }
            .justified-video-item {
                flex-basis: calc(50% - 4px);
                max-width: calc(50% - 4px);
            }
        }
        @media (min-width: 992px) and (max-width: 1199.98px) {
            .justified-gallery-item {
                height: 150px;
            }
        }
    </style>
    <script>
        (function () {
            if (window.__skdGalleryInit) {
                return;
            }
            window.__skdGalleryInit = true;

            const postaviPocetnuSliku = (carouselElement, index) => {
                if (!carouselElement) {
                    return;
                }

                const slides = Array.from(carouselElement.querySelectorAll('.carousel-item'));
                if (slides.length === 0) {
                    return;
                }

                const safeIndex = Math.max(0, Math.min(index, slides.length - 1));

                if (window.bootstrap && window.bootstrap.Carousel) {
                    const existingInstance = window.bootstrap.Carousel.getInstance(carouselElement);
                    if (existingInstance) {
                        existingInstance.pause();
                        existingInstance.dispose();
                    }
                }

                slides.forEach((slide, i) => {
                    slide.classList.toggle('active', i === safeIndex);
                });

                const indicators = Array.from(carouselElement.querySelectorAll('.carousel-indicators button'));
                indicators.forEach((indicator, i) => {
                    const isActive = i === safeIndex;
                    indicator.classList.toggle('active', isActive);
                    if (isActive) {
                        indicator.setAttribute('aria-current', 'true');
                    } else {
                        indicator.removeAttribute('aria-current');
                    }
                });
            };

            document.addEventListener('show.bs.modal', function (event) {
                const modal = event.target.closest('.js-gallery-modal');
                if (!modal) {
                    return;
                }

                const trigger = event.relatedTarget;
                if (!trigger || !trigger.classList.contains('js-gallery-thumb')) {
                    return;
                }

                const carouselId = trigger.getAttribute('data-carousel-id') || '';
                if (!carouselId) {
                    return;
                }

                const carouselElement = document.getElementById(carouselId);
                if (!carouselElement) {
                    return;
                }

                const parsedIndex = parseInt(trigger.getAttribute('data-galerija-slide-to') || '0', 10);
                const startIndex = Number.isNaN(parsedIndex) ? 0 : parsedIndex;

                modal.dataset.startIndex = String(startIndex);
                modal.dataset.carouselId = carouselId;
                postaviPocetnuSliku(carouselElement, startIndex);
            });
        })();
    </script>
@endonce
