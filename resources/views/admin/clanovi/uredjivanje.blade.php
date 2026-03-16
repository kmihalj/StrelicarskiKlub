{{-- Administratorski profil člana: podaci, dokumenti, liječnički, treninzi i plaćanja. --}}
@extends('layouts.app')

@section('content')
    @php
        $otvoriDokumente = request()->boolean('open_documents');
        $otvoriPlacanja = $otvoriPlacanja ?? request()->boolean('open_payments');
        $paymentSetup = $paymentSetup ?? app(\App\Services\PaymentTrackingService::class)->setupViewData();
        $paymentSummary = $paymentSummary ?? app(\App\Services\PaymentTrackingService::class)->memberSummary($clan);
        $paymentNotice = $paymentNotice ?? app(\App\Services\PaymentTrackingService::class)->noticeForClan($clan);
        $paymentTrackingEnabled = (bool)($paymentSetup['paymentTrackingEnabled'] ?? false);
        $paymentOptions = $paymentSetup['paymentOptions'] ?? collect();
        $paymentProfile = $paymentSummary['profile'] ?? null;
        $paymentCharges = $paymentSummary['charges'] ?? collect();
    @endphp
    <div class="row">
        <div class="col-12 mb-2 mt-2">
            <div class="card">
                <div class="card-header bg-danger fw-bolder text-white">{{ $clan->Ime }} {{ $clan->Prezime }}</div>
                <div class="card-body bg-secondary-subtle shadow">
                    <form id="uredjivanje_clana" action="{{ route('admin.clanovi.update', $clan) }}" method="POST">
                        <input type="hidden" id="clan_id" name="clan_id" value="{{ $clan->id }}">
                        @csrf
                    </form>
                    <form id="brisanje_clana" action="{{ route('admin.clanovi.brisanje_clana', $clan) }}" method="POST">
                        @csrf
                    </form>
                    <div class="row">
                        <div class="col-lg-2">
                            <div class="row">
                                <div class="col">
                                    <p>Slika:</p>
                                    <div class="container">
                                        @if((empty($clan->slika_link)))
                                            <img src="@if( $clan->spol == 'M') {{ asset('storage/slike/avatar_m.png') }} @else {{ asset('storage/slike/avatar_f.png') }} @endif" class="img-thumbnail" alt="">
                                        @else
                                            <img src="{{ asset('storage/slike_clanova/' . $clan->slika_link) }}" class="img-thumbnail" alt="">
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <form id="brisanje_slike_clana" action="{{ route('admin.clanovi.brisanje_slike_clana', $clan->id) }}" method="POST">
                                @csrf
                                <input type="hidden" id="clan_id" name="clan_id" value="{{ $clan->id }}">
                            </form>

                            <div class="row">
                                <div class="col">
                                    <div class="container mt-2">
                                        <button type="button" class="btn btn-outline-primary" title="Upload" data-bs-toggle="modal" data-bs-target="#upload_slike">
                                            @include('admin.SVG.upload')
                                        </button>
                                        <button type="submit" form="brisanje_slike_clana" class="btn btn-outline-danger float-end" title="Delete" onclick="return confirm('Da li ste sigurni da želite obrisati sliku člana ?')">
                                            @include('admin.SVG.obrisi')
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-10">
                            <div class="row">
                                <div class="col-lg-6 mb-2">
                                    <label for="Prezime">Prezime:</label>
                                    <input type="text" form="uredjivanje_clana" class="form-control" name="Prezime" id="Prezime" value="{{ $clan->Prezime }}" required>
                                </div>
                                <div class="col-lg-6 mb-2">
                                    <label for="Ime">Ime:</label>
                                    <input type="text" form="uredjivanje_clana" class="form-control" name="Ime" id="Ime" value="{{ $clan->Ime }}" required>
                                </div>
                                <div class="col-lg-3 mb-2">
                                    <label for="datum_rodjenja">Datum rođenja:</label>
                                    <input type="date" form="uredjivanje_clana" class="form-control" name="datum_rodjenja" id="datum_rodjenja" value="{{ $clan->datum_rodjenja }}" required>
                                </div>
                                <div class="col-lg-3 mb-2">
                                    <label for="oib">OIB:</label>
                                    <input type="text" form="uredjivanje_clana" class="form-control" name="oib" id="oib" value="{{ $clan->oib }}" required>
                                </div>
                                <div class="col-lg-3 mb-2">
                                    <label for="br_telefona">Br. telefona:</label>
                                    <input type="tel" form="uredjivanje_clana" class="form-control" name="br_telefona" id="br_telefona" value="{{ $clan->br_telefona }}">
                                </div>
                                <div class="col-lg-3 mb-2">
                                    <label for="email">E-mail:</label>
                                    <input type="email" form="uredjivanje_clana" class="form-control" name="email" id="email" value="{{ $clan->email }}">
                                </div>
                                <div class="col-lg-3 mb-2">
                                    <label for="datum_pocetka_clanstva" class="fw-bold">Datum početka članstva:</label>
                                    <input type="date" form="uredjivanje_clana" class="form-control" name="datum_pocetka_clanstva" id="datum_pocetka_clanstva"
                                           value="{{ optional($clan->datum_pocetka_clanstva)->format('Y-m-d') }}">
                                </div>
                                <div class="col-lg-3 mb-2">
                                    <label for="broj_licence">Br. licence:</label>
                                    <input type="text" form="uredjivanje_clana" class="form-control" name="broj_licence" id="broj_licence" value="{{ $clan->broj_licence }}">
                                </div>
                                <div class="col-lg-3 mb-2">
                                    <label for="spol">Spol:</label>
                                    <select form="uredjivanje_clana" class="form-select" id="spol" name="spol" required>
                                        <option value="M" @if($clan->spol == 'M') selected @endif>Muško</option>
                                        <option value="Ž" @if($clan->spol == 'Ž') selected @endif>Žensko</option>
                                    </select>
                                </div>
                                <div class="col-lg-3 mb-2 align-self-end">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" form="uredjivanje_clana" id="aktivan" name="aktivan"
                                               @if($clan->aktivan) value=true checked @else value=false @endif>
                                        <label class="form-check-label" for="aktivan">Aktivan član</label>
                                    </div>
                                </div>
                                <div class="col-lg-3 mb-2">
                                    <label for="lijecnicki_do">Zadnji liječnički vrijedi do:</label>
                                    <input type="date" class="form-control" id="lijecnicki_do" value="{{ $clan->lijecnicki_do }}" disabled>
                                </div>
                                <div class="col-lg-12 mb-2 align-self-end">
                                    <div class="d-grid gap-2 d-md-flex justify-content-between align-items-center">
                                        <div class="d-grid gap-2 d-md-flex">
                                            <button class="btn btn-outline-danger" type="submit" form="brisanje_clana"
                                                    onclick="return confirm('Da li ste sigurni da želite obrisati člana ?')">
                                                Obriši člana
                                            </button>
                                        </div>
                                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                            <button class="btn btn-primary me-md-2" type="submit" form="uredjivanje_clana">Spremi</button>
                                            <button class="btn btn-outline-success" type="button" onclick="location.href='{{ route('javno.clanovi') }}'">Popis članova</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @auth
        @if(auth()->user()->rola <= 1 && $paymentTrackingEnabled)
            <div class="card mt-3">
                <div class="card-header bg-danger fw-bolder text-white">
                    Praćenje plaćanja člana
                    <span id="skrivanje_admin_placanja" class="text-white" style="float: right; cursor: pointer; @if($otvoriPlacanja) display: block; @else display: none; @endif"
                          onclick="document.getElementById('admin_placanja_dropdown').style.display = 'none';document.getElementById('skrivanje_admin_placanja').style.display = 'none';document.getElementById('pokazivanje_admin_placanja').style.display = 'block';">_</span>
                    <span id="pokazivanje_admin_placanja" class="text-white" style="float: right; cursor: pointer; @if($otvoriPlacanja) display: none; @endif"
                          onclick="document.getElementById('admin_placanja_dropdown').style.display = 'block';document.getElementById('skrivanje_admin_placanja').style.display = 'block';document.getElementById('pokazivanje_admin_placanja').style.display = 'none';">+</span>
                </div>
                <div id="admin_placanja_dropdown" class="card-body bg-secondary-subtle shadow" style="@if($otvoriPlacanja) display: block; @else display: none; @endif">
                    @if(!empty($paymentNotice))
                        <div class="alert alert-{{ $paymentNotice['variant'] ?? 'secondary' }} mb-3">
                            <div class="fw-bold">{{ $paymentNotice['title'] ?? 'Status plaćanja' }}</div>
                            <div>{{ $paymentNotice['message'] ?? '' }}</div>
                        </div>
                    @endif

                    <div class="card mb-3">
                        <div class="card-header bg-warning text-dark fw-bold">Model plaćanja člana</div>
                        <div class="card-body">
                            <form action="{{ route('admin.clanovi.placanja.profil', $clan) }}" method="POST">
                                @csrf
                                @php
                                    $selectedOptionId = old('membership_payment_option_id', $paymentProfile?->membership_payment_option_id);
                                    $startDateValue = old('start_date', optional($paymentProfile?->start_date)->format('Y-m-d') ?? now()->toDateString());
                                    $selectedOptionModel = $paymentProfile?->paymentOption;
                                    $optionsForSelect = collect($paymentOptions->all());
                                    if ($selectedOptionModel !== null && !$optionsForSelect->contains(fn ($option) => (int)$option->id === (int)$selectedOptionModel->id)) {
                                        $optionsForSelect->push($selectedOptionModel);
                                    }
                                    $optionPool = $optionsForSelect
                                        ->filter(fn ($option) => !($option->is_archived ?? false) || (int)$option->id === (int)$selectedOptionId)
                                        ->values();

                                    $pickOption = function (string $periodType, ?string $exactAnchor = null, array $anchorPriority = []) use ($optionPool, $selectedOptionId) {
                                        $candidates = $optionPool->filter(function ($option) use ($periodType, $exactAnchor) {
                                            if ((string)($option->period_type ?? '') !== $periodType) {
                                                return false;
                                            }

                                            if ($exactAnchor === null) {
                                                return true;
                                            }

                                            return (string)($option->period_anchor ?? '') === $exactAnchor;
                                        });

                                        if ($candidates->isEmpty()) {
                                            return null;
                                        }

                                        $selectedCandidate = $candidates->first(fn ($option) => (int)$option->id === (int)$selectedOptionId);
                                        if ($selectedCandidate !== null) {
                                            return $selectedCandidate;
                                        }

                                        $enabledCandidates = $candidates->filter(fn ($option) => (bool)($option->is_enabled ?? false))->values();
                                        if ($enabledCandidates->isEmpty()) {
                                            return null;
                                        }

                                        $preferredPool = $enabledCandidates;

                                        foreach ($anchorPriority as $anchorValue) {
                                            $anchorCandidate = $preferredPool->first(fn ($option) => (string)($option->period_anchor ?? '') === $anchorValue);
                                            if ($anchorCandidate !== null) {
                                                return $anchorCandidate;
                                            }
                                        }

                                        return $preferredPool->sortBy([
                                            ['sort_order', 'asc'],
                                            ['id', 'asc'],
                                        ])->first();
                                    };

                                    $monthlyOptions = $optionPool
                                        ->filter(fn ($option) => (string)($option->period_type ?? '') === 'monthly')
                                        ->sortBy([
                                            ['sort_order', 'asc'],
                                            ['id', 'asc'],
                                        ])
                                        ->values();

                                    $memberModelOptions = collect();

                                    $exemptOption = $pickOption('exempt');
                                    if ($exemptOption !== null) {
                                        $memberModelOptions->push(['label' => 'Oslobođen', 'option' => $exemptOption]);
                                    }

                                    $monthlyOptionsCount = $monthlyOptions->count();
                                    foreach ($monthlyOptions as $monthlyOption) {
                                        $isCashMonthly = (string)($monthlyOption->collection_method ?? 'bank') === 'cash';
                                        $baseLabel = $isCashMonthly ? 'Mjesečno (gotovina treneru)' : 'Mjesečno';
                                        $optionName = trim((string)($monthlyOption->name ?? ''));
                                        $label = ($monthlyOptionsCount > 1 && $optionName !== '')
                                            ? $baseLabel . ' - ' . $optionName
                                            : $baseLabel;
                                        $memberModelOptions->push(['label' => $label, 'option' => $monthlyOption]);
                                    }

                                    $seasonalOption = $pickOption('seasonal', null, ['oct', 'apr', 'both']);
                                    if ($seasonalOption !== null) {
                                        $memberModelOptions->push(['label' => 'Sezonski', 'option' => $seasonalOption]);
                                    }

                                    $annualAprOption = $pickOption('annual', 'apr');
                                    if ($annualAprOption !== null) {
                                        $memberModelOptions->push(['label' => 'Godišnje od 01.04.', 'option' => $annualAprOption]);
                                    }

                                    $annualOctOption = $pickOption('annual', 'oct');
                                    if ($annualOctOption !== null) {
                                        $memberModelOptions->push(['label' => 'Godišnje od 01.10.', 'option' => $annualOctOption]);
                                    }
                                @endphp
                                <div class="row g-2 align-items-end">
                                    <div class="col-md-6">
                                        <label for="membership_payment_option_id" class="form-label">Model plaćanja</label>
                                        <select class="form-select" id="membership_payment_option_id" name="membership_payment_option_id">
                                            <option value="">-- nije odabrano --</option>
                                            @foreach($memberModelOptions as $modelOption)
                                            @php $option = $modelOption['option']; @endphp
                                            <option value="{{ $option->id }}" @selected((int)$selectedOptionId === (int)$option->id)>
                                                    {{ $modelOption['label'] }}
                                                    @if(($option->is_archived ?? false))
                                                        (arhivirano)
                                                    @elseif(!$option->is_enabled)
                                                        (nije aktivno u postavkama)
                                                    @endif
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label for="start_date" class="form-label">Model vrijedi od</label>
                                        <input type="date" class="form-control" id="start_date" name="start_date"
                                               value="{{ $startDateValue }}" required>
                                    </div>
                                    <div class="col-md-3 text-end">
                                        <button type="submit" class="btn btn-primary text-nowrap px-3">Spremi</button>
                                    </div>
                                </div>
                                <div class="small text-muted mt-2">
                                    @if($paymentProfile?->paymentOption)
                                        Trenutni model: <strong>{{ $paymentProfile->paymentOption->name }}</strong>
                                        (vrijedi od {{ optional($paymentProfile->start_date)->format('d.m.Y.') }})
                                    @else
                                        Trenutni model nije postavljen.
                                    @endif
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="card mb-3">
                        <div class="card-header bg-light fw-bold">Dodatna plaćanja (najam opreme, dvorana, ostalo)</div>
                        <div class="card-body">
                            <form action="{{ route('admin.clanovi.placanja.manual', $clan) }}" method="POST">
                                @csrf
                                <div class="row g-2 align-items-end">
                                    <div class="col-lg-4">
                                        <label for="manual_title" class="form-label">Naziv</label>
                                        <input type="text" class="form-control" id="manual_title" name="title" required>
                                    </div>
                                    <div class="col-lg-3">
                                        <label for="manual_description" class="form-label">Opis</label>
                                        <input type="text" class="form-control" id="manual_description" name="description">
                                    </div>
                                    <div class="col-lg-2">
                                        <label for="manual_amount" class="form-label">Iznos (EUR)</label>
                                        <input type="text" class="form-control" id="manual_amount" name="amount" placeholder="0.00" required>
                                    </div>
                                    <div class="col-lg-2">
                                        <label for="manual_due_date" class="form-label">Datum zaduženja</label>
                                        <input type="date" class="form-control" id="manual_due_date" name="due_date"
                                               value="{{ old('due_date', now()->toDateString()) }}">
                                    </div>
                                    <div class="col-lg-1 text-end">
                                        <button type="submit" class="btn btn-primary text-nowrap px-3">Dodaj</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="card mb-2">
                        <div class="card-header bg-light fw-bold">Popis stavki plaćanja</div>
                        <div class="card-body">
                            @if($paymentCharges->count() === 0)
                                <p class="mb-0">Nema unosa plaćanja za člana.</p>
                            @else
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover align-middle mb-0">
                                        <thead class="table-warning">
                                        <tr>
                                            <th>Naziv</th>
                                            <th>Razdoblje</th>
                                            <th>Iznos</th>
                                            <th>Status</th>
                                            <th>Datum uplate</th>
                                            <th>Tip</th>
                                            <th class="text-end">Akcija</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @php $paymentService = app(\App\Services\PaymentTrackingService::class); @endphp
                                        @foreach($paymentCharges as $charge)
                                            @php
                                                $statusFormId = 'status_placanja_' . $charge->id;
                                                $deleteFormId = 'obrisi_placanje_' . $charge->id;
                                                $isPaid = $charge->status === \App\Services\PaymentTrackingService::STATUS_PAID;
                                                $canDeleteCharge = true;
                                                $variantOptions = $paymentService->availableVariantsForCharge($charge);
                                                $selectedVariant = $paymentService->selectedVariantForCharge($charge, !$isPaid);
                                                $displayAmountValue = number_format((float)$paymentService->resolvedChargeAmount($charge, true), 2, '.', '');
                                            @endphp
                                            <form id="{{ $statusFormId }}" action="{{ route('admin.clanovi.placanja.status', [$clan, $charge]) }}" method="POST">
                                                @csrf
                                                <input type="hidden" name="is_paid" value="{{ $isPaid ? 0 : 1 }}">
                                            </form>
                                            <form id="{{ $deleteFormId }}" action="{{ route('admin.clanovi.placanja.destroy', [$clan, $charge]) }}" method="POST">
                                                @csrf
                                            </form>
                                            <tr>
                                                <td>{{ $charge->title }}</td>
                                                <td>
                                                    @if($charge->period_start && $charge->period_end)
                                                        {{ $charge->period_start->format('d.m.Y.') }} - {{ $charge->period_end->format('d.m.Y.') }}
                                                    @elseif($charge->due_date)
                                                        Zaduženje: {{ $charge->due_date->format('d.m.Y.') }}
                                                    @else
                                                        -
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($isPaid)
                                                        {{ number_format((float)$charge->amount, 2, ',', '.') }} EUR
                                                    @else
                                                        <div class="input-group input-group-sm">
                                                            <input type="text"
                                                                   class="form-control text-end js-charge-amount-input"
                                                                   data-charge-id="{{ $charge->id }}"
                                                                   data-manual="0"
                                                                   name="amount"
                                                                   form="{{ $statusFormId }}"
                                                                   value="{{ $displayAmountValue }}">
                                                            <span class="input-group-text">EUR</span>
                                                        </div>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($isPaid)
                                                        <span class="badge bg-success">Plaćeno</span>
                                                    @else
                                                        <span class="badge bg-danger">Nije plaćeno</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($charge->paid_at)
                                                        {{ $charge->paid_at->format('d.m.Y.') }}
                                                    @elseif(!$isPaid)
                                                        <input type="date" class="form-control form-control-sm"
                                                               name="paid_at" form="{{ $statusFormId }}"
                                                               value="{{ now()->toDateString() }}">
                                                    @else
                                                        -
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($charge->source === \App\Services\PaymentTrackingService::SOURCE_AUTO)
                                                        Članarina
                                                    @elseif($charge->source === \App\Services\PaymentTrackingService::SOURCE_OPENING)
                                                        Početni dug
                                                    @else
                                                        Dodatno
                                                    @endif
                                                </td>
                                                <td class="text-end">
                                                    <div class="d-inline-flex flex-nowrap justify-content-end align-items-center gap-2">
                                                        @if(!$isPaid && count($variantOptions) > 0)
                                                            <select class="form-select form-select-sm js-payment-variant-select"
                                                                    data-charge-id="{{ $charge->id }}"
                                                                    style="min-width: 280px;"
                                                                    name="payment_variant" form="{{ $statusFormId }}">
                                                                @foreach($variantOptions as $variantOption)
                                                                    @php
                                                                        $variantValue = $variantOption['value'] ?? '';
                                                                        $variantLabel = $variantOption['label'] ?? $variantValue;
                                                                        $variantAmount = isset($variantOption['amount']) ? (float)$variantOption['amount'] : null;
                                                                    @endphp
                                                                    <option value="{{ $variantValue }}"
                                                                            data-variant-amount="{{ $variantAmount !== null ? number_format($variantAmount, 2, '.', '') : '' }}"
                                                                            @selected($selectedVariant === $variantValue)>
                                                                        {{ $variantLabel }}
                                                                        @if($variantAmount !== null)
                                                                            ({{ number_format($variantAmount, 2, ',', '.') }} EUR)
                                                                        @endif
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                        @endif
                                                        @if($isPaid)
                                                            <button type="submit" form="{{ $statusFormId }}" class="btn btn-outline-secondary btn-sm text-nowrap">
                                                                Vrati na neplaćeno
                                                            </button>
                                                        @else
                                                            <button type="submit" form="{{ $statusFormId }}" class="btn btn-outline-success btn-sm text-nowrap">
                                                                Potvrdi uplatu
                                                            </button>
                                                        @endif
                                                        @if($canDeleteCharge)
                                                            <button type="submit" form="{{ $deleteFormId }}" class="btn btn-outline-danger btn-sm text-nowrap"
                                                                    onclick="return confirm('Da li ste sigurni da želite obrisati ovu stavku plaćanja?')">
                                                                Obriši
                                                            </button>
                                                        @endif
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endif
    @endauth

    @auth
        @if(auth()->user()->rola <= 1)
            <div class="card mt-3">
                <div class="card-header bg-danger fw-bolder text-white">
                    Liječnički pregledi i dokumenti člana
                    <span id="skrivanje_admin_dokumenata" class="text-white" style="float: right; cursor: pointer; @if($otvoriDokumente) display: block; @else display: none; @endif"
                          onclick="document.getElementById('admin_dokumenti_dropdown').style.display = 'none';document.getElementById('skrivanje_admin_dokumenata').style.display = 'none';document.getElementById('pokazivanje_admin_dokumenata').style.display = 'block';">_</span>
                    <span id="pokazivanje_admin_dokumenata" class="text-white" style="float: right; cursor: pointer; @if($otvoriDokumente) display: none; @endif"
                          onclick="document.getElementById('admin_dokumenti_dropdown').style.display = 'block';document.getElementById('skrivanje_admin_dokumenata').style.display = 'block';document.getElementById('pokazivanje_admin_dokumenata').style.display = 'none';">+</span>
                </div>
                <div id="admin_dokumenti_dropdown" class="card-body bg-secondary-subtle shadow" style="@if($otvoriDokumente) display: block; @else display: none; @endif">
                    <div class="row">
                    <div class="col-lg-12">
                        <div class="card mb-3">
                            <div class="card-header bg-warning text-dark fw-bold">Novi liječnički pregled</div>
                            <div class="card-body">
                                <form action="{{ route('admin.clanovi.spremi_lijecnicki_pregled', $clan) }}" enctype="multipart/form-data" method="POST">
                                    @csrf
                                    <div class="row align-items-end">
                                        <div class="col-lg-3 mb-2">
                                            <label for="vrijedi_do_novi">Vrijedi do:</label>
                                            <input type="date" class="form-control" id="vrijedi_do_novi" name="vrijedi_do" required>
                                        </div>
                                        <div class="col-lg-7 mb-2">
                                            <label for="lijecnicki_dokument_novi">PDF:</label>
                                            <input type="file" class="form-control" id="lijecnicki_dokument_novi" name="lijecnicki_dokument" required>
                                        </div>
                                        <div class="col-lg-1 mb-2 text-end">
                                            <button type="submit" class="btn btn-primary">Spremi</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <div class="card mb-4">
                            <div class="card-header bg-light fw-bold">Popis liječničkih pregleda</div>
                            <div class="card-body">
                                @if($clan->lijecnickiPregledi->count() == 0)
                                    <p class="mb-0">Nema spremljenih liječničkih pregleda.</p>
                                @else
                                    <div class="table-responsive">
                                        <table class="table table-sm table-hover align-middle mb-0">
                                            <thead class="table-warning">
                                            <tr>
                                                <th>Vrijedi do</th>
                                                <th>Upload PDF</th>
                                                <th>Dokument</th>
                                                <th class="text-end">Akcije</th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            @foreach($clan->lijecnickiPregledi as $pregled)
                                                <form id="spremi_lijecnicki_{{ $pregled->id }}" action="{{ route('admin.clanovi.update_lijecnicki_pregled', [$clan, $pregled]) }}" enctype="multipart/form-data" method="POST">
                                                    @csrf
                                                </form>
                                                <form id="obrisi_lijecnicki_{{ $pregled->id }}" action="{{ route('admin.clanovi.obrisi_lijecnicki_pregled', [$clan, $pregled]) }}" method="POST">
                                                    @csrf
                                                </form>
                                                <tr>
                                                    <td>
                                                        <input type="date" class="form-control form-control-sm" name="vrijedi_do" form="spremi_lijecnicki_{{ $pregled->id }}" value="{{ optional($pregled->vrijedi_do)->format('Y-m-d') }}" required>
                                                    </td>
                                                    <td>
                                                        @if(empty($pregled->putanja))
                                                            <input type="file" class="form-control form-control-sm" name="lijecnicki_dokument" form="spremi_lijecnicki_{{ $pregled->id }}">
                                                        @else
                                                            <span class="text-muted">Datoteka postoji</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        @if(!empty($pregled->putanja))
                                                            <a class="link-success" href="{{ route('admin.clanovi.preuzmi_lijecnicki_pregled', [$clan, $pregled]) }}" target="_blank">Pregled</a>
                                                        @else
                                                            -
                                                        @endif
                                                    </td>
                                                    <td class="text-end">
                                                        <button type="submit" form="spremi_lijecnicki_{{ $pregled->id }}" class="btn text-success btn-rounded" title="Spremi">
                                                            @include('admin.SVG.unos')
                                                        </button>
                                                        <button type="submit" form="obrisi_lijecnicki_{{ $pregled->id }}" class="btn text-danger btn-rounded" title="Obriši"
                                                                onclick="return confirm('Da li ste sigurni da želite obrisati liječnički pregled ?')">
                                                            @include('admin.SVG.obrisi')
                                                        </button>
                                                    </td>
                                                </tr>
                                            @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div class="card mb-3">
                            <div class="card-header bg-warning text-dark fw-bold">Novi dokument člana</div>
                            <div class="card-body">
                                <form action="{{ route('admin.clanovi.spremi_dokument', $clan) }}" enctype="multipart/form-data" method="POST">
                                    @csrf
                                    <div class="row align-items-end">
                                        <div class="col-lg-2 mb-2">
                                            <label for="vrsta_novi_dokument">Vrsta:</label>
                                            <select class="form-select" id="vrsta_novi_dokument" name="vrsta" required>
                                                <option value="" disabled selected>Odaberi</option>
                                                <option value="Upisnica">Upisnica</option>
                                                <option value="GDPR">GDPR</option>
                                                <option value="Slika">Slika</option>
                                                <option value="Ostalo">Ostalo</option>
                                            </select>
                                        </div>
                                        <div class="col-lg-2 mb-2">
                                            <label for="naziv_novi_dokument">Naziv:</label>
                                            <input type="text" class="form-control" id="naziv_novi_dokument" name="naziv" readonly>
                                        </div>
                                        <div class="col-lg-2 mb-2">
                                            <label for="datum_novi_dokument">Datum dokumenta:</label>
                                            <input type="date" class="form-control" id="datum_novi_dokument" name="datum_dokumenta">
                                        </div>
                                        <div class="col-lg-3 mb-2">
                                            <label for="napomena_novi_dokument">Napomena:</label>
                                            <input type="text" class="form-control" id="napomena_novi_dokument" name="napomena">
                                        </div>
                                        <div class="col-lg-2 mb-2">
                                            <label for="datoteka_novi_dokument">Datoteka:</label>
                                            <input type="file" class="form-control" id="datoteka_novi_dokument" name="dokument" required>
                                        </div>
                                        <div class="col-lg-1 mb-2 text-end">
                                            <button type="submit" class="btn btn-primary">Spremi</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <div class="card mb-2">
                            <div class="card-header bg-light fw-bold">Popis dokumenata člana</div>
                            <div class="card-body">
                                @if($clan->dokumenti->count() == 0)
                                    <p class="mb-0">Nema spremljenih dokumenata.</p>
                                @else
                                    <div class="table-responsive">
                                        <table class="table table-sm table-hover align-middle mb-0">
                                            <thead class="table-warning">
                                            <tr>
                                                <th>Naziv</th>
                                                <th>Datum</th>
                                                <th>Napomena</th>
                                                <th>Datoteka</th>
                                                <th class="text-end">Akcije</th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            @foreach($clan->dokumenti as $dokument)
                                                <form id="obrisi_dokument_{{ $dokument->id }}" action="{{ route('admin.clanovi.obrisi_dokument', [$clan, $dokument]) }}" method="POST">
                                                    @csrf
                                                </form>
                                                <tr>
                                                    <td>{{ $dokument->naziv }}</td>
                                                    <td>{{ optional($dokument->datum_dokumenta)->format('d.m.Y.') }}</td>
                                                    <td>{{ $dokument->napomena ?: '-' }}</td>
                                                    <td>
                                                        @if(!empty($dokument->putanja))
                                                            <a class="link-success" href="{{ route('admin.clanovi.preuzmi_dokument', [$clan, $dokument]) }}" target="_blank">Pregled</a>
                                                        @else
                                                            -
                                                        @endif
                                                    </td>
                                                    <td class="text-end">
                                                        <button type="submit" form="obrisi_dokument_{{ $dokument->id }}" class="btn text-danger btn-rounded" title="Obriši"
                                                                onclick="return confirm('Da li ste sigurni da želite obrisati dokument ?')">
                                                            @include('admin.SVG.obrisi')
                                                        </button>
                                                    </td>
                                                </tr>
                                            @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    </div>
                </div>
            </div>
        @endif
    @endauth

    <div class="modal fade" id="upload_slike">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-danger">
                    <h4 class="modal-title text-white">Dodavanje slike člana</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="upload_slike_clana" action="{{ route('admin.clanovi.upload_slike_clana', $clan->id) }}" enctype="multipart/form-data" method="POST">
                        @csrf
                        <div class="row pb-4">
                            <div class="col-10">
                                <input type="hidden" id="clan_id" name="clan_id" value="{{ $clan->id }}">
                                <input class="form-control" type="file" id="clan_slika" name="clan_slika">
                            </div>
                            <div class="col-2">
                                <button type="submit" class="btn btn-primary float-end">Upload</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const vrsta = document.getElementById('vrsta_novi_dokument');
            const naziv = document.getElementById('naziv_novi_dokument');
            if (!vrsta || !naziv) return;

            const syncNaziv = () => {
                if (!vrsta.value) {
                    naziv.value = '';
                    naziv.readOnly = true;
                    naziv.required = false;
                    return;
                }

                if (vrsta.value === 'Ostalo') {
                    naziv.readOnly = false;
                    naziv.required = true;
                    if (naziv.value === 'Upisnica' || naziv.value === 'GDPR' || naziv.value === 'Slika') {
                        naziv.value = '';
                    }
                    return;
                }

                naziv.value = vrsta.value;
                naziv.readOnly = true;
                naziv.required = false;
            };

            vrsta.addEventListener('change', syncNaziv);
            syncNaziv();

            document.querySelectorAll('.js-payment-variant-select').forEach((variantSelect) => {
                const chargeId = variantSelect.getAttribute('data-charge-id');
                if (!chargeId) {
                    return;
                }

                const amountInput = document.querySelector('.js-charge-amount-input[data-charge-id="' + chargeId + '"]');
                if (!amountInput) {
                    return;
                }

                const syncAmountFromVariant = () => {
                    if (amountInput.dataset.manual === '1') {
                        return;
                    }

                    const selectedOption = variantSelect.options[variantSelect.selectedIndex];
                    const variantAmount = selectedOption?.dataset.variantAmount || '';
                    if (variantAmount !== '') {
                        amountInput.value = variantAmount;
                    }
                };

                variantSelect.addEventListener('change', syncAmountFromVariant);
                amountInput.addEventListener('input', () => {
                    amountInput.dataset.manual = '1';
                });
                amountInput.addEventListener('blur', () => {
                    if (amountInput.value.trim() === '') {
                        amountInput.dataset.manual = '0';
                        syncAmountFromVariant();
                    }
                });

                syncAmountFromVariant();
            });
        })();
    </script>
@endsection
