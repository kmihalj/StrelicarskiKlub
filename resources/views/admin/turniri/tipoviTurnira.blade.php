{{-- Administratorski ekran za definiranje tipova turnira. --}}
<div class="card">
    <div class="card-header bg-danger fw-bolder text-white">
        Tipovi turnira
    </div>
    <div class="card-body bg-secondary-subtle shadow">
        <div class="row">
            <div class="col m-1">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 border">
                        <thead class="table-warning">
                        <tr>
                            <th>Naziv tipa turnira</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                        @if($tipoviTurnira->count() == 0)
                            <tr>
                                <td colspan="2" class="text-center">
                                    <div class="ms-3">
                                        <p class="fw-bold mb-1">Nema podataka</p>
                                    </div>
                                </td>
                            </tr>
                        @else
                            @foreach($tipoviTurnira as $tipTurnira)
                                <tr>
                                    <td>
                                        <div class="ms-3">
                                            <p class="fw-bold mb-1">{{ $tipTurnira->naziv }}</p>
                                        </div>
                                    </td>
                                    <td class="text-end">
                                        <form id="brisanje{{ $tipTurnira->id }}" action="{{ route('admin.turniri.brisanje_tipaTurnira', $tipTurnira->id) }}" method="POST">
                                            @csrf
                                        </form>
                                        <button type="submit" form="brisanje{{ $tipTurnira->id }}" class="btn text-danger btn-rounded" title="Obriši" onclick="return confirm('Da li ste sigurni da želite obrisati tip turnira ?')">
                                            @include('admin.SVG.obrisi')
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col m-1">
                <div class="input-group mb-3">
                    <form id="unos_tipa_turnira" action="{{ route('admin.turniri.spremanje_tipaTurnira') }}" method="POST">
                        @csrf
                    </form>
                    <input type="text" form="unos_tipa_turnira" id="naziv_tipa_turnira_za_unos" name="naziv_tipa_turnira_za_unos" class="form-control" placeholder="naziv" aria-label="naziv" aria-describedby="tipoviTurnira_button-addon2">
                    <button class="btn btn-outline-danger" type="submit" form="unos_tipa_turnira" id="tipoviTurnira_button-addon2">Dodaj</button>
                </div>
            </div>
        </div>
    </div>
</div>
