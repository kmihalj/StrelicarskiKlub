<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Cinzel:wght@600&display=swap" rel="stylesheet">
<style>
    html, body, #app {
        margin: 0;
        padding: 0;
    }

    .nav-item {
        font-family: 'Bebas Neue', cursive;
        font-size: 1.8em;
        line-height: 1.2em;
        border-left: 1px solid var(--theme-nav-item-border, #eaeaea);
        border-right: 1px solid var(--theme-nav-item-border, #eaeaea);
        padding-right: 2rem;
        padding-left: 2rem;
        width: auto;
        box-shadow: inset 8px 2px 16px rgba(0, 0, 0, 0.2);
    }

    .nav-item > a,
    .nav-item > span > a {
        color: var(--theme-nav-item-text, #fff) !important;
    }

    .nav-item:hover > span > a,
    .nav-item:hover > a {
        color: var(--theme-nav-item-hover-text, #000) !important;
    }

    .nav-item > span > .js-mobile-dropdown-toggle:first-child {
        flex: 1 1 auto;
        text-align: center;
    }

    .dropdown-item {
        font-family: 'Bebas Neue', cursive;
        font-size: 1.4em;
        line-height: 1em;
        padding: 10px;
        color: var(--theme-nav-dropdown-text, #000) !important;
        background: var(--theme-nav-dropdown-bg, rgba(224, 241, 68, 0.9));
    }

    .dropdown-menu .dropdown-item,
    .dropdown-menu form .dropdown-item {
        color: var(--theme-nav-dropdown-text, #000) !important;
    }

    .dropdown-menu {
        background: var(--theme-nav-dropdown-bg, rgba(224, 241, 68, 0.9));
    }

    .dropdown-item:hover {
        color: var(--theme-nav-dropdown-hover-text, #fff) !important;
        background: var(--theme-nav-dropdown-hover-bg, rgba(98, 204, 70, 1));
    }

    .dropdown-menu .dropdown-item:hover,
    .dropdown-menu form .dropdown-item:hover {
        color: var(--theme-nav-dropdown-hover-text, #fff) !important;
    }

    .navbar {
        margin: 0 !important;
        padding: 0;
        background: var(--theme-nav-solid-bg, #000);
        background: -moz-linear-gradient(-45deg, var(--theme-nav-gradient-start, #ff0000) 0%, var(--theme-nav-gradient-start, #ff0000) 13%, var(--theme-nav-gradient-start, #ff0000) 13%, var(--theme-nav-gradient-mid, #ffd700) 61%, var(--theme-nav-gradient-end, #07c818) 100%);
        background: -webkit-linear-gradient(-45deg, var(--theme-nav-gradient-start, #ff0000) 0%, var(--theme-nav-gradient-start, #ff0000) 13%, var(--theme-nav-gradient-start, #ff0000) 13%, var(--theme-nav-gradient-mid, #ffd700) 61%, var(--theme-nav-gradient-end, #07c818) 100%);
        background: linear-gradient(135deg, var(--theme-nav-gradient-start, #ff0000) 0%, var(--theme-nav-gradient-start, #ff0000) 13%, var(--theme-nav-gradient-start, #ff0000) 13%, var(--theme-nav-gradient-mid, #ffd700) 61%, var(--theme-nav-gradient-end, #07c818) 100%);
        filter: progid:DXImageTransform.Microsoft.gradient(startColorstr='#040c56', endColorstr='#0e72f4', GradientType=1);
    }

    .navbar .navbar-toggler {
        border-color: rgba(255, 255, 255, 0.85);
        background-color: rgba(0, 0, 0, 0.18);
    }

    .navbar .navbar-toggler:focus {
        box-shadow: 0 0 0 .2rem rgba(255, 255, 255, 0.35);
    }

    .navbar .navbar-toggler-icon {
        filter: drop-shadow(0 0 1px rgba(0, 0, 0, 0.65));
    }

    @media (min-width: 992px) {
        .dropdown:hover > .dropdown-menu {
            display: block;
        }
    }

    @media (max-width: 991.98px) {
        .nav-item:hover > span > a,
        .nav-item:hover > a {
            color: var(--theme-nav-item-text, #fff) !important;
        }

        .nav-item {
            width: 100%;
            border-left: 0;
            border-right: 0;
            box-shadow: none;
            padding: 0 !important;
        }

        .nav-item > span {
            width: 100%;
            justify-content: space-between;
            display: flex;
            align-items: center;
        }

        .nav-item > span > a {
            padding: .55rem 0;
            -webkit-tap-highlight-color: transparent;
            touch-action: manipulation;
        }

        .navbar .dropdown-menu {
            position: static;
            float: none;
            width: 100%;
            margin-top: .4rem;
            transform: none !important;
            border: 0;
            box-shadow: none;
        }

        .dropdown.show > .dropdown-menu {
            display: block;
        }
    }
</style>
