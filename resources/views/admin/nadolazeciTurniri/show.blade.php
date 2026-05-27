{{-- Administratorski pregled prijava na jedan nadolazeći turnir. --}}
@extends('layouts.app')

@section('content')
    @php
        $turnirJeProsao = $turnir->datum?->copy()->startOfDay()?->lte(now()->startOfDay()) ?? false;
        $otvoriPrijavaEmailModal = $errors->any() && old('_form_context') === 'prijava_email';
        $otvoriAdminDodajModal = $errors->any() && old('_form_context') === 'admin_dodaj_prijavu';
        $prijavaDokumentOpcionalnaPolja = $prijavaDokumentOpcionalnaPolja ?? [];
        $oldPrijavaDokumentPolja = $otvoriPrijavaEmailModal && old('document_fields_submitted') !== null
            ? array_map('strval', (array) old('document_fields', []))
            : array_keys($prijavaDokumentOpcionalnaPolja);
        $odabranaPrijavaDokumentPolja = array_values(array_intersect(array_keys($prijavaDokumentOpcionalnaPolja), $oldPrijavaDokumentPolja));
    @endphp
    <style>
        .section-collapse-toggle .when-open,
        .section-collapse-toggle .when-closed {
            line-height: 1;
        }

        .section-collapse-toggle[aria-expanded="true"] .when-closed {
            display: none;
        }

        .section-collapse-toggle[aria-expanded="false"] .when-open {
            display: none;
        }

        .prijava-scrollable-modal {
            height: calc(100vh - 2rem);
            margin-top: 1rem;
            margin-bottom: 1rem;
        }

        .prijava-scrollable-modal .modal-content {
            max-height: 100%;
            overflow: hidden;
        }

        .prijava-scrollable-modal .prijava-modal-form {
            min-height: 0;
            overflow: hidden;
        }

        .prijava-scrollable-modal .modal-body {
            min-height: 0;
            overflow-y: auto;
        }

        .prijava-scrollable-modal .modal-footer {
            flex-shrink: 0;
        }

        @media (max-height: 700px) {
            .prijava-scrollable-modal {
                height: calc(100vh - 1rem);
                margin-top: .5rem;
                margin-bottom: .5rem;
            }
        }
    </style>
    <div class="container-xxl bg-white shadow mb-3">
        <div class="row justify-content-center p-2 shadow bg-danger fw-bolder">
            <div class="col-lg-12 text-white">
                Prijave - {{ $turnir->naziv }}
            </div>
        </div>
        <div class="row p-3">
            <div class="col-lg-8">
                <div><span class="fw-semibold">Datum:</span> {{ $turnir->datumRasponLabel() }}</div>
                <div><span class="fw-semibold">Organizator:</span> {{ $turnir->organizator ?: '-' }}</div>
                <div><span class="fw-semibold">Mjesto:</span> {{ $turnir->mjesto }}</div>
                <div><span class="fw-semibold">Tip turnira:</span> {{ $turnir->tipTurnira->naziv ?? '-' }}</div>
                @if(!empty($turnir->napomena))
                    <div><span class="fw-semibold">Napomena:</span> {{ $turnir->napomena }}</div>
                @endif
            </div>
            <div class="col-lg-4 text-lg-end">
                <div>
                    @if($turnir->prijaveZakljucane())
                        <span class="badge bg-danger">Prijave zaključane</span>
                    @else
                        <span class="badge bg-success">Prijave otvorene</span>
                    @endif
                </div>
                @if(!empty($turnir->poziv_pdf_path))
                    <div class="mt-2">
                        <a href="{{ asset('storage/' . $turnir->poziv_pdf_path) }}" target="_blank" class="btn btn-sm btn-primary">PDF poziv</a>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="container-xxl bg-white shadow mb-3">
        <div class="row p-3">
            <div class="col-12 mb-3">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <div class="flex-shrink-0">
                        <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#adminDodajPrijavuModal">
                            Dodaj prijavu člana
                        </button>
                    </div>
                    <div class="d-flex flex-wrap align-items-center justify-content-end gap-2 ms-auto">
                        <a href="{{ route('admin.nadolazeci_turniri.prijava_docx', $turnir) }}" class="btn btn-sm btn-primary js-prijava-document-export-link">Prijava .docx</a>
                        <a href="{{ route('admin.nadolazeci_turniri.prijava_pdf', $turnir) }}" class="btn btn-sm btn-primary js-prijava-document-export-link">Prijava .pdf</a>
                        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#posaljiPrijavaEmailModal">
                            Pošalji prijavu e-mailom
                        </button>
                        @if($turnirJeProsao)
                            <form action="{{ route('admin.nadolazeci_turniri.kreiraj_rezultate', $turnir) }}" method="POST" class="m-0">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-warning">Kreiraj rezultate</button>
                            </form>
                        @endif
                        <a href="{{ route('admin.nadolazeci_turniri.index') }}" class="btn btn-sm btn-secondary">Povratak</a>
                    </div>
                </div>
            </div>
            <div class="col-12 mb-3">
                <div class="d-flex flex-wrap align-items-center gap-3">
                    <span class="fw-semibold">Stupci u prijavnici:</span>
                    @foreach($prijavaDokumentOpcionalnaPolja as $polje => $nazivPolja)
                        <div class="form-check mb-0">
                            <input class="form-check-input js-prijava-document-field"
                                   type="checkbox"
                                   value="{{ $polje }}"
                                   id="prijava-dokument-polje-{{ $polje }}"
                                @checked(in_array($polje, $odabranaPrijavaDokumentPolja, true))>
                            <label class="form-check-label" for="prijava-dokument-polje-{{ $polje }}">{{ $nazivPolja }}</label>
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="col-12 table-responsive">
                <table class="table table-hover align-middle mb-0 border">
                    <thead class="table-warning">
                    <tr>
                        <th>Status</th>
                        <th>Član</th>
                        <th>Kategorija</th>
                        <th>Stil</th>
                        <th>KUP</th>
                        <th>Smjena / dan</th>
                        <th>Obrok</th>
                        <th>Napomena člana</th>
                        <th>Kotizacija</th>
                        <th>Radnje</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($prijave as $prijava)
                        @php
                            $statusClass = $prijava->status === \App\Models\PrijavaTurnira::STATUS_ACTIVE
                                ? 'bg-success'
                                : 'bg-secondary';
                            $statusLabel = $prijava->status === \App\Models\PrijavaTurnira::STATUS_ACTIVE
                                ? 'Aktivna'
                                : 'Odjavljena';
                            $clan = $prijava->clan;
                            $turnirDatum = $turnir->datum;
                            $lijecnickiWarning = null;
                            if ($clan && $turnirDatum) {
                                if (empty($clan->lijecnicki_do)) {
                                    $lijecnickiWarning = 'Liječnički nije evidentiran.';
                                } else {
                                    try {
                                        $lijecnickiDo = \Carbon\Carbon::parse((string) $clan->lijecnicki_do)->endOfDay();
                                        if ($lijecnickiDo->lt($turnirDatum->copy()->startOfDay())) {
                                            $lijecnickiWarning = 'Liječnički ističe '.$lijecnickiDo->format('d.m.Y.');
                                        }
                                    } catch (\Throwable) {
                                        $lijecnickiWarning = 'Liječnički datum nije valjan.';
                                    }
                                }
                            }
                            $charge = $prijava->paymentCharge;
                            $urlPlacanja = ($charge && $charge->status === \App\Services\PaymentTrackingService::STATUS_OPEN && $clan)
                                ? route('admin.clanovi.prikaz_clana', ['clan' => $clan, 'open_payments' => 1])
                                : null;
                        @endphp
                        <tr>
                            <td><span class="badge {{ $statusClass }}">{{ $statusLabel }}</span></td>
                            <td>
                                @if($prijava->clan)
                                    <a href="{{ route('javno.clanovi.prikaz_clana', $prijava->clan) }}" class="fw-semibold link-primary text-decoration-underline">
                                        {{ $prijava->clan->Ime }} {{ $prijava->clan->Prezime }}
                                    </a>
                                @else
                                    <div class="fw-semibold">-</div>
                                @endif
                                @if($lijecnickiWarning)
                                    <div class="small text-danger">{{ $lijecnickiWarning }}</div>
                                @endif
                            </td>
                            <td>{{ $prijava->kategorija?->naziv ?? '-' }}</td>
                            <td>{{ $prijava->stil?->naziv ?? '-' }}</td>
                            <td>{{ $prijava->sudjelujem_u_kupu ? 'Da' : 'Ne' }}</td>
                            <td>{{ $prijava->terminPrijaveLabel() }}</td>
                            <td>{{ $prijava->obrokLabel() }}</td>
                            <td>
                                @if($prijava->status !== \App\Models\PrijavaTurnira::STATUS_REMOVED)
                                    <form action="{{ route('admin.nadolazeci_turniri.prijave.napomena', [$turnir, $prijava]) }}"
                                          method="POST"
                                          class="d-flex flex-wrap flex-lg-nowrap align-items-center gap-2">
                                        @csrf
                                        <input type="text"
                                               class="form-control form-control-sm"
                                               style="max-width: 340px;"
                                               name="napomena_clana"
                                               maxlength="255"
                                               value="{{ $prijava->napomena_clana }}"
                                               placeholder="Napomena člana">
                                        <button type="submit" class="btn btn-sm btn-primary" title="Spremi napomenu" aria-label="Spremi napomenu">
                                            @include('admin.SVG.clipboard')
                                        </button>
                                    </form>
                                @else
                                    {{ $prijava->napomena_clana ?: '-' }}
                                @endif
                            </td>
                            <td>
                                @if($charge)
                                    @if($charge->status === \App\Services\PaymentTrackingService::STATUS_PAID)
                                        <span class="badge bg-success">Plaćeno</span>
                                    @elseif($charge->status === \App\Services\PaymentTrackingService::STATUS_OPEN && $urlPlacanja)
                                        <a href="{{ $urlPlacanja }}" class="badge bg-danger text-white text-decoration-none">Nije plaćeno</a>
                                    @elseif($charge->status === \App\Services\PaymentTrackingService::STATUS_OPEN)
                                        <span class="badge bg-danger">Nije plaćeno</span>
                                    @else
                                        <span class="badge bg-secondary">Zatvoreno</span>
                                    @endif
                                    <div class="small">{{ number_format((float) $charge->amount, 2, ',', '.') }} EUR</div>
                                @else
                                    -
                                @endif
                            </td>
                            <td class="text-end">
                                @if($prijava->status === \App\Models\PrijavaTurnira::STATUS_ACTIVE)
                                    <button type="button"
                                            class="btn btn-sm btn-danger"
                                            title="Ukloni prijavu"
                                            aria-label="Ukloni prijavu"
                                            data-bs-toggle="modal"
                                            data-bs-target="#ukloniPrijavuModal"
                                            data-action="{{ route('admin.nadolazeci_turniri.prijave.ukloni', [$turnir, $prijava]) }}"
                                            data-clan="{{ trim((string) (($prijava->clan?->Ime ?? '') . ' ' . ($prijava->clan?->Prezime ?? ''))) }}">
                                        @include('admin.SVG.obrisi')
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center">Nema prijava za ovaj turnir.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="row px-3 pb-3">
            <div class="col-12">
                <div class="fw-bold mb-2 mt-3">Uklonjeni članovi sa turnira</div>
            </div>
            <div class="col-12 table-responsive">
                <table class="table table-hover align-middle mb-0 border">
                    <thead class="table-warning">
                    <tr>
                        <th>Status</th>
                        <th>Član</th>
                        <th>Kategorija</th>
                        <th>Stil</th>
                        <th>KUP</th>
                        <th>Smjena / dan</th>
                        <th>Obrok</th>
                        <th>Napomena člana</th>
                        <th>Kotizacija</th>
                        <th>Uklonjeno</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($uklonjenePrijave as $prijava)
                        @php
                            $clan = $prijava->clan;
                            $charge = $prijava->paymentCharge;
                            $urlPlacanja = ($charge && $charge->status === \App\Services\PaymentTrackingService::STATUS_OPEN && $clan)
                                ? route('admin.clanovi.prikaz_clana', ['clan' => $clan, 'open_payments' => 1])
                                : null;
                        @endphp
                        <tr>
                            <td><span class="badge bg-danger">Maknuta</span></td>
                            <td>
                                @if($prijava->clan)
                                    <a href="{{ route('javno.clanovi.prikaz_clana', $prijava->clan) }}" class="fw-semibold link-primary text-decoration-underline">
                                        {{ $prijava->clan->Ime }} {{ $prijava->clan->Prezime }}
                                    </a>
                                @else
                                    <div class="fw-semibold">-</div>
                                @endif
                            </td>
                            <td>{{ $prijava->kategorija?->naziv ?? '-' }}</td>
                            <td>{{ $prijava->stil?->naziv ?? '-' }}</td>
                            <td>{{ $prijava->sudjelujem_u_kupu ? 'Da' : 'Ne' }}</td>
                            <td>{{ $prijava->terminPrijaveLabel() }}</td>
                            <td>{{ $prijava->obrokLabel() }}</td>
                            <td>{{ $prijava->napomena_clana ?: '-' }}</td>
                            <td>
                                @if($charge)
                                    @if($charge->status === \App\Services\PaymentTrackingService::STATUS_PAID)
                                        <span class="badge bg-success">Plaćeno</span>
                                    @elseif($charge->status === \App\Services\PaymentTrackingService::STATUS_OPEN && $urlPlacanja)
                                        <a href="{{ $urlPlacanja }}" class="badge bg-danger text-white text-decoration-none">Nije plaćeno</a>
                                    @elseif($charge->status === \App\Services\PaymentTrackingService::STATUS_OPEN)
                                        <span class="badge bg-danger">Nije plaćeno</span>
                                    @else
                                        <span class="badge bg-secondary">Zatvoreno</span>
                                    @endif
                                    <div class="small">{{ number_format((float) $charge->amount, 2, ',', '.') }} EUR</div>
                                @else
                                    -
                                @endif
                            </td>
                            <td>
                                <div>{{ $prijava->removed_at?->format('d.m.Y. H:i') ?? '-' }}</div>
                                @if($prijava->napomena_admin)
                                    <div class="small">{{ $prijava->napomena_admin }}</div>
                                    <div class="small text-muted">Uklanjanje člana sa turnira od strane administratora.</div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center">Nema uklonjenih članova.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <div class="container-xxl bg-white shadow mb-3">
        <div class="row justify-content-center p-2 shadow bg-danger fw-bolder">
            <div class="col-lg-12 text-white d-flex align-items-center justify-content-between">
                <span>CSV export prijava</span>
                <button type="button"
                        class="btn btn-sm btn-link text-white text-decoration-none p-0 section-collapse-toggle"
                        data-bs-toggle="collapse"
                        data-bs-target="#csv_export_prijava_dropdown"
                        aria-expanded="false"
                        aria-controls="csv_export_prijava_dropdown">
                    <span class="when-open">-</span>
                    <span class="when-closed">+</span>
                </button>
            </div>
        </div>
        <div id="csv_export_prijava_dropdown" class="collapse">
            <div class="row p-3">
                <div class="col-12">
                    <form action="{{ route('admin.nadolazeci_turniri.export_csv', $turnir) }}" method="GET" class="row g-2 align-items-end">
                        @foreach($csvPoljaPrijava as $polje => $nazivPolja)
                            <div class="col-6 col-md-4 col-lg-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="fields[]" value="{{ $polje }}" id="csv-polje-{{ $polje }}"
                                        @checked(in_array($polje, $csvZadanaPoljaPrijava, true))>
                                    <label class="form-check-label" for="csv-polje-{{ $polje }}">{{ $nazivPolja }}</label>
                                </div>
                            </div>
                        @endforeach
                        <div class="col-12 d-flex justify-content-end">
                            <button type="submit" class="btn btn-sm btn-success">Preuzmi CSV (aktivne prijave)</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="adminDodajPrijavuModal" tabindex="-1" aria-labelledby="adminDodajPrijavuTitle" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable prijava-scrollable-modal">
            <div class="modal-content">
                <div class="modal-header bg-danger">
                    <h5 class="modal-title text-white" id="adminDodajPrijavuTitle">Administratorski unos prijave</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Zatvori"></button>
                </div>
                @php
                    $adminOdabraniClanId = (int) old('clan_id', (int) ($adminClanoviZaPrijavu->first()->id ?? 0));
                @endphp
                <form action="{{ route('admin.nadolazeci_turniri.prijave.admin_dodaj', $turnir) }}" method="POST" class="d-flex flex-column flex-grow-1 prijava-modal-form" id="admin-dodaj-prijavu-form">
                    @csrf
                    <input type="hidden" name="_form_context" value="admin_dodaj_prijavu">
                    <div class="modal-body overflow-auto">
                        <div class="row g-3">
                            @if($otvoriAdminDodajModal && $errors->any())
                                <div class="col-12">
                                    <div class="alert alert-danger py-2 mb-0">{{ $errors->first() }}</div>
                                </div>
                            @endif

                            <div class="col-lg-6">
                                <label for="admin_clan_id" class="form-label fw-semibold mb-1">Član</label>
                                <select class="form-select" id="admin_clan_id" name="clan_id" required>
                                    @forelse($adminClanoviZaPrijavu as $clan)
                                        <option value="{{ (int) $clan->id }}" @selected($adminOdabraniClanId === (int) $clan->id)>
                                            {{ $clan->Prezime }} {{ $clan->Ime }}
                                        </option>
                                    @empty
                                        <option value="" selected disabled>Nema dostupnih članova za prijavu</option>
                                    @endforelse
                                </select>
                            </div>
                            <div class="col-lg-6">
                                <label for="admin_kategorija_id" class="form-label fw-semibold mb-1">Kategorija</label>
                                <select class="form-select"
                                        id="admin_kategorija_id"
                                        name="kategorija_id"
                                        data-old-value="{{ old('kategorija_id', '') }}"
                                        required></select>
                            </div>
                            <div class="col-lg-4">
                                <label for="admin_stil_id" class="form-label fw-semibold mb-1">Stil luka</label>
                                <select class="form-select" id="admin_stil_id" name="stil_id" required>
                                    <option value="">Odaberi stil</option>
                                    @foreach($adminStiloviZaPrijavu as $stil)
                                        <option value="{{ (int) $stil->id }}" @selected((int) old('stil_id') === (int) $stil->id)>
                                            {{ $stil->naziv }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            @if($adminTurnirJeVisednevni)
                                <input type="hidden" name="smjena" value="nebitno">
                                <div class="col-lg-4">
                                    <label for="admin_odabrani_dan" class="form-label fw-semibold mb-1">Odabir dana</label>
                                    <select class="form-select" id="admin_odabrani_dan" name="odabrani_dan">
                                        <option value="" @selected(old('odabrani_dan', '') === '')>Nije bitno</option>
                                        @foreach($adminOdabirDanaOpcije as $opcijaDana)
                                            <option value="{{ $opcijaDana['value'] }}" @selected(old('odabrani_dan') === $opcijaDana['value'])>
                                                {{ $opcijaDana['label'] }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <div class="form-text">*dan može ovisiti o kategoriji i stilu, provjerite poziv...</div>
                                </div>
                            @else
                                <input type="hidden" name="odabrani_dan" value="">
                                <div class="col-lg-4">
                                    <label for="admin_smjena" class="form-label fw-semibold mb-1">Smjena</label>
                                    <select class="form-select" id="admin_smjena" name="smjena">
                                        @foreach($adminSmjeneOpcije as $smjenaOpcija)
                                            <option value="{{ $smjenaOpcija }}" @selected(old('smjena', 'nebitno') === $smjenaOpcija)>
                                                {{ ucfirst($smjenaOpcija) }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <div class="form-text">*ako ima smjena</div>
                                </div>
                            @endif

                            <div class="col-lg-4">
                                <label for="admin_obrok" class="form-label fw-semibold mb-1">Obrok</label>
                                <select class="form-select" id="admin_obrok" name="obrok">
                                    @foreach($adminObrokOpcije as $obrokVrijednost => $obrokLabel)
                                        <option value="{{ $obrokVrijednost }}" @selected(old('obrok', \App\Models\PrijavaTurnira::OBROK_NE) === $obrokVrijednost)>
                                            {{ $obrokLabel }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="form-text">*ako je obrok osiguran</div>
                            </div>
                            <div class="col-lg-8">
                                <label for="admin_napomena_clana" class="form-label fw-semibold mb-1">Napomena</label>
                                <input type="text"
                                       class="form-control"
                                       id="admin_napomena_clana"
                                       name="napomena_clana"
                                       value="{{ old('napomena_clana') }}"
                                       maxlength="255"
                                       placeholder="Kratka napomena (nije obavezno)">
                            </div>
                            <div class="col-12">
                                <div class="form-check">
                                    <input class="form-check-input"
                                           type="checkbox"
                                           id="admin_sudjelujem_u_kupu"
                                           name="sudjelujem_u_kupu"
                                           value="1"
                                        @checked((bool) old('sudjelujem_u_kupu'))>
                                    <label class="form-check-label" for="admin_sudjelujem_u_kupu">
                                        Sudjelujem u natjecanju za KUP
                                    </label>
                                </div>
                                <div class="form-text">*ako se boduje za KUP</div>
                            </div>
                            <div class="col-12 d-none" id="admin-prijava-status-wrap">
                                <div class="alert alert-warning py-2 mb-0" id="admin-prijava-status-text"></div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Odustani</button>
                        <button type="submit"
                                class="btn btn-danger"
                                id="admin-dodaj-prijavu-submit"
                            @disabled($adminClanoviZaPrijavu->isEmpty())>
                            Spremi prijavu
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="posaljiPrijavaEmailModal" tabindex="-1" aria-labelledby="posaljiPrijavaEmailTitle" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable prijava-scrollable-modal">
            <div class="modal-content">
                <div class="modal-header bg-danger">
                    <h5 class="modal-title text-white" id="posaljiPrijavaEmailTitle">Slanje prijavnice e-mailom</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Zatvori"></button>
                </div>
                <form action="{{ route('admin.nadolazeci_turniri.prijava_email', $turnir) }}" method="POST" class="d-flex flex-column flex-grow-1 prijava-modal-form" id="prijava-email-form">
                    @csrf
                    <input type="hidden" name="_form_context" value="prijava_email">
                    <input type="hidden" name="document_fields_submitted" value="1">
                    <div id="prijava-email-document-fields">
                        @foreach($odabranaPrijavaDokumentPolja as $polje)
                            <input type="hidden" name="document_fields[]" value="{{ $polje }}">
                        @endforeach
                    </div>
                    <div class="modal-body overflow-auto">
                        <div class="row g-3">
                            <div class="col-lg-6">
                                <label for="email_to" class="form-label fw-semibold mb-1">E-mail primatelja</label>
                                <input type="text"
                                       class="form-control @error('email_to') is-invalid @enderror"
                                       id="email_to"
                                       name="email_to"
                                       maxlength="1000"
                                       value="{{ old('email_to') }}"
                                       placeholder="npr. prijave@domena.hr; druga@domena.hr"
                                       required>
                                @error('email_to')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-lg-6">
                                <label for="email_subject" class="form-label fw-semibold mb-1">Naslov poruke</label>
                                <input type="text"
                                       class="form-control"
                                       id="email_subject"
                                       name="email_subject"
                                       maxlength="191"
                                       value="{{ old('email_subject', $emailDefaultSubject) }}"
                                       required>
                            </div>
                            <div class="col-12">
                                <label for="email_poruka" class="form-label fw-semibold mb-1">Poruka (možete urediti prije slanja)</label>
                                <textarea class="form-control"
                                          id="email_poruka"
                                          name="email_poruka"
                                          rows="4"
                                          maxlength="5000"
                                          placeholder="Tekst poruke...">{{ old('email_poruka', 'U privitku šaljemo prijavnicu za turnir.') }}</textarea>
                            </div>
                            <div class="col-12">
                                <div class="alert alert-light border mb-0 py-2">
                                    <div class="fw-semibold">Privici koji se šalju:</div>
                                    <div class="small">1. Prijava .docx</div>
                                    <div class="small">2. Prijava .pdf</div>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="fw-semibold mb-2">Preview tablice u tijelu poruke</div>
                                <div class="table-responsive">
                                    @include('admin.nadolazeciTurniri.partials.prijavaDokumentTabela', [
                                        'redovi' => $dokumentPrijava['redovi'],
                                        'kolone' => $dokumentPrijava['kolone'],
                                    ])
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Odustani</button>
                        <button type="submit" class="btn btn-danger">Pošalji e-mail</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="ukloniPrijavuModal" tabindex="-1" aria-labelledby="ukloniPrijavuTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-danger">
                    <h5 class="modal-title text-white" id="ukloniPrijavuTitle">Uklanjanje člana s turnira</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Zatvori"></button>
                </div>
                <form id="ukloniPrijavuForm" method="POST" action="">
                    @csrf
                    <div class="modal-body">
                        <p class="mb-2">
                            Uklanjate prijavu člana:
                            <span class="fw-semibold" id="ukloniPrijavuClanNaziv">-</span>
                        </p>
                        <label for="ukloniPrijavuNapomena" class="form-label fw-semibold mb-1">Razlog uklanjanja</label>
                        <textarea id="ukloniPrijavuNapomena"
                                  class="form-control"
                                  name="napomena_admin"
                                  rows="3"
                                  maxlength="2000"
                                  required
                                  placeholder="Unesite razlog uklanjanja..."></textarea>
                        <div class="small text-muted mt-2">Uklanjanje člana sa turnira od strane administratora.</div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Odustani</button>
                        <button type="submit" class="btn btn-danger">Ukloni</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const optionalFields = @json(array_keys($prijavaDokumentOpcionalnaPolja));
            const fieldCheckboxes = Array.from(document.querySelectorAll('.js-prijava-document-field'))
                .filter((element) => element instanceof HTMLInputElement);
            const exportLinks = Array.from(document.querySelectorAll('.js-prijava-document-export-link'))
                .filter((element) => element instanceof HTMLAnchorElement);
            const emailFormElement = document.getElementById('prijava-email-form');
            const emailForm = emailFormElement instanceof HTMLFormElement ? emailFormElement : null;
            const hiddenFieldsContainer = document.getElementById('prijava-email-document-fields');

            if (fieldCheckboxes.length === 0) {
                return;
            }

            function selectedFields() {
                return fieldCheckboxes
                    .filter((checkbox) => checkbox.checked)
                    .map((checkbox) => String(checkbox.value || ''))
                    .filter((value) => optionalFields.includes(value));
            }

            function syncPreviewColumns() {
                const selected = new Set(selectedFields());
                document.querySelectorAll('[data-document-field]').forEach((element) => {
                    const field = String(element.getAttribute('data-document-field') || '');
                    if (!optionalFields.includes(field)) {
                        return;
                    }

                    element.classList.toggle('d-none', !selected.has(field));
                });
            }

            function syncEmailHiddenFields() {
                if (!(hiddenFieldsContainer instanceof HTMLElement)) {
                    return;
                }

                hiddenFieldsContainer.innerHTML = '';
                selectedFields().forEach((field) => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'document_fields[]';
                    input.value = field;
                    hiddenFieldsContainer.appendChild(input);
                });
            }

            function appendFieldsToUrl(url) {
                url.searchParams.set('document_fields_submitted', '1');
                url.searchParams.delete('document_fields');
                url.searchParams.delete('document_fields[]');

                selectedFields().forEach((field) => {
                    url.searchParams.append('document_fields[]', field);
                });
            }

            fieldCheckboxes.forEach((checkbox) => {
                checkbox.addEventListener('change', function () {
                    syncPreviewColumns();
                    syncEmailHiddenFields();
                });
            });

            exportLinks.forEach((link) => {
                link.addEventListener('click', function () {
                    const url = new URL(link.href, window.location.href);
                    appendFieldsToUrl(url);
                    link.href = url.toString();
                });
            });

            if (emailForm instanceof HTMLFormElement) {
                emailForm.addEventListener('submit', syncEmailHiddenFields);
            }

            syncPreviewColumns();
            syncEmailHiddenFields();
        })();
    </script>

    <script>
        (function () {
            const clanSelectElement = document.getElementById('admin_clan_id');
            const clanSelect = clanSelectElement instanceof HTMLSelectElement ? clanSelectElement : null;
            const kategorijaSelectElement = document.getElementById('admin_kategorija_id');
            const kategorijaSelect = kategorijaSelectElement instanceof HTMLSelectElement ? kategorijaSelectElement : null;
            const statusWrap = document.getElementById('admin-prijava-status-wrap');
            const statusText = document.getElementById('admin-prijava-status-text');
            const submitButtonElement = document.getElementById('admin-dodaj-prijavu-submit');
            const submitButton = submitButtonElement instanceof HTMLButtonElement ? submitButtonElement : null;

            if (!(clanSelect instanceof HTMLSelectElement) || !(kategorijaSelect instanceof HTMLSelectElement)) {
                return;
            }

            const kategorijePoClanu = @json($adminKategorijePoClanu);
            const clanoviMetaZaKategoriju = @json($adminClanoviMetaZaKategoriju);
            const aktivniClanIdsTurnira = new Set((@json($adminAktivniClanIdsTurnira)).map(String));
            const oldKategorijaId = String(kategorijaSelect.dataset.oldValue || '');
            const turnirDatum = @json($turnir->datum?->toDateString());
            let inicijalnaKategorijaPrimijenjena = false;

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

            function referentniDatumZaDob() {
                const datumTurnira = parseIsoDate(turnirDatum);
                if (datumTurnira instanceof Date) {
                    return datumTurnira;
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
                const clanId = String(clanSelect.value || '');
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

            function updateStatusClana() {
                const clanId = String(clanSelect.value || '');
                if (clanId === '') {
                    if (statusWrap instanceof HTMLElement) {
                        statusWrap.classList.remove('d-none');
                    }
                    if (statusText instanceof HTMLElement) {
                        statusText.textContent = 'Nema dostupnih članova za prijavu na ovaj turnir.';
                    }
                    if (submitButton instanceof HTMLButtonElement) {
                        submitButton.disabled = true;
                    }

                    return;
                }

                const vecPrijavljen = aktivniClanIdsTurnira.has(clanId);

                if (vecPrijavljen) {
                    if (statusWrap instanceof HTMLElement) {
                        statusWrap.classList.remove('d-none');
                    }
                    if (statusText instanceof HTMLElement) {
                        statusText.textContent = 'Odabrani član već ima aktivnu prijavu na ovom turniru.';
                    }
                    if (submitButton instanceof HTMLButtonElement) {
                        submitButton.disabled = true;
                    }

                    return;
                }

                if (statusWrap instanceof HTMLElement) {
                    statusWrap.classList.add('d-none');
                }
                if (statusText instanceof HTMLElement) {
                    statusText.textContent = '';
                }
                if (submitButton instanceof HTMLButtonElement) {
                    submitButton.disabled = false;
                }
            }

            clanSelect.addEventListener('change', function () {
                updateKategorije(true);
                updateStatusClana();
            });

            updateKategorije(false);
            updateStatusClana();
        })();
    </script>

    <script>
        (function () {
            const modalEl = document.getElementById('ukloniPrijavuModal');
            const formEl = document.getElementById('ukloniPrijavuForm');
            const clanNazivEl = document.getElementById('ukloniPrijavuClanNaziv');
            const napomenaEl = document.getElementById('ukloniPrijavuNapomena');

            if (!(modalEl instanceof HTMLElement) || !(formEl instanceof HTMLFormElement)) {
                return;
            }

            modalEl.addEventListener('show.bs.modal', function (event) {
                const trigger = event.relatedTarget;
                if (!(trigger instanceof HTMLElement)) {
                    return;
                }

                const action = String(trigger.dataset.action || '').trim();
                const clan = String(trigger.dataset.clan || '').trim();

                formEl.action = action;
                if (clanNazivEl instanceof HTMLElement) {
                    clanNazivEl.textContent = clan !== '' ? clan : '-';
                }
                if (napomenaEl instanceof HTMLTextAreaElement) {
                    napomenaEl.value = '';
                    napomenaEl.focus();
                }
            });
        })();
    </script>

    @if($otvoriPrijavaEmailModal)
        <button type="button"
                id="prijavaEmailAutoOpen"
                class="d-none"
                data-bs-toggle="modal"
                data-bs-target="#posaljiPrijavaEmailModal"
                aria-hidden="true"></button>
        <script>
            window.addEventListener('load', function () {
                const trigger = document.getElementById('prijavaEmailAutoOpen');
                if (trigger instanceof HTMLButtonElement) {
                    trigger.click();
                }
            });
        </script>
    @endif

    @if($otvoriAdminDodajModal)
        <button type="button"
                id="adminDodajPrijavuAutoOpen"
                class="d-none"
                data-bs-toggle="modal"
                data-bs-target="#adminDodajPrijavuModal"
                aria-hidden="true"></button>
        <script>
            window.addEventListener('load', function () {
                const trigger = document.getElementById('adminDodajPrijavuAutoOpen');
                if (trigger instanceof HTMLButtonElement) {
                    trigger.click();
                }
            });
        </script>
    @endif
@endsection
