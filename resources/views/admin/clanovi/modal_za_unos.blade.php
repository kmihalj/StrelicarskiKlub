{{-- Modal forma za unos novog člana kluba. --}}
<div class="modal fade" id="UnosClana_modal">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">

            <!-- Modal Header -->
            <div class="modal-header bg-danger">
                <h4 class="modal-title text-white">Dodavanje člana</h4>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <!-- Modal body -->
            <div class="modal-body">
                <form id="unos_novog_clana" action="{{ route('admin.clanovi.spremanje_clana') }}" method="POST">
                    @csrf
                </form>
                <div class="row">
                    <div class="col-lg-6 mb-2">
                        <label for="Prezime">Prezime:</label>
                        <input type="text" form="unos_novog_clana" class="form-control" name="Prezime" id="Prezime"
                               required>
                    </div>
                    <div class="col-lg-6 mb-2">
                        <label for="Ime">Ime:</label>
                        <input type="text" form="unos_novog_clana" class="form-control" name="Ime" id="Ime"
                               required>
                    </div>
                    <div class="col-lg-3 mb-2">
                        <label for="datum_rodjenja">Datum rođenja:</label>
                        <input type="date" form="unos_novog_clana" class="form-control" name="datum_rodjenja"
                               id="datum_rodjenja" required>
                    </div>
                    <div class="col-lg-3 mb-2">
                        <label for="oib">OIB:</label>
                        <input type="text" form="unos_novog_clana" class="form-control" name="oib" id="oib"
                               required>
                    </div>
                    <div class="col-lg-3 mb-2">
                        <label for="br_telefona">Br. telefona:</label>
                        <input type="tel" form="unos_novog_clana" class="form-control" name="br_telefona"
                               id="br_telefona">
                    </div>
                    <div class="col-lg-3 mb-2">
                        <label for="email">E-mail:</label>
                        <input type="email" form="unos_novog_clana" class="form-control" name="email" id="email">
                    </div>
                    <div class="col-lg-3 mb-2">
                        <label for="datum_pocetka_clanstva" class="fw-bold">Datum početka članstva:</label>
                        <input type="date" form="unos_novog_clana" class="form-control" name="datum_pocetka_clanstva" id="datum_pocetka_clanstva">
                    </div>
                    <div class="col-lg-3 mb-2">
                        <label for="broj_licence">Br. licence:</label>
                        <input type="text" form="unos_novog_clana" class="form-control" name="broj_licence" id="broj_licence">
                    </div>

                    <div class="col-lg-3 mb-2">
                        <label for="spol">Spol:</label>
                        <select form="unos_novog_clana" class="form-select" id="spol" name="spol" required>
                            <option value="" disabled selected></option>
                            <option value="M">Muško</option>
                            <option value="Ž">Žensko</option>
                        </select>
                    </div>
                    <div class="col-lg-3 mb-2 align-self-end">
                        <div class="form-check form-switch ">
                            <input class="form-check-input" type="checkbox" form="unos_novog_clana" id="aktivan" name="aktivan" value=true checked>
                            <label class="form-check-label" for="aktivan">Aktivan član</label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal footer -->
            <div class="modal-footer">
                <button type="submit" class="btn btn-danger" form="unos_novog_clana">Spremi</button>
                <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Odustani</button>
            </div>

        </div>
    </div>
</div>
