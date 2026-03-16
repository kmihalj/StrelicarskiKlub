<div class="row">
    <div class="col-lg-12 col-md-12 col-12 fw-bolder">
        <p>Unos rezultata:</p>
    </div>
    <div class="col-lg-6 mb-2">
        <form id="unos_rezultata" action="{{ route('admin.rezultati.SpremanjeRezultata') }}" method="POST">
            @csrf
            <input type="hidden" id="turnir_id" name="turnir_id" value={{$turnir->id}}>
        </form>
        <label for="clan">Prezime i ime:</label>
        <select class="form-select" form="unos_rezultata" id="clan" name="clan" aria-label="Odabir člana" required>
            <option value="" selected>Odaberite člana</option>
            @foreach($clanovi as $clan)
                <option value="{{ $clan->id }}" data-spol="{{ $clan->spol }}">{{ $clan->Prezime }} {{ $clan->Ime }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-lg-3 mb-2">
        <label for="stil">Stil:</label>
        <select class="form-select" form="unos_rezultata" id="stil" name="stil" aria-label="Odabir stila" required>
            <option value="" selected>Odaberite stil</option>
            @foreach($stilovi as $stil)
                <option value="{{ $stil->id }}">{{ $stil->naziv }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-lg-3 mb-2">
        <label for="kategorija">Kategorija:</label>
        <select class="form-select" form="unos_rezultata" id="kategorija" name="kategorija" aria-label="Odabir kategorije" required>
            <option value="" selected>Prvo odaberite člana</option>
            @foreach($kategorije as $kategorija)
                <option value="{{ $kategorija->id }}" data-spol="{{ $kategorija->spol }}">{{ $kategorija->naziv }}</option>
            @endforeach
        </select>
    </div>
    @foreach($turnir->tipTurnira->polja as $index => $polje)
        <div class="col-lg-2 col-md-2 col-3 mb-2">
            <label for="polje[]">{{ $polje->naziv }}</label>
            <input type="number" form="unos_rezultata" class="form-control" name="polje[]" id="polje[]" aria-label="polje[]" required>
        </div>
    @endforeach
    <div class="col-lg-2 col-md-2 col-3 mb-2">
        <label for="plasman">Plasman:</label>
        <input type="number" form="unos_rezultata" class="form-control" name="plasman" id="plasman" aria-label="plasman" required>
    </div>
    @if($turnir->eliminacije)
        <div class="col-lg-2 col-md-2 col-3 mb-2">
            <label for="plasman_eliminacije">Eliminacije - plasman:</label>
            <input type="number" form="unos_rezultata" class="form-control" name="plasman_eliminacije" id="plasman_eliminacije" aria-label="plasman_eliminacije">
        </div>
    @endif
    <div class="col-lg-12 col-md-12 col-12 mb-2 text-end">
        <button type="submit" form="unos_rezultata" class="btn btn-danger">Spremi</button>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const clanSelect = document.getElementById('clan');
        const kategorijaSelect = document.getElementById('kategorija');

        if (!clanSelect || !kategorijaSelect || kategorijaSelect.options.length === 0) {
            return;
        }

        const placeholder = kategorijaSelect.options[0];
        const sveKategorije = Array.from(kategorijaSelect.options)
            .filter((option) => option.value !== '')
            .map((option) => ({
                value: option.value,
                text: option.textContent,
                spol: normalizirajSpol(option.dataset.spol),
            }));

        function normalizirajSpol(vrijednost) {
            if (!vrijednost) {
                return '';
            }

            const bezRazmaka = vrijednost.toString().trim().toUpperCase();
            const bezDijakritika = typeof bezRazmaka.normalize === 'function'
                ? bezRazmaka.normalize('NFD').replace(/[\u0300-\u036f]/g, '')
                : bezRazmaka;

            if (bezDijakritika.startsWith('M')) {
                return 'M';
            }

            if (bezDijakritika.startsWith('Z')) {
                return 'Z';
            }

            return bezDijakritika;
        }

        function osvjeziKategorije() {
            const selectedClan = clanSelect.options[clanSelect.selectedIndex];
            const clanSpol = selectedClan ? normalizirajSpol(selectedClan.dataset.spol) : '';
            const prethodnaVrijednost = kategorijaSelect.value;

            while (kategorijaSelect.options.length > 1) {
                kategorijaSelect.remove(1);
            }

            const odgovarajuceKategorije = clanSpol === ''
                ? []
                : sveKategorije.filter((kategorija) => kategorija.spol === clanSpol);

            odgovarajuceKategorije.forEach((kategorija) => {
                const option = new Option(kategorija.text, kategorija.value);
                kategorijaSelect.add(option);
            });

            const postojiPrethodniOdabir = odgovarajuceKategorije.some(
                (kategorija) => kategorija.value === prethodnaVrijednost
            );
            if (postojiPrethodniOdabir) {
                kategorijaSelect.value = prethodnaVrijednost;
            } else {
                kategorijaSelect.value = '';
            }

            if (!clanSpol) {
                placeholder.textContent = 'Prvo odaberite člana';
                return;
            }

            placeholder.textContent = odgovarajuceKategorije.length > 0
                ? 'Odaberite kategoriju'
                : 'Nema kategorija za odabrani spol';
        }

        clanSelect.addEventListener('change', osvjeziKategorije);
        osvjeziKategorije();
    });
</script>
