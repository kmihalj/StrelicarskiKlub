{{-- Administratorski ekran za definiranje polja unosa po tipu turnira. --}}
<div class="card">
    <div class="card-header bg-danger fw-bolder text-white">
        Polja za pojedini tip turnira za kreiranje rezultata
    </div>
    <div class="card-body bg-secondary-subtle shadow">
        <div class="row">
            <div class="col-xl-3 col-lg-4 col-md-6 col-sm-12 m-1">
                @if($tipoviTurnira->count() == 0)
                    <p class="text-danger fw-bold">Potrebno je unijeti tipove turnira.</p>
                @else
                    <form id="odabir_tipa_turnira_forma" action="{{ route('admin.turniri.odabir_tipa_turnira') }}" method="POST">
                        @csrf
                    </form>
                    <label for="odabir_tipa_turnira">Tip turnira</label>
                    <select class="form-select" id="odabir_tipa_turnira" name="odabir_tipa_turnira" form="odabir_tipa_turnira_forma" onchange="this.form.submit()" aria-label="Odabir tipa turnira">
                        <option selected></option>
                        @foreach($tipoviTurnira as $tipTurnira)
                            <option value={{ $tipTurnira->id }} @isset($odabraniTipTurnira) @if($odabraniTipTurnira->id == $tipTurnira->id) selected @endif @endisset >{{ $tipTurnira->naziv }}</option>
                        @endforeach
                    </select>
                @endif
            </div>
        </div>
        @isset($odabraniTipTurnira)
            <div class="row">
                <div class="col-12 m-1">
                    Sve tabele za rezultate imaju predefinirana standardna polja, custom polja se dodaju između "Kategorija" i "Plasman". <b>Polja se dodaju redom kako su unesena.</b><br>
                    Zadnje polje bi trebalo imati naziv "Ukupno" i služi za statistiku streličara.
                    <div class="table-responsive">
                        <table class="table table-hover align-middle border">
                            <thead class="table-warning">
                            <tr>
                                <th>Ime i prezime</th>
                                <th>Stil</th>
                                <th>Kategorija</th>
                                @if($odabraniTipTurnira->polja->count() != 0)
                                    @foreach($odabraniTipTurnira->polja as $polje)
                                        <th class="border border-danger">
                                            <div class="row">
                                                <div class="col text-end">
                                                    @if (($odabraniTipTurnira->polja[$odabraniTipTurnira->polja->count() - 1]->naziv) ==  $polje->naziv)
                                                        <form id="brisanje_polja_za_tip_turnira" action="{{ route('admin.turniri.obrisi_polje_za_tipTurnira') }}" method="POST">
                                                            @csrf
                                                            <input type="hidden" id="zadnje_polje" name="zadnje_polje" value={{ $polje->id }}>
                                                        </form>
                                                        <button type="submit" form="brisanje_polja_za_tip_turnira" class="btn btn-sm text-danger btn-rounded" title="Obriši">
                                                            @include('admin.SVG.obrisi')
                                                        </button>
                                                </div>
                                            </div>
                                            @endif
                                            <div class="row">
                                                <div class="col text-start">
                                                    {{ $polje->naziv }}
                                                </div>
                                            </div>

                                        </th>
                                    @endforeach
                                @else
                                    <th class="border border-danger">&nbsp;</th>
                                @endif

                                <th>Plasman</th>
                                <th><small class="text-danger fw-lighter">Ako turnir ima eliminacije</small><br>
                                    Plasman nakon eliminacija
                                </th>
                            </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-xl-3 col-lg-4 col-md-6 col-sm-12 m-1">
                    <div class="input-group mb-3">
                        <form id="unos_polja_za_tip_turnira" action="{{ route('admin.turniri.spremi_poljeZatipTurnira') }}" method="POST">
                            @csrf
                            <input type="hidden" id="odabir_tipa_turnira" name="odabir_tipa_turnira" value="{{$odabraniTipTurnira->id}}">
                        </form>
                        <input type="text" form="unos_polja_za_tip_turnira" id="naziv_polja_za_tip_turnira_unos" name="naziv_polja_za_tip_turnira_unos" class="form-control" placeholder="naziv" aria-label="naziv" aria-describedby="poljeZaTipTurnira_button-addon2">
                        <button class="btn btn-outline-danger" type="submit" form="unos_polja_za_tip_turnira" id="poljeZaTipTurnira_button-addon2">Dodaj</button>
                    </div>
                </div>
            </div>
        @endisset
    </div>
</div>
