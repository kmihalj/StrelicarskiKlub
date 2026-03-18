{{-- Forma za unos jednog novog pojedinačnog rezultata člana na turniru. --}}
<div class="row">
    <div class="col-lg-12 col-md-12 col-12 fw-bolder">
        <p id="rezultat_form_title">Unos rezultata:</p>
    </div>
    <div class="col-lg-6 mb-2">
        {{-- Jedna forma pokriva oba slučaja:
             1) novi unos (POST na SpremanjeRezultata)
             2) uređivanje (POST na updateRezultat nakon klika na „Uredi“ u tablici). --}}
        <form
            id="unos_rezultata"
            action="{{ route('admin.rezultati.SpremanjeRezultata') }}"
            method="POST"
            data-store-action="{{ route('admin.rezultati.SpremanjeRezultata') }}">
            @csrf
            <input type="hidden" id="turnir_id" name="turnir_id" value={{$turnir->id}}>
            <input type="hidden" id="rezultat_id" name="rezultat_id" value="">
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
            <label for="polje_{{ $index }}">{{ $polje->naziv }}</label>
            {{-- Rezultat pojedinog polja definiranog tipom turnira (npr. 1. krug, 2. krug, ukupno). --}}
            <input type="number" form="unos_rezultata" class="form-control" name="polje[]" id="polje_{{ $index }}" aria-label="polje_{{ $index }}" required>
            {{-- Kod uređivanja čuvamo ID retka u tablici rezultati_po_tipu_turniras kako bi update bio precizan. --}}
            <input type="hidden" form="unos_rezultata" name="rez_po_tipu_ids[]" id="rez_po_tipu_id_{{ $index }}">
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
        {{-- Odustani je vidljiv samo u edit modu; vraća formu na "novi unos". --}}
        <button type="button" id="rezultat_odustani_btn" class="btn btn-outline-secondary d-none">Odustani</button>
        <button type="submit" id="rezultat_spremi_btn" form="unos_rezultata" class="btn btn-danger">Spremi</button>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const rezultatForm = /** @type {HTMLFormElement|null} */ (document.getElementById('unos_rezultata'));
        const formTitle = /** @type {HTMLElement|null} */ (document.getElementById('rezultat_form_title'));
        const clanSelect = /** @type {HTMLSelectElement|null} */ (document.getElementById('clan'));
        const stilSelect = /** @type {HTMLSelectElement|null} */ (document.getElementById('stil'));
        const kategorijaSelect = /** @type {HTMLSelectElement|null} */ (document.getElementById('kategorija'));
        const plasmanInput = /** @type {HTMLInputElement|null} */ (document.getElementById('plasman'));
        const plasmanEliminacijeInput = /** @type {HTMLInputElement|null} */ (document.getElementById('plasman_eliminacije'));
        const rezultatIdInput = /** @type {HTMLInputElement|null} */ (document.getElementById('rezultat_id'));
        const spremiButton = /** @type {HTMLButtonElement|null} */ (document.getElementById('rezultat_spremi_btn'));
        const odustaniButton = /** @type {HTMLButtonElement|null} */ (document.getElementById('rezultat_odustani_btn'));
        const poljeInputi = /** @type {HTMLInputElement[]} */ (Array.from(document.querySelectorAll('input[name="polje[]"][form="unos_rezultata"]')));
        const rezPoTipuIdInputi = /** @type {HTMLInputElement[]} */ (Array.from(document.querySelectorAll('input[name="rez_po_tipu_ids[]"][form="unos_rezultata"]')));
        const editButtons = /** @type {HTMLButtonElement[]} */ (Array.from(document.querySelectorAll('.js-rezultat-edit')));

        if (!rezultatForm || !clanSelect || !stilSelect || !kategorijaSelect || !plasmanInput || kategorijaSelect.options.length === 0) {
            return;
        }

        const placeholder = kategorijaSelect.options.item(0);
        if (!placeholder) {
            return;
        }
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
            // Kategorije se filtriraju prema spolu odabranog člana kako bi unos ostao valjan.
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

        function procitajJson(niz, fallback) {
            try {
                const parsed = JSON.parse(niz);
                return Array.isArray(parsed) ? parsed : fallback;
            } catch (e) {
                return fallback;
            }
        }

        function prebaciUNoviUnos() {
            // Reset svih polja i povratak na osnovnu akciju spremanja (novi redak).
            rezultatForm.action = rezultatForm.dataset.storeAction || rezultatForm.action;
            if (formTitle) {
                formTitle.textContent = 'Unos rezultata:';
            }
            if (spremiButton) {
                spremiButton.textContent = 'Spremi';
            }
            if (odustaniButton) {
                odustaniButton.classList.add('d-none');
            }

            if (rezultatIdInput) {
                rezultatIdInput.value = '';
            }

            clanSelect.value = '';
            stilSelect.value = '';
            osvjeziKategorije();
            kategorijaSelect.value = '';
            plasmanInput.value = '';

            if (plasmanEliminacijeInput) {
                plasmanEliminacijeInput.value = '';
            }

            poljeInputi.forEach((input) => {
                input.value = '';
            });

            rezPoTipuIdInputi.forEach((input) => {
                input.value = '';
            });
        }

        function prebaciUUredjivanje(button) {
            // Podatke čitamo iz data-* atributa retka tablice (bez dodatnog server round-tripa).
            const polja = procitajJson(button.dataset.polja || '[]', []);
            const poljaIds = procitajJson(button.dataset.poljaIds || '[]', []);

            rezultatForm.action = button.dataset.updateUrl || rezultatForm.dataset.storeAction || rezultatForm.action;
            if (rezultatIdInput) {
                rezultatIdInput.value = button.dataset.rezultatId || '';
            }

            clanSelect.value = button.dataset.clanId || '';
            osvjeziKategorije();
            kategorijaSelect.value = button.dataset.kategorijaId || '';
            stilSelect.value = button.dataset.stilId || '';
            plasmanInput.value = button.dataset.plasman || '';

            if (plasmanEliminacijeInput) {
                plasmanEliminacijeInput.value = button.dataset.plasmanEliminacije || '';
            }

            poljeInputi.forEach((input, index) => {
                const vrijednost = polja[index];
                input.value = vrijednost === null || typeof vrijednost === 'undefined' ? '' : vrijednost;
            });

            rezPoTipuIdInputi.forEach((input, index) => {
                const id = poljaIds[index];
                input.value = id === null || typeof id === 'undefined' ? '' : id;
            });

            if (formTitle) {
                formTitle.textContent = 'Uređivanje rezultata:';
            }
            if (spremiButton) {
                spremiButton.textContent = 'Spremi izmjene';
            }
            if (odustaniButton) {
                odustaniButton.classList.remove('d-none');
            }

            // Korisnika automatski dovodimo na formu da je uređivanje odmah vidljivo.
            rezultatForm.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }

        clanSelect.addEventListener('change', osvjeziKategorije);
        editButtons.forEach((button) => {
            button.addEventListener('click', function () {
                prebaciUUredjivanje(button);
            });
        });
        if (odustaniButton) {
            odustaniButton.addEventListener('click', prebaciUNoviUnos);
        }

        osvjeziKategorije();
    });
</script>
