@include('layouts.nav2CSS')
<nav class="navbar navbar-expand-lg navbar-dark" style="background-color: var(--theme-nav-solid-bg, #000);">
    <div class="container-fluid">
        <a class="navbar-brand" href="{{ url('/') }}">
            <img src="{{ $activeThemeLogoUrl ?? asset('storage/slike/logo.png') }}" height="50" alt="Streličarski klub Dubrava"/>
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#collapsibleNavbar" aria-controls="collapsibleNavbar" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="collapsibleNavbar">
            <ul class="navbar-nav ms-auto">
                @auth()
                    @php
                        $authUser = auth()->user();
                        $mozePregledClanova = $authUser->imaPravoAdminOrMember();
                        $mozePregledPolaznika = $authUser->imaPravoAdminMemberOrSchool();
                        $mozeSkolaEvidencija = (int)$authUser->rola === 1;
                    @endphp
                    @if(auth()->user()->rola <= 1)
                        <li class="nav-item dropdown px-4 py-2 text-center position-relative">
                            <span class="btn-group">
                                <a class="nav-link js-mobile-dropdown-toggle" href="#" role="button" aria-expanded="false">Admin</a>
                                <a class="nav-link dropdown-toggle js-mobile-dropdown-toggle" href="#" role="button" aria-expanded="false"></a>
                            </span>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="{{ route('admin.turniri.naslovna') }}">Podešenja</a></li>
                                <li><a class="dropdown-item" href="{{ route('admin.placanja.index') }}">Plaćanja</a></li>
                                <li><a class="dropdown-item" href="{{ route('admin.klub.naslovna') }}">Klub</a></li>
                                <li><a class="dropdown-item" href="{{ route('admin.clanci.popisClanaka') }}">Članci</a></li>
                                <li><a class="dropdown-item" href="{{ route('admin.korisnici.index') }}">Korisnici</a></li>
                                <li><a class="dropdown-item" href="{{ route('admin.teme.index') }}">Teme</a></li>
                            </ul>
                        </li>
                    @endif
                @endauth

                <li class="nav-item dropdown px-4 py-2 text-center position-relative">
                    <span class="btn-group">
                        <a class="nav-link js-mobile-dropdown-toggle" href="#" role="button" aria-expanded="false">Početna</a>
                        <a class="nav-link dropdown-toggle js-mobile-dropdown-toggle" href="#" role="button" aria-expanded="false"></a>
                    </span>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="{{ url('/') }}">Početna</a></li>
                        @guest
                            @if (Route::has('login'))
                                <li><a class="dropdown-item" href="{{ route('login') }}">{{ __('Prijava') }}</a></li>
                                <li><a class="dropdown-item" href="{{ route('register') }}">{{ __('Registriraj se') }}</a></li>
                            @endif
                        @else
                            <li>
                                <form action="{{ route('logout') }}" method="POST" class="m-0">
                                    @csrf
                                    <button type="submit" class="dropdown-item">{{ __('Logout') }}</button>
                                </form>
                            </li>
                        @endguest
                    </ul>
                </li>

                <li class="nav-item dropdown px-4 py-2 text-center position-relative">
                    <span class="btn-group">
                        <a class="nav-link js-mobile-dropdown-toggle" href="#" role="button" aria-expanded="false">Obavijesti</a>
                        <a class="nav-link dropdown-toggle js-mobile-dropdown-toggle" href="#" role="button" aria-expanded="false"></a>
                    </span>
                    <ul class="dropdown-menu">
                        @isset($menu['Obavijesti'])
                            @if($menu['Obavijesti']->count() != 0)
                                @foreach($menu['Obavijesti'] as $obavijest)
                                    <li><a class="dropdown-item" href="{{ route('javno.clanci.prikaz_clanka', $obavijest) }}">{{ $obavijest->menu_naslov }}</a></li>
                                @endforeach
                            @endif
                        @endisset
                        <li><a class="dropdown-item" href="{{ route('javno.clanci.popisClanaka', 'Obavijest') }}">Sve obavijesti</a></li>
                    </ul>
                </li>

                @guest
                    <li class="nav-item dropdown px-4 py-2 text-center position-relative">
                        <span class="btn-group">
                            <a class="nav-link js-mobile-dropdown-toggle" href="#" role="button" aria-expanded="false">Rezultati</a>
                            <a class="nav-link dropdown-toggle js-mobile-dropdown-toggle" href="#" role="button" aria-expanded="false"></a>
                        </span>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="{{ route('javno.rezultati') }}">Rezultati</a></li>
                            <li><a class="dropdown-item" href="{{ route('admin.rezultati.popisTurnira') }}">Turniri</a></li>
                        </ul>
                    </li>
                @else
                    <li class="nav-item dropdown px-4 py-2 text-center position-relative">
                        <span class="btn-group">
                            <a class="nav-link js-mobile-dropdown-toggle" href="#" role="button" aria-expanded="false">Rezultati</a>
                            <a class="nav-link dropdown-toggle js-mobile-dropdown-toggle" href="#" role="button" aria-expanded="false"></a>
                        </span>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="{{ route('javno.rezultati') }}">Rezultati</a></li>
                            <li><a class="dropdown-item" href="{{ route('admin.rezultati.popisTurnira') }}">Turniri</a></li>
                        </ul>
                    </li>
                @endguest

                <li class="nav-item dropdown px-4 py-2 text-center position-relative">
                    <span class="btn-group">
                        <a class="nav-link js-mobile-dropdown-toggle" href="#" role="button" aria-expanded="false">O&nbsp;nama</a>
                        <a class="nav-link dropdown-toggle js-mobile-dropdown-toggle" href="#" role="button" aria-expanded="false"></a>
                    </span>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="{{ route('javno.klub') }}">Podaci o klubu</a></li>
                        @auth()
                            @if($mozePregledClanova)
                                <li><a class="dropdown-item" href="{{ route('javno.clanovi') }}">Članovi</a></li>
                            @endif
                            @if($mozePregledPolaznika)
                                <li><a class="dropdown-item" href="{{ route('javno.skola.polaznici.index') }}">Polaznici škole</a></li>
                            @endif
                            @if($mozeSkolaEvidencija)
                                <li><a class="dropdown-item" href="{{ route('javno.skola.evidencija.index') }}">Evidencija dolazaka - škola</a></li>
                            @endif
                        @endauth
                        @isset($menu['O nama'])
                            @if($menu['O nama']->count() != 0)
                                @foreach($menu['O nama'] as $oNama)
                                    <li><a class="dropdown-item" href="{{ route('javno.clanci.prikaz_clanka', $oNama) }}">{{ $oNama->menu_naslov }}</a></li>
                                @endforeach
                            @endif
                        @endisset
                        <li><a class="dropdown-item" href="{{ route('javno.clanci.popisClanaka', 'O nama') }}">Svi članci</a></li>
                    </ul>
                </li>

                <li class="nav-item dropdown px-4 py-2 text-center position-relative">
                    <span class="btn-group">
                        <a class="nav-link js-mobile-dropdown-toggle" href="#" role="button" aria-expanded="false">Streličarstvo</a>
                        <a class="nav-link dropdown-toggle js-mobile-dropdown-toggle" href="#" role="button" aria-expanded="false"></a>
                    </span>
                    <ul class="dropdown-menu">
                        @isset($menu['Strelicarstvo'])
                            @if($menu['Strelicarstvo']->count() != 0)
                                @foreach($menu['Strelicarstvo'] as $strelicarstvo)
                                    <li><a class="dropdown-item" href="{{ route('javno.clanci.prikaz_clanka', $strelicarstvo) }}">{{ $strelicarstvo->menu_naslov }}</a></li>
                                @endforeach
                            @endif
                        @endisset
                        <li><a class="dropdown-item" href="{{ route('javno.clanci.popisClanaka', 'Streličarstvo') }}">Svi članci</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

<script>
    (function () {
        const collapseEl = document.getElementById('collapsibleNavbar');
        if (!collapseEl) {
            return;
        }

        const mobileQuery = window.matchMedia('(max-width: 991.98px)');
        const dropdownItems = Array.from(collapseEl.querySelectorAll('.nav-item.dropdown'));

        function setExpanded(dropdownItem, isExpanded) {
            dropdownItem.querySelectorAll('.js-mobile-dropdown-toggle').forEach(function (toggle) {
                toggle.setAttribute('aria-expanded', isExpanded ? 'true' : 'false');
            });
        }

        function closeAllDropdowns() {
            dropdownItems.forEach(function (dropdownItem) {
                const menu = dropdownItem.querySelector('.dropdown-menu');
                dropdownItem.classList.remove('show');
                if (menu) {
                    menu.classList.remove('show');
                }
                setExpanded(dropdownItem, false);
            });
        }

        function toggleDropdown(dropdownItem) {
            const menu = dropdownItem.querySelector('.dropdown-menu');
            if (!menu) {
                return;
            }

            const isOpen = dropdownItem.classList.contains('show') || menu.classList.contains('show');
            closeAllDropdowns();

            if (!isOpen) {
                dropdownItem.classList.add('show');
                menu.classList.add('show');
                setExpanded(dropdownItem, true);
            }
        }

        dropdownItems.forEach(function (dropdownItem) {
            dropdownItem.addEventListener('click', function (event) {
                if (!mobileQuery.matches) {
                    return;
                }

                const menuLink = event.target.closest('.dropdown-menu .dropdown-item');
                if (menuLink) {
                    return;
                }

                const toggleHit = event.target.closest('.js-mobile-dropdown-toggle');
                const insideHeader = event.target.closest('span.btn-group');
                if (!toggleHit && !insideHeader) {
                    return;
                }

                event.preventDefault();
                event.stopPropagation();
                toggleDropdown(dropdownItem);
            });
        });

        document.addEventListener('click', function (event) {
            if (!mobileQuery.matches || collapseEl.contains(event.target)) {
                return;
            }
            closeAllDropdowns();
        });

        collapseEl.querySelectorAll('.dropdown-item').forEach(function (link) {
            link.addEventListener('click', function () {
                if (!mobileQuery.matches) {
                    return;
                }
                closeAllDropdowns();
            });
        });

        mobileQuery.addEventListener('change', function (event) {
            if (!event.matches) {
                closeAllDropdowns();
            }
        });
    })();
</script>
