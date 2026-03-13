@if($turnir->rezultatiOpci->count() != 0)
    <div class="row mb-2">
        <div class="col m-1">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 border">
                    <thead class="table-warning">
                    <tr>
                        <th>Prezime i ime</th>
                        <th>Stil</th>
                        <th>Kategorija</th>
                        @foreach($turnir->tipTurnira->polja as $polje)
                            <th>{{ $polje->naziv }}</th>
                        @endforeach
                        <th>Plasman</th>
                        @if($turnir->eliminacije)
                            <th>Plasman nakon eliminacija</th>
                        @endif
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($turnir->rezultatiOpci as $i => $rezultat)
                        <tr>
                            <td>
                                <p class="fw-bold mb-1">{{ $rezultat->clan->Prezime}} {{ $rezultat->clan->Ime}} </p>
                            </td>
                            <td>
                                <p class="fw-bold mb-1"> {{ $rezultat->stil->naziv }} </p>
                            </td>
                            <td>
                                <p class="fw-bold mb-1"> {{ $rezultat->kategorija->naziv }} </p>
                            </td>
                            @foreach($turnir->rezultatiPoTipuTurnira as $polje)
                                @if ($rezultat->clan->id == $polje['clan_id'] && $rezultat->stil->id == $polje['stil_id'])
                                <td>
                                    <p class="fw-bold mb-1"> @if($polje['rezultat'] == 0) - @else {{ $polje['rezultat'] }} @endif </p>
                                </td>
                                @endif
                            @endforeach
                            <td>
                                <p class="fw-bold mb-1"> {{ $rezultat->plasman }} </p>
                            </td>
                            @if($turnir->eliminacije)
                                <td>
                                    <p class="fw-bold mb-1"> {{ $rezultat->plasman_nakon_eliminacija }}
                                </td>
                            @endif
                            <td class="text-end">
                                <form id="brisanje{{ $rezultat->id }}" action="{{ route('admin.rezultati.brisanjeRezultata', $rezultat->id) }}" method="POST">
                                    @csrf
                                </form>

                                <button type="submit" form="brisanje{{ $rezultat->id }}" class="btn text-danger btn-rounded" title="Obriši" onclick="return confirm('Da li ste sigurni da želite obrisati rezultat ?')">
                                    @include('admin.SVG.obrisi')
                                </button>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endif
