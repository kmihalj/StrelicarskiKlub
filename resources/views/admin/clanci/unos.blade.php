{{-- Administratorska forma za unos i uređivanje članka (tekst, menu, mediji, Facebook link). --}}
@extends('layouts.app')
@auth()
    @if(auth()->user()->rola <= 1)
        @php
            $isEditing = isset($clanak);
        @endphp
        @section('content')
            <div class="container-xxl bg-white shadow mb-3">
                <div class="row justify-content-center p-2 shadow bg-danger fw-bolder">
                    <div class="col-lg-12 text-white d-flex justify-content-between align-items-center">
                        <span>
                            @isset($clanak)
                                Uređivanje
                            @else
                                Unos
                            @endisset članka</span>
                        <form id="unosClanka" action="{{ route('admin.clanci.spremanjeClanka') }}" method="POST">
                            @isset($clanak)
                                <input type="hidden" id="id_clanka" name="id_clanka" value="{{$clanak->id}}">
                            @endisset
                            @csrf
                        </form>
                        <button class="btn btn-sm btn-warning" onclick="location.href='{{ route('admin.clanci.popisClanaka') }}'" type="button">Popis članaka</button>
                    </div>
                </div>
                <div class="row p-2 shadow bg-white">
                    <div class="col-lg-3 mb-3">
                        <label for="vrsta" class="fw-bolder">Vrsta:</label>
                        <select class="form-select" form="unosClanka" id="vrsta" name="vrsta" aria-label="Odabir vrste" required>
                            <option selected></option>
                            <option value="Obavijest" @selected($isEditing && $clanak->vrsta === 'Obavijest')>Obavijest</option>
                            <option value="O nama" @selected($isEditing && $clanak->vrsta === 'O nama')>O nama</option>
                            <option value="Streličarstvo" @selected($isEditing && $clanak->vrsta === 'Streličarstvo')>Streličarstvo</option>
                            <option value="Naslovnica" @selected($isEditing && $clanak->vrsta === 'Naslovnica')>Naslovnica</option>
                        </select>
                    </div>
                    <div class="col-lg-8 mb-3">
                        <label for="naslov" class="fw-bolder">Naslov:</label>
                        <input type="text" form="unosClanka" class="form-control" name="naslov" id="naslov"
                               @isset($clanak) value="{{$clanak->naslov}}" @endisset
                               required>
                    </div>
                    <div class="col-lg-2 mb-2">
                        <label for="datum" class="fw-bolder">Datum:</label>
                        <input type="date" class="form-control" form="unosClanka" name="datum" id="datum"
                               value="{{ isset($clanak) ? $clanak->datum : now()->format('Y-m-d') }}"
                               required>
                    </div>
                    <div class="col-lg-12 mb-3">
                        <label for="sadrzaj" class="fw-bolder">Sadržaj:</label>
                        <textarea class="form-control" form="unosClanka" name="sadrzaj" id="sadrzaj" aria-label="sadrzaj">{{ old('sadrzaj', $sadrzajEditor ?? (isset($clanak) ? $clanak->sadrzaj : '')) }}</textarea>

                        <div class="mt-3">
                            <label for="facebook_link_sadrzaj">Facebook link (opcionalno):</label>
                            <input type="text"
                                   class="form-control"
                                   form="unosClanka"
                                   name="facebook_link_sadrzaj"
                                   id="facebook_link_sadrzaj"
                                   value="{{ old('facebook_link_sadrzaj', $facebookLinkSadrzaj ?? '') }}"
                                   placeholder="https://www.facebook.com/...">
                            <small class="text-muted">Ako je polje prazno, Facebook link se neće prikazivati.</small>
                        </div>
                    </div>

                    <div class="col-lg-3 mb-3">
                        <label for="menu_naslov" class="fw-bolder">Naslov u meniju:</label>
                        <input type="text" form="unosClanka" class="form-control" name="menu_naslov" id="menu_naslov"
                               @isset($clanak) value="{{$clanak->menu_naslov}}" @endisset
                        >
                    </div>
                    <div class="col-lg-2 mb-2 align-self-center">
                        <div class="form-check form-switch ">
                            <br>
                            <input class="form-check-input"
                                   type="checkbox"
                                   form="unosClanka"
                                   id="menu"
                                   name="menu"
                                   value="1"
                                   aria-label="menu"
                                   @checked($isEditing && !empty($clanak->menu))>
                            <label class="form-check-label" for="menu">Stavka u meniju</label>


                        </div>
                    </div>


                    <div class="row">
                        <div class="col-lg-10 col-md-10 col-10 mb-2">
                            @if(!$isEditing)
                                <p class="fw-bold text-danger" style="text-align: justify; text-justify: inter-word;">Mediji (slike ili video zapisi) se mogu dodati samo na spremljeni članak. Spremite članak barem sa naslovom da bi mogli dodavati medije.</p>
                            @else
                                <p class="fw-bold text-danger" style="text-align: justify; text-justify: inter-word;">Mediji (slike ili video zapisi) koji su dodani mogu se sa klikom spremiti u clipboard, te se dodaju u source sadržaja (gumb: Source). <br>Prije dodavanje novog medija spremite sadržaj jer se dodavanjem medija gube promjene ukoliko nisu spremljene.</p>
                            @endif
                        </div>
                        <div class="col-lg-2 col-md-2 col-2 mb-2 text-end align-self-end">
                            <button class="btn btn-warning" onclick="location.href='{{ route('admin.clanci.popisClanaka') }}'" type="button">Odustani</button>
                            <button type="submit" form="unosClanka" class="btn btn-danger">Spremi</button>
                        </div>
                    </div>

                </div>
            </div>


            @if($isEditing)
                <div class="container-xxl bg-white shadow">
                    <div class="row justify-content-center p-2 shadow bg-danger fw-bolder">
                        <div class="col-lg-12 text-white d-flex justify-content-between align-items-center">
                            <span>Dodavanje slika <i>(.jpg, .jpeg, .png, .webp)</i> dokumenata <i>(.doc, .docx, .pdf)</i> i/ili videa <i>(.mp4)</i></span>
                        </div>
                    </div>
                    <div class="row p-2 bg-white">
                        <div class="col-lg-12">
                            @php
                                $maxFileUploads = max((int)ini_get('max_file_uploads'), 1);
                            @endphp
                            <form id="uploadMedija"
                                  action="{{ route('admin.clanci.uploadMedija') }}"
                                  enctype="multipart/form-data"
                                  method="POST"
                                  data-max-file-uploads="{{ $maxFileUploads }}">
                                @csrf
                                <div class="row mt-3">
                                    <div class="col-lg-10 pb-2">
                                        <input type="hidden" id="clanak_id" name="clanak_id" value="{{$clanak->id}}">
                                        <input class="form-control" type="file" id="medij" name="medij[]" accept=".jpg,.jpeg,.png,.webp,.doc,.docx,.pdf,.mp4" multiple>
                                        <small class="text-muted">Možete odabrati više datoteka odjednom.</small>
                                    </div>
                                    <div class="col-lg-2 pb-2">
                                        <button type="submit" class="btn btn-primary float-end">Upload</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                    @if($clanak->mediji->count() != 0)
                        <div class="row p-2 bg-white">
                            <div class="col-lg-12 mb-2 align-self-center">
                                <form id="galerija{{ $clanak->id }}" action="{{ route('admin.clanci.galerija') }}" method="POST">
                                    @csrf
                                    <input type="hidden" id="id_clanka_galerija" name="id_clanka" value="{{$clanak->id}}">
                                </form>
                                <div class="form-check form-switch ">
                                    <br>
                                    <input class="form-check-input"
                                           onchange="galerija{{ $clanak->id }}.submit()"
                                           type="checkbox"
                                           form="galerija{{ $clanak->id }}"
                                           id="galerija"
                                           name="galerija"
                                           value="1"
                                           aria-label="galerija"
                                           @checked(!empty($clanak->galerija))>
                                    <label class="form-check-label" for="menu">Galerija</label>
                                </div>
                            </div>
                            @foreach($clanak->mediji as $medij)
                                <div class="col-auto text-center p-2">
                                    @switch($medij->vrsta)
                                        @case('slika')
                                            <img src="{{ asset('storage/clanci/' . $clanak->id . '/' . $medij->link) }}"
                                                 class="img-thumbnail"
                                                 style="max-width: 10rem; max-height: 10rem;"
                                                 alt="">
                                            @break
                                        @case('video')
                                            <video controls="controls" style="max-width: 20rem; max-height: 20rem;">
                                                <source src="{{ asset('storage/clanci/' . $clanak->id . '/' . $medij->link) }}"
                                                        type="video/mp4"/>
                                                Vaš browser ne podržava video.
                                            </video>
                                            @break
                                        @default
                                            <a href="{{ asset('storage/clanci/' . $clanak->id . '/' . $medij->link) }}" target="_blank">{{$medij->link}}</a>
                                    @endswitch
                                    <br>
                                    <form id="brisanjeMedija{{$medij->id}}" action="{{ route('admin.clanci.brisanjeMedija') }}" method="POST">
                                        @csrf
                                        <input type="hidden" id="medijBrisanje{{$medij->id}}" name="medijBrisanje" value="{{$medij->id}}">
                                    </form>
                                    @php
                                        $copyMarkup = null;
                                        if ($medij->vrsta === 'slika') {
                                            $copyMarkup = '<img src="' . asset('storage/clanci/' . $clanak->id . '/' . $medij->link) . '" alt="" style="width:35%">';
                                        } elseif ($medij->vrsta === 'video') {
                                            $copyMarkup = '<video controls="controls" style="max-width: 50%;"><source src="' . asset('storage/clanci/' . $clanak->id . '/' . $medij->link) . '" type="video/mp4"/> Vaš browser ne podržava video. </video>';
                                        } elseif ($medij->vrsta === 'dokument') {
                                            $copyMarkup = '<a href="' . asset('storage/clanci/' . $clanak->id . '/' . $medij->link) . '">' . e($medij->link) . '</a>';
                                        }
                                    @endphp
                                    @if($copyMarkup !== null)
                                        <button type="button"
                                                class="btn btn-outline-success js-copy-media"
                                                title="Kopiraj u Clipboard"
                                                data-copy-html="{{ base64_encode($copyMarkup) }}">
                                            @include('admin.SVG.clipboard')
                                        </button>
                                    @endif
                                    <button type="submit" form="brisanjeMedija{{$medij->id}}" class="btn btn-outline-danger" title="Delete">
                                        @include('admin.SVG.obrisi')
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <script>
                    (() => {
                        const form = /** @type {HTMLFormElement|null} */ (document.getElementById('uploadMedija'));
                        if (!form) {
                            return;
                        }

                        const input = /** @type {HTMLInputElement|null} */ (form.querySelector('#medij'));
                        const submitButton = /** @type {HTMLButtonElement|null} */ (form.querySelector('button[type="submit"]'));
                        const tokenInput = /** @type {HTMLInputElement|null} */ (form.querySelector('input[name="_token"]'));
                        const clanakIdInput = /** @type {HTMLInputElement|null} */ (form.querySelector('input[name="clanak_id"]'));
                        if (!input || !submitButton || !tokenInput || !clanakIdInput) {
                            return;
                        }

                        const parsedChunkSize = Number(form.dataset.maxFileUploads || '');
                        const chunkSize = Number.isFinite(parsedChunkSize) && parsedChunkSize > 0 ? parsedChunkSize : 20;

                        form.addEventListener('submit', async (event) => {
                            const files = Array.from(input.files || []);
                            if (files.length === 0) {
                                return;
                            }

                            event.preventDefault();

                            const originalButtonText = submitButton.textContent || 'Upload';
                            submitButton.disabled = true;
                            input.disabled = true;

                            let batchNumber = 1;
                            const totalBatches = Math.ceil(files.length / chunkSize);
                            let uploadErrorMessage = '';

                            for (let start = 0; start < files.length; start += chunkSize) {
                                submitButton.textContent = `Upload ${batchNumber}/${totalBatches}`;
                                const formData = new FormData();
                                formData.append('_token', tokenInput.value);
                                formData.append('clanak_id', clanakIdInput.value);

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
                                    uploadErrorMessage = 'Greška pri uploadu datoteka.';
                                    try {
                                        const body = await response.json();
                                        if (body && body.message) {
                                            uploadErrorMessage = body.message;
                                        }
                                    } catch (_) {}
                                    break;
                                }

                                batchNumber++;
                            }

                            if (uploadErrorMessage !== '') {
                                alert(uploadErrorMessage);
                                submitButton.disabled = false;
                                input.disabled = false;
                                submitButton.textContent = originalButtonText;
                                return;
                            }

                            window.location.reload();
                        });

                        const copyButtons = /** @type {HTMLButtonElement[]} */ (Array.from(document.querySelectorAll('.js-copy-media')));
                        copyButtons.forEach((button) => {
                            button.addEventListener('click', () => {
                                const encodedMarkup = button.dataset.copyHtml || '';
                                if (encodedMarkup === '') {
                                    return;
                                }

                                try {
                                    const markup = atob(encodedMarkup);
                                    navigator.clipboard.writeText(markup);
                                } catch (_) {}
                            });
                        });
                    })();
                </script>
            @endif

            <script src="{{ asset('assets/ckeditor5/ckeditor5.js') }}"></script>
            <script src="{{ asset('assets/ckeditor5/hr.js') }}"></script>
            <script>
                (() => {
                    const editorNamespace = window['CKEDITOR'];
                    const classicEditor = editorNamespace ? editorNamespace['ClassicEditor'] : null;
                    if (!classicEditor || typeof classicEditor.create !== 'function') {
                        return;
                    }

                    classicEditor.create(document.getElementById("sadrzaj"), {
                    toolbar: {
                        items: [
                            'heading', '|',
                            'bold', 'italic', 'strikethrough', 'underline', 'subscript', 'superscript', 'removeFormat', '|',
                            'alignment', '|',
                            'fontSize', 'fontFamily', 'fontColor', 'fontBackgroundColor', 'highlight', '|',
                            'bulletedList', 'numberedList', 'todoList', '|',
                            'outdent', 'indent', '|',
                            'undo', 'redo',
                            // 'link', 'uploadImage', 'insertTable', 'mediaEmbed', '|',
                            'link', 'insertTable', '|',
                            'horizontalLine', '|',
                            'sourceEditing'
                        ],
                        shouldNotGroupWhenFull: true
                    },
                    // Changing the language of the interface requires loading the language file using the <script> tag.
                    language: 'hr',
                    list: {
                        properties: {
                            styles: true,
                            startIndex: true,
                            reversed: true
                        }
                    },
                    heading: {
                        options: [
                            {model: 'paragraph', title: 'Paragraph', class: 'ck-heading_paragraph'},
                            {model: 'heading1', view: 'h1', title: 'Heading 1', class: 'ck-heading_heading1'},
                            {model: 'heading2', view: 'h2', title: 'Heading 2', class: 'ck-heading_heading2'},
                            {model: 'heading3', view: 'h3', title: 'Heading 3', class: 'ck-heading_heading3'},
                            {model: 'heading4', view: 'h4', title: 'Heading 4', class: 'ck-heading_heading4'},
                            {model: 'heading5', view: 'h5', title: 'Heading 5', class: 'ck-heading_heading5'},
                            {model: 'heading6', view: 'h6', title: 'Heading 6', class: 'ck-heading_heading6'}
                        ]
                    },
                    fontFamily: {
                        options: [
                            'default',
                            'Arial, Helvetica, sans-serif',
                            'Courier New, Courier, monospace',
                            'Georgia, serif',
                            'Lucida Sans Unicode, Lucida Grande, sans-serif',
                            'Tahoma, Geneva, sans-serif',
                            'Times New Roman, Times, serif',
                            'Trebuchet MS, Helvetica, sans-serif',
                            'Verdana, Geneva, sans-serif'
                        ],
                        supportAllValues: true
                    },
                    fontSize: {
                        options: [10, 12, 14, 'default', 18, 20, 22],
                        supportAllValues: true
                    },
                    htmlSupport: {
                        allow: [
                            {
                                name: /.*/,
                                attributes: true,
                                classes: true,
                                styles: true
                            }
                        ]
                    },
                    link: {
                        decorators: {
                            addTargetToExternalLinks: true,
                            defaultProtocol: 'https://',
                            toggleDownloadable: {
                                mode: 'manual',
                                label: 'Downloadable',
                                attributes: {
                                    download: 'file'
                                }
                            }
                        }
                    },
                    removePlugins: [
                        // These two are commercial, but you can try them out without registering to a trial.
                        // 'ExportPdf',
                        // 'ExportWord',
                        'AIAssistant',
                        'CKBox',
                        'CKFinder',
                        'EasyImage',
                        // This sample uses the Base64UploadAdapter to handle image uploads as it requires no configuration.
                        // https://ckeditor.com/docs/ckeditor5/latest/features/images/image-upload/base64-upload-adapter.html
                        // Storing images as Base64 is usually a very bad idea.
                        // Replace it on production website with other solutions:
                        // https://ckeditor.com/docs/ckeditor5/latest/features/images/image-upload/image-upload.html
                        // 'Base64UploadAdapter',
                        'RealTimeCollaborativeComments',
                        'RealTimeCollaborativeTrackChanges',
                        'RealTimeCollaborativeRevisionHistory',
                        'PresenceList',
                        'Comments',
                        'TrackChanges',
                        'TrackChangesData',
                        'RevisionHistory',
                        'Pagination',
                        'WProofreader',
                        // Careful, with the Mathtype plugin CKEditor will not load when loading this sample
                        // from a local file system (file://) - load this site via HTTP server if you enable MathType.
                        'MathType',
                        // The following features are part of the Productivity Pack and require additional license.
                        'SlashCommand',
                        'Template',
                        'DocumentOutline',
                        'FormatPainter',
                        'TableOfContents',
                        'PasteFromOfficeEnhanced',
                        'CaseChange'
                    ]
                    });
                })();
            </script>
            <style>
                /*noinspection CssUnusedSymbol*/
                .ck-content {
                    height: 30rem;
                }
            </style>

        @endsection
    @else
        @section('content')
            @include('layouts.neovlasteno')
        @endsection
    @endif
@endauth
@guest()
    @section('content')
        @include('layouts.neovlasteno')
    @endsection
@endguest
