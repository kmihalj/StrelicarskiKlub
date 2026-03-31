{{-- Detaljni pregled jedne prijave na turnir (član/roditelj). --}}
@extends('layouts.app')

@section('content')
    @php
        $aktivna = $prijava->status === \App\Models\PrijavaTurnira::STATUS_ACTIVE;
        $mozeUredjivati = $aktivna && !$zakljucano;
    @endphp

    <div class="container-xxl bg-white shadow mb-3">
        <div class="row justify-content-center p-2 shadow bg-danger fw-bolder">
            <div class="col-lg-12 text-white d-flex flex-wrap justify-content-between align-items-center gap-2">
                <span class="mb-0">Pregled prijave na turnir</span>
                <a href="{{ route('javno.prijave_turnira.index') }}" class="btn btn-sm btn-light">Povratak</a>
            </div>
        </div>
        <div class="row p-3">
            <div class="col-lg-8">
                <div><span class="fw-semibold">Član:</span> {{ $clan->Prezime }} {{ $clan->Ime }}</div>
                <div><span class="fw-semibold">Turnir:</span> {{ $turnir->naziv }}</div>
                <div><span class="fw-semibold">Datum i mjesto:</span> {{ $turnir->datumRasponLabel() }}, {{ $turnir->mjesto }}</div>
                <div><span class="fw-semibold">Tip turnira:</span> {{ $turnir->tipTurnira->naziv ?? '-' }}</div>
                @if(!empty($turnir->napomena))
                    <div><span class="fw-semibold">Napomena:</span> {{ $turnir->napomena }}</div>
                @endif
                <div><span class="fw-semibold">Status prijave:</span>
                    @if($prijava->status === \App\Models\PrijavaTurnira::STATUS_ACTIVE)
                        <span class="badge bg-success">Aktivna</span>
                    @elseif($prijava->status === \App\Models\PrijavaTurnira::STATUS_CANCELLED)
                        <span class="badge bg-secondary">Odjavljena</span>
                    @else
                        <span class="badge bg-danger">Maknuta od strane admina</span>
                    @endif
                </div>
                @if(!empty($prijava->napomena_admin))
                    <div class="mt-2"><span class="fw-semibold">Napomena administratora:</span> {{ $prijava->napomena_admin }}</div>
                @endif
            </div>
            <div class="col-lg-4 text-lg-end">
                @if($zakljucano)
                    <span class="badge bg-danger">Prijave zaključane</span>
                @else
                    <span class="badge bg-success">Prijave otvorene</span>
                @endif
                @if(!empty($turnir->poziv_pdf_path))
                    <div class="mt-2">
                        <a href="{{ asset('storage/' . $turnir->poziv_pdf_path) }}" target="_blank" class="btn btn-sm btn-primary">
                            Poziv na turnir (PDF)
                        </a>
                    </div>
                @endif
            </div>
        </div>
        @if(!empty($lijecnickoUpozorenje))
            <div class="row px-3 pb-3">
                <div class="col-12">
                    <div class="alert alert-warning mb-0">{{ $lijecnickoUpozorenje }}</div>
                </div>
            </div>
        @endif
        @if($turnir->kotizacija_nacin === 'bank' && $prijava->paymentCharge)
            <div class="row px-3 pb-3">
                <div class="col-12">
                    <div class="alert alert-info mb-0">
                        Kotizacija je evidentirana kao stavka plaćanja.
                        <a href="{{ route('javno.clanovi.placanja', $clan) }}" class="alert-link">Otvori plaćanja člana</a>.
                    </div>
                </div>
            </div>
        @endif
        @if(($prijavljeniClanoviTurnira ?? collect())->count() > 0)
            <div class="row px-3 pb-3">
                <div class="col-12">
                    <div class="border rounded p-2">
                        <div class="fw-semibold">Prijavljeni članovi</div>
                        <div class="small mt-1">
                            @foreach($prijavljeniClanoviTurnira as $prijavljeniClan)
                                <a href="{{ $prijavljeniClan['url'] }}" class="link-primary text-decoration-none">{{ $prijavljeniClan['naziv'] }}</a>@if(!$loop->last), @endif
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        @endif
        <div class="row px-3 pb-3">
            <div class="col-12">
                @if($aktivna)
                    <form id="update-prijava-form" action="{{ route('javno.prijave_turnira.update', $prijava) }}" method="POST" class="row g-3">
                        @csrf
                        <div class="col-lg-4">
                            <label for="kategorija_id" class="form-label fw-semibold mb-1">Kategorija</label>
                            <select class="form-select" id="kategorija_id" name="kategorija_id" @disabled(!$mozeUredjivati) required>
                                @foreach($kategorije as $kategorija)
                                    <option value="{{ $kategorija->id }}"
                                        @selected((int) old('kategorija_id', $prijava->kategorija_id) === (int) $kategorija->id)>
                                        {{ $kategorija->naziv }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-lg-4">
                            <label for="stil_id" class="form-label fw-semibold mb-1">Stil luka</label>
                            <select class="form-select" id="stil_id" name="stil_id" @disabled(!$mozeUredjivati) required>
                                @foreach($stilovi as $stil)
                                    <option value="{{ $stil->id }}"
                                        @selected((int) old('stil_id', $prijava->stil_id) === (int) $stil->id)>
                                        {{ $stil->naziv }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-lg-4">
                            <label for="smjena" class="form-label fw-semibold mb-1">Smjena</label>
                            <select class="form-select" id="smjena" name="smjena" @disabled(!$mozeUredjivati)>
                                @foreach($smjene as $smjenaOpcija)
                                    <option value="{{ $smjenaOpcija }}"
                                        @selected(old('smjena', $prijava->smjena ?: 'nebitno') === $smjenaOpcija)>
                                        {{ ucfirst($smjenaOpcija) }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-text">*ako ima smjena</div>
                        </div>

                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="1" id="sudjelujem_u_kupu"
                                       name="sudjelujem_u_kupu"
                                    @checked((bool) old('sudjelujem_u_kupu', $prijava->sudjelujem_u_kupu))
                                    @disabled(!$mozeUredjivati)>
                                <label class="form-check-label" for="sudjelujem_u_kupu">
                                    Sudjelujem u natjecanju za KUP
                                </label>
                            </div>
                            <div class="form-text">*ako se boduje za KUP</div>
                        </div>

                        <div class="col-12 d-flex flex-wrap align-items-center gap-2">
                            <button type="submit" class="btn btn-danger" @disabled(!$mozeUredjivati)>Spremi izmjene</button>
                            <button type="submit" class="btn btn-danger"
                                    form="odjava-prijava-form"
                                    @disabled(!$mozeUredjivati)
                                    onclick="return confirm('Odjaviti turnir?')">
                                Odjavi turnir
                            </button>
                        </div>
                    </form>
                    <form id="odjava-prijava-form" action="{{ route('javno.prijave_turnira.odjava', $prijava) }}" method="POST" class="d-none">
                        @csrf
                    </form>
                    @if(!$mozeUredjivati)
                        <div class="alert alert-warning mt-3 mb-0">
                            Prijavu više nije moguće mijenjati jer su prijave zaključane.
                        </div>
                    @endif
                @else
                    <div class="alert alert-secondary mb-0">
                        Ova prijava nije aktivna i nije ju moguće uređivati.
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
