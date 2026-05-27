@php
    $tableClass = isset($tableClass) ? (string) $tableClass : 'table table-bordered table-sm align-middle mb-0';
    $kolone = isset($kolone) && is_array($kolone) ? $kolone : [
        ['key' => 'rb', 'label' => 'R. br.', 'width' => 500, 'align' => 'center'],
        ['key' => 'licenca', 'label' => 'Licenca', 'width' => 850, 'align' => 'center'],
        ['key' => 'ime', 'label' => 'Ime', 'width' => 1100, 'align' => 'start'],
        ['key' => 'prezime', 'label' => 'Prezime', 'width' => 1300, 'align' => 'start'],
        ['key' => 'stil', 'label' => 'Stil', 'width' => 1600, 'align' => 'start'],
        ['key' => 'kategorija', 'label' => 'Kategorija', 'width' => 1500, 'align' => 'start'],
        ['key' => 'lijecnicki', 'label' => 'Liječnički pregled', 'width' => 1500, 'align' => 'center'],
        ['key' => 'kup', 'label' => 'Kup', 'width' => 700, 'align' => 'center'],
        ['key' => 'smjena', 'label' => 'Smjena/dan', 'width' => 1200, 'align' => 'center'],
        ['key' => 'obrok', 'label' => 'Obrok', 'width' => 1100, 'align' => 'start'],
        ['key' => 'napomena', 'label' => 'Napomena', 'width' => 2600, 'align' => 'start'],
    ];
    $ukupnaSirina = max(1, array_sum(array_map(static fn ($kolona) => (int) ($kolona['width'] ?? 0), $kolone)));
@endphp

<table class="{{ $tableClass }}">
    <colgroup>
        @foreach($kolone as $kolona)
            @php
                $kljuc = (string) ($kolona['key'] ?? '');
                $sirina = round(((int) ($kolona['width'] ?? 0) / $ukupnaSirina) * 100, 2);
            @endphp
            <col style="width: {{ $sirina }}%;" data-document-field="{{ $kljuc }}">
        @endforeach
    </colgroup>
    <thead>
    <tr>
        @foreach($kolone as $kolona)
            @php
                $kljuc = (string) ($kolona['key'] ?? '');
                $poravnanje = (string) ($kolona['align'] ?? 'start') === 'center' ? 'center' : 'left';
            @endphp
            <th data-document-field="{{ $kljuc }}" style="text-align: {{ $poravnanje }};">{{ $kolona['label'] ?? '' }}</th>
        @endforeach
    </tr>
    </thead>
    <tbody>
    @foreach($redovi as $red)
        <tr>
            @foreach($kolone as $kolona)
                @php
                    $kljuc = (string) ($kolona['key'] ?? '');
                    $poravnanje = (string) ($kolona['align'] ?? 'start') === 'center' ? 'center' : 'left';
                @endphp
                <td data-document-field="{{ $kljuc }}" style="text-align: {{ $poravnanje }};">{{ $red[$kljuc] ?? '' }}</td>
            @endforeach
        </tr>
    @endforeach
    </tbody>
</table>
