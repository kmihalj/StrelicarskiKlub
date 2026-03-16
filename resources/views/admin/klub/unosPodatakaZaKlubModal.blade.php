{{-- Modal forma za unos ili izmjenu podataka o klubu. --}}
<div class="modal fade" id="UnosPodataka">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <!-- Modal Header -->
            <div class="modal-header bg-danger">
                <h4 class="modal-title text-white">@if(is_null($klub))
                        Dodavanje
                    @else
                        Uređivanje
                    @endif podataka</h4>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" data-bs-target="#UnosPodataka"></button>
            </div>

            <!-- Modal body -->
            <div class="modal-body">
                <form id="unosKlub" action="{{ route('admin.klub.spremanjePodataka') }}" method="POST">
                    @csrf
                </form>
                <div class="row">
                    <div class="col-lg-6 mb-2">
                        <label for="naziv">Naziv:</label>
                        <input type="text" form="unosKlub" class="form-control" name="naziv" id="naziv"
                               value="@if(!is_null($klub)){{$klub->naziv}}@endif"
                               required>
                    </div>
                    <div class="col-lg-6 mb-2">
                        <label for="adresa">Adresa:</label>
                        <input type="text" form="unosKlub" class="form-control" name="adresa" id="adresa"
                               value="@if(!is_null($klub)){{$klub->adresa}}@endif"
                               required>
                    </div>
                    <div class="col-lg-3 mb-2">
                        <label for="telefon">Br. telefona:</label>
                        <input type="tel" form="unosKlub" class="form-control" name="telefon"
                               value="@if(!is_null($klub)){{$klub->telefon}}@endif"
                               id="telefon" required>
                    </div>
                    <div class="col-lg-3 mb-2">
                        <label for="email">E-mail:</label>
                        <input type="email" form="unosKlub" class="form-control" name="email" id="email"
                               value="@if(!is_null($klub)){{$klub->email}}@endif"
                               required>
                    </div>
                    <div class="col-lg-6 mb-2">
                        <label for="racun">Br. računa:</label>
                        <input type="text" form="unosKlub" class="form-control" name="racun" id="racun"
                               value="@if(!is_null($klub)){{$klub->racun}}@endif"
                               required>
                    </div>
                </div>
            </div>

            <!-- Modal footer -->
            <div class="modal-footer">
                <button type="submit" class="btn btn-danger" form="unosKlub">Spremi</button>
                <button type="button" class="btn btn-danger" data-bs-dismiss="modal" data-bs-target="#UnosPodataka">Odustani</button>
            </div>
        </div>
    </div>
</div>
