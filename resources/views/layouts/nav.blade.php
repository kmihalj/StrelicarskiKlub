@include('layouts.navCSS')
<nav class="navbar navbar-expand-lg navbar-dark" style="background-color: var(--theme-nav-solid-bg, #000); z-index: 9999;">
    <div class="container-fluid">
        <a class="navbar-brand" href="{{ url('/') }}">
            <img src="{{ $activeThemeLogoUrl ?? asset('storage/slike/logo.png') }}" height="50" alt="Streličarski klub Dubrava"/>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navigacijaMobile"
                aria-controls="navigacijaMobile" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navigacijaMobile">
            <ul class="navbar-nav ms-auto">
                @auth()
                    @if(auth()->user()->rola <= 1)
                        <li class="nav-item px-4 py-2 text-center position-relative hasSubMenu">
                            <a class="nav-link mx-2" aria-current="page" href="#">
                                <div class="style1">Admin</div>
                            </a>
                            <div class="customSubMenu">
                                <div class="subLink"><a href="{{ route('admin.turniri.naslovna') }}">Podešenja</a></div>
                                <div class="subLink"><a href="{{ route('admin.klub.naslovna') }}">Klub</a></div>
                                <div class="subLink"><a href="{{ route('admin.clanci.popisClanaka') }}">Članci</a></div>
                                <div class="subLink"><a href="{{ route('admin.teme.index') }}">Teme</a></div>
                            </div>
                        </li>
                    @endif
                @endauth
                @php
                    $menu = (new App\Http\Controllers\JavnoController)->menu();
                @endphp
                <li class="nav-item px-4 py-2 text-center position-relative hasSubMenu">
                    <a class="nav-link mx-2" aria-current="page" href="{{ url('/') }}">
                        <div class="style1">Početna</div>
                    </a>
                    <div class="customSubMenu">
                        <div class="subLink"><a href="{{ url('/') }}">Početna</a></div>
                        @guest
                            @if (Route::has('login'))
                                <div class="subLink"><a href="{{ route('login') }}">{{ __('Login') }}</a></div>
                            @endif
                        @else
                            <div class="subLink">
                                <!--suppress JSDeprecatedSymbols -->
                                <a href="{{ route('logout') }}"
                                   onclick="event.preventDefault(); document.getElementById('logout-form').submit();">{{ __('Logout') }}</a>
                            </div>
                            <form id="logout-form" action="{{ route('logout') }}" method="POST">
                                @csrf
                            </form>
                        @endguest
                    </div>
                </li>
                <li class="nav-item px-4 py-2 text-center position-relative hasSubMenu">
                    <a class="nav-link mx-2" aria-current="page" href="{{route('javno.clanci.popisClanaka', 'Obavijest')}}">
                        <div class="style1">Obavijesti</div>
                    </a>
                    <div class="customSubMenu">
                        @isset($menu['Obavijesti'])
                            @if($menu['Obavijesti']->count() != 0)
                                @foreach($menu['Obavijesti'] as $obavijest)
                                    <div class="subLink"><a href="{{ route('javno.clanci.prikaz_clanka', $obavijest) }}">{{$obavijest->menu_naslov}}</a></div>
                                @endforeach
                            @endif
                        @endisset
                        <div class="subLink"><a href="{{route('javno.clanci.popisClanaka', 'Obavijest')}}">Sve obavijesti</a></div>
                    </div>
                </li>
                <li class="nav-item px-4 py-2 text-center position-relative hasSubMenu">
                    <a class="nav-link mx-2" aria-current="page" href="{{ route('javno.rezultati') }}">
                        <div class="style1">Rezultati</div>
                    </a>
                    <div class="customSubMenu">
                        @auth()
                            <div class="subLink"><a href="{{ route('javno.rezultati') }}">Rezultati</a></div>
                            <div class="subLink"><a href="{{ route('admin.rezultati.popisTurnira') }}">Turniri</a></div>
                        @endauth
                    </div>
                </li>
                <li class="nav-item px-4 py-2 text-center position-relative hasSubMenu">
                    <a class="nav-link mx-2" aria-current="page" href="{{route('javno.clanci.popisClanaka', 'O nama')}}">
                        <div class="style1">O nama</div>
                    </a>
                    <div class="customSubMenu">
                        <div class="subLink"><a href="{{route('javno.klub')}}">Podaci o klubu</a></div>
                        @auth()
                            @if(auth()->user()->rola <=2)
                                <div class="subLink"><a href="{{ route('javno.clanovi') }}">Članovi</a></div>
                            @endif
                        @endauth
                        @isset($menu['O nama'])
                            @if($menu['O nama']->count() != 0)
                                @foreach($menu['O nama'] as $oNama)
                                    <div class="subLink"><a href="{{ route('javno.clanci.prikaz_clanka', $oNama) }}">{{$oNama->menu_naslov}}</a></div>
                                @endforeach
                            @endif
                        @endisset
                        <div class="subLink"><a href="{{route('javno.clanci.popisClanaka', 'O nama')}}">Svi članci</a></div>
                    </div>
                </li>
                <li class="nav-item px-4 py-2 text-center position-relative hasSubMenu">
                    <a class="nav-link mx-2" aria-current="page" href="{{route('javno.clanci.popisClanaka', 'Streličarstvo')}}">
                        <div class="style1">Streličarstvo</div>
                    </a>
                    <div class="customSubMenu">
                        @isset($menu['Strelicarstvo'])
                            @if($menu['Strelicarstvo']->count() != 0)
                                @foreach($menu['Strelicarstvo'] as $Strelicarstvo)
                                    <div class="subLink"><a href="{{ route('javno.clanci.prikaz_clanka', $Strelicarstvo) }}">{{$Strelicarstvo->menu_naslov}}</a>
                                    </div>
                                @endforeach
                            @endif
                        @endisset
                        <div class="subLink"><a href="{{route('javno.clanci.popisClanaka', 'Streličarstvo')}}">Svi članci</a></div>
                    </div>
                </li>
                {{--<li class="nav-item px-4 py-2 text-center">
                    <a class="nav-link mx-2" aria-current="page" href="#">
                        <div class="style1">Kontakt</div>
                    </a>
                </li>--}}
            </ul>
        </div>
    </div>
</nav>
