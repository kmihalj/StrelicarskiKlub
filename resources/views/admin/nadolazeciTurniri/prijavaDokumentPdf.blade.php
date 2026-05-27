<!DOCTYPE html>
<html lang="hr">
<head>
    <meta charset="UTF-8">
    <title>Prijava - {{ $turnirNaziv }}</title>
    <style>
        @page {
            margin: 16px;
        }

        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 11px;
            color: #111827;
        }

        .logo-wrap {
            margin-bottom: 6px;
        }

        .logo {
            width: 420px;
            max-width: 100%;
            height: auto;
        }

        .header {
            margin-bottom: 8px;
        }

        .header-row {
            display: table;
            width: 100%;
            margin-bottom: 2px;
        }

        .header-cell {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }

        .header-label {
            font-weight: 700;
        }

        table {
            border-collapse: collapse;
            width: 100%;
            table-layout: fixed;
            border: 1px solid #6b7280;
        }

        th,
        td {
            border: 1px solid #9ca3af;
            padding: 4px 5px;
            vertical-align: middle;
            font-size: 9px;
            word-wrap: break-word;
        }

        th {
            background: #d1d5db;
            text-align: center;
            font-weight: 700;
            color: #111827;
        }

        td:nth-child(1),
        td:nth-child(2),
        td:nth-child(7),
        td:nth-child(8) {
            text-align: center;
        }

        .footer {
            margin-top: 18px;
            font-size: 11px;
            position: relative;
            min-height: 100px;
        }

        .potpis-red {
            min-height: 54px;
            margin-bottom: 0;
            padding-top: 4px;
            white-space: nowrap;
        }

        .potpis-label {
            font-weight: 400;
            display: inline-block;
            margin-right: 6px;
            vertical-align: middle;
        }

        .potpis-linija {
            display: inline-block;
            vertical-align: middle;
            border-bottom: 1px solid #111827;
            width: 285px;
            height: 32px;
            line-height: 32px;
            margin-bottom: 3px;
            position: relative;
        }

        .potpis-slika {
            position: absolute;
            left: 18px;
            bottom: -11px;
            width: 195px;
            height: auto;
        }

        .pecat-slika {
            position: absolute;
            left: 268px;
            top: 26px;
            width: 84px;
            height: auto;
        }

        .predsjednik-red {
            margin-top: 6px;
        }
    </style>
</head>
<body>
@if(!empty($logoPath))
    <div class="logo-wrap">
        <img src="{{ $logoPath }}" alt="Hrvatski streličarski savez" class="logo">
    </div>
@endif
<div class="header">
    <div class="header-row">
        <div class="header-cell"><span class="header-label">Natjecanje:</span> {{ $turnirNaziv }}</div>
        <div class="header-cell"><span class="header-label">Datum natjecanja:</span> {{ $turnirDatum }}</div>
    </div>
    <div class="header-row">
        <div class="header-cell"><span class="header-label">Klub:</span> {{ $klubNaziv }}</div>
        <div class="header-cell"><span class="header-label">Datum prijave:</span> {{ $datumPrijave }}</div>
    </div>
</div>

@include('admin.nadolazeciTurniri.partials.prijavaDokumentTabela', [
    'redovi' => $redovi,
    'kolone' => $kolone,
    'tableClass' => '',
])

<div class="footer">
    <div class="potpis-red">
        <span class="potpis-label">Potpis odgovorne osobe kluba:</span>
        <span class="potpis-linija">
            @if(!empty($potpisPredsjednikPath))
                <img src="{{ $potpisPredsjednikPath }}" alt="Potpis predsjednika kluba" class="potpis-slika">
            @endif
        </span>
    </div>
    @if(!empty($pecatKlubaPath))
        <img src="{{ $pecatKlubaPath }}" alt="Pečat kluba" class="pecat-slika">
    @endif
    @if(!empty($predsjednikImePrezime))
        <div class="predsjednik-red">Predsjednik kluba: {{ $predsjednikImePrezime }}</div>
    @endif
</div>
</body>
</html>
