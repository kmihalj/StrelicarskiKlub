@extends('layouts.app')

@section('content')
    @php
        $clanoviOpcije = $clanovi->map(fn ($clan) => [
            'id' => (int)$clan->id,
            'label' => trim((string)$clan->Ime) . ' ' . trim((string)$clan->Prezime) . ' (' . (string)$clan->oib . ')',
        ])->values();

        $polazniciOpcije = $polaznici->map(fn ($polaznik) => [
            'id' => (int)$polaznik->id,
            'label' => trim((string)$polaznik->Ime) . ' ' . trim((string)$polaznik->Prezime) . ' (' . (string)$polaznik->oib . ')',
            'prebacen' => !empty($polaznik->prebacen_u_clana_id),
        ])->values();

        $odabraniRoditeljClanovi = $user->djecaClanovi->pluck('id')->map(fn ($id) => (int)$id)->all();
        $odabraniRoditeljPolaznici = $user->djecaPolaznici->pluck('id')->map(fn ($id) => (int)$id)->all();
        $staraRola = (int)old('rola', $user->rola);
        $stariPovezaniId = old('povezani_id');
        $inicijalniPovezaniClanId = null;
        if ($staraRola <= 2) {
            $inicijalniPovezaniClanId = (int)($stariPovezaniId ?? $user->clan_id ?? 0);
            if ($inicijalniPovezaniClanId <= 0) {
                $inicijalniPovezaniClanId = null;
            }
        }
    @endphp

    <div class="container-xxl">
        <div class="row justify-content-center p-2 mb-3 shadow bg-dark fw-bolder">
            <div class="col-lg-12 text-white d-flex flex-wrap align-items-center justify-content-between gap-2">
                <span>Uređivanje korisnika</span>
                <button type="button" class="btn btn-sm btn-warning" onclick="location.href='{{ route('admin.korisnici.index') }}'">
                    Povratak na popis
                </button>
            </div>
        </div>
    </div>

    <div class="container-xxl bg-white shadow">
        <div class="row justify-content-center p-2 shadow bg-danger fw-bolder">
            <div class="col-lg-12 text-white">
                Korisnik: {{ $user->name }}
            </div>
        </div>

        <div class="row p-3 bg-secondary-subtle">
            <div class="col-12">
                <form id="spremi_korisnika" action="{{ route('admin.korisnici.update', $user) }}" method="POST">
                    @csrf
                    <input type="hidden" name="je_roditelj" value="0">

                    <div class="row g-3">
                        <div class="col-lg-6">
                            <label for="name" class="form-label mb-1">Ime i prezime</label>
                            <input type="text" class="form-control" id="name" name="name" value="{{ old('name', $user->name) }}" required>
                        </div>
                        <div class="col-lg-6">
                            <label for="email" class="form-label mb-1">E-mail</label>
                            <input type="email" class="form-control" id="email" name="email" value="{{ old('email', $user->email) }}" required>
                        </div>
                        <div class="col-lg-3">
                            <label for="oib" class="form-label mb-1">OIB</label>
                            <input type="text" class="form-control" id="oib" name="oib" maxlength="11" value="{{ old('oib', $user->oib) }}">
                        </div>
                        <div class="col-lg-3">
                            <label for="br_telefona" class="form-label mb-1">Telefon</label>
                            <input type="text" class="form-control" id="br_telefona" name="br_telefona" value="{{ old('br_telefona', $user->br_telefona) }}" placeholder="+385xxxxxxxxx">
                        </div>
                        <div class="col-lg-3">
                            <label for="rola" class="form-label mb-1">Rola</label>
                            <select class="form-select js-rola-select" id="rola" name="rola">
                                <option value="1" @if($staraRola === 1) selected @endif>1 - Admin</option>
                                <option value="2" @if($staraRola === 2) selected @endif>2 - Član</option>
                                <option value="3" @if($staraRola === 3) selected @endif>3 - Korisnik</option>
                                <option value="4" @if($staraRola === 4) selected @endif>4 - Polaznik škole</option>
                            </select>
                        </div>
                        <div class="col-lg-3">
                            <label for="povezani_id" class="form-label mb-1">Povezani profil</label>
                            <select class="form-select js-povezani-select" id="povezani_id" name="povezani_id"
                                    data-user-id="{{ (int)$user->id }}"
                                    data-selected-clan-id="{{ $stariPovezaniId ?? (($staraRola <= 2) ? $user->clan_id : '') }}"
                                    data-selected-polaznik-id="{{ $stariPovezaniId ?? (($staraRola === 4) ? $user->polaznik_id : '') }}">
                            </select>
                        </div>
                        <div class="col-lg-3 d-flex align-items-end">
                            <a id="povezani_clan_link"
                               class="btn btn-sm btn-outline-danger @if(empty($inicijalniPovezaniClanId)) d-none @endif"
                               href="@if(!empty($inicijalniPovezaniClanId)){{ route('javno.clanovi.prikaz_clana', $inicijalniPovezaniClanId) }}@else#@endif"
                               target="_blank">
                                Profil povezanog člana
                            </a>
                        </div>

                        <div class="col-12">
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input js-je-roditelj" type="checkbox" role="switch"
                                       id="je_roditelj"
                                       name="je_roditelj"
                                       value="1"
                                       @if((bool)old('je_roditelj', $user->je_roditelj)) checked @endif>
                                <label class="form-check-label" for="je_roditelj">Roditelj</label>
                            </div>
                        </div>

                        <div class="col-lg-6">
                            <label for="roditelj_clanovi" class="form-label mb-1">Djeca (članovi, maloljetni)</label>
                            <select class="form-select js-roditelj-clanovi" id="roditelj_clanovi" name="roditelj_clanovi[]" multiple size="6">
                                @foreach($maloljetniClanovi as $clan)
                                    @php
                                        $selected = collect(old('roditelj_clanovi', $odabraniRoditeljClanovi))->map(fn ($id) => (int)$id)->contains((int)$clan->id);
                                    @endphp
                                    <option value="{{ (int)$clan->id }}" @if($selected) selected @endif>
                                        {{ trim((string)$clan->Ime) }} {{ trim((string)$clan->Prezime) }} ({{ (string)$clan->oib }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-lg-6">
                            <label for="roditelj_polaznici" class="form-label mb-1">Djeca (polaznici škole, maloljetni)</label>
                            <select class="form-select js-roditelj-polaznici" id="roditelj_polaznici" name="roditelj_polaznici[]" multiple size="6">
                                @foreach($maloljetniPolaznici as $polaznik)
                                    @php
                                        $selected = collect(old('roditelj_polaznici', $odabraniRoditeljPolaznici))->map(fn ($id) => (int)$id)->contains((int)$polaznik->id);
                                    @endphp
                                    <option value="{{ (int)$polaznik->id }}" @if($selected) selected @endif>
                                        {{ trim((string)$polaznik->Ime) }} {{ trim((string)$polaznik->Prezime) }} ({{ (string)$polaznik->oib }})
                                    </option>
                                @endforeach
                            </select>
                            <p class="small text-muted mt-1 mb-0">Maksimalno 3 djece po roditelju, 2 roditelja po djetetu.</p>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="container-xxl bg-white shadow mt-3">
        <div class="row p-3">
            <div class="col-12">
                <div class="d-grid gap-2 d-md-flex justify-content-between align-items-center">
                    <div class="d-grid gap-2 d-md-flex">
                        @if((int)auth()->id() !== (int)$user->id)
                            <form action="{{ route('admin.korisnici.destroy', $user) }}" method="POST" class="d-inline-block">
                                @csrf
                                <button type="submit" class="btn btn-outline-danger"
                                        onclick="return confirm('Da li ste sigurni da želite obrisati korisnika?')">
                                    Obriši korisnika
                                </button>
                            </form>
                        @endif
                    </div>

                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <button class="btn btn-primary me-md-2" type="submit" form="spremi_korisnika">Spremi</button>
                        <button class="btn btn-outline-secondary" type="button" onclick="location.href='{{ route('admin.korisnici.index') }}'">Popis korisnika</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const clanovi = @json($clanoviOpcije);
            const polaznici = @json($polazniciOpcije);
            const zauzetiClanovi = @json($zauzetiClanovi);
            const zauzetiPolaznici = @json($zauzetiPolaznici);

            const rolaSelect = document.querySelector('.js-rola-select');
            const povezaniSelect = document.querySelector('.js-povezani-select');
            const roditeljSwitch = document.querySelector('.js-je-roditelj');
            const roditeljClanovi = document.querySelector('.js-roditelj-clanovi');
            const roditeljPolaznici = document.querySelector('.js-roditelj-polaznici');
            const povezaniClanLink = document.getElementById('povezani_clan_link');
            const clanProfilBaseUrl = @json(url('clanovi'));

            if (!rolaSelect || !povezaniSelect) {
                return;
            }

            const toInt = function (value) {
                const parsed = parseInt(value, 10);
                if (Number.isNaN(parsed) || parsed <= 0) {
                    return null;
                }

                return parsed;
            };

            const addOption = function (select, value, label, selected) {
                const option = document.createElement('option');
                option.value = value;
                option.textContent = label;
                option.selected = !!selected;
                select.appendChild(option);
            };

            const popuniPovezaniDropdown = function () {
                const rola = rolaSelect.value;
                const userId = toInt(povezaniSelect.dataset.userId);
                const odabraniClanId = toInt(povezaniSelect.dataset.selectedClanId);
                const odabraniPolaznikId = toInt(povezaniSelect.dataset.selectedPolaznikId);

                povezaniSelect.innerHTML = '';
                povezaniSelect.disabled = false;

                if (rola === '3') {
                    addOption(povezaniSelect, '', 'Nije primjenjivo za rolu 3', true);
                    povezaniSelect.disabled = true;
                    return;
                }

                if (rola === '4') {
                    addOption(povezaniSelect, '', 'Odaberi polaznika škole', odabraniPolaznikId === null);

                    polaznici
                        .filter(function (polaznik) {
                            if (polaznik.prebacen && polaznik.id !== odabraniPolaznikId) {
                                return false;
                            }

                            const vlasnik = toInt(zauzetiPolaznici[polaznik.id]);
                            return vlasnik === null || vlasnik === userId;
                        })
                        .forEach(function (polaznik) {
                            addOption(
                                povezaniSelect,
                                String(polaznik.id),
                                polaznik.label,
                                odabraniPolaznikId !== null && polaznik.id === odabraniPolaznikId
                            );
                        });

                    return;
                }

                addOption(
                    povezaniSelect,
                    '',
                    rola === '2' ? 'Odaberi člana' : 'Nije povezano',
                    odabraniClanId === null
                );

                clanovi
                    .filter(function (clan) {
                        const vlasnik = toInt(zauzetiClanovi[clan.id]);
                        return vlasnik === null || vlasnik === userId;
                    })
                    .forEach(function (clan) {
                        addOption(
                            povezaniSelect,
                            String(clan.id),
                            clan.label,
                            odabraniClanId !== null && clan.id === odabraniClanId
                        );
                    });
            };

            const syncPovezaniClanLink = function () {
                if (!povezaniClanLink) {
                    return;
                }

                const rola = rolaSelect.value;
                const povezaniClanId = toInt(povezaniSelect.value);
                const prikazi = (rola === '1' || rola === '2') && povezaniClanId !== null;

                if (!prikazi) {
                    povezaniClanLink.classList.add('d-none');
                    povezaniClanLink.setAttribute('href', '#');
                    return;
                }

                povezaniClanLink.classList.remove('d-none');
                povezaniClanLink.setAttribute('href', `${clanProfilBaseUrl}/${povezaniClanId}`);
            };

            const toggleRoditeljControls = function () {
                if (!roditeljSwitch || !roditeljClanovi || !roditeljPolaznici) {
                    return;
                }

                const enabled = roditeljSwitch.checked;
                roditeljClanovi.disabled = !enabled;
                roditeljPolaznici.disabled = !enabled;
            };

            rolaSelect.addEventListener('change', function () {
                if (this.value === '3') {
                    povezaniSelect.dataset.selectedClanId = '';
                    povezaniSelect.dataset.selectedPolaznikId = '';
                }

                popuniPovezaniDropdown();
                syncPovezaniClanLink();
            });

            povezaniSelect.addEventListener('change', function () {
                const rola = rolaSelect.value;
                if (rola === '4') {
                    povezaniSelect.dataset.selectedPolaznikId = this.value;
                } else if (rola === '1' || rola === '2') {
                    povezaniSelect.dataset.selectedClanId = this.value;
                }

                syncPovezaniClanLink();
            });

            if (roditeljSwitch) {
                roditeljSwitch.addEventListener('change', toggleRoditeljControls);
            }

            popuniPovezaniDropdown();
            toggleRoditeljControls();
            syncPovezaniClanLink();
        });
    </script>
@endsection
