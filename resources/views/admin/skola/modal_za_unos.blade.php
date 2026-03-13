<div class="modal fade" id="UnosPolaznikaSkole_modal">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-danger">
                <h4 class="modal-title text-white">Dodavanje polaznika škole</h4>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <form id="unos_novog_polaznika" action="{{ route('admin.skola.polaznici.store') }}" method="POST">
                    @csrf
                </form>

                <div class="row">
                    <div class="col-lg-6 mb-2">
                        <label for="polaznik_prezime">Prezime:</label>
                        <input type="text" form="unos_novog_polaznika" class="form-control" name="Prezime" id="polaznik_prezime" required>
                    </div>
                    <div class="col-lg-6 mb-2">
                        <label for="polaznik_ime">Ime:</label>
                        <input type="text" form="unos_novog_polaznika" class="form-control" name="Ime" id="polaznik_ime" required>
                    </div>
                    <div class="col-lg-3 mb-2">
                        <label for="polaznik_datum_rodjenja">Datum rođenja:</label>
                        <input type="date" form="unos_novog_polaznika" class="form-control" name="datum_rodjenja" id="polaznik_datum_rodjenja">
                    </div>
                    <div class="col-lg-3 mb-2">
                        <label for="polaznik_oib">OIB:</label>
                        <input type="text" form="unos_novog_polaznika" class="form-control" name="oib" id="polaznik_oib" maxlength="11" required>
                    </div>
                    <div class="col-lg-3 mb-2">
                        <label for="polaznik_br_telefona">Br. telefona:</label>
                        <input type="tel" form="unos_novog_polaznika" class="form-control" name="br_telefona" id="polaznik_br_telefona" placeholder="+385xxxxxxxxx">
                    </div>
                    <div class="col-lg-3 mb-2">
                        <label for="polaznik_email">E-mail:</label>
                        <input type="email" form="unos_novog_polaznika" class="form-control" name="email" id="polaznik_email">
                    </div>
                    <div class="col-lg-3 mb-2">
                        <label for="polaznik_datum_upisa">Datum upisa u školu:</label>
                        <input type="date" form="unos_novog_polaznika" class="form-control" name="datum_upisa" id="polaznik_datum_upisa"
                               value="{{ date('Y-m-d') }}" required>
                    </div>
                    <div class="col-lg-3 mb-2">
                        <label for="polaznik_spol">Spol:</label>
                        <select form="unos_novog_polaznika" class="form-select" id="polaznik_spol" name="spol">
                            <option value="" selected></option>
                            <option value="M">Muško</option>
                            <option value="Ž">Žensko</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="submit" class="btn btn-danger" form="unos_novog_polaznika">Spremi</button>
                <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Odustani</button>
            </div>
        </div>
    </div>
</div>
