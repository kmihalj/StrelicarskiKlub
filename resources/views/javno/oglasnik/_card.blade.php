@php
    /** @var \App\Models\Oglas $oglas */
    $canManage = $canManage ?? false;
    $showStateBadge = $showStateBadge ?? false;
    $slike = $oglas->slike ?? collect();
    $carouselId = 'oglas-carousel-' . (int) $oglas->id;
    $autorNaziv = trim((string) (($oglas->clan?->Ime ?? '') . ' ' . ($oglas->clan?->Prezime ?? '')));
@endphp

<div class="card h-100 shadow-sm oglasnik-card">
    <div class="card-body d-flex flex-column">
        <div class="d-flex align-items-start justify-content-between gap-2 mb-2">
            <h5 class="card-title mb-0">{{ $oglas->naslov }}</h5>
            @if($canManage)
                <div class="d-inline-flex align-items-center gap-1 oglasnik-card-actions">
                    <a href="{{ route('javno.oglasnik.edit', $oglas) }}"
                       class="btn btn-sm oglasnik-icon-btn"
                       title="Uredi oglas"
                       aria-label="Uredi oglas">
                        @include('admin.SVG.uredi')
                    </a>

                    @if((bool) $oglas->is_active)
                        <form action="{{ route('javno.oglasnik.deactivate', $oglas) }}" method="POST" class="m-0">
                            @csrf
                            <button type="submit"
                                    class="btn btn-sm oglasnik-icon-btn oglasnik-icon-deactivate"
                                    title="Deaktiviraj oglas"
                                    aria-label="Deaktiviraj oglas"
                                    onclick="return confirm('Deaktivirati oglas?')">
                                <span aria-hidden="true">||</span>
                            </button>
                        </form>
                    @else
                        <form action="{{ route('javno.oglasnik.reactivate', $oglas) }}" method="POST" class="m-0">
                            @csrf
                            <button type="submit"
                                    class="btn btn-sm oglasnik-icon-btn oglasnik-icon-reactivate"
                                    title="Aktiviraj oglas"
                                    aria-label="Aktiviraj oglas">
                                <span aria-hidden="true">&#9654;</span>
                            </button>
                        </form>
                    @endif

                    <form action="{{ route('javno.oglasnik.destroy', $oglas) }}" method="POST" class="m-0">
                        @csrf
                        <button type="submit"
                                class="btn btn-sm oglasnik-icon-btn oglasnik-icon-delete"
                                title="Obriši oglas"
                                aria-label="Obriši oglas"
                                onclick="return confirm('Trajno obrisati oglas?')">
                            @include('admin.SVG.obrisi')
                        </button>
                    </form>
                </div>
            @endif
        </div>

        @if($showStateBadge)
            <div class="mb-2">
                @if((bool) $oglas->is_active)
                    <span class="badge bg-success">Aktivan</span>
                @else
                    <span class="badge bg-secondary">Deaktiviran</span>
                @endif
            </div>
        @endif

        @if($slike->count() > 0)
            <div id="{{ $carouselId }}" class="carousel slide mb-3 oglasnik-media" data-bs-ride="false">
                <div class="carousel-inner">
                    @foreach($slike as $slika)
                        <div class="carousel-item @if($loop->first) active @endif">
                            <img src="{{ asset('storage/' . $slika->putanja) }}"
                                 class="d-block w-100 oglasnik-image"
                                 alt="Slika oglasa {{ $loop->iteration }}">
                        </div>
                    @endforeach
                </div>
                @if($slike->count() > 1)
                    <button class="carousel-control-prev" type="button" data-bs-target="#{{ $carouselId }}" data-bs-slide="prev">
                        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                        <span class="visually-hidden">Prethodna</span>
                    </button>
                    <button class="carousel-control-next" type="button" data-bs-target="#{{ $carouselId }}" data-bs-slide="next">
                        <span class="carousel-control-next-icon" aria-hidden="true"></span>
                        <span class="visually-hidden">Sljedeća</span>
                    </button>
                @endif
            </div>
        @else
            <div class="oglasnik-image-placeholder oglasnik-media mb-3">
                Bez fotografija
            </div>
        @endif

        <div class="mb-2">
            <span class="fw-semibold">Cijena:</span>
            {{ number_format((float) $oglas->cijena, 2, ',', '.') }} €
        </div>

        <div class="small mb-2 oglasnik-opis">{!! $oglas->opis_html !!}</div>

        <div class="small mb-2">
            <span class="fw-semibold">Telefon:</span>
            <a href="tel:{{ $oglas->kontakt_telefon }}">{{ $oglas->kontakt_telefon }}</a>
        </div>
        @if(trim((string) $oglas->kontakt_email) !== '')
            <div class="small mb-3">
                <span class="fw-semibold">E-mail:</span>
                <a href="mailto:{{ $oglas->kontakt_email }}">{{ $oglas->kontakt_email }}</a>
            </div>
        @endif

        <div class="mt-auto d-flex justify-content-between align-items-end gap-2 oglasnik-meta">
            <a href="{{ route('javno.clanovi.prikaz_clana', (int) $oglas->clan_id) }}"
               class="small text-decoration-none">
                {{ $autorNaziv !== '' ? $autorNaziv : 'Član' }}
            </a>
            <div class="small text-muted text-end">
                {{ $oglas->created_at?->format('d.m.Y.') ?? '-' }}
            </div>
        </div>
    </div>
</div>
