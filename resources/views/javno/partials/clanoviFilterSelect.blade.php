{{-- Administratorski filter popisa članova. Stavke plaćanja dostupne su kada je praćenje plaćanja uključeno. --}}
<label for="{{ $filterId }}" class="form-label mb-1">Filter članova</label>
<select id="{{ $filterId }}" class="form-select form-select-sm js-clanovi-filter">
    <option value="">Bez filtriranja</option>
    <option value="licensed">Licencirani članovi</option>
    <option value="unlicensed">Bez licence</option>
    @if($showPaymentColumn)
        <option value="unpaid">Neplaćeno</option>
        <option value="paid">Plaćeno</option>
    @endif
    <option value="medical_invalid">Istekao liječnički / bez liječničkog</option>
    <option value="medical_valid">Važeći liječnički</option>
</select>
