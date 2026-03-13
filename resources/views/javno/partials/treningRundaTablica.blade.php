@once
    <style>
        .trening-hit-chip {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 2rem;
            min-height: 1.85rem;
            padding: .1rem .45rem;
            border-radius: .375rem;
            font-weight: 700;
            border: 1px solid #ced4da;
            line-height: 1;
        }

        .trening-hit-empty {
            background: #fff;
            color: #6c757d;
            border-color: #d7dce2;
        }

        .trening-hit-gold {
            background: #ffd447;
            color: #222;
            border-color: #c9a31b;
        }

        .trening-hit-red {
            background: #e33b3b;
            color: #fff;
            border-color: #b11717;
        }

        .trening-hit-blue {
            background: #1f65db;
            color: #fff;
            border-color: #13449b;
        }

        .trening-hit-black {
            background: #20242a;
            color: #fff;
            border-color: #000;
        }

        .trening-hit-white {
            background: #fff;
            color: #1a1a1a;
            border-color: #adb5bd;
        }

        .trening-hit-green {
            background: #22b259;
            color: #fff;
            border-color: #12813d;
        }
    </style>
@endonce

<div class="table-responsive mt-2">
    <table class="table table-sm table-hover align-middle mb-0 border">
        <thead class="theme-thead-accent">
        <tr>
            <th class="text-white">Serija</th>
            @for($i = 1; $i <= (int)$konfig['broj_strijela_u_seriji']; $i++)
                <th class="text-white">{{ $i }}</th>
            @endfor
            <th class="text-white">Zbroj</th>
            <th class="text-white">Total</th>
            <th class="text-white">9</th>
            <th class="text-white">10</th>
            @if($konfig['ima_x_kolonu'])
                <th class="text-white">X</th>
            @endif
        </tr>
        </thead>
        <tbody>
        @foreach($runda['serije'] as $serija)
            <tr>
                <td class="fw-semibold">{{ $serija['broj'] }}</td>
                @foreach($serija['pogodci'] as $pogodak)
                    @php
                        $hitKlasa = 'trening-hit-empty';
                        if (!is_null($pogodak)) {
                            if (in_array($pogodak, ['X', '10', '9'], true)) {
                                $hitKlasa = 'trening-hit-gold';
                            } elseif (in_array($pogodak, ['8', '7'], true)) {
                                $hitKlasa = 'trening-hit-red';
                            } elseif (in_array($pogodak, ['6', '5'], true)) {
                                $hitKlasa = 'trening-hit-blue';
                            } elseif (in_array($pogodak, ['4', '3'], true)) {
                                $hitKlasa = 'trening-hit-black';
                            } elseif (in_array($pogodak, ['2', '1'], true)) {
                                $hitKlasa = 'trening-hit-white';
                            } elseif ($pogodak === 'M') {
                                $hitKlasa = 'trening-hit-green';
                            }
                        }
                    @endphp
                    <td><span class="trening-hit-chip {{ $hitKlasa }}">{{ $pogodak ?? '-' }}</span></td>
                @endforeach
                <td>{{ is_null($serija['zbroj']) ? '-' : $serija['zbroj'] }}</td>
                <td>{{ is_null($serija['total']) ? '-' : $serija['total'] }}</td>
                <td>
                    @if(is_null($serija['devetke']) || $serija['devetke'] === 0)
                        -
                    @else
                        {{ $serija['devetke'] }}
                    @endif
                </td>
                <td>
                    @if(is_null($serija['desetke']) || $serija['desetke'] === 0)
                        -
                    @else
                        {{ $serija['desetke'] }}
                    @endif
                </td>
                @if($konfig['ima_x_kolonu'])
                    <td>
                        @if(is_null($serija['x']) || $serija['x'] === 0)
                            -
                        @else
                            {{ $serija['x'] }}
                        @endif
                    </td>
                @endif
            </tr>
        @endforeach
        <tr class="fw-bold">
            <td colspan="{{ (int)$konfig['broj_strijela_u_seriji'] + 1 }}">Runda ukupno</td>
            <td>-</td>
            <td>{{ $runda['imaUnosa'] ? $runda['total'] : '-' }}</td>
            <td>{{ $runda['imaUnosa'] ? $runda['devetke'] : '-' }}</td>
            <td>{{ $runda['imaUnosa'] ? $runda['desetke'] : '-' }}</td>
            @if($konfig['ima_x_kolonu'])
                <td>{{ $runda['imaUnosa'] ? $runda['x'] : '-' }}</td>
            @endif
        </tr>
        </tbody>
    </table>
</div>
