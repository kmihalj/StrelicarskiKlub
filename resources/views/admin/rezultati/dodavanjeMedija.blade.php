{{-- Forma za upload slika/video datoteka uz turnir. --}}
<div class="row">
    <div class="col-lg-12 col-md-6 fw-bolder">
        @php
            $maxFileUploads = max((int)ini_get('max_file_uploads'), 1);
        @endphp
        <form id="uploadMedija"
              action="{{ route('admin.rezultati.uploadMedija') }}"
              enctype="multipart/form-data"
              method="POST"
              data-max-file-uploads="{{ $maxFileUploads }}">
            @csrf
            <div class="row pb-4">
                <div class="col-10">
                    <input type="hidden" id="turnir_id" name="turnir_id" value={{$turnir->id}}>
                    <input class="form-control" type="file" id="medij" name="medij[]" accept=".jpg,.jpeg,.png,.webp,.mp4" multiple>
                    <small class="text-muted">Možete odabrati više datoteka odjednom.</small>
                </div>
                <div class="col-2">
                    <button type="submit" class="btn btn-primary float-end">Upload</button>
                </div>
            </div>
        </form>
    </div>
</div>


@if($turnir->mediji->count() != 0)
    <div class="row">
        @foreach($turnir->mediji as $medij)
            <div class="col-auto text-center mb-1">
                @if($medij->vrsta == "slika")
                    <img src="{{ asset('storage/turniri/' . $turnir->id . '/' . $medij->link) }}"
                         class="img-thumbnail"
                         style="max-width: 10rem; max-height: 10rem;"
                         alt="">
                @else
                    <video controls="controls" style="max-width: 20rem; max-height: 20rem;">
                        <source src="{{ asset('storage/turniri/' . $turnir->id . '/' . $medij->link) }}"
                                type="video/mp4"/>
                        Vaš browser ne podržava video.
                    </video>
                @endif
                <br>
                <form id="brisanjeMedija{{$medij->id}}" action="{{ route('admin.rezultati.brisanjeMedija') }}" method="POST">
                    @csrf
                    <input type="hidden" id="medijBrisanje" name="medijBrisanje" value="{{$medij->id}}">
                </form>
                @if($medij->vrsta == "slika")
                    <button class="btn btn-outline-success" title="Kopiraj u Clipboard" onclick="copySlikaLink{{$medij->id}}()">
                        @include('admin.SVG.clipboard')
                    </button>
                    <script>
                        function copySlikaLink{{$medij->id}}() {
                            navigator.clipboard.writeText
                            ('<img src="{{ asset('storage/turniri/' . $turnir->id . '/' . $medij->link) }}" alt="" style="width:25%">');
                        }
                    </script>
                @endif
                <button type="submit" form="brisanjeMedija{{$medij->id}}" class="btn btn-outline-danger" title="Delete">
                    @include('admin.SVG.obrisi')
                </button>
            </div>
        @endforeach
    </div>
@endif

<script>
    (() => {
        const form = document.getElementById('uploadMedija');
        if (!form) {
            return;
        }

        const input = form.querySelector('#medij');
        const submitButton = form.querySelector('button[type="submit"]');
        const parsedChunkSize = Number(form.dataset.maxFileUploads);
        const chunkSize = Number.isFinite(parsedChunkSize) && parsedChunkSize > 0 ? parsedChunkSize : 20;

        form.addEventListener('submit', async (event) => {
            const files = Array.from(input && input.files ? input.files : []);
            if (files.length === 0) {
                return;
            }

            event.preventDefault();

            const originalButtonText = submitButton.textContent;
            submitButton.disabled = true;
            input.disabled = true;

            try {
                let batchNumber = 1;
                const totalBatches = Math.ceil(files.length / chunkSize);

                for (let start = 0; start < files.length; start += chunkSize) {
                    submitButton.textContent = `Upload ${batchNumber}/${totalBatches}`;
                    const formData = new FormData();
                    formData.append('_token', form.querySelector('input[name="_token"]').value);
                    formData.append('turnir_id', form.querySelector('input[name="turnir_id"]').value);

                    files.slice(start, start + chunkSize).forEach((file) => {
                        formData.append('medij[]', file);
                    });

                    const response = await fetch(form.action, {
                        method: 'POST',
                        body: formData,
                        credentials: 'same-origin',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        }
                    });

                    if (!response.ok) {
                        let message = 'Greška pri uploadu datoteka.';
                        try {
                            const body = await response.json();
                            if (body && body.message) {
                                message = body.message;
                            }
                        } catch (_) {}
                        throw new Error(message);
                    }

                    batchNumber++;
                }

                window.location.reload();
            } catch (error) {
                alert(error && error.message ? error.message : 'Greška pri uploadu datoteka.');
                submitButton.disabled = false;
                input.disabled = false;
                submitButton.textContent = originalButtonText;
            }
        });
    })();
</script>
