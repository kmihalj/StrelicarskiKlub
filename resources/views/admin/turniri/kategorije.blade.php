{{-- Administratorski ekran za upravljanje natjecateljskim kategorijama. --}}
<div class="card">
    <div class="card-header bg-danger fw-bolder text-white">
        Kategorije
    </div>
    <div class="card-body bg-secondary-subtle shadow">
        <div class="row">
            <div class="col m-1">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 border">
                        <thead class="table-warning">
                        <tr>
                            <th>Spol</th>
                            <th>Kategorija</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                        @if($kategorije->count() == 0)
                            <tr>
                                <td colspan="3" class="text-center">
                                    <div class="ms-3">
                                        <p class="fw-bold mb-1">Nema podataka</p>
                                    </div>
                                </td>
                            </tr>
                        @else
                            @foreach($kategorije as $kategorija)
                                <tr>
                                    <td>
                                        <div class="ms-3">
                                            <p class="fw-bold mb-1">{{ $kategorija->spol }}</p>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="ms-3">
                                            <p class="fw-bold mb-1">{{ $kategorija->naziv }}</p>
                                        </div>
                                    </td>
                                    <td class="text-end">
                                        <form id="brisanje_kategorije{{ $kategorija->id }}" action="{{ route('admin.turniri.obrisi_kategoriju', $kategorija->id) }}" method="POST">
                                            @csrf
                                        </form>
                                        <button type="submit" form="brisanje_kategorije{{ $kategorija->id }}" class="btn text-danger btn-rounded" title="Obriši" onclick="return confirm('Da li ste sigurni da želite obrisati kategoriju ?')">
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
                    <form id="unos_Kategorije" action="{{ route('admin.turniri.spremi_kategoriju') }}" method="POST">
                        @csrf
                    </form>
                    <label class="input-group-text text-danger" for="spol_kategorija">Spol</label>
                    <select class="form-select" id="spol_kategorija" name="spol_kategorija" form="unos_Kategorije">
                        <option selected></option>
                        <option value="M">Muško</option>
                        <option value="Ž">Žensko</option>
                    </select>
                    <input type="text" id="naziv_kategorije" name="naziv_kategorije" form="unos_Kategorije" class="form-control" placeholder="naziv" aria-label="naziv" aria-describedby="naziv_kategorije-addon2">
                    <button class="btn btn-outline-danger" type="submit" form="unos_Kategorije" id="naziv_kategorije-addon2">Dodaj</button>
                </div>
            </div>
        </div>
    </div>
</div>
