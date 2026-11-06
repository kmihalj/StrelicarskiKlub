@php
    $feeOld = old('_form_context') === $feeContext ? old('kotizacija_razredi') : null;
    $feeRates = is_array($feeOld) ? $feeOld : ($feeTurnir?->kotizacijaRazredi() ?? []);
    if ($feeRates === []) {
        $feeRates = [['kategorije' => array_keys(\App\Support\TournamentFeeCategories::LABELS), 'iznos' => '']];
    }
@endphp
<div class="col-12" data-fee-editor data-rates="{{ json_encode($feeRates) }}"
     data-categories="{{ json_encode(\App\Support\TournamentFeeCategories::LABELS) }}">
    <div class="fw-semibold mb-2">Kotizacije po kategorijama</div>
    <div class="small text-muted mb-2">Kategorije vrijede za muške i ženske prijave. Za isti iznos odaberite Sve; za različite iznose rasporedite preostale kategorije u sljedeće retke.</div>
    <div data-fee-rows></div>
    <noscript>Za uređivanje kotizacija po kategorijama uključite JavaScript.</noscript>
</div>
