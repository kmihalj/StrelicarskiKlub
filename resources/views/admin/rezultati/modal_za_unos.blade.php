{{-- Modal forma za unos novog turnira. --}}
<div class="modal fade" id="UnosTurnira_modal">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">

            <!-- Modal Header -->
            <div class="modal-header bg-danger">
                <h4 class="modal-title text-white">Dodavanje turnira</h4>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <!-- Modal body -->
            <div class="modal-body">
                <div class="row">
                    <form id="kreiranjeTurnira" action="{{ route('admin.rezultati.kreirajTurnir') }}" method="POST">
                        @csrf
                    </form>
                    <div class="col-lg-2 mb-2">
                        <label for="datum_turnira">Datum održavanja:</label>
                        <input type="date" class="form-control" form="kreiranjeTurnira" name="datum_turnira" id="datum_turnira" required>
                    </div>
                    <div class="col-lg-10 mb-2">
                        <label for="naziv_turnira">Naziv turnira:</label>
                        <input type="text"  class="form-control" form="kreiranjeTurnira" name="naziv_turnira" id="naziv_turnira" required>
                    </div>
                    <div class="col-lg-2 mb-2">
                        <label for="lokacija_turnira">Mjesto:</label>
                        <input type="text"  class="form-control" form="kreiranjeTurnira" name="lokacija_turnira" id="lokacija_turnira" required>
                    </div>
                    <div class="col-lg-2 mb-2">
                        <label for="odabir_tipa_turnira">Tip turnira</label>
                        <select class="form-select" id="odabir_tipa_turnira" name="odabir_tipa_turnira" form="kreiranjeTurnira" aria-label="Odabir tipa turnira" required>
                            <option disabled selected></option>
                            @foreach($tipoviTurnira as $tipTurnira)
                                <option value={{ $tipTurnira->id }}>{{ $tipTurnira->naziv }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-2 mb-2 align-self-end">
                        <div class="form-check form-switch ">
                            <input class="form-check-input" type="checkbox" form="kreiranjeTurnira" id="eliminacije" name="eliminacije"
                                   value=true>
                            <label class="form-check-label" for="aktivan">Eliminacije</label>
                        </div>
                    </div>

                </div>
            </div>

            <!-- Modal footer -->
            <div class="modal-footer">
                <button type="submit" form="kreiranjeTurnira" class="btn btn-danger">Spremi</button>
                <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Odustani</button>
            </div>
        </div>
    </div>
</div>
