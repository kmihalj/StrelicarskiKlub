{{-- Unos timskih rezultata: odabir članova tima, zbroj rezultata i plasman. --}}
@php
    // Samo administrator može mijenjati stanje "ima timove" i dodavati/brisati timske rezultate.
    $mozeUredivatiTimove = auth()->check() && (int)auth()->user()->rola === 1;
    $timovi = $turnir->relationLoaded('rezultatiTimovi') ? $turnir->rezultatiTimovi : collect();
    // Blok držimo otvorenim ako je turnir označen da ima timove ili već postoje spremljeni timovi.
    $prikazTimova = (bool)$turnir->ima_timove || ($timovi->count() > 0);
@endphp

<div class="row g-3">
    <div class="col-12">
        {{-- "Master switch" kojim turnir aktivira/deaktivira timski modul u obrascu. --}}
        <form id="timovi_aktivno_form" action="{{ route('admin.rezultati.timovi.aktivno', $turnir) }}" method="POST">
            @csrf
            <input type="hidden" name="ima_timove" value="0">
            <div class="form-check form-switch">
                <input class="form-check-input"
                       type="checkbox"
                       role="switch"
                       id="ima_timove"
                       name="ima_timove"
                       value="1"
                    @checked($turnir->ima_timove)
                    @if(!$mozeUredivatiTimove) disabled @endif
                    @if($mozeUredivatiTimove) onchange="this.form.submit()" @endif>
                <label class="form-check-label fw-bold" for="ima_timove">Turnir ima timske rezultate</label>
            </div>
        </form>
    </div>

    @if($prikazTimova)
        <div class="col-12">
            @if($timovi->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 border">
                        <thead class="table-warning">
                        <tr>
                            <th>Članovi</th>
                            <th>Stil</th>
                            <th>Kategorija</th>
                            <th>Rezultat</th>
                            <th>Plasman</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($timovi as $tim)
                            @php
                                // Sastavljamo tekst članova tima iz relacije stavki tima.
                                $naziviClanova = $tim->clanoviStavke
                                    ->filter(fn ($stavka) => $stavka->rezultatOpci !== null && $stavka->rezultatOpci->clan !== null)
                                    ->map(function ($stavka) {
                                        return trim((string)$stavka->rezultatOpci->clan->Prezime . ' ' . (string)$stavka->rezultatOpci->clan->Ime);
                                    })
                                    ->implode(', ');
                            @endphp
                            <tr @if((int)$tim->plasman <= 3) class="fw-bold" @endif>
                                <td>{{ $naziviClanova }}</td>
                                <td>{{ $tim->stil?->naziv ?? 'Mix / više stilova' }}</td>
                                <td>{{ $tim->kategorija?->naziv ?? 'Mix / više kategorija' }}</td>
                                <td>{{ $tim->rezultat }}</td>
                                <td>{{ $tim->plasman }}</td>
                                <td class="text-end">
                                    @if($mozeUredivatiTimove)
                                        <form id="tim_brisanje_{{ $tim->id }}" action="{{ route('admin.rezultati.timovi.brisanje', $tim->id) }}" method="POST">
                                            @csrf
                                        </form>
                                        <button type="submit"
                                                form="tim_brisanje_{{ $tim->id }}"
                                                class="btn text-danger btn-rounded"
                                                title="Obriši tim"
                                                onclick="return confirm('Da li ste sigurni da želite obrisati timski rezultat?')">
                                            @include('admin.SVG.obrisi')
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="mb-0">Nema unesenih timskih rezultata.</p>
            @endif
        </div>

        @if($mozeUredivatiTimove)
            <div class="col-12">
                <hr class="my-2">
                <p class="fw-bold mb-2">Dodavanje tima</p>
                {{-- Novi tim se formira iz već unesenih pojedinačnih rezultata na ovom turniru. --}}
                <form action="{{ route('admin.rezultati.timovi.spremi') }}" method="POST" class="row g-2">
                    @csrf
                    <input type="hidden" name="turnir_id" value="{{ $turnir->id }}">
                    <div class="col-lg-2">
                        <label for="plasman_tima" class="form-label">Plasman tima</label>
                        <input type="number"
                               min="1"
                               class="form-control"
                               id="plasman_tima"
                               name="plasman_tima"
                               value="{{ old('plasman_tima') }}"
                               required>
                    </div>
                    <div class="col-lg-12">
                        <label for="tim_clanovi" class="form-label">Članovi tima (odaberite 2 ili više)</label>
                        {{-- U selectu se svaka stavka opisuje član + stil + kategorija + pojedinačni rezultat,
                             kako bi administrator jasno vidio što kombinira u timski rezultat. --}}
                        <select class="form-select" id="tim_clanovi" name="tim_clanovi[]" multiple size="8" required>
                            @foreach($dostupniRezultatiZaTim as $stavka)
                                <option value="{{ $stavka['id'] }}" @selected(collect(old('tim_clanovi', []))->contains($stavka['id']))>
                                    {{ $stavka['clan'] }} | {{ $stavka['stil'] }} | {{ $stavka['kategorija'] }} | rezultat: {{ $stavka['rezultat'] }}
                                </option>
                            @endforeach
                        </select>
                        <small class="text-muted">Za višestruki odabir držite <code>Ctrl</code> (Windows/Linux) ili <code>Cmd</code> (Mac).</small>
                    </div>
                    <div class="col-12 text-end">
                        <button type="submit" class="btn btn-danger">Spremi tim</button>
                    </div>
                </form>
            </div>
        @endif
    @endif
</div>
