{{-- Kombinirani prikaz najnovijih rezultata turnira i članaka na naslovnici. --}}
@php
    use App\Models\Clanci;
    use Illuminate\Support\Facades\Storage;
@endphp

@foreach($stavkeRezultataIClanaka as $stavka)
    @if($stavka['tip'] === 'turnir')
        @include('admin.rezultati.prikazRezultata', ['turniri' => collect([$stavka['model']])])
    @else
        @php
            /** @var Clanci $clanak */
            $clanak = $stavka['model'];
            $medijiPreview = $clanak->mediji
                ->sortBy('id')
                ->filter(fn ($medij) =>
                    in_array($medij->vrsta, ['slika', 'video'], true)
                    && Storage::disk('public')->exists('clanci/' . $clanak->id . '/' . $medij->link)
                )
                ->values();
            $slikePreview = $medijiPreview->where('vrsta', 'slika')->take(12)->values();
            $videoPreview = $medijiPreview->firstWhere('vrsta', 'video');
        @endphp

        <div class="row justify-content-center mb-3 pt-3 shadow bg-white">
            <div class="col-lg-12 d-flex flex-wrap justify-content-between gap-2">
                <h3 class="fw-semibold mb-0">
                    <a class="homepage-clanak-title-link link-underline-light" href="{{ route('javno.clanci.prikaz_clanka', $clanak) }}">
                        {{ $clanak->naslov }}
                    </a>
                </h3>
                <p class="fw-normal mb-0 text-nowrap">{{ date('d.m.Y.', strtotime($clanak->datum)) }}</p>
            </div>

            <div class="col-lg-12 pt-2">
                <div class="homepage-clanak-preview ck-content">
                    {!! $clanak->sadrzaj !!}

                    @if($slikePreview->isNotEmpty())
                        <div class="homepage-clanak-media-grid mt-2">
                            @foreach($slikePreview as $medijSlike)
                                <img src="{{ asset('storage/clanci/' . $clanak->id . '/' . $medijSlike->link) }}"
                                     class="homepage-clanak-media-thumb"
                                     loading="lazy"
                                     alt="{{ $clanak->naslov }} - slika">
                            @endforeach
                        </div>
                    @elseif(!is_null($videoPreview))
                        <div class="homepage-clanak-media-wrap mt-2">
                            <video class="homepage-clanak-media-preview"
                                   muted
                                   autoplay
                                   loop
                                   playsinline
                                   preload="metadata"
                                   aria-label="{{ $clanak->naslov }} - video">
                                <source src="{{ asset('storage/clanci/' . $clanak->id . '/' . $videoPreview->link) }}" type="video/mp4">
                            </video>
                        </div>
                    @endif
                </div>
                <p class="homepage-readmore-wrap mb-0 mt-2 text-center">
                    <a class="homepage-readmore link-danger link-underline-opacity-0 link-underline-opacity-75-hover"
                       href="{{ route('javno.clanci.prikaz_clanka', $clanak) }}">
                        Više...
                    </a>
                </p>
            </div>
        </div>
    @endif
@endforeach

@once
    <style>
        .homepage-clanak-preview {
            position: relative;
            height: 10.5rem;
            overflow: hidden;
            padding-right: .25rem;
        }

        .homepage-clanak-preview * {
            pointer-events: none !important;
        }

        .homepage-clanak-preview img,
        .homepage-clanak-preview video,
        .homepage-clanak-preview iframe {
            max-width: 100%;
        }

        .homepage-clanak-media-wrap {
            overflow: hidden;
        }

        .homepage-clanak-media-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(82px, 1fr));
            gap: .5rem;
        }

        .homepage-clanak-media-thumb {
            display: block;
            width: 100%;
            aspect-ratio: 3 / 4;
            object-fit: cover;
            border-radius: .2rem;
        }

        .homepage-clanak-media-preview {
            display: block;
            width: 100%;
            height: auto;
            max-height: 18rem;
            object-fit: cover;
            border-radius: .2rem;
        }

        .homepage-clanak-preview::after {
            content: "";
            position: absolute;
            left: 0;
            right: 0;
            top: 50%;
            bottom: 0;
            background: linear-gradient(to bottom, rgba(255, 255, 255, 0) 0%, rgba(255, 255, 255, 0.82) 62%, #fff 100%);
            pointer-events: none;
        }

        .homepage-clanak-title-link {
            /*noinspection CssUnresolvedCustomProperty*/
            color: var(--bs-body-color);
            text-decoration-color: rgba(0, 0, 0, .18);
        }

        .theme-dark .homepage-clanak-title-link {
            /*noinspection CssUnresolvedCustomProperty*/
            color: var(--bs-body-color);
            text-decoration-color: rgba(233, 236, 239, .45);
        }

        .theme-dark .homepage-clanak-preview::after {
            background: linear-gradient(
                to bottom,
                rgba(31, 37, 44, 0) 0%,
                rgba(31, 37, 44, 0.82) 62%,
                #2b3035 100%
            );
        }

        .homepage-readmore {
            display: inline-block;
            font-size: 1.2rem;
            font-weight: 700;
            font-style: italic;
        }

        @media (max-width: 768px) {
            .homepage-clanak-media-grid {
                grid-template-columns: repeat(auto-fill, minmax(68px, 1fr));
                gap: .35rem;
            }
        }
    </style>
@endonce
