<button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#skolaQrModal"
        data-qr-url="{{ route('admin.skola.evidencija.qr.probni') }}" data-qr-ime="{{ auth()->user()->name }}">
    QR Evidencija dolazaka
</button>
