{{-- Pregled markdown korisničkih uputa iz direktorija docs/. --}}
@extends('layouts.app')

@section('content')
    <style>
        .upute-markdown img {
            max-width: 100%;
            height: auto;
        }

        .upute-markdown table {
            width: 100%;
        }

        .upute-sidebar .list-group-item {
            border-color: rgba(var(--bs-primary-rgb), 0.25);
            background-color: var(--bs-secondary-bg-subtle);
            color: var(--bs-body-color) !important;
            transition: background-color 0.15s ease-in-out, color 0.15s ease-in-out, border-color 0.15s ease-in-out;
        }

        .upute-sidebar .list-group-item:not(.active):hover,
        .upute-sidebar .list-group-item:not(.active):focus {
            background-color: rgba(var(--bs-primary-rgb), 0.08);
            color: var(--bs-body-color) !important;
        }

        .upute-sidebar .list-group-item.active {
            background-color: var(--bs-primary);
            border-color: var(--bs-primary);
            color: var(--theme-on-primary, #ffffff) !important;
        }

        .theme-dark .upute-sidebar .list-group-item {
            background-color: var(--bs-dark-bg-subtle);
            border-color: rgba(255, 255, 255, 0.14);
            color: var(--bs-body-color) !important;
        }

        .theme-dark .upute-sidebar .list-group-item:not(.active):hover,
        .theme-dark .upute-sidebar .list-group-item:not(.active):focus {
            background-color: rgba(var(--bs-primary-rgb), 0.18);
            color: var(--bs-body-color) !important;
        }

        .upute-scroll-top-btn {
            position: fixed;
            right: 1rem;
            bottom: 1rem;
            z-index: 1030;
            opacity: 0;
            visibility: hidden;
            transform: translateY(8px);
            transition: opacity 0.2s ease, visibility 0.2s ease, transform 0.2s ease;
        }

        .upute-scroll-top-btn.is-visible {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }
    </style>
    <div class="container-xxl bg-white shadow mb-3">
        <div class="row justify-content-center p-2 shadow bg-danger fw-bolder">
            <div class="col-lg-12 text-white">
                Upute
            </div>
        </div>
        <div class="row p-3 g-3">
            <div class="col-lg-4 col-xl-3 upute-sidebar">
                <div class="list-group">
                    @foreach($uputeDokumenti as $dokument)
                        <a href="{{ route('javno.upute', ['dok' => $dokument['putanja']]) }}"
                           class="list-group-item list-group-item-action @if($uputeAktivniDokument === $dokument['putanja']) active @endif">
                            {{ $dokument['naslov'] }}
                        </a>
                    @endforeach
                </div>
            </div>
            <div class="col-lg-8 col-xl-9">
                <div class="border rounded p-3 bg-body-tertiary upute-markdown">
                    <h2 class="h4 mb-3">{{ $uputeNaslov }}</h2>
                    {!! $uputeSadrzajHtml !!}
                </div>
            </div>
        </div>
    </div>

    <button type="button"
            class="btn btn-danger btn-sm upute-scroll-top-btn"
            id="uputeScrollTopBtn"
            aria-label="Povratak na vrh">
        ↑
    </button>

    <script>
        (function () {
            const button = document.getElementById('uputeScrollTopBtn');
            if (!(button instanceof HTMLButtonElement)) {
                return;
            }

            const toggleVisibility = function () {
                if (window.scrollY > 350) {
                    button.classList.add('is-visible');
                } else {
                    button.classList.remove('is-visible');
                }
            };

            window.addEventListener('scroll', toggleVisibility, { passive: true });
            toggleVisibility();

            button.addEventListener('click', function () {
                window.scrollTo({ top: 0, behavior: 'smooth' });
            });
        })();
    </script>
@endsection
