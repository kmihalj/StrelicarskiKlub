@once
    <style>
        .oglasnik-card .card-title {
            line-height: 1.3;
            font-size: 1.05rem;
        }

        .oglasnik-card {
            border: 1px solid rgba(0, 0, 0, .12);
            background: #fff;
        }

        .oglasnik-media {
            position: relative;
            width: 100%;
            height: clamp(210px, 28vw, 260px);
            border: 1px solid rgba(0, 0, 0, .14);
            border-radius: .375rem;
            background: #f8f9fa;
            overflow: hidden;
        }

        .oglasnik-media .carousel-inner,
        .oglasnik-media .carousel-item {
            height: 100%;
        }

        .oglasnik-image {
            width: 100%;
            height: 100%;
            object-fit: contain;
            object-position: center;
            background: #f8f9fa;
        }

        .oglasnik-media .carousel-control-prev,
        .oglasnik-media .carousel-control-next {
            width: 2.2rem;
            height: 2.2rem;
            top: calc(50% - 1.1rem);
            bottom: auto;
            opacity: .92;
            border-radius: 999px;
            background: rgba(0, 0, 0, .58);
        }

        .oglasnik-media .carousel-control-prev {
            left: .45rem;
        }

        .oglasnik-media .carousel-control-next {
            right: .45rem;
        }

        .oglasnik-media .carousel-control-prev-icon,
        .oglasnik-media .carousel-control-next-icon {
            width: 1.05rem;
            height: 1.05rem;
        }

        .oglasnik-media .carousel-control-prev:hover,
        .oglasnik-media .carousel-control-next:hover {
            background: rgba(0, 0, 0, .75);
        }

        .oglasnik-image-placeholder {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6c757d;
            font-size: .9rem;
        }

        .oglasnik-opis {
            line-height: 1.45;
            word-break: break-word;
            overflow-wrap: anywhere;
        }

        .oglasnik-card-actions {
            flex-shrink: 0;
        }

        .oglasnik-icon-btn {
            width: 1.8rem;
            height: 1.8rem;
            border-radius: 999px;
            border: 1px solid rgba(0, 0, 0, .25);
            background: transparent;
            color: #495057;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0;
            line-height: 1;
        }

        .oglasnik-icon-btn svg {
            width: 13px;
            height: 13px;
        }

        .oglasnik-icon-btn:hover {
            background: rgba(0, 0, 0, .06);
        }

        .oglasnik-icon-deactivate:hover {
            color: #fd7e14;
            border-color: rgba(253, 126, 20, .6);
            background: rgba(253, 126, 20, .1);
        }

        .oglasnik-icon-reactivate:hover {
            color: #198754;
            border-color: rgba(25, 135, 84, .55);
            background: rgba(25, 135, 84, .12);
        }

        .oglasnik-icon-delete:hover {
            color: #dc3545;
            border-color: rgba(220, 53, 69, .55);
            background: rgba(220, 53, 69, .1);
        }

        .theme-dark .oglasnik-image-placeholder {
            color: rgba(255, 255, 255, .72);
            background: rgba(255, 255, 255, .04);
        }

        .theme-dark .oglasnik-card {
            background: linear-gradient(180deg, rgba(255, 255, 255, .06) 0%, rgba(255, 255, 255, .03) 100%) !important;
            border-color: rgba(255, 255, 255, .24);
            box-shadow: 0 8px 24px rgba(0, 0, 0, .28) !important;
        }

        .theme-dark .oglasnik-card .card-title {
            color: rgba(255, 255, 255, .96);
        }

        .theme-dark .oglasnik-media {
            border-color: rgba(255, 255, 255, .28);
            background: rgba(255, 255, 255, .06);
        }

        .theme-dark .oglasnik-image {
            background: transparent;
        }

        .theme-dark .oglasnik-media .carousel-control-prev,
        .theme-dark .oglasnik-media .carousel-control-next {
            border: 1px solid rgba(255, 255, 255, .25);
            background: rgba(0, 0, 0, .62);
        }

        .theme-dark .oglasnik-media .carousel-control-prev:hover,
        .theme-dark .oglasnik-media .carousel-control-next:hover {
            background: rgba(0, 0, 0, .8);
        }

        .theme-dark .oglasnik-icon-btn {
            color: rgba(255, 255, 255, .78);
            border-color: rgba(255, 255, 255, .35);
        }

        .theme-dark .oglasnik-icon-btn:hover {
            background: rgba(255, 255, 255, .12);
        }

        .theme-dark .oglasnik-icon-deactivate:hover {
            color: #fdba74;
            border-color: rgba(253, 186, 116, .55);
            background: rgba(253, 186, 116, .2);
        }

        .theme-dark .oglasnik-icon-reactivate:hover {
            color: #4ade80;
            border-color: rgba(74, 222, 128, .55);
            background: rgba(74, 222, 128, .2);
        }

        .theme-dark .oglasnik-icon-delete:hover {
            color: #f87171;
            border-color: rgba(248, 113, 113, .55);
            background: rgba(248, 113, 113, .2);
        }
    </style>
@endonce
