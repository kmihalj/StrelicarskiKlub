{{-- Glavni layout aplikacije: uključuje navigaciju, stilove teme i sadržaj stranice. --}}
<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>
    @php
        $faviconUrl = $activeThemeFaviconUrl ?? asset('favicon.ico');
        $faviconType = $activeThemeFaviconType ?? 'image/x-icon';
        $faviconIcoUrl = asset('favicon.ico');
        $faviconVersion = $activeThemeFaviconVersion
            ?? (@filemtime(public_path('favicon.png')) ?: (@filemtime(public_path('favicon.ico')) ?: time()));
        $resolvedThemeMode = $themeModeResolved ?? (($activeThemeIsDark ?? false) ? 'dark' : 'light');
    @endphp
    <link rel="icon" type="{{ $faviconType }}" href="{{ $faviconUrl }}?v={{ $faviconVersion }}">
    <link rel="icon" type="image/x-icon" href="{{ $faviconIcoUrl }}?v={{ $faviconVersion }}">
    <link rel="shortcut icon" href="{{ $faviconIcoUrl }}?v={{ $faviconVersion }}">
    <link rel="apple-touch-icon" href="{{ $faviconUrl }}?v={{ $faviconVersion }}">

    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=Nunito" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/ckeditor5/ck_style.css') }}" type="text/css">

    <!-- Scripts -->
    @vite(['resources/js/app.js'])
    @include('layouts.themeStyles')
</head>
<body class="bg-secondary-subtle m-0 p-0 @if($resolvedThemeMode === 'dark') theme-dark @else theme-light @endif" style="margin:0; padding:0;">
<div id="app" class="m-0 p-0" style="margin:0; padding:0;">
    @include('layouts.nav2')
    @php
        $isAdminSetupPage = request()->routeIs('admin.turniri.*') || request()->routeIs('admin.placanja.*');
        $mainContainerClass = $isAdminSetupPage ? 'container-xxl' : 'container-fluid';
    @endphp
    <main class="{{ $mainContainerClass }} py-4"
{{--          style="background-image: url('https://skdubrava.hr/new/storage/slike/bck.jpeg');"--}}
    >
        <div class="row">
            <div class="col-lg-12">
                @if (session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif
                @if (session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        @if( is_array(session('error')))
                            @foreach(session('error') as $err)
                                {{ $err }}
                            @endforeach
                        @else
                            {{ session('error') }}
                        @endif
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                @isset($success)
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ $success }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endisset
                @isset ($error)
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        {{ $error }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endisset
            </div>
        </div>
        @yield('content')
    </main>
</div>
@if(($themeModePreference ?? 'auto') === 'auto')
    <script>
        (function () {
            const preferenceCookieName = @json($themeModePreferenceCookieName ?? 'theme_mode_preference');
            const resolvedCookieName = @json($themeModeResolvedCookieName ?? 'theme_mode_resolved');
            const currentResolvedMode = @json($resolvedThemeMode);
            const cookiePath = @json($themeModeCookiePath ?? '/');
            const cookieDomain = @json($themeModeCookieDomain ?? null);
            const cookieSecure = @json($themeModeCookieSecure ?? false);
            const cookieSameSiteConfig = @json($themeModeCookieSameSite ?? 'lax');
            const reloadGuardKey = 'theme_mode_auto_reload_guard';
            const reloadGuardTtlMs = 3000;
            const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');

            const resolveSameSiteValue = function (value) {
                const normalized = String(value || '').trim().toLowerCase();
                if (normalized === 'strict') {
                    return 'Strict';
                }

                if (normalized === 'none') {
                    return 'None';
                }

                return 'Lax';
            };

            const sameSiteValue = resolveSameSiteValue(cookieSameSiteConfig);

            const writeCookie = function (name, value) {
                let serialized = name + '=' + encodeURIComponent(value);
                serialized += '; path=' + (cookiePath || '/');
                serialized += '; max-age=31536000';
                serialized += '; SameSite=' + sameSiteValue;

                if (cookieDomain) {
                    serialized += '; domain=' + cookieDomain;
                }

                if (cookieSecure || window.location.protocol === 'https:') {
                    serialized += '; Secure';
                }

                document.cookie = serialized;
            };

            const recentlyReloadedForMode = function (mode) {
                try {
                    const raw = sessionStorage.getItem(reloadGuardKey);
                    if (!raw) {
                        return false;
                    }

                    const parsed = JSON.parse(raw);
                    if (!parsed || parsed.mode !== mode) {
                        return false;
                    }

                    const ts = Number(parsed.ts || 0);

                    return Number.isFinite(ts) && (Date.now() - ts) < reloadGuardTtlMs;
                } catch (error) {
                    return false;
                }
            };

            const markReloadForMode = function (mode) {
                try {
                    sessionStorage.setItem(reloadGuardKey, JSON.stringify({
                        mode: mode,
                        ts: Date.now(),
                    }));
                } catch (error) {
                    // Best effort guard; failure here must not break theme switching.
                }
            };

            const clearReloadGuard = function () {
                try {
                    sessionStorage.removeItem(reloadGuardKey);
                } catch (error) {
                    // No-op: if storage is blocked, theme behavior still works.
                }
            };

            const detectedMode = mediaQuery.matches ? 'dark' : 'light';
            writeCookie(preferenceCookieName, 'auto');

            if (detectedMode !== currentResolvedMode) {
                writeCookie(resolvedCookieName, detectedMode);
                if (!recentlyReloadedForMode(detectedMode)) {
                    markReloadForMode(detectedMode);
                    window.location.reload();
                }
                return;
            }

            clearReloadGuard();

            const handleModeChange = function (event) {
                const nextMode = event.matches ? 'dark' : 'light';
                writeCookie(resolvedCookieName, nextMode);
                markReloadForMode(nextMode);
                window.location.reload();
            };

            if (typeof mediaQuery.addEventListener === 'function') {
                mediaQuery.addEventListener('change', handleModeChange);
            } else if (typeof mediaQuery.addListener === 'function') {
                mediaQuery.addListener(handleModeChange);
            }
        })();
    </script>
@endif
</body>
</html>
