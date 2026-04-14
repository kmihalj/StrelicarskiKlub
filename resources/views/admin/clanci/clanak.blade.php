{{-- Sažeti prikaz članka unutar liste članaka. --}}
<div class="row justify-content-center p-2 shadow bg-white">
    <div class="col-lg-10 mt-3 mb-3">
        <h3 class="fw-semibold">
            <a class="link-dark link-underline-light" href="{{ route('javno.clanci.prikaz_clanka', $clanak) }}">{{$clanak->naslov}}</a>
        </h3>
    </div>
    <div class="col-lg-2 mt-3 mb-3 text-end align-self-center">
        <p class="fw-normal mb-0">{{ date('d.m.Y.', strtotime($clanak->datum)) }}
            @auth()
                @if(auth()->user()->rola <= 1)
                    <button type="submit" onclick="location.href='{{ route('admin.clanci.uredjivanje', $clanak->id) }}'" class="btn text-success btn-rounded"
                            title="Uređivanje">
                        @include('admin.SVG.uredi')
                    </button>
                @endif
            @endauth
        </p>
    </div>
    <div class="col-lg-12 ck-content">
        {!! $clanak->sadrzaj !!}
    </div>
    @php
        $slikeClanka = $clanak->mediji
            ->where('vrsta', 'slika')
            ->filter(fn ($medij) => Storage::disk('public')->exists('clanci/' . $clanak->id . '/' . $medij->link))
            ->values();
        $videoClanka = $clanak->mediji
            ->where('vrsta', 'video')
            ->filter(fn ($medij) => Storage::disk('public')->exists('clanci/' . $clanak->id . '/' . $medij->link))
            ->values();
        $prikaziMedijeIspod = (bool) $clanak->galerija;
    @endphp

    <div class="col-lg-12">
        @if($prikaziMedijeIspod && $slikeClanka->count() != 0)
            <div class="justified-gallery js-justified-gallery mt-2" data-row-height="165" data-gap="8">
                @foreach($slikeClanka as $i => $medij)
                    <button type="button"
                            class="btn justified-gallery-item js-gallery-thumb"
                            data-bs-toggle="modal"
                            data-bs-target="#GalerijaClanka{{$clanak->id}}"
                            data-galerija-slide-to="{{ $i }}"
                            data-carousel-id="carouselClanka{{$clanak->id}}">
                        <img src="{{ asset('storage/clanci/' . $clanak->id . '/' . $medij->link) }}"
                             class="justified-gallery-img"
                             loading="lazy"
                             alt="{{ $clanak->naslov }} - slika {{ $i + 1 }}">
                    </button>
                @endforeach
            </div>

            <div class="modal fade js-gallery-modal" id="GalerijaClanka{{$clanak->id}}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-fullscreen-md-down modal-xl modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header bg-danger">
                            <h4 class="modal-title text-white">{{ $clanak->naslov }}</h4>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body bg-secondary-subtle">
                            <div id="carouselClanka{{$clanak->id}}" class="carousel slide" data-bs-ride="false" data-bs-interval="false">
                                <div class="carousel-indicators">
                                    @foreach($slikeClanka as $i => $medij)
                                        <button type="button"
                                                data-bs-target="#carouselClanka{{$clanak->id}}"
                                                data-bs-slide-to="{{ $i }}"
                                                @if($i === 0) class="active" aria-current="true" @endif
                                                aria-label="Slika {{ $i + 1 }}"></button>
                                    @endforeach
                                </div>
                                <div class="carousel-inner">
                                    @foreach($slikeClanka as $i => $medij)
                                        <div class="carousel-item @if($i === 0) active @endif">
                                            <div class="d-flex align-items-center justify-content-center galerija-slide-box">
                                                <img src="{{ asset('storage/clanci/' . $clanak->id . '/' . $medij->link) }}"
                                                     class="d-block w-100 galerija-slide-img"
                                                     alt="{{ $clanak->naslov }} - slika {{ $i + 1 }}">
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                                <button class="carousel-control-prev" type="button" data-bs-target="#carouselClanka{{$clanak->id}}" data-bs-slide="prev">
                                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                    <span class="visually-hidden">Prethodna</span>
                                </button>
                                <button class="carousel-control-next" type="button" data-bs-target="#carouselClanka{{$clanak->id}}" data-bs-slide="next">
                                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                    <span class="visually-hidden">Sljedeća</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        @if($prikaziMedijeIspod && $videoClanka->count() != 0)
            <div class="justified-video-gallery pt-2">
                @foreach($videoClanka as $medij)
                    <div class="justified-video-item pb-3">
                        <video controls class="justified-video-player">
                            <source src="{{ asset('storage/clanci/' . $clanak->id . '/' . $medij->link) }}" type="video/mp4"/>
                            Vaš browser ne podržava video.
                        </video>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>

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
        @media (max-width: 767px) {
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
        @media (min-width: 768px) and (max-width: 991px) {
            .justified-gallery-item {
                height: 135px;
            }
            .justified-video-item {
                flex-basis: calc(50% - 4px);
                max-width: calc(50% - 4px);
            }
        }
        @media (min-width: 992px) and (max-width: 1199px) {
            .justified-gallery-item {
                height: 150px;
            }
        }
    </style>
    <script>
        (function () {
            const galleryWindow = /** @type {Window & { __skdGalleryInit?: boolean }} */ (window);

            if (galleryWindow.__skdGalleryInit) {
                return;
            }
            galleryWindow.__skdGalleryInit = true;

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
