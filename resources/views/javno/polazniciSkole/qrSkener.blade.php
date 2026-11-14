@extends('layouts.app')

@section('page_title', 'Evidencija dolazaka')

@push('head')
    <link rel="manifest" href="{{ route('skola.evidencija.qr_manifest') }}">
@endpush

@section('content')
    <div class="container" style="max-width:42rem" data-attendance-scanner
         data-url="{{ route('admin.skola.evidencija.qr.spremi') }}">
        <div class="card shadow-sm">
            <div class="card-header bg-danger text-white">
                <h1 class="h5 mb-0">Evidencija dolazaka</h1>
            </div>
            <div class="card-body">
                <p>Postavi QR kod polaznika ispred kamere.</p>
                <video class="w-100 rounded mb-3 bg-dark" style="max-height:55vh; object-fit:contain"
                       aria-label="Prikaz prednje kamere" playsinline muted autoplay hidden></video>
                <div class="alert alert-secondary" data-stanje role="status" aria-live="polite">Skener je spreman.</div>
                <div class="d-grid gap-2">
                    <button type="button" class="btn btn-primary btn-lg" data-pokreni>Pokreni skeniranje</button>
                    <button type="button" class="btn btn-outline-secondary" data-zaustavi hidden>Zaustavi kameru</button>
                    <label class="form-label mb-0" for="slika-qr-koda">Učitaj sliku QR koda</label>
                    <input class="form-control" type="file" id="slika-qr-koda" accept="image/*">
                    <a class="btn btn-outline-primary mt-2" href="{{ route('javno.skola.evidencija.index') }}">Pregled dolazaka</a>
                </div>
            </div>
        </div>
    </div>
@endsection
