<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Cinzel:wght@600&display=swap" rel="stylesheet">
<!--suppress CssUnusedSymbol -->
<style>

    .style1 {
        font-family: 'Bebas Neue', cursive;
    }

    a > .style1 {
        font-size: 1.8em;
        line-height: 1.2em;
    }

    .navbar {
        padding: 0;
        background: var(--theme-nav-solid-bg, #000);
        background: -moz-linear-gradient(-45deg, var(--theme-nav-gradient-start, #ff0000) 0%, var(--theme-nav-gradient-start, #ff0000) 13%, var(--theme-nav-gradient-start, #ff0000) 13%, var(--theme-nav-gradient-mid, #ffd700) 61%, var(--theme-nav-gradient-end, #07c818) 100%);
        /* FF3.6-15 */
        background: -webkit-linear-gradient(-45deg, var(--theme-nav-gradient-start, #ff0000) 0%, var(--theme-nav-gradient-start, #ff0000) 13%, var(--theme-nav-gradient-start, #ff0000) 13%, var(--theme-nav-gradient-mid, #ffd700) 61%, var(--theme-nav-gradient-end, #07c818) 100%);
        /* Chrome10-25,Safari5.1-6 */
        background: linear-gradient(135deg, var(--theme-nav-gradient-start, #ff0000) 0%, var(--theme-nav-gradient-start, #ff0000) 13%, var(--theme-nav-gradient-start, #ff0000) 13%, var(--theme-nav-gradient-mid, #ffd700) 61%, var(--theme-nav-gradient-end, #07c818) 100%);
        /* W3C, IE10+, FF16+, Chrome26+, Opera12+, Safari7+ */
        filter: progid:DXImageTransform.Microsoft.gradient(startColorstr='#040c56', endColorstr='#0e72f4', GradientType=1);
        /* IE6-9 fallback on horizontal gradient */
    }

    .nav-item > a {
        color: var(--theme-nav-item-text, #fff) !important
    }

    @media screen and (min-width: 720px) {
        .nav-item {
            transform: skewX(-20deg);
            transition: all 200ms linear;
            box-shadow: inset 8px 2px 16px rgba(0, 0, 0, 0.2)
        }

        .nav-item a {
            transition: all 200ms linear;
            transform: skewX(20deg)
        }

        .nav-item:nth-child(2n+1) {
            border-left: 1px solid var(--theme-nav-item-border, #eaeaea);
            border-right: 1px solid var(--theme-nav-item-border, #eaeaea);
        }

        .nav-item:nth-last-child(1) {
            border-left: 1px solid var(--theme-nav-item-border, #eaeaea);
            border-right: none !important;
        }

        .customSubMenu {
            display: none;
            margin-left: -4px;
            position: absolute;
            width: 100%;
            z-index: 123;
        }

        .hasSubMenu:hover > .customSubMenu {
            display: block !important;
        }

        .subLink {
            transform: skewX(40deg);
            padding: 12px;
            background: var(--theme-nav-dropdown-bg, rgba(224, 241, 68, 0.9));
            margin-top: 8px;
            width: 100%;
        }

        .subLink a {
            display: inline-block;
            transform: skewX(-25deg);
            color: var(--theme-nav-dropdown-text, #000);
            text-decoration: none;
        }
    }

    @media screen and (max-width: 719px) {
        .nav-item a {
            background: rgba(0, 0, 0, 0.0);
            color: var(--theme-nav-item-text, #fff)
        }
    }

    .nav-item.active,
    .nav-item:hover {
        background: var(--theme-nav-item-hover-bg, rgba(98, 204, 70, 0.5));
    }

    .nav-item.active > a,
    .nav-item:hover > a {
        color: var(--theme-nav-item-hover-text, #000) !important
    }

</style>
