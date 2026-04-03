@php
    $tableClass = isset($tableClass) ? (string) $tableClass : 'table table-bordered table-sm align-middle mb-0';
@endphp

<table class="{{ $tableClass }}">
    <colgroup>
        <col style="width: 4%;">
        <col style="width: 6%;">
        <col style="width: 8%;">
        <col style="width: 10%;">
        <col style="width: 12%;">
        <col style="width: 13%;">
        <col style="width: 12%;">
        <col style="width: 6%;">
        <col style="width: 12%;">
        <col style="width: 17%;">
    </colgroup>
    <thead>
    <tr>
        <th>R. br.</th>
        <th>Licenca</th>
        <th>Ime</th>
        <th>Prezime</th>
        <th>Stil</th>
        <th>Kategorija</th>
        <th>Liječnički pregled</th>
        <th>Kup</th>
        <th>Obrok</th>
        <th>Napomena</th>
    </tr>
    </thead>
    <tbody>
    @foreach($redovi as $red)
        <tr>
            <td>{{ $red['rb'] ?? '' }}</td>
            <td>{{ $red['licenca'] ?? '' }}</td>
            <td>{{ $red['ime'] ?? '' }}</td>
            <td>{{ $red['prezime'] ?? '' }}</td>
            <td>{{ $red['stil'] ?? '' }}</td>
            <td>{{ $red['kategorija'] ?? '' }}</td>
            <td>{{ $red['lijecnicki'] ?? '' }}</td>
            <td>{{ $red['kup'] ?? '' }}</td>
            <td>{{ $red['obrok'] ?? '' }}</td>
            <td>{{ $red['napomena'] ?? '' }}</td>
        </tr>
    @endforeach
    </tbody>
</table>
