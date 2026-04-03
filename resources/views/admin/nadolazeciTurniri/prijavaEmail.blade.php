<!DOCTYPE html>
<html lang="hr">
<head>
    <meta charset="UTF-8">
    <title>Prijava na turnir</title>
    <style>
        .prijava-tablica {
            border-collapse: collapse;
            width: 100%;
            table-layout: fixed;
            font-size: 12px;
            border: 1px solid #6b7280;
        }

        .prijava-tablica th,
        .prijava-tablica td {
            border: 1px solid #9ca3af;
            padding: 4px 6px;
            vertical-align: middle;
            word-wrap: break-word;
        }

        .prijava-tablica th {
            background-color: #d1d5db;
            text-align: center;
            font-weight: 700;
        }

        .prijava-tablica td:nth-child(1),
        .prijava-tablica td:nth-child(2),
        .prijava-tablica td:nth-child(7),
        .prijava-tablica td:nth-child(8) {
            text-align: center;
        }

        .signature {
            margin-top: 14px;
            font-size: 14px;
            line-height: 1.35;
        }

        .signature-logo {
            display: block;
            width: 137px;
            height: 50px;
            margin-bottom: 6px;
        }
    </style>
</head>
<body style="font-family: Arial, sans-serif; color: #1f2937; font-size: 14px;">
    <p style="margin: 0 0 10px 0;">Poštovani,</p>

    @if($porukaTekst !== '')
        <p style="margin: 0 0 10px 0;">{!! nl2br(e($porukaTekst)) !!}</p>
    @else
        <p style="margin: 0 0 10px 0;">
            U privitku Vam šaljemo prijavnicu za turnir <strong>{{ $podaci['turnirNaziv'] }}</strong>
            ({{ $podaci['turnirDatum'] }}) u <strong>DOCX</strong> i <strong>PDF</strong> formatu.
        </p>
    @endif

    <p style="margin: 0 0 12px 0;">
        <strong>Natjecanje:</strong> {{ $podaci['turnirNaziv'] }}<br>
        <strong>Datum natjecanja:</strong> {{ $podaci['turnirDatum'] }}<br>
        <strong>Klub:</strong> {{ $podaci['klubNaziv'] }}<br>
        <strong>Datum prijave:</strong> {{ $podaci['datumPrijave'] }}
    </p>

    @include('admin.nadolazeciTurniri.partials.prijavaDokumentTabela', [
        'redovi' => $podaci['redovi'],
        'tableClass' => 'prijava-tablica',
    ])

    <p style="margin: 12px 0 10px 0;">Srdačan pozdrav,</p>

    <div class="signature">
        @if(!empty($podaci['potpisSvgInline']))
            {!! $podaci['potpisSvgInline'] !!}
        @elseif(!empty($podaci['potpisLogoUrl']))
            <img src="{{ $podaci['potpisLogoUrl'] }}" alt="{{ $podaci['klubNaziv'] }}" class="signature-logo">
        @endif

        <div><strong>{{ $podaci['klubNaziv'] }}</strong></div>
        @if(!empty($podaci['klubAdresa']))
            <div>{{ $podaci['klubAdresa'] }}</div>
        @endif
        @if(!empty($podaci['klubTelefon']))
            <div><a href="tel:{{ $podaci['klubTelefon'] }}">{{ $podaci['klubTelefon'] }}</a></div>
        @endif
        @if(!empty($podaci['klubEmail']))
            <div><a href="mailto:{{ $podaci['klubEmail'] }}">{{ $podaci['klubEmail'] }}</a></div>
        @endif
        @if(!empty($podaci['klubWeb']))
            <div><a href="{{ $podaci['klubWeb'] }}">{{ $podaci['klubWeb'] }}</a></div>
        @endif
        @if(!empty($podaci['klubRacun']))
            <div>{{ $podaci['klubRacun'] }}</div>
        @endif
        @if(!empty($podaci['predsjednikImePrezime']))
            <div style="margin-top: 8px;"><strong>Predsjednik kluba</strong><br>{{ $podaci['predsjednikImePrezime'] }}</div>
        @endif
    </div>
</body>
</html>
