<div class="modal fade" id="skolaQrModal" tabindex="-1" aria-labelledby="skolaQrNaslov" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="skolaQrNaslov" class="modal-title fs-5">QR Evidencija dolazaka</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Zatvori"></button>
            </div>
            <div class="modal-body text-center d-flex flex-column justify-content-center align-items-center">
                <p class="h4 mb-2" data-qr-ime></p>
                <p class="mb-3">Pokaži ovaj kod treneru pri dolasku na trening.</p>
                <div data-qr-stanje role="status"></div>
                <img class="img-fluid rounded" alt="QR kod za evidenciju dolazaka" width="532" height="532"
                     style="width:min(100%, 24rem, 62dvh); height:auto; background:#fff" hidden>
            </div>
        </div>
    </div>
</div>

<script>
    (() => {
        const modal = document.getElementById('skolaQrModal');
        const slika = modal.querySelector('img');
        const stanje = modal.querySelector('[data-qr-stanje]');
        modal.addEventListener('show.bs.modal', (event) => {
            const poveznica = event.relatedTarget;
            if (!poveznica?.dataset.qrUrl) return;

            modal.querySelector('[data-qr-ime]').textContent = poveznica.dataset.qrIme;
            slika.hidden = true;
            stanje.textContent = 'Učitavanje QR koda…';
            slika.src = poveznica.dataset.qrUrl;
        });
        slika.addEventListener('load', () => {
            slika.hidden = false;
            stanje.textContent = '';
        });
        slika.addEventListener('error', () => {
            slika.hidden = true;
            stanje.textContent = 'QR kod nije dostupan. Osvježi naslovnicu i pokušaj ponovno.';
        });
        modal.addEventListener('hidden.bs.modal', () => {
            slika.hidden = true;
            slika.removeAttribute('src');
        });
    })();
</script>
