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
                                            $label = $turnir->datum?->format('d.m.Y.') . ' - ' . $turnir->naziv . ' (' . $turnir->mjesto . ') - tip turnira: ' . $tipTurnira;
                                        @endphp
                                        <option value="{{ $turnir->id }}"
                                                data-label="{{ $label }}"
                                                data-zakljucan="{{ $locked ? '1' : '0' }}"
                                                data-kotizacija-nacin="{{ $turnir->kotizacija_nacin ?? 'undefined' }}"
                                                data-kotizacija-iznos="{{ $turnir->kotizacija_iznos ?? '' }}"
                                                data-kotizacija-rok="{{ $turnir->kotizacija_rok_uplate?->format('d.m.Y.') ?? '' }}"
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
                                            $label = $turnir->datum?->format('d.m.Y.') . ' - ' . $turnir->naziv . ' (' . $turnir->mjesto . ') - tip turnira: ' . $tipTurnira;
                                        @endphp
                                        <option value="{{ $turnir->id }}"
                                                data-label="{{ $label }}"
                                                data-zakljucan="{{ $locked ? '1' : '0' }}"
                                                data-kotizacija-nacin="{{ $turnir->kotizacija_nacin ?? 'undefined' }}"
                                                data-kotizacija-iznos="{{ $turnir->kotizacija_iznos ?? '' }}"
                                                data-kotizacija-rok="{{ $turnir->kotizacija_rok_uplate?->format('d.m.Y.') ?? '' }}"
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
                            <select class="form-select" id="kategorija_id" name="kategorija_id" required></select>
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
                        <div class="col-lg-4">
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

                        <div class="col-12">
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
                        <th>Smjena</th>
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
                            <td rowspan="{{ $rowspan }}" class="align-middle">{{ $prijava->turnir?->datum?->format('d.m.Y.') ?? '-' }}</td>
                            <td rowspan="{{ $rowspan }}" class="align-middle">{{ $prijava->clan?->Prezime }} {{ $prijava->clan?->Ime }}</td>
                            <td rowspan="{{ $rowspan }}" class="align-middle">{{ $prijava->turnir?->naziv ?? '-' }}</td>
                            <td>{{ $prijava->turnir?->mjesto ?? '-' }}</td>
                            <td>{{ $prijava->smjena ?: 'nebitno' }}</td>
                            <td>{{ $prijava->kategorija?->naziv ?? '-' }}</td>
                            <td>{{ $prijava->stil?->naziv ?? '-' }}</td>
                            <td>{{ $prijava->turnir?->tipTurnira?->naziv ?? '-' }}</td>
                            <td rowspan="{{ $rowspan }}" class="align-middle">
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
                            </td>
                            <td rowspan="{{ $rowspan }}" class="text-end align-middle">
                                <a href="{{ route('javno.prijave_turnira.show', $prijava) }}" class="btn btn-sm btn-outline-primary">Pregled</a>
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
            <div class="col-lg-12 text-white d-flex justify-content-between align-items-center">
                <span>Prošli turniri</span>
                <button class="btn btn-sm btn-outline-light" type="button" data-bs-toggle="collapse" data-bs-target="#prosli-turniri-collapse" aria-expanded="false" aria-controls="prosli-turniri-collapse">+</button>
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
                            <th>Smjena</th>
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
                                <td>{{ $prijava->turnir?->datum?->format('d.m.Y.') ?? '-' }}</td>
                                <td>{{ $prijava->clan?->Prezime }} {{ $prijava->clan?->Ime }}</td>
                                <td>{{ $prijava->turnir?->naziv ?? '-' }}</td>
                                <td>{{ $prijava->turnir?->mjesto ?? '-' }}</td>
                                <td>{{ $prijava->smjena ?: 'nebitno' }}</td>
                                <td>{{ $prijava->kategorija?->naziv ?? '-' }}</td>
                                <td>{{ $prijava->stil?->naziv ?? '-' }}</td>
                                <td>{{ $prijava->turnir?->tipTurnira?->naziv ?? '-' }}</td>
                                <td>
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
            const kotizacijaInfoWrap = document.getElementById('kotizacija-info-wrap');
            const kotizacijaInfoText = document.getElementById('kotizacija-info-text');
            const prijavljeniClanoviWrap = document.getElementById('prijavljeni-clanovi-wrap');
            const prijavljeniClanoviList = document.getElementById('prijavljeni-clanovi-list');

            if (!(turnirSelect instanceof HTMLSelectElement) || !(kategorijaSelect instanceof HTMLSelectElement)) {
                return;
            }

            const kategorijePoClanu = @json($kategorijePoClanu);
            const aktivnoPoClanuTurniru = @json($aktivnoPoClanuTurniru);
            const prijavljeniPoTurniru = @json($prijavljeniPoTurniru ?? []);

            function currentClanId() {
                if (clanSelect) {
                    return String(clanSelect.value || '');
                }
                if (clanHidden) {
                    return String(clanHidden.value || '');
                }
                return '';
            }

            function updateKategorije() {
                const clanId = currentClanId();
                const kategorije = Array.isArray(kategorijePoClanu[clanId]) ? kategorijePoClanu[clanId] : [];
                const oldValue = kategorijaSelect.value;
                kategorijaSelect.innerHTML = '';

                const defaultOption = document.createElement('option');
                defaultOption.value = '';
                defaultOption.textContent = 'Odaberi kategoriju';
                kategorijaSelect.appendChild(defaultOption);

                kategorije.forEach((kategorija) => {
                    const option = document.createElement('option');
                    option.value = String(kategorija.id || '');
                    option.textContent = String(kategorija.naziv || '');
                    if (oldValue && oldValue === option.value) {
                        option.selected = true;
                    }
                    kategorijaSelect.appendChild(option);
                });
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

            function updatePrijavljeniClanovi() {
                if (!prijavljeniClanoviWrap || !prijavljeniClanoviList) {
                    return;
                }

                const selected = turnirSelect.selectedOptions.length > 0 ? turnirSelect.selectedOptions[0] : null;
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
                    updateKategorije();
                    updateTurniri();
                    updatePrijavljeniClanovi();
                    updateKotizacijaInfo();
                });
            }

            turnirSelect.addEventListener('change', () => {
                updatePrijavljeniClanovi();
                updateKotizacijaInfo();
            });

            updateKategorije();
            updateTurniri();
            updatePrijavljeniClanovi();
            updateKotizacijaInfo();
        })();
    </script>
@endsection
