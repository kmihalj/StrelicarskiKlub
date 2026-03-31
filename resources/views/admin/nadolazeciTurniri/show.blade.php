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
                <div><span class="fw-semibold">Datum:</span> {{ $turnir->datum?->format('d.m.Y.') ?? '-' }}</div>
                <div><span class="fw-semibold">Organizator:</span> {{ $turnir->organizator ?: '-' }}</div>
                <div><span class="fw-semibold">Mjesto:</span> {{ $turnir->mjesto }}</div>
                <div><span class="fw-semibold">Tip turnira:</span> {{ $turnir->tipTurnira->naziv ?? '-' }}</div>
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
                        <th>Napomena</th>
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
                                    $lijecnickiWarning = 'Član nema evidentiran važeći liječnički pregled. Potrebno je obaviti pregled prije turnira i dostaviti dokument klubu.';
                                } else {
                                    try {
                                        $lijecnickiDo = \Carbon\Carbon::parse((string) $clan->lijecnicki_do)->endOfDay();
                                        if ($lijecnickiDo->lt($turnirDatum->copy()->startOfDay())) {
                                            $lijecnickiWarning = 'Liječnički pregled ističe '.$lijecnickiDo->format('d.m.Y.').'. - prije turnira. Potrebno je obaviti novi pregled i dostaviti dokument klubu.';
                                        }
                                    } catch (\Throwable) {
                                        $lijecnickiWarning = 'Liječnički pregled člana nije valjano evidentiran. Potrebno je provjeriti dokumentaciju prije turnira.';
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
                                <div class="fw-semibold">{{ $prijava->clan?->Prezime }} {{ $prijava->clan?->Ime }}</div>
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
                            <td>
                                @if($prijava->napomena_admin)
                                    <span class="small">{{ $prijava->napomena_admin }}</span>
                                @else
                                    -
                                @endif
                            </td>
                            <td class="text-end">
                                @if($prijava->status === \App\Models\PrijavaTurnira::STATUS_ACTIVE)
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
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center">Nema prijava za ovaj turnir.</td>
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
                        <th>Napomena</th>
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
                                <div class="fw-semibold">{{ $prijava->clan?->Prezime }} {{ $prijava->clan?->Ime }}</div>
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
                                @if($prijava->napomena_admin)
                                    <span class="small">{{ $prijava->napomena_admin }}</span>
                                @else
                                    -
                                @endif
                            </td>
                            <td>{{ $prijava->removed_at?->format('d.m.Y. H:i') ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center">Nema uklonjenih članova.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
