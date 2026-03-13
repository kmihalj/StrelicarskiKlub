<div class="table-responsive">
    <table class="table table-hover align-middle mb-0 border">
        <thead class="table-warning">
        <tr>
            @auth()
                @if(auth()->user()->rola <= 1)
                    <th>Rezultati</th>
                @endif
            @endauth
            <th>Datum</th>
            <th>Naziv turnira</th>
            <th>Mjesto</th>
            <th>Vrsta turnira</th>
            <th>Eliminacije</th>
            @auth()
                @if(auth()->user()->rola <= 1)
                    <th></th>
                @endif
            @endauth
        </tr>
        </thead>
        <tbody>
        @if($turniri->count() == 0)
            <tr>
                <td colspan="7" class="text-center">
                    <div class="ms-3">
                        <p class="fw-bold mb-1">Nema unešenih turnira</p>
                    </div>

                </td>
            </tr>
        @else
            @foreach($turniri as $turnir)
                <tr>
                    @auth()
                        @if(auth()->user()->rola <= 1)
                            <td>
                                <form id="unosRezultata{{ $turnir->id }}" action="{{ route('admin.rezultati.unosRezultata', $turnir->id) }}" method="POST">
                                    @csrf
                                </form>
                                <button type="submit" form="unosRezultata{{ $turnir->id }}" class="btn text-success btn-rounded" title="Unos rezultata">
                                    @include('admin.SVG.unos')
                                </button>
                            </td>
                        @endif
                    @endauth
                    <td>
                        <p class="fw-normal mb-1"> {{ date('d.m.Y.', strtotime( $turnir->datum  )) }} </p>
                    </td>
                    <td>
                        <a class="link-dark link-offset-2 link-underline-opacity-0 link-underline-opacity-50-hover fw-normal"
                           href="{{ route('javno.rezultati.prikaz_turnira', $turnir) }}">{{ $turnir->naziv  }}</a>
                    </td>
                    <td>
                        <p class="fw-normal mb-1"> {{ $turnir->lokacija  }} </p>
                    </td>
                    <td>
                        <p class="fw-normal mb-1"> {{ $turnir->tipTurnira->naziv }} </p>
                    </td>
                    <td>
                        <p class="fw-normal mb-1">
                            @if($turnir->eliminacije)
                                <label class="form-check-label" for="eliminacije_prikaz_true"></label>
                                <input class="form-check-input" id="eliminacije_prikaz_true" name="eliminacije_prikaz_true" type="checkbox" checked disabled>
                            @else
                                <label class="form-check-label" for="eliminacije_prikaz_false"></label>
                                <input class="form-check-input border-danger" id="eliminacije_prikaz_false" name="eliminacije_prikaz_false" type="checkbox" disabled>
                            @endif
                        </p>
                    </td>
                    @auth()
                        @if(auth()->user()->rola <= 1)
                            <td class="text-end">
                                <form id="urediTurnir{{$turnir->id}}" action="{{ route('admin.rezultati.urediTurnir') }}" method="POST">
                                    @csrf
                                    <input type="hidden" id="turnir_id" name="turnir_id" value="{{$turnir->id}}">
                                    <input type="hidden" id="stranica" name="stranica" value="{{$turniri->currentPage()}}">
                                </form>
                                <form id="brisanje{{ $turnir->id }}" action="{{ route('admin.rezultati.obrisiTurnir', $turnir->id) }}" method="POST">
                                    @csrf
                                </form>
                                <button type="submit" form="urediTurnir{{$turnir->id}}" class="btn text-success btn-rounded" title="Uređivanje">
                                    @include('admin.SVG.uredi')
                                </button>
                                <button type="submit" class="btn text-danger btn-rounded" form="brisanje{{ $turnir->id }}" title="Obriši" onclick="return confirm('Da li ste sigurni da želite obrisati turnir ?')">
                                    @include('admin.SVG.obrisi')
                                </button>
                            </td>
                        @endif
                    @endauth
                </tr>
            @endforeach
        @endif
        </tbody>
    </table>
</div>
