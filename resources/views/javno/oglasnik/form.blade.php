{{-- Forma za predaju/uređivanje oglasa u oglasniku. --}}
@extends('layouts.app')

@section('content')
    @include('javno.oglasnik._styles')

    @php
        $isEdit = $mode === 'edit' && $oglas !== null;
        $odabraniClanId = (int) old('clan_id', (int) ($odabraniClanId ?? 0));
        $kontaktDefault = $kontaktPoClanu[$odabraniClanId] ?? ['telefon' => '', 'email' => ''];
        $brojPostojecihSlika = $isEdit ? (int) ($oglas->slike->count()) : 0;
        $preostaloSlika = max((int) $maxSlike - $brojPostojecihSlika, 0);
        $defaultCijena = $isEdit ? number_format((float) $oglas->cijena, 2, ',', '.') : '';
    @endphp

    <div class="container-xxl bg-white shadow mb-3">
        <div class="row justify-content-center p-2 shadow bg-danger fw-bolder">
            <div class="col-lg-12 text-white d-flex flex-wrap justify-content-between align-items-center gap-2">
                <span>{{ $isEdit ? 'Uređivanje oglasa' : 'Predaja oglasa' }}</span>
                <a href="{{ route('javno.oglasnik.index') }}" class="btn btn-sm btn-light">Povratak</a>
            </div>
        </div>

        <div class="row p-3">
            <div class="col-12">
                @if($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ $isEdit ? route('javno.oglasnik.update', $oglas) : route('javno.oglasnik.store') }}"
                      method="POST"
                      enctype="multipart/form-data"
                      class="row g-3"
                      id="oglas-form">
                    @csrf

                    @if($mozeOdabratiClana)
                        <div class="col-lg-6">
                            <label for="clan_id" class="form-label fw-semibold mb-1">Objava u ime člana</label>
                            <select class="form-select @error('clan_id') is-invalid @enderror" id="clan_id" name="clan_id" required>
                                @foreach($clanoviZaObjavu as $clan)
                                    <option value="{{ (int) $clan->id }}"
                                            data-telefon="{{ trim((string) ($clan->br_telefona ?? '')) }}"
                                            data-email="{{ trim((string) ($clan->email ?? '')) }}"
                                            @selected($odabraniClanId === (int) $clan->id)>
                                        {{ trim((string) $clan->Prezime) }} {{ trim((string) $clan->Ime) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @else
                        @php
                            $clan = $clanoviZaObjavu->first();
                        @endphp
                        <div class="col-lg-6">
                            <label class="form-label fw-semibold mb-1">Objava u ime člana</label>
                            <input type="text"
                                   class="form-control"
                                   value="{{ trim((string) ($clan->Prezime ?? '')) }} {{ trim((string) ($clan->Ime ?? '')) }}"
                                   disabled>
                            <input type="hidden" name="clan_id" id="clan_id_hidden" value="{{ (int) ($clan->id ?? 0) }}">
                        </div>
                    @endif

                    <div class="col-lg-6">
                        <label for="naslov" class="form-label fw-semibold mb-1">Naslov</label>
                        <input type="text"
                               class="form-control @error('naslov') is-invalid @enderror"
                               id="naslov"
                               name="naslov"
                               value="{{ old('naslov', $isEdit ? $oglas->naslov : '') }}"
                               maxlength="191"
                               required>
                    </div>

                    <div class="col-lg-4">
                        <label for="cijena" class="form-label fw-semibold mb-1">Cijena (€)</label>
                        <input type="text"
                               class="form-control @error('cijena') is-invalid @enderror"
                               id="cijena"
                               name="cijena"
                               value="{{ old('cijena', $defaultCijena) }}"
                               placeholder="npr. 120,00"
                               required>
                    </div>

                    <div class="col-lg-4">
                        <label for="kontakt_telefon" class="form-label fw-semibold mb-1">Kontakt telefon</label>
                        <input type="text"
                               class="form-control @error('kontakt_telefon') is-invalid @enderror"
                               id="kontakt_telefon"
                               name="kontakt_telefon"
                               value="{{ old('kontakt_telefon', $isEdit ? $oglas->kontakt_telefon : $kontaktDefault['telefon']) }}"
                               maxlength="64"
                               required>
                    </div>

                    <div class="col-lg-4">
                        <label for="kontakt_email" class="form-label fw-semibold mb-1">Kontakt e-mail</label>
                        <input type="email"
                               class="form-control @error('kontakt_email') is-invalid @enderror"
                               id="kontakt_email"
                               name="kontakt_email"
                               value="{{ old('kontakt_email', $isEdit ? $oglas->kontakt_email : $kontaktDefault['email']) }}"
                               maxlength="191">
                        <div class="form-text">Ako ostavite prazno, e-mail se neće prikazati u oglasu.</div>
                    </div>

                    <div class="col-12">
                        <label for="opis" class="form-label fw-semibold mb-1">Opis</label>
                        <textarea class="form-control @error('opis') is-invalid @enderror"
                                  id="opis"
                                  name="opis"
                                  rows="6"
                                  maxlength="5000"
                                  required>{{ old('opis', $isEdit ? $oglas->opis : '') }}</textarea>
                        <div class="form-text">HTTP/HTTPS linkovi u opisu automatski se prikazuju kao klikabilni linkovi.</div>
                    </div>

                    <div class="col-12">
                        <label for="slike" class="form-label fw-semibold mb-1">Galerija slika (max {{ (int) $maxSlike }})</label>
                        <input type="file"
                               class="form-control @error('slike') is-invalid @enderror @error('slike.*') is-invalid @enderror"
                               id="slike"
                               name="slike[]"
                               accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                               multiple>
                        @if($isEdit)
                            <div class="form-text">Trenutno slika: {{ $brojPostojecihSlika }}. Trenutno je moguće dodati do {{ $preostaloSlika }} novih (ovisno o brisanju postojećih).</div>
                        @else
                            <div class="form-text">Maksimalno je dozvoljeno {{ (int) $maxSlike }} slika.</div>
                        @endif
                    </div>

                    @if($isEdit && $oglas->slike->count() > 0)
                        <div class="col-12">
                            <div class="fw-semibold mb-2">Postojeće slike</div>
                            <div class="row g-2">
                                @foreach($oglas->slike as $slika)
                                    <div class="col-6 col-md-4 col-lg-3">
                                        <div class="border rounded p-2 h-100">
                                            <img src="{{ asset('storage/' . $slika->putanja) }}"
                                                 class="img-fluid rounded mb-2"
                                                 alt="Postojeća slika {{ $loop->iteration }}">
                                            <div class="form-check">
                                                <input class="form-check-input"
                                                       type="checkbox"
                                                       value="{{ (int) $slika->id }}"
                                                       id="obrisi_sliku_{{ (int) $slika->id }}"
                                                       name="obrisi_slike[]"
                                                       @checked(in_array((int) $slika->id, array_map('intval', (array) old('obrisi_slike', [])), true))>
                                                <label class="form-check-label small" for="obrisi_sliku_{{ (int) $slika->id }}">
                                                    Obriši sliku
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <div class="col-12 d-flex flex-wrap align-items-center gap-2">
                        <button type="submit" class="btn btn-danger">
                            {{ $isEdit ? 'Spremi izmjene' : 'Predaj oglas' }}
                        </button>
                        <a href="{{ route('javno.oglasnik.index') }}" class="btn btn-secondary">Odustani</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const clanSelect = /** @type {HTMLSelectElement|null} */ (document.getElementById('clan_id'));
            const clanHidden = /** @type {HTMLInputElement|null} */ (document.getElementById('clan_id_hidden'));
            const telefonInput = /** @type {HTMLInputElement|null} */ (document.getElementById('kontakt_telefon'));
            const emailInput = /** @type {HTMLInputElement|null} */ (document.getElementById('kontakt_email'));

            if (!(telefonInput instanceof HTMLInputElement) || !(emailInput instanceof HTMLInputElement)) {
                return;
            }

            const mode = @json($mode);
            const imaStariUnos = @json(old('kontakt_telefon') !== null || old('kontakt_email') !== null);

            const popuniKontakt = function () {
                if (clanSelect instanceof HTMLSelectElement) {
                    const option = clanSelect.selectedOptions.length > 0 ? clanSelect.selectedOptions[0] : null;
                    if (!(option instanceof HTMLOptionElement)) {
                        return;
                    }

                    telefonInput.value = option.dataset.telefon || '';
                    emailInput.value = option.dataset.email || '';
                    return;
                }

                if (clanHidden instanceof HTMLInputElement) {
                    // Za člana bez dropdowna, vrijednosti su već učitane iz backenda.
                    return;
                }
            };

            if (clanSelect instanceof HTMLSelectElement) {
                clanSelect.addEventListener('change', popuniKontakt);
                if (mode === 'create' && !imaStariUnos) {
                    popuniKontakt();
                }
            }
        })();
    </script>
@endsection
