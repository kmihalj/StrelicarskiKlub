@extends('layouts.app')

@section('content')
    @php
        $mozeNapredniPrikaz = auth()->user()->imaPravoAdminOrMember();
        $showPaymentColumn = (bool)($showPaymentColumn ?? false);
        $aktivniColspan = 3 + ($mozeNapredniPrikaz ? 2 : 0) + ($showPaymentColumn ? 1 : 0);
        $neaktivniColspan = 5 + ($showPaymentColumn ? 1 : 0);
        $roditeljDjecaPolaznici = auth()->user()->jeRoditelj()
            ? auth()->user()->djecaPolaznici()->pluck('polaznici_skole.id')->map(fn ($id) => (int)$id)->all()
            : [];
    @endphp

    <div class="container-xxl bg-white shadow">
        <div class="row justify-content-center pt-3 shadow">
            @if($mozeNapredniPrikaz)
                <div class="col-12 m-1">
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        @if((int)auth()->user()->rola === 1)
                            <button class="btn btn-danger me-md-2" type="button" data-bs-toggle="modal" data-bs-target="#UnosPolaznikaSkole_modal">
                                Dodaj polaznika škole
                            </button>
                        @endif
                        @if((int)auth()->user()->rola === 1)
                            <button class="btn btn-outline-danger" type="button" onclick="location.href='{{ route('javno.skola.evidencija.index') }}'">
                                Evidencija dolazaka - škola
                            </button>
                        @endif
                    </div>
                </div>
            @endif

            @if((int)auth()->user()->rola === 1)
                @include('admin.skola.modal_za_unos')
            @endif

            <div class="col-lg-12 m-3">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 border">
                        <thead class="table-warning">
                        <tr>
                            <th>Ime i prezime</th>
                            <th>Datum rođenja</th>
                            <th>Datum upisa</th>
                            @if($mozeNapredniPrikaz)
                                <th>Telefon</th>
                                <th>E-mail</th>
                            @endif
                            @if($showPaymentColumn)
                                <th>Plaćanja</th>
                            @endif
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($aktivniPolaznici as $polaznik)
                            @php
                                $mozeOtvoritiProfil = $mozeNapredniPrikaz
                                    || ((int)auth()->user()->rola === 4 && (int)auth()->user()->polaznik_id === (int)$polaznik->id)
                                    || in_array((int)$polaznik->id, $roditeljDjecaPolaznici, true);
                            @endphp
                            <tr>
                                <td>
                                    @if($mozeOtvoritiProfil)
                                        <a class="link-primary link-offset-2 link-underline-opacity-0 link-underline-opacity-0-hover fw-bold"
                                           href="{{ route('javno.skola.polaznici.show', $polaznik) }}">
                                            {{ trim((string)$polaznik->Prezime) }} {{ trim((string)$polaznik->Ime) }}
                                        </a>
                                    @else
                                        <span class="fw-bold">{{ trim((string)$polaznik->Prezime) }} {{ trim((string)$polaznik->Ime) }}</span>
                                    @endif
                                </td>
                                <td>{{ empty($polaznik->datum_rodjenja) ? '-' : optional($polaznik->datum_rodjenja)->format('d.m.Y.') }}</td>
                                <td>{{ empty($polaznik->datum_upisa) ? '-' : optional($polaznik->datum_upisa)->format('d.m.Y.') }}</td>

                                @if($mozeNapredniPrikaz)
                                    <td>
                                        @if(!empty($polaznik->br_telefona))
                                            <a href="tel:{{ $polaznik->br_telefona }}">{{ $polaznik->br_telefona }}</a>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>
                                        @if(!empty($polaznik->email))
                                            <a href="mailto:{{ $polaznik->email }}">{{ $polaznik->email }}</a>
                                        @else
                                            -
                                        @endif
                                    </td>
                                @endif
                                @if($showPaymentColumn)
                                    @php $paymentStatus = $paymentStatusByPolaznik[(int)$polaznik->id] ?? null; @endphp
                                    <td>
                                        @if(is_array($paymentStatus))
                                            @if(($paymentStatus['state'] ?? '') === 'paid')
                                                <span class="text-success fw-bold" title="Sve podmireno">&#10003;</span>
                                            @elseif(($paymentStatus['state'] ?? '') === 'pending')
                                                <span class="text-warning fw-semibold">
                                                    2. rata: {{ number_format((float)($paymentStatus['amount'] ?? 0), 2, ',', '.') }} EUR
                                                    ({{ (int)($paymentStatus['attendance'] ?? 0) }}/{{ (int)($paymentStatus['limit'] ?? 8) }})
                                                </span>
                                            @elseif(($paymentStatus['state'] ?? '') === 'debt')
                                                <span class="text-danger fw-bold">
                                                    {{ number_format((float)($paymentStatus['amount'] ?? 0), 2, ',', '.') }} EUR
                                                </span>
                                            @endif
                                        @endif
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $aktivniColspan }}" class="text-center">
                                    <p class="fw-bold mb-1">Nema aktivnih polaznika škole.</p>
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    @if($mozeNapredniPrikaz)
        <div class="container-xxl shadow mt-3">
            <div class="row justify-content-center p-2 shadow bg-danger fw-bolder">
                <div class="col-lg-12 text-white">
                    Neaktivni polaznici škole
                    <span id="skrivanje_neaktivnih_polaznika" class="text-white" style="float: right; cursor: pointer; display: none"
                          onclick="document.getElementById('popis-neaktivnih-polaznika').style.display = 'none';document.getElementById('skrivanje_neaktivnih_polaznika').style.display = 'none';document.getElementById('pokazivanje_neaktivnih_polaznika').style.display = 'block';">_</span>
                    <span id="pokazivanje_neaktivnih_polaznika" class="text-white" style="float: right; cursor: pointer;"
                          onclick="document.getElementById('popis-neaktivnih-polaznika').style.display = 'block';document.getElementById('skrivanje_neaktivnih_polaznika').style.display = 'block';document.getElementById('pokazivanje_neaktivnih_polaznika').style.display = 'none';">+</span>
                </div>
            </div>
        </div>

        <div id="popis-neaktivnih-polaznika" class="container-xxl bg-secondary-subtle shadow" style="display: none">
            <div class="row justify-content-center pt-3 shadow">
                <div class="col-lg-12 m-3">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 border">
                            <thead class="table-warning">
                            <tr>
                                <th>Ime i prezime</th>
                                <th>Datum rođenja</th>
                                <th>Datum upisa</th>
                                <th>Telefon</th>
                                <th>E-mail</th>
                                @if($showPaymentColumn)
                                    <th>Plaćanja</th>
                                @endif
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($neaktivniPolaznici as $polaznik)
                                <tr>
                                    <td>
                                        <a class="link-primary link-offset-2 link-underline-opacity-0 link-underline-opacity-0-hover fw-bold"
                                           href="{{ route('javno.skola.polaznici.show', $polaznik) }}">
                                            {{ trim((string)$polaznik->Prezime) }} {{ trim((string)$polaznik->Ime) }}
                                        </a>
                                    </td>
                                    <td>{{ empty($polaznik->datum_rodjenja) ? '-' : optional($polaznik->datum_rodjenja)->format('d.m.Y.') }}</td>
                                    <td>{{ empty($polaznik->datum_upisa) ? '-' : optional($polaznik->datum_upisa)->format('d.m.Y.') }}</td>
                                    <td>
                                        @if(!empty($polaznik->br_telefona))
                                            <a href="tel:{{ $polaznik->br_telefona }}">{{ $polaznik->br_telefona }}</a>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>
                                        @if(!empty($polaznik->email))
                                            <a href="mailto:{{ $polaznik->email }}">{{ $polaznik->email }}</a>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    @if($showPaymentColumn)
                                        @php $paymentStatus = $paymentStatusByPolaznik[(int)$polaznik->id] ?? null; @endphp
                                        <td>
                                            @if(is_array($paymentStatus))
                                                @if(($paymentStatus['state'] ?? '') === 'paid')
                                                    <span class="text-success fw-bold" title="Sve podmireno">&#10003;</span>
                                                @elseif(($paymentStatus['state'] ?? '') === 'pending')
                                                    <span class="text-warning fw-semibold">
                                                        2. rata: {{ number_format((float)($paymentStatus['amount'] ?? 0), 2, ',', '.') }} EUR
                                                        ({{ (int)($paymentStatus['attendance'] ?? 0) }}/{{ (int)($paymentStatus['limit'] ?? 8) }})
                                                    </span>
                                                @elseif(($paymentStatus['state'] ?? '') === 'debt')
                                                    <span class="text-danger fw-bold">
                                                        {{ number_format((float)($paymentStatus['amount'] ?? 0), 2, ',', '.') }} EUR
                                                    </span>
                                                @endif
                                            @endif
                                        </td>
                                    @endif
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ $neaktivniColspan }}" class="text-center">
                                        <p class="fw-bold mb-1">Nema neaktivnih polaznika škole.</p>
                                    </td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endsection
