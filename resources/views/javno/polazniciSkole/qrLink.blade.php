@if(isset($qrPolaznici[$qrPolaznik->id]))
    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#skolaQrModal"
            data-qr-url="{{ route('skola.polaznici.qr_dolasci', $qrPolaznik) }}"
            data-qr-ime="{{ trim($qrPolaznik->Ime.' '.$qrPolaznik->Prezime) }}">
        QR Evidencija dolazaka
    </button>
@endif
