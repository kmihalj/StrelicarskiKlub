{{-- Administratorski pregled prijava na jedan nadolazeći turnir. --}}
@extends('layouts.app')

@section('content')
    <div class="container-xxl bg-white shadow mb-3">
        <div class="row justify-content-center p-2 shadow bg-danger fw-bolder">
            <div class="col-lg-12 text-white d-flex justify-content-between align-items-center">
                <span>Prijave - {{ $turnir->naziv }}</span>
                <a href="{{ route('admin.nadolazeci_turniri.index') }}" class="btn btn-sm btn-light">Povratak</a>
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
                <div class="border rounded p-3">
                    <div class="fw-semibold mb-2">CSV export prijava</div>
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
            <div class="col-12 table-responsive">
                <table class="table table-hover align-middle mb-0 border">
                    <thead class="table-warning">
                    <tr>
                        <th>Status</th>
                        <th>Član</th>
                        <th>Kategorija</th>
                        <th>Stil</th>
                        <th>KUP</th>
                        <th>Smjena</th>
                        <th>Kotizacija</th>
                        <th></th>
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
                            <td>{{ $prijava->smjena ?: '-' }}</td>
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
                                    <div class="d-inline-flex flex-column align-items-end gap-1">
                                        <form action="{{ route('admin.nadolazeci_turniri.prijave.ukloni', [$turnir, $prijava]) }}"
                                              method="POST"
                                              class="d-flex flex-wrap flex-lg-nowrap justify-content-end align-items-center gap-2">
                                            @csrf
                                            <input type="text"
                                                   class="form-control form-control-sm"
                                                   style="max-width: 320px;"
                                                   name="napomena_admin"
                                                   placeholder="Napomena zašto je član maknut"
                                                   required>
                                            <button type="submit"
                                                    class="btn btn-sm btn-danger"
                                                    onclick="return confirm('Maknuti člana s turnira?')">Ukloni</button>
                                        </form>
                                        <span class="small text-muted">Uklanjanje člana sa turnira od strane administratora.</span>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center">Nema prijava za ovaj turnir.</td>
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
                        <th>Smjena</th>
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
                            <td>{{ $prijava->smjena ?: '-' }}</td>
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
                            <td colspan="8" class="text-center">Nema uklonjenih članova.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
