{{-- Ubrizgavanje CSS varijabli aktivne teme (boje, linkovi, kontrasti). --}}
<style>
    :root {
        @foreach(($activeThemeCssVars ?? []) as $cssVar => $cssValue)
            {{ $cssVar }}: {{ $cssValue }};
        @endforeach
    }

    body {
        background-color: var(--theme-body-bg, #e2e3e5) !important;
        color: var(--theme-body-color, #212529);
    }

    .theme-dark {
        color-scheme: dark;
    }

    .theme-dark .bg-white,
    .theme-dark .container-xxl.bg-white,
    .theme-dark .container.bg-white,
    .theme-dark .card,
    .theme-dark .modal-content,
    .theme-dark .list-group-item {
        background-color: var(--bs-dark-bg-subtle) !important;
        color: var(--bs-body-color) !important;
    }

    .theme-dark .bg-secondary-subtle {
        background-color: var(--bs-secondary-bg-subtle) !important;
        color: var(--bs-body-color) !important;
    }

    .theme-dark .bg-light,
    .theme-dark .bg-light-subtle,
    .theme-dark .bg-body-tertiary {
        background-color: var(--bs-secondary-bg-subtle) !important;
        color: var(--bs-body-color) !important;
    }

    .theme-dark .bg-dark-subtle {
        background-color: var(--bs-dark-bg-subtle) !important;
        color: var(--bs-body-color) !important;
    }

    .theme-dark .card-header,
    .theme-dark .card-body,
    .theme-dark .card-footer,
    .theme-dark .modal-header,
    .theme-dark .modal-body,
    .theme-dark .modal-footer {
        color: var(--bs-body-color);
        border-color: rgba(255, 255, 255, 0.15) !important;
    }

    .theme-dark .table {
        --bs-table-bg: var(--bs-dark-bg-subtle);
        --bs-table-color: var(--bs-body-color);
        --bs-table-border-color: rgba(255, 255, 255, 0.16);
        --bs-table-striped-bg: rgba(255, 255, 255, 0.04);
        --bs-table-striped-color: var(--bs-body-color);
        --bs-table-hover-bg: rgba(255, 255, 255, 0.08);
        --bs-table-hover-color: var(--bs-body-color);
        color: var(--bs-body-color);
    }

    .theme-dark .table tbody > tr > *,
    .theme-dark .table tfoot > tr > * {
        color: var(--bs-body-color) !important;
        border-color: rgba(255, 255, 255, 0.18) !important;
    }

    .theme-dark .table thead > tr > * {
        border-color: rgba(255, 255, 255, 0.18) !important;
    }

    .theme-dark .table thead > tr > *.border-danger,
    .theme-dark .table thead > tr > *.border.border-danger {
        border-color: rgba(var(--bs-primary-rgb), 0.65) !important;
    }

    .theme-dark .table-light,
    .theme-dark .table > :not(caption) > .table-light > * {
        --bs-table-bg: var(--bs-secondary-bg-subtle);
        --bs-table-color: var(--bs-body-color);
        --bs-table-border-color: rgba(255, 255, 255, 0.18);
        --bs-table-striped-bg: rgba(255, 255, 255, 0.04);
        --bs-table-striped-color: var(--bs-body-color);
        --bs-table-hover-bg: rgba(255, 255, 255, 0.08);
        --bs-table-hover-color: var(--bs-body-color);
        background-color: var(--bs-secondary-bg-subtle) !important;
        color: var(--bs-body-color) !important;
        border-color: rgba(255, 255, 255, 0.18) !important;
    }

    .theme-dark .table thead.table-warning > tr > * {
        color: var(--bs-body-color) !important;
    }

    .theme-dark .table thead > tr > *.text-white {
        color: #ffffff !important;
    }

    a:not(.nav-link):not(.dropdown-item):not(.btn):not(.page-link):not(.navbar-brand) {
        color: var(--theme-link-color, var(--bs-primary));
    }

    a:not(.nav-link):not(.dropdown-item):not(.btn):not(.page-link):not(.navbar-brand):hover {
        color: var(--theme-link-hover-color, var(--bs-primary));
    }

    .link-primary {
        color: var(--theme-link-color, var(--bs-primary)) !important;
    }

    .link-primary:hover {
        color: var(--theme-link-hover-color, var(--bs-primary)) !important;
    }

    .theme-dark .table a {
        color: var(--theme-link-color, var(--bs-primary));
    }

    .theme-dark .table a.link-dark,
    .theme-dark a.link-dark {
        color: var(--bs-body-color) !important;
    }

    .theme-dark .table a.link-dark:hover,
    .theme-dark a.link-dark:hover {
        color: #ffffff !important;
    }

    .theme-dark .link-danger {
        color: var(--bs-danger) !important;
    }

    .theme-dark .link-primary {
        color: var(--theme-link-color, var(--bs-primary)) !important;
    }

    .theme-dark .link-primary:hover {
        color: var(--theme-link-hover-color, var(--bs-primary)) !important;
    }

    .theme-dark .link-underline-light {
        text-decoration-color: rgba(233, 236, 239, 0.6) !important;
    }

    .theme-dark .text-muted {
        color: #adb5bd !important;
    }

    .theme-dark .text-dark,
    .theme-dark .text-black {
        color: var(--bs-body-color) !important;
    }

    .theme-dark .btn-danger {
        --bs-btn-color: #ffffff;
        --bs-btn-hover-color: #ffffff;
        --bs-btn-active-color: #ffffff;
        --bs-btn-disabled-color: #ffffff;
    }

    .theme-dark .btn-outline-dark {
        --bs-btn-color: #e9ecef;
        --bs-btn-border-color: #adb5bd;
        --bs-btn-hover-color: #111111;
        --bs-btn-hover-bg: #ced4da;
        --bs-btn-hover-border-color: #ced4da;
        --bs-btn-focus-shadow-rgb: 206, 212, 218;
        --bs-btn-active-color: #111111;
        --bs-btn-active-bg: #dee2e6;
        --bs-btn-active-border-color: #dee2e6;
        --bs-btn-disabled-color: #adb5bd;
        --bs-btn-disabled-border-color: #6c757d;
    }

    .theme-dark .badge.text-bg-secondary {
        background-color: #6c757d !important;
        color: #ffffff !important;
    }

    .theme-dark .alert-secondary {
        color: #dee2e6 !important;
    }

    .theme-dark .alert-secondary .fw-bold,
    .theme-dark .alert-secondary .small,
    .theme-dark .alert-secondary div,
    .theme-dark .alert-secondary span,
    .theme-dark .alert-secondary p {
        color: #dee2e6 !important;
    }

    .theme-dark .ck-content [style*="background-color:rgb(255, 255, 255)"],
    .theme-dark .ck-content [style*="background-color: rgb(255, 255, 255)"],
    .theme-dark .ck-content [style*="background-color:#fff"],
    .theme-dark .ck-content [style*="background-color: #fff"],
    .theme-dark .ck-content [style*="background-color:white"],
    .theme-dark .ck-content [style*="background-color: white"] {
        background-color: transparent !important;
    }

    .theme-dark .ck-content [style*="color:rgb(51, 51, 51)"],
    .theme-dark .ck-content [style*="color: rgb(51, 51, 51)"],
    .theme-dark .ck-content [style*="color:#333"],
    .theme-dark .ck-content [style*="color: #333"],
    .theme-dark .ck-content [style*="color:rgb(0, 0, 0)"],
    .theme-dark .ck-content [style*="color: rgb(0, 0, 0)"],
    .theme-dark .ck-content [style*="color:#000"],
    .theme-dark .ck-content [style*="color: #000"],
    .theme-dark .ck-content [style*="color:black"],
    .theme-dark .ck-content [style*="color: black"] {
        color: var(--bs-body-color) !important;
    }

    /* CKEditor (editing UI) dark mode adjustments */
    .theme-dark .ck.ck-editor {
        --ck-color-base-background: #1a1f26;
        --ck-color-base-foreground: #2b3035;
        --ck-color-base-border: #495057;
        --ck-color-toolbar-background: #2b3035;
        --ck-color-toolbar-border: #495057;
        --ck-color-panel-background: #2b3035;
        --ck-color-panel-border: #495057;
        --ck-color-input-background: #1a1f26;
        --ck-color-input-border: #495057;
        --ck-color-input-text: #e9ecef;
        --ck-color-dropdown-panel-background: #2b3035;
        --ck-color-dropdown-panel-border: #495057;
        --ck-color-list-background: #2b3035;
        --ck-color-list-button-on-background: rgba(255, 255, 255, 0.16);
        --ck-color-list-button-hover-background: rgba(255, 255, 255, 0.08);
        --ck-color-text: #e9ecef;
        --ck-color-focus-border: var(--bs-primary);
    }

    .theme-dark .ck.ck-toolbar,
    .theme-dark .ck.ck-toolbar .ck-toolbar__separator,
    .theme-dark .ck.ck-panel,
    .theme-dark .ck.ck-dropdown__panel,
    .theme-dark .ck.ck-list,
    .theme-dark .ck.ck-input,
    .theme-dark .ck.ck-labeled-field-view__input-wrapper .ck.ck-input,
    .theme-dark .ck.ck-button {
        border-color: #495057 !important;
    }

    .theme-dark .ck.ck-toolbar,
    .theme-dark .ck.ck-panel,
    .theme-dark .ck.ck-dropdown__panel,
    .theme-dark .ck.ck-list {
        background: #2b3035 !important;
        color: #e9ecef !important;
    }

    .theme-dark .ck.ck-editor__main > .ck-editor__editable,
    .theme-dark .ck.ck-editor__main > .ck-editor__editable:not(.ck-focused) {
        background: #1a1f26 !important;
        color: #e9ecef !important;
        border-color: #495057 !important;
    }

    .theme-dark .ck.ck-editor__main > .ck-editor__editable.ck-focused {
        border-color: var(--bs-primary) !important;
        box-shadow: 0 0 0 0.2rem rgba(var(--bs-primary-rgb), 0.25) !important;
    }

    .theme-dark .ck.ck-editor__main > .ck-editor__editable.ck-placeholder::before {
        color: #9aa4ad !important;
    }

    .theme-dark .ck.ck-input,
    .theme-dark .ck.ck-labeled-field-view__input-wrapper .ck.ck-input,
    .theme-dark .ck.ck-input-text {
        background: #1a1f26 !important;
        color: #e9ecef !important;
    }

    .theme-dark .ck.ck-button:not(.ck-disabled):hover,
    .theme-dark .ck.ck-button.ck-on {
        background: rgba(255, 255, 255, 0.12) !important;
        color: #ffffff !important;
    }

    .theme-dark .ck.ck-button .ck-button__label,
    .theme-dark .ck.ck-icon,
    .theme-dark .ck.ck-label {
        color: #e9ecef !important;
    }

    .theme-dark .ck.ck-source-editing-area textarea {
        background: #1a1f26 !important;
        color: #e9ecef !important;
        border-color: #495057 !important;
    }

    .theme-dark .form-control,
    .theme-dark .form-select,
    .theme-dark .form-control-color {
        background-color: #1a1f26 !important;
        border-color: #495057 !important;
        color: var(--bs-body-color) !important;
    }

    .theme-dark .form-control[type="file"] {
        background-color: #1a1f26 !important;
        border-color: #495057 !important;
        color: var(--bs-body-color) !important;
    }

    .theme-dark .form-control[type="file"]::file-selector-button {
        color: #f8f9fa;
        background-color: #3a424c;
        border-color: #3a424c;
        margin: -.375rem .75rem -.375rem -.75rem;
        padding: .375rem .75rem;
    }

    .theme-dark .form-control[type="file"]::file-selector-button:hover {
        background-color: #4a5562;
        border-color: #4a5562;
        color: #ffffff;
    }

    .theme-dark .form-control[type="file"]::-webkit-file-upload-button {
        color: #f8f9fa;
        background-color: #3a424c;
        border-color: #3a424c;
        margin: -.375rem .75rem -.375rem -.75rem;
        padding: .375rem .75rem;
    }

    .theme-dark .form-control[type="file"]::-webkit-file-upload-button:hover {
        background-color: #4a5562;
        border-color: #4a5562;
        color: #ffffff;
    }

    .theme-dark .form-control::placeholder {
        color: #99a3ad !important;
    }

    .theme-dark .form-control:focus,
    .theme-dark .form-select:focus {
        border-color: var(--bs-primary) !important;
        box-shadow: 0 0 0 .2rem rgba(var(--bs-primary-rgb), 0.25) !important;
    }

    .theme-dark hr {
        border-color: rgba(255, 255, 255, 0.18);
    }

    .btn-primary {
        --bs-btn-color: var(--theme-on-primary, #fff);
        --bs-btn-hover-color: var(--theme-on-primary, #fff);
        --bs-btn-active-color: var(--theme-on-primary, #fff);
    }

    .btn-secondary {
        --bs-btn-color: var(--theme-on-secondary, #fff);
        --bs-btn-hover-color: var(--theme-on-secondary, #fff);
        --bs-btn-active-color: var(--theme-on-secondary, #fff);
    }

    .btn-success {
        --bs-btn-color: var(--theme-on-success, #fff);
        --bs-btn-hover-color: var(--theme-on-success, #fff);
        --bs-btn-active-color: var(--theme-on-success, #fff);
    }

    .btn-danger {
        --bs-btn-color: var(--theme-on-danger, #fff);
        --bs-btn-hover-color: var(--theme-on-danger, #fff);
        --bs-btn-active-color: var(--theme-on-danger, #fff);
    }

    .btn-warning {
        --bs-btn-color: var(--theme-on-warning, #111);
        --bs-btn-hover-color: var(--theme-on-warning, #111);
        --bs-btn-active-color: var(--theme-on-warning, #111);
    }

    .btn-info {
        --bs-btn-color: var(--theme-on-info, #111);
        --bs-btn-hover-color: var(--theme-on-info, #111);
        --bs-btn-active-color: var(--theme-on-info, #111);
    }

    /* Theme-adaptive section headers, card headers and table headers */
    .row.bg-danger.fw-bolder,
    .card-header.bg-danger,
    .modal-header.bg-danger {
        background-color: var(--bs-primary) !important;
        color: var(--theme-on-primary, #ffffff) !important;
        border-color: rgba(var(--bs-primary-rgb), 0.65) !important;
    }

    .row.bg-danger.fw-bolder .text-white,
    .card-header.bg-danger .text-white,
    .modal-header.bg-danger .text-white {
        color: inherit !important;
    }

    .table thead.theme-thead-accent > tr > *,
    .table > :not(caption) > thead.theme-thead-accent > tr > * {
        background-color: var(--bs-primary) !important;
        color: var(--theme-on-primary, #ffffff) !important;
        border-color: rgba(var(--bs-primary-rgb), 0.65) !important;
    }

    .table thead.theme-thead-accent > tr > *.text-white,
    .table > :not(caption) > thead.theme-thead-accent > tr > *.text-white {
        color: var(--theme-on-primary, #ffffff) !important;
    }

    .table thead.table-warning > tr > *,
    .table > :not(caption) > thead.table-warning > tr > * {
        --bs-table-bg: var(--bs-secondary-bg-subtle);
        --bs-table-color: var(--bs-body-color);
        --bs-table-border-color: rgba(var(--bs-secondary-rgb), 0.45);
        background-color: var(--bs-secondary-bg-subtle) !important;
        color: var(--bs-body-color) !important;
        border-color: rgba(var(--bs-secondary-rgb), 0.45) !important;
    }

    .table thead > tr > *.border-danger,
    .table thead > tr > *.border.border-danger {
        border-color: rgba(var(--bs-primary-rgb), 0.65) !important;
    }
</style>
