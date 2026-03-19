{{-- Modal za admin odabir stupaca prije CSV izvoza popisa članova. --}}
@php
    $dostupneGodineCsv = ($dostupneGodineCsv ?? collect())
        ->map(static fn ($godina): int => (int)$godina)
        ->filter(static fn (int $godina): bool => $godina > 0)
        ->unique()
        ->sortDesc()
        ->values();

    if ($dostupneGodineCsv->isEmpty()) {
        $dostupneGodineCsv = collect([(int)date('Y')]);
    }
@endphp

<div class="modal fade" id="CsvExportClanova_modal" tabindex="-1" aria-labelledby="CsvExportClanova_modal_label" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-danger">
                <h5 class="modal-title text-white" id="CsvExportClanova_modal_label">CSV izvoz članova</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Zatvori"></button>
            </div>

            <div class="modal-body">
                <form id="csv_export_clanova_form" action="{{ route('javno.clanovi.csv_export') }}" method="GET">
                    <div class="alert alert-secondary py-2 px-3 mb-3">
                        <p class="fw-bold mb-2">Obavezni stupci (uvijek uključeni)</p>
                        <p class="mb-0 small">
                            Prezime, Ime, OIB, Spol, Datum rođenja
                        </p>
                    </div>

                    <p class="fw-bold mb-2">Opcionalni stupci</p>
                    <div class="row g-2">
                        <div class="col-12 col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="phone" id="csv-field-phone" name="fields[]">
                                <label class="form-check-label" for="csv-field-phone">Br. telefona</label>
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="email" id="csv-field-email" name="fields[]">
                                <label class="form-check-label" for="csv-field-email">E-mail</label>
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="license_number" id="csv-field-license-number" name="fields[]">
                                <label class="form-check-label" for="csv-field-license-number">Br. licence</label>
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="member_since" id="csv-field-member-since" name="fields[]">
                                <label class="form-check-label" for="csv-field-member-since">Član od</label>
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="club_function" id="csv-field-club-function" name="fields[]">
                                <label class="form-check-label" for="csv-field-club-function">Funkcija u klubu</label>
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="last_medical_duration" id="csv-field-last-medical-duration" name="fields[]">
                                <label class="form-check-label" for="csv-field-last-medical-duration">Trajanje zadnjeg liječničkog</label>
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="tournaments_total" id="csv-field-tournaments-total" name="fields[]">
                                <label class="form-check-label" for="csv-field-tournaments-total">Br. nastupa na turnirima (ukupno)</label>
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="medals_total" id="csv-field-medals-total" name="fields[]">
                                <label class="form-check-label" for="csv-field-medals-total">Broj osvojenih medalja (ukupno)</label>
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="medals_gold_total" id="csv-field-medals-gold-total" name="fields[]">
                                <label class="form-check-label" for="csv-field-medals-gold-total">Zlatne medalje (ukupno)</label>
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="medals_silver_total" id="csv-field-medals-silver-total" name="fields[]">
                                <label class="form-check-label" for="csv-field-medals-silver-total">Srebrne medalje (ukupno)</label>
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="medals_bronze_total" id="csv-field-medals-bronze-total" name="fields[]">
                                <label class="form-check-label" for="csv-field-medals-bronze-total">Brončane medalje (ukupno)</label>
                            </div>
                        </div>
                    </div>

                    <hr class="my-3">

                    <div class="border rounded p-2">
                        <div class="row g-2 align-items-start">
                            <div class="col-12 col-md-6">
                                <p class="fw-semibold mb-2">Dodaj statistiku po godinama</p>

                                <div class="form-check">
                                    <input class="form-check-input js-csv-year-field" type="checkbox" value="tournaments_year" id="csv-field-tournaments-year" name="fields[]">
                                    <label class="form-check-label" for="csv-field-tournaments-year">Br. nastupa na turnirima</label>
                                </div>

                                <div class="form-check">
                                    <input class="form-check-input js-csv-year-field" type="checkbox" value="medals_year" id="csv-field-medals-year" name="fields[]">
                                    <label class="form-check-label" for="csv-field-medals-year">Broj osvojenih medalja</label>
                                </div>

                                <div class="form-check">
                                    <input class="form-check-input js-csv-year-field" type="checkbox" value="medals_gold_year" id="csv-field-medals-gold-year" name="fields[]">
                                    <label class="form-check-label" for="csv-field-medals-gold-year">Zlatne medalje</label>
                                </div>

                                <div class="form-check">
                                    <input class="form-check-input js-csv-year-field" type="checkbox" value="medals_silver_year" id="csv-field-medals-silver-year" name="fields[]">
                                    <label class="form-check-label" for="csv-field-medals-silver-year">Srebrne medalje</label>
                                </div>

                                <div class="form-check">
                                    <input class="form-check-input js-csv-year-field" type="checkbox" value="medals_bronze_year" id="csv-field-medals-bronze-year" name="fields[]">
                                    <label class="form-check-label" for="csv-field-medals-bronze-year">Brončane medalje</label>
                                </div>

                                <p class="small text-muted mb-0 mt-2">
                                    Odabrani godišnji podaci dodaju se na kraj CSV-a za svaku odabranu godinu.
                                </p>
                            </div>
                            <div class="col-12 col-md-6">
                                <label for="csv-export-stat-years" class="form-label mb-1 fw-semibold">Odabir godina (više odjednom)</label>
                                <select class="form-select form-select-sm" id="csv-export-stat-years" name="stat_years[]" multiple size="6" disabled>
                                    @foreach($dostupneGodineCsv as $godina)
                                        <option value="{{ (int)$godina }}">{{ (int)$godina }}</option>
                                    @endforeach
                                </select>
                                <div id="csv-export-stat-year-help" class="form-text">
                                    Držite Ctrl (Windows) ili Cmd (Mac) za odabir više godina.
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <div class="modal-footer">
                <button type="submit" class="btn btn-danger" form="csv_export_clanova_form">Preuzmi CSV</button>
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Odustani</button>
            </div>
        </div>
    </div>
</div>
