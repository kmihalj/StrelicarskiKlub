import jsQR from 'jsqr';

export function mountAttendanceScanner(scanner) {
    const video = scanner.querySelector('video');
    const start = scanner.querySelector('[data-pokreni]');
    const stop = scanner.querySelector('[data-zaustavi]');
    const feedback = scanner.querySelector('[data-stanje]');
    const imageInput = scanner.querySelector('input[type="file"]');
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    const canvas = document.createElement('canvas');
    const context = canvas.getContext('2d', { willReadFrequently: true });
    let camera = null;
    let timer = null;
    let cameraAttempt = 0;
    let pending = false;
    let readingImage = false;
    let lastCode = null;
    let lastSeen = 0;

    function show(message, variant = 'secondary') {
        feedback.textContent = message;
        feedback.className = `alert alert-${variant}`;
    }

    function readCode(image, width, height, maxSize) {
        const scale = Math.min(1, maxSize / Math.max(width, height));
        const canvasWidth = Math.max(1, Math.round(width * scale));
        const canvasHeight = Math.max(1, Math.round(height * scale));
        if (canvas.width !== canvasWidth || canvas.height !== canvasHeight) {
            canvas.width = canvasWidth;
            canvas.height = canvasHeight;
        }
        context.drawImage(image, 0, 0, canvasWidth, canvasHeight);
        const frame = context.getImageData(0, 0, canvasWidth, canvasHeight);
        return jsQR(frame.data, frame.width, frame.height, { inversionAttempts: 'dontInvert' })?.data;
    }

    async function record(code) {
        if (!/^(?:SKD1|SKDTEST1):[a-f0-9]{32}$/.test(code)) {
            show('Ovaj QR kod ne pripada evidenciji škole streličarstva.', 'warning');
            return;
        }

        pending = true;
        imageInput.disabled = true;
        show('Evidentiram dolazak…');
        try {
            const response = await fetch(scanner.dataset.url, {
                method: 'POST',
                credentials: 'same-origin',
                headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify({ kod: code }),
            });
            if ([401, 419].includes(response.status)) {
                stopCamera();
                show('Prijava je istekla. Osvježi stranicu i prijavi se ponovno.', 'warning');
                return;
            }
            if (response.status === 403) {
                stopCamera();
                show('Skeniranje dolazaka dostupno je administratoru.', 'warning');
                return;
            }
            if (response.status === 429) {
                show('Pričekaj trenutak pa ponovno skeniraj kod.', 'warning');
                return;
            }
            const result = await response.json();
            if (response.ok && ['evidentiran', 'vec_evidentiran', 'probni'].includes(result.status)) {
                show(`${result.polaznik} — ${result.message}`, result.status === 'vec_evidentiran' ? 'info' : 'success');
            } else if ([404, 409, 422].includes(response.status)) {
                show(result.polaznik ? `${result.polaznik} — ${result.message}` : result.message, 'warning');
            } else {
                show('Nije potvrđen upis dolaska. Pokušaj ponovno.', 'danger');
            }
        } catch {
            show('Nije potvrđen upis dolaska. Provjeri vezu i pokušaj ponovno.', 'danger');
        } finally {
            pending = false;
            imageInput.disabled = false;
        }
    }

    function scanFrame() {
        if (!camera) return;

        if (!pending && video.readyState >= 2 && video.videoWidth > 0) {
            const code = readCode(video, video.videoWidth, video.videoHeight, 720);
            const now = performance.now();
            if (code) {
                if (code !== lastCode || now - lastSeen > 1000) {
                    void record(code);
                }
                lastCode = code;
                lastSeen = now;
            }
        }
        if (camera) timer = window.setTimeout(scanFrame, 180);
    }

    function stopCamera() {
        cameraAttempt++;
        window.clearTimeout(timer);
        camera?.getTracks().forEach((track) => track.stop());
        camera = null;
        video.srcObject = null;
        video.hidden = true;
        start.disabled = false;
        start.hidden = false;
        stop.hidden = true;
        lastCode = null;
    }

    start.addEventListener('click', async () => {
        if (!navigator.mediaDevices?.getUserMedia) {
            show('Kamera je dostupna preko HTTPS-a uz dopuštenje preglednika. Možeš učitati sliku QR koda.', 'warning');
            return;
        }

        const attempt = ++cameraAttempt;
        start.disabled = true;
        stop.hidden = false;
        show('Pokrećem kameru…');
        try {
            const stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' }, audio: false });
            if (attempt !== cameraAttempt) {
                stream.getTracks().forEach((track) => track.stop());
                return;
            }
            camera = stream;
            video.srcObject = stream;
            video.hidden = false;
            await video.play();
            if (attempt !== cameraAttempt) return;

            start.hidden = true;
            show('Skeniranje je pokrenuto. Približi QR kod kameri.');
            scanFrame();
        } catch (error) {
            if (attempt !== cameraAttempt) return;

            stopCamera();
            show(error.name === 'NotAllowedError'
                ? 'Dopusti pristup kameri u pregledniku ili učitaj sliku QR koda.'
                : 'Kamera nije dostupna. Možeš učitati sliku QR koda.', 'warning');
        }
    });

    stop.addEventListener('click', () => {
        stopCamera();
        show('Kamera je zaustavljena.');
    });

    imageInput.addEventListener('change', async () => {
        const file = imageInput.files[0];
        if (!file || pending || readingImage) return;

        stopCamera();
        readingImage = true;
        start.disabled = true;
        imageInput.disabled = true;
        const url = URL.createObjectURL(file);
        try {
            const image = new Image();
            image.src = url;
            await image.decode();
            const code = readCode(image, image.naturalWidth, image.naturalHeight, 1280);
            if (code) {
                await record(code);
            } else {
                show('Na slici nije prepoznat QR kod.', 'warning');
            }
        } catch {
            show('Sliku QR koda nije moguće otvoriti.', 'warning');
        } finally {
            URL.revokeObjectURL(url);
            imageInput.value = '';
            imageInput.disabled = false;
            start.disabled = false;
            readingImage = false;
        }
    });

    window.addEventListener('pagehide', stopCamera);
    document.addEventListener('visibilitychange', () => {
        if (document.hidden && (camera || start.disabled)) {
            stopCamera();
            show('Kamera je zaustavljena.');
        }
    });
}
