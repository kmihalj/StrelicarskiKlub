{{-- Forma za uređivanje završnog opisa turnira i Facebook linka. --}}
<div class="row">
    <div class="col-lg-12 col-md-12 col-12 fw-bolder">
        <p>Unos podataka za turnir (ispod medija):</p>
    </div>
    <div class="col-12 mb-2">
        <form id="dodatniPodaci2" action="{{ route('admin.rezultati.dodatniPodaci2Rezultat') }}" method="POST">
            @csrf
            <input type="hidden" id="turnir_id" name="turnir_id" value={{$turnir->id}}>
            <label for="opis_turnira2">Opis turnira (tekst ispod galerije na prikazu rezultata):</label>
            <textarea class="form-control ck-content" name="opis_turnira2" id="opis_turnira2" aria-label="opis_turnira2">{{ old('opis_turnira2', $opis2Editor ?? $turnir->opis2) }}</textarea>

            <div class="mt-3">
                <label for="facebook_link_opis2">Facebook link (opcionalno):</label>
                <input type="text"
                       class="form-control"
                       form="dodatniPodaci2"
                       name="facebook_link_opis2"
                       id="facebook_link_opis2"
                       value="{{ old('facebook_link_opis2', $facebookLinkOpis2 ?? '') }}"
                       placeholder="https://www.facebook.com/...">
                <small class="text-muted">Ako je polje prazno, Facebook link se neće prikazivati.</small>
            </div>
        </form>
    </div>
    <div class="col-lg-12 col-md-12 col-12 mb-2 text-end">
        <button type="submit" form="dodatniPodaci2" class="btn btn-danger">Spremi</button>
    </div>
</div>

<script src="{{ asset('assets/ckeditor5/ckeditor5.js') }}"></script>
<script src="{{ asset('assets/ckeditor5/hr.js') }}"></script>
    <script>
    (() => {
        const editorNamespace = window['CKEDITOR'];
        const classicEditor = editorNamespace ? editorNamespace['ClassicEditor'] : null;
        const opisTurniraElement = document.getElementById('opis_turnira2');

        if (!classicEditor || !opisTurniraElement) {
            return;
        }

        classicEditor.create(opisTurniraElement, {
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
                { model: 'paragraph', title: 'Paragraph', class: 'ck-heading_paragraph' },
                { model: 'heading1', view: 'h1', title: 'Heading 1', class: 'ck-heading_heading1' },
                { model: 'heading2', view: 'h2', title: 'Heading 2', class: 'ck-heading_heading2' },
                { model: 'heading3', view: 'h3', title: 'Heading 3', class: 'ck-heading_heading3' },
                { model: 'heading4', view: 'h4', title: 'Heading 4', class: 'ck-heading_heading4' },
                { model: 'heading5', view: 'h5', title: 'Heading 5', class: 'ck-heading_heading5' },
                { model: 'heading6', view: 'h6', title: 'Heading 6', class: 'ck-heading_heading6' }
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
            options: [ 10, 12, 14, 'default', 18, 20, 22 ],
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
    .ck-content {
        height: 15rem;
    }
</style>
