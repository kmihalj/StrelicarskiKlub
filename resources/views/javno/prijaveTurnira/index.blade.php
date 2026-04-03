{{-- Korisnički prikaz prijava na nadolazeće turnire (član/roditelj). --}}
@extends('layouts.app')

@section('content')
    <div class="container-xxl bg-white shadow mb-3">
        <div class="row justify-content-center p-2 shadow bg-danger fw-bolder">
            <div class="col-lg-12 text-white">
                Prijave na turnire (dostupni turniri u narednih {{ (int) $daniVidljivosti }} dana)
            </div>
        </div>
        <div class="row p-3">
            @if($clanoviZaPrijavu->count() === 0)
                <div class="col-12">
                    <div class="alert alert-warning mb-0">
                        Trenutni korisnički račun nema člana kojeg je moguće prijaviti na turnir.
                    </div>
                </div>
            @else
                @php
                    $odabraniClanId = (int) old('clan_id', $zadaniClanId ?? 0);
                    if ($odabraniClanId <= 0) {
                        $odabraniClanId = (int) ($clanoviZaPrijavu->first()->id ?? 0);
                    }
                @endphp
                <div class="col-12">
                    <form action="{{ route('javno.prijave_turnira.store') }}" method="POST" class="row g-3" id="prijava-turnira-form">
                        @csrf

                        @if($prikaziOdabirClana)
                            <div class="col-lg-4">
                                <label for="clan_id" class="form-label fw-semibold mb-1">Odabir člana</label>
                                <select class="form-select" id="clan_id" name="clan_id" required>
                                    @foreach($clanoviZaPrijavu as $clan)
                                        <option value="{{ $clan->id }}" @selected($odabraniClanId === (int) $clan->id)>
                                            {{ $clan->Prezime }} {{ $clan->Ime }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-lg-8">
                                <label for="nadolazeci_turnir_id" class="form-label fw-semibold mb-1">Odabir turnira</label>
                                <select class="form-select" id="nadolazeci_turnir_id" name="nadolazeci_turnir_id" required>
                                    <option value="">Odaberi turnir</option>
                                    @foreach($dostupniTurniri as $turnir)
                                        @php
                                            $locked = $turnir->prijaveZakljucane();
                                            $tipTurnira = $turnir->tipTurnira?->naziv ?? '-';
                                            $label = $turnir->datumRasponLabel() . ' - ' . $turnir->naziv . ' (' . $turnir->mjesto . ') - tip turnira: ' . $tipTurnira;
                                            $visednevni = $turnir->datum && $turnir->datum_do && $turnir->datum_do->gt($turnir->datum);
                                        @endphp
                                        <option value="{{ $turnir->id }}"
                                                data-label="{{ $label }}"
                                                data-zakljucan="{{ $locked ? '1' : '0' }}"
                                                data-kotizacija-nacin="{{ $turnir->kotizacija_nacin ?? 'undefined' }}"
                                                data-kotizacija-iznos="{{ $turnir->kotizacija_iznos ?? '' }}"
                                                data-kotizacija-rok="{{ $turnir->kotizacija_rok_uplate?->format('d.m.Y.') ?? '' }}"
                                                data-datum="{{ $turnir->datum?->toDateString() ?? '' }}"
                                                data-datum-do="{{ $turnir->datum_do?->toDateString() ?? '' }}"
                                                data-visednevni="{{ $visednevni ? '1' : '0' }}"
                                            @selected((int) old('nadolazeci_turnir_id') === (int) $turnir->id)
                                            @disabled($locked)>
                                            {{ $label }}@if($locked) - zaključano @endif
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @else
                            @php
                                $clan = $clanoviZaPrijavu->first();
                            @endphp
                            <div class="col-lg-4">
                                <label class="form-label fw-semibold mb-1">Prijavljuje se član</label>
                                <input type="text" class="form-control" value="{{ $clan?->Prezime }} {{ $clan?->Ime }}" disabled>
                                <input type="hidden" id="clan_id_hidden" name="clan_id" value="{{ (int) ($clan?->id ?? 0) }}">
                            </div>
                            <div class="col-lg-8">
                                <label for="nadolazeci_turnir_id" class="form-label fw-semibold mb-1">Odabir turnira</label>
                                <select class="form-select" id="nadolazeci_turnir_id" name="nadolazeci_turnir_id" required>
                                    <option value="">Odaberi turnir</option>
                                    @foreach($dostupniTurniri as $turnir)
                                        @php
                                            $locked = $turnir->prijaveZakljucane();
                                            $tipTurnira = $turnir->tipTurnira?->naziv ?? '-';
                                            $label = $turnir->datumRasponLabel() . ' - ' . $turnir->naziv . ' (' . $turnir->mjesto . ') - tip turnira: ' . $tipTurnira;
                                            $visednevni = $turnir->datum && $turnir->datum_do && $turnir->datum_do->gt($turnir->datum);
                                        @endphp
                                        <option value="{{ $turnir->id }}"
                                                data-label="{{ $label }}"
                                                data-zakljucan="{{ $locked ? '1' : '0' }}"
                                                data-kotizacija-nacin="{{ $turnir->kotizacija_nacin ?? 'undefined' }}"
                                                data-kotizacija-iznos="{{ $turnir->kotizacija_iznos ?? '' }}"
                                                data-kotizacija-rok="{{ $turnir->kotizacija_rok_uplate?->format('d.m.Y.') ?? '' }}"
                                                data-datum="{{ $turnir->datum?->toDateString() ?? '' }}"
                                                data-datum-do="{{ $turnir->datum_do?->toDateString() ?? '' }}"
                                                data-visednevni="{{ $visednevni ? '1' : '0' }}"
                                            @selected((int) old('nadolazeci_turnir_id') === (int) $turnir->id)
                                            @disabled($locked)>
                                            {{ $label }}@if($locked) - zaključano @endif
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        <div class="col-lg-4">
                            <label for="kategorija_id" class="form-label fw-semibold mb-1">Kategorija</label>
                            <select class="form-select" id="kategorija_id" name="kategorija_id"
                                    data-old-value="{{ old('kategorija_id', '') }}" required></select>
                        </div>
                        <div class="col-lg-4">
                            <label for="stil_id" class="form-label fw-semibold mb-1">Stil luka</label>
                            <select class="form-select" id="stil_id" name="stil_id" required>
                                <option value="">Odaberi stil</option>
                                @foreach($stilovi as $stil)
                                    <option value="{{ $stil->id }}" @selected((int) old('stil_id') === (int) $stil->id)>
                                        {{ $stil->naziv }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-lg-4" id="smjena-wrap">
                            <label for="smjena" class="form-label fw-semibold mb-1">Smjena</label>
                            <select class="form-select" id="smjena" name="smjena">
                                @foreach($smjeneOpcije as $smjenaOpcija)
                                    <option value="{{ $smjenaOpcija }}" @selected(old('smjena', 'nebitno') === $smjenaOpcija)>
                                        {{ ucfirst($smjenaOpcija) }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-text">*ako ima smjena</div>
                        </div>
                        <div class="col-lg-4 d-none" id="odabrani-dan-wrap">
                            <label for="odabrani_dan" class="form-label fw-semibold mb-1">Odabir dana</label>
                            <select class="form-select" id="odabrani_dan" name="odabrani_dan" data-old-value="{{ old('odabrani_dan', '') }}"></select>
                            <div class="form-text">*dan može ovisiti o kategoriji i stilu, provjerite poziv...</div>
                        </div>
                        <div class="col-lg-4">
                            <label for="obrok" class="form-label fw-semibold mb-1">Obrok</label>
                            <select class="form-select" id="obrok" name="obrok">
                                @foreach($obrokOpcije as $obrokVrijednost => $obrokLabel)
                                    <option value="{{ $obrokVrijednost }}" @selected(old('obrok', \App\Models\PrijavaTurnira::OBROK_NE) === $obrokVrijednost)>
                                        {{ $obrokLabel }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-text">* ako je obrok osiguran</div>
                        </div>
                        <div class="col-lg-8">
                            <label for="napomena_clana" class="form-label fw-semibold mb-1">Napomena</label>
                            <input type="text"
                                   class="form-control"
                                   id="napomena_clana"
                                   name="napomena_clana"
                                   value="{{ old('napomena_clana') }}"
                                   maxlength="255"
                                   placeholder="Kratka napomena (nije obavezno)">
                        </div>

                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="sudjelujem_u_kupu" name="sudjelujem_u_kupu" value="1"
                                    @checked((bool) old('sudjelujem_u_kupu'))>
                                <label class="form-check-label" for="sudjelujem_u_kupu">
                                    Sudjelujem u natjecanju za KUP
                                </label>
                            </div>
                            <div class="form-text">*ako se boduje za KUP</div>
                        </div>

                        <div class="col-12 d-none" id="prijavljeni-clanovi-wrap">
                            <div class="alert alert-light border mb-0 py-2">
                                <div class="fw-semibold">Prijavljeni članovi na odabrani turnir</div>
                                <div class="small mt-1" id="prijavljeni-clanovi-list"></div>
                            </div>
                        </div>

                        <div class="col-12" id="kotizacija-info-wrap">
                            <div class="alert alert-warning mb-0 py-2" id="kotizacija-info-text"></div>
                        </div>

                        <div class="col-12 d-flex flex-wrap align-items-center gap-2">
                            <button type="submit" class="btn btn-danger">Prijavi turnir</button>
                        </div>
                    </form>
                </div>
            @endif
        </div>
    </div>

    <div class="container-xxl bg-white shadow mb-3">
        <div class="row justify-content-center p-2 shadow bg-danger fw-bolder">
            <div class="col-lg-12 text-white">Moje prijave na turnire</div>
        </div>
        <div class="row p-3">
            <div class="col-12 table-responsive">
                <table class="table table-hover align-middle mb-0 border">
                    <thead class="table-warning">
                    <tr>
                        <th>Datum</th>
                        <th>Član</th>
                        <th>Naziv</th>
                        <th>Mjesto</th>
                        <th>Smjena / dan</th>
                        <th>Kategorija</th>
                        <th>Stil</th>
                        <th>Tip turnira</th>
                        <th>Kotizacija</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($aktivnePrijave as $prijava)
                        @php
                            $warning = $lijecnickiUpozorenja[(int) $prijava->id] ?? null;
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
                        @endphp
                        @php
                            $imaWarning = !empty($warning);
                            $rowspan = $imaWarning ? 2 : 1;
                        @endphp
                        <tr @class(['table-danger' => $imaWarning])>
                            <td rowspan="{{ $rowspan }}" class="align-middle">{{ $prijava->datumTurniraZaPrikazLabel() }}</td>
                            <td rowspan="{{ $rowspan }}" class="align-middle">{{ $prijava->clan?->Prezime }} {{ $prijava->clan?->Ime }}</td>
                            <td rowspan="{{ $rowspan }}" class="align-middle">{{ $prijava->turnir?->naziv ?? '-' }}</td>
                            <td>{{ $prijava->turnir?->mjesto ?? '-' }}</td>
                            <td>{{ $prijava->terminPrijaveLabel() }}</td>
                            <td>{{ $prijava->kategorija?->naziv ?? '-' }}</td>
                            <td>{{ $prijava->stil?->naziv ?? '-' }}</td>
                            <td>{{ $prijava->turnir?->tipTurnira?->naziv ?? '-' }}</td>
                            <td rowspan="{{ $rowspan }}" class="align-middle">
                                @if($nacinKotizacije === 'bank')
                                    @if($jePlaceno)
                                        <span class="badge bg-success align-middle">
                                            Plaćeno
                                            @if($iznosKotizacije !== null)
                                                : {{ number_format((float)$iznosKotizacije, 2, ',', '.') }} €
                                            @endif
                                        </span>
                                    @elseif($nijePlaceno && $urlPlacanja)
                                        <a href="{{ $urlPlacanja }}" class="badge bg-danger text-white text-decoration-none align-middle">
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
                            </td>
                            <td rowspan="{{ $rowspan }}" class="text-end align-middle">
                                <a href="{{ route('javno.prijave_turnira.show', $prijava) }}" class="btn btn-sm btn-primary">Pregled</a>
                            </td>
                        </tr>
                        @if($imaWarning)
                            <tr class="table-danger">
                                <td colspan="5" class="small text-danger py-1">{{ $warning }}</td>
                            </tr>
                        @endif
                    @empty
                        <tr>
                            <td colspan="10" class="text-center">Nema aktivnih prijava.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="container-xxl bg-white shadow mb-3">
        <div class="row justify-content-center p-2 shadow bg-danger fw-bolder">
            <div class="col-lg-12 text-white d-flex flex-wrap justify-content-between align-items-center gap-2">
                <span class="mb-0">Prošli turniri</span>
                <button class="btn btn-sm btn-light" type="button" data-bs-toggle="collapse" data-bs-target="#prosli-turniri-collapse" aria-expanded="false" aria-controls="prosli-turniri-collapse">+</button>
            </div>
        </div>
        <div class="collapse" id="prosli-turniri-collapse">
            <div class="row p-3">
                <div class="col-12 table-responsive">
                    <table class="table table-hover align-middle mb-0 border">
                        <thead class="table-warning">
                        <tr>
                            <th>Datum</th>
                            <th>Član</th>
                            <th>Naziv</th>
                            <th>Mjesto</th>
                            <th>Smjena / dan</th>
                            <th>Kategorija</th>
                            <th>Stil</th>
                            <th>Tip turnira</th>
                            <th>Kotizacija</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse(($proslePrijave ?? collect()) as $prijava)
                            @php
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
                            @endphp
                            <tr>
                                <td>{{ $prijava->datumTurniraZaPrikazLabel() }}</td>
                                <td>{{ $prijava->clan?->Prezime }} {{ $prijava->clan?->Ime }}</td>
                                <td>{{ $prijava->turnir?->naziv ?? '-' }}</td>
                                <td>{{ $prijava->turnir?->mjesto ?? '-' }}</td>
                                <td>{{ $prijava->terminPrijaveLabel() }}</td>
                                <td>{{ $prijava->kategorija?->naziv ?? '-' }}</td>
                                <td>{{ $prijava->stil?->naziv ?? '-' }}</td>
                                <td>{{ $prijava->turnir?->tipTurnira?->naziv ?? '-' }}</td>
                                <td>
                                    @if($nacinKotizacije === 'bank')
                                        @if($jePlaceno)
                                            <span class="badge bg-success align-middle">
                                                Plaćeno
                                                @if($iznosKotizacije !== null)
                                                    : {{ number_format((float)$iznosKotizacije, 2, ',', '.') }} €
                                                @endif
                                            </span>
                                        @elseif($nijePlaceno && $urlPlacanja)
                                            <a href="{{ $urlPlacanja }}" class="badge bg-danger text-white text-decoration-none align-middle">
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
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center">Nema prošlih prijava.</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const clanSelectElement = document.getElementById('clan_id');
            const clanSelect = clanSelectElement instanceof HTMLSelectElement ? clanSelectElement : null;
            const clanHiddenElement = document.getElementById('clan_id_hidden');
            const clanHidden = clanHiddenElement instanceof HTMLInputElement ? clanHiddenElement : null;
            const turnirSelectElement = document.getElementById('nadolazeci_turnir_id');
            const turnirSelect = turnirSelectElement instanceof HTMLSelectElement ? turnirSelectElement : null;
            const kategorijaSelectElement = document.getElementById('kategorija_id');
            const kategorijaSelect = kategorijaSelectElement instanceof HTMLSelectElement ? kategorijaSelectElement : null;
            const smjenaWrap = document.getElementById('smjena-wrap');
            const smjenaSelectElement = document.getElementById('smjena');
            const smjenaSelect = smjenaSelectElement instanceof HTMLSelectElement ? smjenaSelectElement : null;
            const odabraniDanWrap = document.getElementById('odabrani-dan-wrap');
            const odabraniDanSelectElement = document.getElementById('odabrani_dan');
            const odabraniDanSelect = odabraniDanSelectElement instanceof HTMLSelectElement ? odabraniDanSelectElement : null;
            const kotizacijaInfoWrap = document.getElementById('kotizacija-info-wrap');
            const kotizacijaInfoText = document.getElementById('kotizacija-info-text');
            const prijavljeniClanoviWrap = document.getElementById('prijavljeni-clanovi-wrap');
            const prijavljeniClanoviList = document.getElementById('prijavljeni-clanovi-list');

            if (!(turnirSelect instanceof HTMLSelectElement) || !(kategorijaSelect instanceof HTMLSelectElement)) {
                return;
            }

            const kategorijePoClanu = @json($kategorijePoClanu);
            const clanoviMetaZaKategoriju = @json($clanoviMetaZaKategoriju ?? []);
            const aktivnoPoClanuTurniru = @json($aktivnoPoClanuTurniru);
            const prijavljeniPoTurniru = @json($prijavljeniPoTurniru ?? []);
            const oldKategorijaId = String(kategorijaSelect.dataset.oldValue || '');
            const oldOdabraniDan = odabraniDanSelect instanceof HTMLSelectElement
                ? String(odabraniDanSelect.dataset.oldValue || '')
                : '';
            let inicijalnaKategorijaPrimijenjena = false;
            let inicijalniOdabraniDanPrimijenjen = false;

            function currentClanId() {
                if (clanSelect) {
                    return String(clanSelect.value || '');
                }
                if (clanHidden) {
                    return String(clanHidden.value || '');
                }
                return '';
            }

            function selectedTurnirOption() {
                if (turnirSelect.selectedOptions.length === 0) {
                    return null;
                }

                const option = turnirSelect.selectedOptions[0];

                return option instanceof HTMLOptionElement ? option : null;
            }

            function parseIsoDate(value) {
                const tekst = String(value || '').trim();
                if (!/^\d{4}-\d{2}-\d{2}$/.test(tekst)) {
                    return null;
                }

                const [godina, mjesec, dan] = tekst.split('-').map(Number);
                const datum = new Date(godina, mjesec - 1, dan);
                if (Number.isNaN(datum.getTime())) {
                    return null;
                }

                return datum;
            }

            function formatDateLabel(value) {
                const datum = parseIsoDate(value);
                if (!(datum instanceof Date)) {
                    return '';
                }

                const dan = String(datum.getDate()).padStart(2, '0');
                const mjesec = String(datum.getMonth() + 1).padStart(2, '0');
                const godina = String(datum.getFullYear());

                return `${dan}.${mjesec}.${godina}.`;
            }

            function referentniDatumZaDob() {
                const selected = selectedTurnirOption();
                if (selected && selected.value !== '') {
                    const datumTurnira = parseIsoDate(selected.dataset.datum || '');
                    if (datumTurnira instanceof Date) {
                        return datumTurnira;
                    }
                }

                return new Date();
            }

            function izracunajDob(datumRodjenja, referentniDatum) {
                const rodjenje = parseIsoDate(datumRodjenja);
                if (!(rodjenje instanceof Date) || !(referentniDatum instanceof Date)) {
                    return null;
                }

                let dob = referentniDatum.getFullYear() - rodjenje.getFullYear();
                const jeRodendanProsao =
                    referentniDatum.getMonth() > rodjenje.getMonth()
                    || (
                        referentniDatum.getMonth() === rodjenje.getMonth()
                        && referentniDatum.getDate() >= rodjenje.getDate()
                    );
                if (!jeRodendanProsao) {
                    dob -= 1;
                }

                return dob >= 0 ? dob : null;
            }

            function ciljnaKategorijaOznaka(clanId) {
                const meta = clanoviMetaZaKategoriju[clanId];
                if (typeof meta !== 'object' || meta === null) {
                    return '';
                }

                const spol = String(meta.spol || '');
                const dob = izracunajDob(meta.datum_rodjenja, referentniDatumZaDob());
                if (dob === null) {
                    return '';
                }

                if (spol === 'M') {
                    if (dob <= 12) return 'U13M';
                    if (dob <= 14) return 'U15M';
                    if (dob <= 18) return 'U18M';
                    if (dob <= 21) return 'U21M';
                    if (dob <= 50) return 'M';

                    return 'M50+';
                }

                if (spol === 'Z') {
                    if (dob <= 12) return 'U13W';
                    if (dob <= 14) return 'U15W';
                    if (dob <= 18) return 'U18W';
                    if (dob <= 21) return 'U21W';
                    if (dob <= 50) return 'W';

                    return 'W50+';
                }

                return '';
            }

            function preporucenaKategorijaId(clanId, kategorije) {
                const trazenaOznaka = ciljnaKategorijaOznaka(clanId);
                if (trazenaOznaka === '' || !Array.isArray(kategorije)) {
                    return '';
                }

                const trazeniKod = `(${trazenaOznaka})`.toUpperCase();
                for (const kategorija of kategorije) {
                    const naziv = String(kategorija?.naziv || '').toUpperCase();
                    if (naziv.includes(trazeniKod)) {
                        return String(kategorija?.id || '');
                    }
                }

                return '';
            }

            function updateKategorije(forceAutoSelection = false) {
                const clanId = currentClanId();
                const kategorije = Array.isArray(kategorijePoClanu[clanId]) ? kategorijePoClanu[clanId] : [];
                const oldValue = forceAutoSelection ? '' : String(kategorijaSelect.value || '');
                const preporucenaId = preporucenaKategorijaId(clanId, kategorije);
                kategorijaSelect.innerHTML = '';

                const defaultOption = document.createElement('option');
                defaultOption.value = '';
                defaultOption.textContent = 'Odaberi kategoriju';
                kategorijaSelect.appendChild(defaultOption);

                let odabrano = false;
                kategorije.forEach((kategorija) => {
                    const option = document.createElement('option');
                    option.value = String(kategorija.id || '');
                    option.textContent = String(kategorija.naziv || '');
                    if (oldValue !== '' && oldValue === option.value) {
                        option.selected = true;
                        odabrano = true;
                    } else if (!inicijalnaKategorijaPrimijenjena && oldKategorijaId !== '' && oldKategorijaId === option.value) {
                        option.selected = true;
                        odabrano = true;
                    } else if (oldValue === '' && preporucenaId !== '' && preporucenaId === option.value) {
                        option.selected = true;
                        odabrano = true;
                    }
                    kategorijaSelect.appendChild(option);
                });

                if (!odabrano) {
                    defaultOption.selected = true;
                }

                inicijalnaKategorijaPrimijenjena = true;
            }

            function formatTurnirOption(option, alreadyRegistered) {
                const baseLabel = option.dataset.label || option.textContent || '';
                const locked = option.dataset.zakljucan === '1';
                option.disabled = locked || alreadyRegistered;
                if (locked) {
                    option.textContent = baseLabel + ' - zaključano';
                    return;
                }
                if (alreadyRegistered) {
                    option.textContent = baseLabel + ' - već prijavljen';
                    return;
                }
                option.textContent = baseLabel;
            }

            function updateTurniri() {
                const clanId = currentClanId();
                const prijavljeniTurniri = Array.isArray(aktivnoPoClanuTurniru[clanId]) ? aktivnoPoClanuTurniru[clanId].map(String) : [];
                let selectedStillAvailable = false;

                Array.from(turnirSelect.options).forEach((option, index) => {
                    if (index === 0) {
                        return;
                    }
                    const turnirId = String(option.value || '');
                    const alreadyRegistered = prijavljeniTurniri.includes(turnirId);
                    formatTurnirOption(option, alreadyRegistered);
                    if (turnirId !== '' && turnirId === turnirSelect.value && !option.disabled) {
                        selectedStillAvailable = true;
                    }
                });

                if (!selectedStillAvailable) {
                    turnirSelect.value = '';
                }
            }

            function postaviDostupneDaneZaTurnir() {
                if (!(smjenaWrap instanceof HTMLElement) || !(smjenaSelect instanceof HTMLSelectElement)
                    || !(odabraniDanWrap instanceof HTMLElement) || !(odabraniDanSelect instanceof HTMLSelectElement)) {
                    return;
                }

                const selected = selectedTurnirOption();
                const imaTurnir = selected instanceof HTMLOptionElement && selected.value !== '';
                const jeVisednevni = imaTurnir && selected.dataset.visednevni === '1';

                if (!jeVisednevni) {
                    smjenaWrap.classList.remove('d-none');
                    smjenaSelect.disabled = false;
                    odabraniDanWrap.classList.add('d-none');
                    odabraniDanSelect.disabled = true;
                    odabraniDanSelect.innerHTML = '';

                    return;
                }

                smjenaWrap.classList.add('d-none');
                smjenaSelect.disabled = true;
                odabraniDanWrap.classList.remove('d-none');
                odabraniDanSelect.disabled = false;

                const start = String(selected.dataset.datum || '');
                const end = String(selected.dataset.datumDo || '');
                const previousValue = String(odabraniDanSelect.value || '');
                odabraniDanSelect.innerHTML = '';

                const nebitnoOption = document.createElement('option');
                nebitnoOption.value = '';
                nebitnoOption.textContent = 'Nije bitno';
                odabraniDanSelect.appendChild(nebitnoOption);

                const vrijednosti = [];
                if (start !== '') {
                    vrijednosti.push(start);
                }
                if (end !== '' && end !== start) {
                    vrijednosti.push(end);
                }

                let odabrano = false;
                vrijednosti.forEach((vrijednost) => {
                    const option = document.createElement('option');
                    option.value = vrijednost;
                    option.textContent = formatDateLabel(vrijednost) || vrijednost;
                    if (previousValue !== '' && previousValue === option.value) {
                        option.selected = true;
                        odabrano = true;
                    } else if (!inicijalniOdabraniDanPrimijenjen && oldOdabraniDan !== '' && oldOdabraniDan === option.value) {
                        option.selected = true;
                        odabrano = true;
                    }
                    odabraniDanSelect.appendChild(option);
                });

                if (!odabrano) {
                    nebitnoOption.selected = true;
                }

                inicijalniOdabraniDanPrimijenjen = true;
            }

            function updatePrijavljeniClanovi() {
                if (!prijavljeniClanoviWrap || !prijavljeniClanoviList) {
                    return;
                }

                const selected = selectedTurnirOption();
                const hasTurnir = selected instanceof HTMLOptionElement && selected.value !== '';
                if (!hasTurnir) {
                    prijavljeniClanoviWrap.classList.add('d-none');
                    prijavljeniClanoviList.textContent = '';

                    return;
                }

                const turnirId = String(selected.value || '');
                const prijavljeni = Array.isArray(prijavljeniPoTurniru[turnirId]) ? prijavljeniPoTurniru[turnirId] : [];
                prijavljeniClanoviList.textContent = '';

                if (prijavljeni.length === 0) {
                    prijavljeniClanoviList.textContent = 'Za ovaj turnir još nema prijavljenih članova.';
                    prijavljeniClanoviWrap.classList.remove('d-none');

                    return;
                }

                prijavljeni.forEach((stavka, index) => {
                    const naziv = String(stavka.naziv || '');
                    const url = String(stavka.url || '');
                    const link = document.createElement('a');
                    link.className = 'link-primary text-decoration-none';
                    link.textContent = naziv;
                    link.href = url;
                    prijavljeniClanoviList.appendChild(link);

                    if (index < prijavljeni.length - 1) {
                        prijavljeniClanoviList.appendChild(document.createTextNode(', '));
                    }
                });

                prijavljeniClanoviWrap.classList.remove('d-none');
            }

            function updateKotizacijaInfo() {
                const selected = turnirSelect.selectedOptions.length > 0 ? turnirSelect.selectedOptions[0] : null;
                const hasTurnir = selected instanceof HTMLOptionElement && selected.value !== '';
                const nacinKotizacije = hasTurnir ? (selected.dataset.kotizacijaNacin || 'undefined') : 'undefined';
                const iznos = hasTurnir ? (selected.dataset.kotizacijaIznos || '') : '';
                const rok = hasTurnir ? (selected.dataset.kotizacijaRok || '') : '';

                if (!kotizacijaInfoWrap || !kotizacijaInfoText) {
                    return;
                }

                kotizacijaInfoWrap.classList.remove('d-none');
                if (!hasTurnir) {
                    kotizacijaInfoText.textContent = 'Odaberite turnir za prikaz informacija o kotizaciji.';
                    return;
                }

                if (nacinKotizacije === 'bank') {
                    if (iznos !== '') {
                        kotizacijaInfoText.textContent = 'Kotizacija se plaća preko računa kluba u iznosu '
                            + iznos + ' EUR' + (rok !== '' ? '. Rok uplate: ' + rok + '.' : '.');
                        return;
                    }

                    kotizacijaInfoText.textContent = 'Plaćanje preko računa kluba je odabrano, ali iznos kotizacije još nije definiran.'
                        + (rok !== '' ? ' Rok uplate: ' + rok + '.' : '');
                    return;
                }

                if (nacinKotizacije === 'cash') {
                    kotizacijaInfoText.textContent = iznos !== ''
                        ? 'Kotizacija se plaća gotovinom u iznosu ' + iznos + ' EUR.'
                        : 'Kotizacija se plaća gotovinom. Iznos kotizacije još nije definiran.';
                    return;
                }

                kotizacijaInfoText.textContent = 'Način i iznos kotizacije još nisu definirani.';
            }

            if (clanSelect) {
                clanSelect.addEventListener('change', () => {
                    updateTurniri();
                    postaviDostupneDaneZaTurnir();
                    updateKategorije(true);
                    updatePrijavljeniClanovi();
                    updateKotizacijaInfo();
                });
            }

            turnirSelect.addEventListener('change', () => {
                postaviDostupneDaneZaTurnir();
                updateKategorije(true);
                updatePrijavljeniClanovi();
                updateKotizacijaInfo();
            });

            updateTurniri();
            postaviDostupneDaneZaTurnir();
            updateKategorije(true);
            updatePrijavljeniClanovi();
            updateKotizacijaInfo();
        })();
    </script>
@endsection
