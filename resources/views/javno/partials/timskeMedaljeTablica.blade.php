{{-- Tablica timskih medalja člana s prikazom članova tima i ukupnog rezultata. --}}
@php
    $prviTim = $timoviTipa->first();
    $poljaTipa = $prviTim?->turnir?->tipTurnira?->polja ?? collect();
@endphp

<div class="container-xxl bg-white shadow">
    <div class="row pt-3 pb-3 mb-3 shadow">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 border">
                <thead>
                <tr>
                    <th class="border-0" colspan="{{ 8 + $poljaTipa->count() }}">
                        Timska medalja - {{ $tipNaziv }}
                    </th>
                </tr>
                <tr style="--bs-table-bg:var(--bs-success);">
                    <th class="text-white">Datum</th>
                    <th class="text-white">Turnir</th>
                    <th class="text-white">Član</th>
                    @foreach($poljaTipa as $polje)
                        <th class="text-white">{{ $polje->naziv }}</th>
                    @endforeach
                    <th class="text-white">Stil</th>
                    <th class="text-white">Kategorija</th>
                    <th class="text-white">Ukupno</th>
                    <th class="text-white">Plasman</th>
                    <th class="text-white"></th>
                </tr>
                </thead>
                <tbody>
                @foreach($timoviTipa as $tim)
                    @php
                        $timStavke = $tim->clanoviStavke
                            ->filter(fn ($stavka) => $stavka->rezultatOpci !== null && $stavka->rezultatOpci->clan !== null)
                            ->sortBy([['redni_broj', 'asc'], ['id', 'asc']])
                            ->values();
                        $brojRedakaTima = max($timStavke->count(), 1);
                        $turnirTima = $tim->turnir;
                        $poljaZaTim = $turnirTima?->tipTurnira?->polja ?? $poljaTipa;
                    @endphp

                    @if($timStavke->count() === 0)
                        <tr class="fw-bold">
                            <td>
                                <a class="link-dark" style="text-decoration: none"
                                   href="{{ route('javno.rezultati.prikaz_turnira', $turnirTima) }}">
                                    {{ date('d.m.Y.', strtotime($turnirTima->datum)) }}
                                </a>
                            </td>
                            <td>
                                <a class="link-dark" style="text-decoration: none"
                                   href="{{ route('javno.rezultati.prikaz_turnira', $turnirTima) }}">
                                    {{ $turnirTima->naziv }}
                                </a>
                            </td>
                            <td>-</td>
                            @foreach($poljaZaTim as $polje)
                                <td>-</td>
                            @endforeach
                            <td>{{ $tim->stil?->naziv ?? 'Mix / više stilova' }}</td>
                            <td>{{ $tim->kategorija?->naziv ?? 'Mix / više kategorija' }}</td>
                            <td>{{ $tim->rezultat }}</td>
                            <td>{{ $tim->plasman }}</td>
                            <td>
                                @switch((int)$tim->plasman)
                                    @case(1)
                                        @include('admin.SVG.gold')
                                        @break
                                    @case(2)
                                        @include('admin.SVG.silver')
                                        @break
                                    @case(3)
                                        @include('admin.SVG.bronze')
                                        @break
                                @endswitch
                            </td>
                        </tr>
                    @else
                        @foreach($timStavke as $index => $stavka)
                            @php
                                $rezultatOpci = $stavka->rezultatOpci;
                                $rezultatiPolja = $turnirTima->rezultatiPoTipuTurnira
                                    ->where('clan_id', $rezultatOpci->clan_id)
                                    ->where('stil_id', $rezultatOpci->stil_id)
                                    ->where('kategorija_id', $rezultatOpci->kategorija_id)
                                    ->keyBy('polje_za_tipove_turnira_id');
                            @endphp
                            <tr class="fw-bold">
                                @if($index === 0)
                                    <td rowspan="{{ $brojRedakaTima }}">
                                        <a class="link-dark" style="text-decoration: none"
                                           href="{{ route('javno.rezultati.prikaz_turnira', $turnirTima) }}">
                                            {{ date('d.m.Y.', strtotime($turnirTima->datum)) }}
                                        </a>
                                    </td>
                                    <td rowspan="{{ $brojRedakaTima }}">
                                        <a class="link-dark" style="text-decoration: none"
                                           href="{{ route('javno.rezultati.prikaz_turnira', $turnirTima) }}">
                                            {{ $turnirTima->naziv }}
                                        </a>
                                    </td>
                                @endif
                                <td>
                                    <a class="link-primary link-offset-2 link-underline-opacity-0 link-underline-opacity-0-hover"
                                       href="{{ route('javno.clanovi.prikaz_clana', $rezultatOpci->clan) }}">
                                        {{ $rezultatOpci->clan->Ime }} {{ $rezultatOpci->clan->Prezime }}
                                    </a>
                                </td>
                                @foreach($poljaZaTim as $polje)
                                    @php
                                        $vrijednostPolja = $rezultatiPolja->get($polje->id)?->rezultat;
                                    @endphp
                                    <td>{{ $vrijednostPolja === null ? '-' : $vrijednostPolja }}</td>
                                @endforeach
                                @if($index === 0)
                                    <td rowspan="{{ $brojRedakaTima }}">{{ $tim->stil?->naziv ?? 'Mix / više stilova' }}</td>
                                    <td rowspan="{{ $brojRedakaTima }}">{{ $tim->kategorija?->naziv ?? 'Mix / više kategorija' }}</td>
                                    <td rowspan="{{ $brojRedakaTima }}">{{ $tim->rezultat }}</td>
                                    <td rowspan="{{ $brojRedakaTima }}">{{ $tim->plasman }}</td>
                                    <td rowspan="{{ $brojRedakaTima }}">
                                        @switch((int)$tim->plasman)
                                            @case(1)
                                                @include('admin.SVG.gold')
                                                @break
                                            @case(2)
                                                @include('admin.SVG.silver')
                                                @break
                                            @case(3)
                                                @include('admin.SVG.bronze')
                                                @break
                                        @endswitch
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    @endif
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

