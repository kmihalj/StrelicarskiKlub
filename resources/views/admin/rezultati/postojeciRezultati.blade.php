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
                        @php
                            $rezultatiPoPoljima = $turnir->rezultatiPoTipuTurnira
                                ->where('clan_id', $rezultat->clan_id)
                                ->where('stil_id', $rezultat->stil_id)
                                ->where('kategorija_id', $rezultat->kategorija_id)
                                ->keyBy('polje_za_tipove_turnira_id');

                            $poljaVrijednosti = [];
                            $poljaIds = [];
                        @endphp
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
                            @foreach($turnir->tipTurnira->polja as $poljeDefinicija)
                                @php
                                    $stavkaPolja = $rezultatiPoPoljima->get($poljeDefinicija->id);
                                    $poljaVrijednosti[] = $stavkaPolja?->rezultat;
                                    $poljaIds[] = $stavkaPolja?->id;
                                @endphp
                                <td>
                                    <p class="fw-bold mb-1">
                                        @if(($stavkaPolja?->rezultat ?? null) == 0) - @else {{ $stavkaPolja?->rezultat ?? '-' }} @endif
                                    </p>
                                </td>
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
                                <div class="d-inline-flex align-items-center gap-1">
                                    <button
                                        type="button"
                                        class="btn text-success btn-rounded js-rezultat-edit"
                                        title="Uredi"
                                        data-rezultat-id="{{ $rezultat->id }}"
                                        data-update-url="{{ route('admin.rezultati.updateRezultat', $rezultat->id) }}"
                                        data-clan-id="{{ $rezultat->clan_id }}"
                                        data-stil-id="{{ $rezultat->stil_id }}"
                                        data-kategorija-id="{{ $rezultat->kategorija_id }}"
                                        data-plasman="{{ $rezultat->plasman }}"
                                        data-plasman-eliminacije="{{ $rezultat->plasman_nakon_eliminacija }}"
                                        data-polja='@json($poljaVrijednosti)'
                                        data-polja-ids='@json($poljaIds)'>
                                        @include('admin.SVG.uredi')
                                    </button>

                                    <form id="brisanje{{ $rezultat->id }}" action="{{ route('admin.rezultati.brisanjeRezultata', $rezultat->id) }}" method="POST" class="d-inline">
                                        @csrf
                                    </form>

                                    <button type="submit" form="brisanje{{ $rezultat->id }}" class="btn text-danger btn-rounded" title="Obriši" onclick="return confirm('Da li ste sigurni da želite obrisati rezultat ?')">
                                        @include('admin.SVG.obrisi')
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endif
