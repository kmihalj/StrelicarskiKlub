{{-- Evidencija dolazaka polaznika škole po datumima treninga. --}}
@extends('layouts.app')

@php
    use App\Models\PolaznikSkole;
@endphp

@section('content')
    <style>
        /*noinspection CssUnknownPseudoSelector,CssUnusedSymbol*/
        .skola-dolazak-input {
            height: 2rem !important;
            text-align: center !important;
            text-align-last: center;
            font-size: .7rem;
            padding: .18rem .12rem !important;
            line-height: 1.15 !important;
        }

        .skola-dolazak-input::-webkit-date-and-time-value {
            text-align: center;
            min-height: 1.15em;
            line-height: 1.15;
        }

        .skola-dolazak-input::-webkit-calendar-picker-indicator {
            margin: 0 .02rem;
            padding: 0;
            opacity: .85;
        }

        @supports (-webkit-touch-callout: none) {
            .skola-dolazak-input {
                padding-top: .2rem !important;
                padding-bottom: .2rem !important;
                line-height: 1.2 !important;
            }

            .skola-dolazak-input::-webkit-date-and-time-value {
                line-height: 1.2;
            }
        }
    </style>

    <div class="container-xxl bg-white shadow">
        <div class="row justify-content-center p-2 shadow bg-danger fw-bolder">
            <div class="col-lg-12 text-white">
                Evidencija dolazaka - škola (aktivni polaznici)
            </div>
        </div>

        <div class="row pt-3 pb-3">
            <div class="col-12">
                @if($mozeUredjivati)
                    <form id="spremi_evidenciju_dolazaka" action="{{ route('admin.skola.evidencija.spremi') }}" method="POST">
                        @csrf
                    </form>
                @endif

                <div class="d-grid gap-2 d-md-flex justify-content-md-end mb-3">
                    @if($mozeUredjivati)
                        <button type="submit" class="btn btn-danger me-md-2" form="spremi_evidenciju_dolazaka">Spremi evidenciju</button>
                    @endif
                    <button type="button" class="btn btn-outline-secondary" onclick="location.href='{{ route('javno.skola.polaznici.index') }}'">Popis polaznika</button>
                </div>

                @forelse($polaznici as $polaznik)
                    @php
                        /** @var PolaznikSkole $polaznik */
                        $dolasciPoRednom = $polaznik->dolasci->keyBy('redni_broj');
                    @endphp
                    <div class="card mb-3" data-polaznik-row="{{ $polaznik->id }}">
                        <div class="card-header bg-success text-white fw-bold d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <a class="link-light link-offset-2 link-underline-opacity-0 link-underline-opacity-0-hover"
                               href="{{ route('javno.skola.polaznici.show', $polaznik) }}">
                                {{ trim((string)$polaznik->Prezime) }} {{ trim((string)$polaznik->Ime) }}
                            </a>
                        </div>
                        <div class="card-body">
                            @if($mozeUredjivati)
                                <div class="row g-1">
                                    @for($i = 1; $i <= 16; $i++)
                                        <div class="col-3">
                                            <input type="date"
                                                   class="form-control form-control-sm js-dolazak-input skola-dolazak-input"
                                                   id="dolazak_{{ $polaznik->id }}_{{ $i }}"
                                                   name="dolasci[{{ $polaznik->id }}][{{ $i }}]"
                                                   value="{{ empty($dolasciPoRednom[$i]?->datum) ? '' : $dolasciPoRednom[$i]->datum?->format('Y-m-d') }}"
                                                   form="spremi_evidenciju_dolazaka">
                                        </div>
                                    @endfor
                                </div>
                                <div class="text-end mt-2">
                                    <button type="button"
                                            class="btn btn-outline-secondary btn-sm js-popuni-zadnje"
                                            data-row-id="{{ $polaznik->id }}"
                                            title="Upiši današnji datum u sljedeće prazno polje">
                                        +
                                    </button>
                                </div>
                            @else
                                <div class="row g-1">
                                    @for($i = 1; $i <= 16; $i++)
                                        <div class="col-3">
                                            <div class="border rounded p-1 h-100 small">
                                                <div>{{ empty($dolasciPoRednom[$i]?->datum) ? '-' : $dolasciPoRednom[$i]->datum?->format('d.m.Y.') }}</div>
                                            </div>
                                        </div>
                                    @endfor
                                </div>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="alert alert-secondary mb-0">Nema aktivnih polaznika škole.</div>
                @endforelse

                <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-3">
                    @if($mozeUredjivati)
                        <button type="submit" class="btn btn-danger me-md-2" form="spremi_evidenciju_dolazaka">Spremi evidenciju</button>
                    @endif
                    <button type="button" class="btn btn-outline-secondary" onclick="location.href='{{ route('javno.skola.polaznici.index') }}'">Popis polaznika</button>
                </div>
            </div>
        </div>
    </div>

    @if($mozeUredjivati)
        <script>
            document.querySelectorAll('.js-popuni-zadnje').forEach(function (button) {
                button.addEventListener('click', function () {
                    const rowId = button.getAttribute('data-row-id');
                    const row = rowId
                        ? /** @type {HTMLElement|null} */ (document.querySelector('[data-polaznik-row="' + rowId + '"]'))
                        : null;
                    if (!row) {
                        return;
                    }

                    const inputs = /** @type {HTMLInputElement[]} */ (Array.from(row.querySelectorAll('.js-dolazak-input')));
                    if (inputs.length === 0) {
                        return;
                    }

                    const target = inputs.find(function (input) {
                        return !input.value;
                    }) || inputs[inputs.length - 1];

                    const today = new Date();
                    const yyyy = today.getFullYear();
                    const mm = String(today.getMonth() + 1).padStart(2, '0');
                    const dd = String(today.getDate()).padStart(2, '0');
                    target.value = yyyy + '-' + mm + '-' + dd;
                });
            });
        </script>
    @endif
@endsection
