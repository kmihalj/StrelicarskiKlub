
<div class="modal fade" id="UredjivanjeTurnira_modal">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <!-- Modal Header -->
            <div class="modal-header bg-danger">
                <h4 class="modal-title text-white">Uređivanje turnira</h4>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" onclick="location.href='{{ route('admin.rezultati.popisTurnira', ['page'=>$turniri->currentPage()]) }}'"></button>
            </div>

            <!-- Modal body -->
            <div class="modal-body">
                <div class="row">
                    <form id="uredjivanjeTurnira" action="{{ route('admin.rezultati.updateTurnir') }}" method="POST">
                        @csrf
                        <input type="hidden" id="turnir_id" name="turnir_id" value="{{$uredi_turnir->id}}">
                        <input type="hidden" id="stranica" name="stranica" value="{{$turniri->currentPage()}}">
                    </form>
                    <div class="col-lg-2 mb-2">
                        <label for="datum_turnira">Datum održavanja:</label>
                        <input type="date" class="form-control" form="uredjivanjeTurnira" name="datum_turnira" id="datum_turnira"
                               value="{{ $uredi_turnir->datum }}" required>
                    </div>
                    <div class="col-lg-10 mb-2">
                        <label for="naziv_turnira">Naziv turnira:</label>
                        <input type="text"  class="form-control" form="uredjivanjeTurnira" name="naziv_turnira" id="naziv_turnira"
                               value="{{ $uredi_turnir->naziv }}" required>
                    </div>
                    <div class="col-lg-2 mb-2">
                        <label for="lokacija_turnira">Mjesto:</label>
                        <input type="text"  class="form-control" form="uredjivanjeTurnira" name="lokacija_turnira" id="lokacija_turnira"
                               value="{{ $uredi_turnir->lokacija }}" required>
                    </div>
                    <div class="col-lg-2 mb-2">
                        <label for="odabir_tipa_turnira">Tip turnira</label>
                        <select class="form-select" id="odabir_tipa_turnira" name="odabir_tipa_turnira" form="uredjivanjeTurnira" aria-label="Odabir tipa turnira" required>
                            @foreach($tipoviTurnira as $tipTurnira)
                                <option value={{ $tipTurnira->id }} @if($tipTurnira->id == $uredi_turnir->tipovi_turnira_id) selected @endif>{{ $tipTurnira->naziv }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-2 mb-2 align-self-end">
                        <div class="form-check form-switch ">
                            <input class="form-check-input" type="checkbox" form="uredjivanjeTurnira" id="eliminacije" name="eliminacije" aria-label="eliminacije"
                                   @if($uredi_turnir->eliminacije) value=true checked
                                   @else
                                       value=false
                                   @endif
                                   >
                            <label class="form-check-label" for="aktivan">Eliminacije</label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal footer -->
            <div class="modal-footer">
                <button type="submit" form="uredjivanjeTurnira" class="btn btn-danger">Spremi</button>
                <button type="button" class="btn btn-danger" data-bs-dismiss="modal" onclick="location.href='{{ route('admin.rezultati.popisTurnira', ['page'=>$turniri->currentPage()]) }}'">Odustani</button>
            </div>
        </div>
    </div>
</div>


<script src="{{ asset('assets/bootstrap5/bootstrap.js') }}"></script>
<script>
    const myModal = new bootstrap.Modal(document.getElementById('UredjivanjeTurnira_modal'), {});
    myModal.show()
</script>
