<div class="card">
    <div class="card-header bg-danger fw-bolder text-white">
        Stilovi luka
    </div>
    <div class="card-body bg-secondary-subtle shadow">
        <div class="row">
            <div class="col m-1">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 border">
                        <thead class="table-warning">
                        <tr>
                            <th>Naziv vrste luka</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                        @if($stilovi->count() == 0)
                            <tr>
                                <td colspan="2" class="text-center">
                                    <div class="ms-3">
                                        <p class="fw-bold mb-1">Nema podataka</p>
                                    </div>
                                </td>
                            </tr>
                        @else
                            @foreach($stilovi as $stil)
                                <tr>
                                    <td>
                                        <div class="ms-3">
                                            <p class="fw-bold mb-1">{{ $stil->naziv }}</p>
                                        </div>
                                    </td>
                                    <td class="text-end">
                                        <form id="brisanje_stila{{ $stil->id }}" action="{{ route('admin.turniri.obrisi_stil', $stil->id) }}" method="POST">
                                            @csrf
                                        </form>
                                        <button type="submit" form="brisanje_stila{{ $stil->id }}" class="btn text-danger btn-rounded" title="Obriši"
                                                onclick="return confirm('Da li ste sigurni da želite obrisati stil luka ?')">
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
                    <form id="unos_stila_luka" action="{{ route('admin.turniri.spremi_stil_luka') }}" method="POST">
                        @csrf
                    </form>
                    <input type="text" form="unos_stila_luka" id="naziv_stila_luka_za_unos" name="naziv_stila_luka_za_unos"
                           class="form-control" placeholder="naziv" aria-label="naziv" aria-describedby="stilLuka_button-addon2">
                    <button class="btn btn-outline-danger" type="submit" form="unos_stila_luka" id="stilLuka_button-addon2">Dodaj</button>
                </div>
            </div>
        </div>
    </div>
</div>
