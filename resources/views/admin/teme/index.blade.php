{{-- Administratorski ekran za uređivanje tema, boja, loga, favicona i aktivne varijante. --}}
@extends('layouts.app')

@section('content')
    <div class="container-xxl">
        <div class="row justify-content-center p-2 mb-3 shadow bg-danger fw-bolder">
            <div class="col-lg-12 text-white d-flex justify-content-between align-items-center">
                <span>Teme stranice</span>
                <span>
                    <button class="btn btn-sm btn-warning" type="button" onclick="location.href='{{ route('admin.turniri.naslovna') }}'">Podešenja</button>
                </span>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-12">
                @include('admin.turniri.temaPrikazPolicy')
            </div>
        </div>

        <div class="row g-3">
            <div class="col-lg-5">
                @if($themes->count() === 0)
                    <div class="card shadow-sm">
                        <div class="card-header bg-dark-subtle fw-bold">Popis tema</div>
                        <div class="card-body">
                            <p class="mb-0 text-danger">Nema tema u bazi.</p>
                        </div>
                    </div>
                @else
                    @php
                        $activeThemeKey = $activeThemeBase?->theme_key ?? $activeTheme?->theme_key ?? null;
                        $themeFamilies = $themes->groupBy(function ($theme) {
                            $themeKey = trim((string)($theme->theme_key ?? ''));
                            if ($themeKey !== '') {
                                return $themeKey;
                            }

                            $slug = strtolower((string)($theme->slug ?? ''));
                            $slugBase = (string)preg_replace('/-(light|dark|svijetla|tamna)$/', '', $slug);

                            return $slugBase !== '' ? $slugBase : ('theme-' . $theme->id);
                        });
                    @endphp

                    @foreach($themeFamilies as $familyThemes)
                        @php
                            $familyThemes = $familyThemes
                                ->sortBy(function ($theme) {
                                    $variantValue = strtolower((string)($theme->variant ?? 'light'));

                                    return $variantValue === 'light' ? 0 : 1;
                                })
                                ->values();

                            $familyFirst = $familyThemes->first();
                            $familyName = $familyFirst?->name ?? 'Tema';
                            $familyThemeKey = trim((string)($familyFirst?->theme_key ?? ''));
                            $isFamilyActive = $familyThemeKey !== '' && !empty($activeThemeKey)
                                ? ((string)$activeThemeKey === $familyThemeKey)
                                : $familyThemes->contains(fn ($theme) => (bool)$theme->is_active);

                            $activationTheme = $familyThemes->first(function ($theme) {
                                return strtolower((string)($theme->variant ?? 'light')) === 'light';
                            }) ?? $familyFirst;
                        @endphp

                        <div class="card shadow-sm mb-3 border-2 @if($isFamilyActive) border-success @else border-secondary-subtle @endif">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="fw-bold">{{ $familyName }}</div>
                                    <small class="text-muted">{{ $familyThemes->count() }} varijante</small>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    @if($isFamilyActive)
                                        <span class="badge text-bg-success">Aktivna tema</span>
                                    @else
                                        <span class="badge text-bg-secondary">Neaktivna</span>
                                    @endif

                                    @if(!$isFamilyActive && $activationTheme !== null)
                                        <form action="{{ route('admin.teme.activate', $activationTheme) }}" method="POST" class="m-0">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-success">Aktiviraj</button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm align-middle mb-2">
                                        <thead>
                                        <tr>
                                            <th>Varijanta</th>
                                            <th>Status varijante</th>
                                            <th class="text-end">Akcije</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @foreach($familyThemes as $theme)
                                            @php
                                                $variant = strtolower((string)($theme->variant ?? 'light'));
                                            @endphp
                                            <tr>
                                                <td class="fw-semibold">
                                                    @if($variant === 'dark')
                                                        <span class="badge text-bg-dark">Tamna</span>
                                                    @else
                                                        <span class="badge text-bg-light border">Svijetla</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if((bool)$theme->is_active)
                                                        <span class="badge text-bg-success">Aktivna u bazi</span>
                                                    @else
                                                        <span class="badge text-bg-secondary">Neaktivna u bazi</span>
                                                    @endif
                                                </td>
                                                <td class="text-end">
                                                    <a href="{{ route('admin.teme.index', ['theme' => $theme->id]) }}" class="btn btn-sm btn-outline-primary">Uredi</a>
                                                </td>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                    </table>
                                </div>

                                @if($activationTheme !== null)
                                    <form action="{{ route('admin.teme.clone', $activationTheme) }}" method="POST" class="d-flex gap-2 mt-2">
                                        @csrf
                                        <input type="text"
                                               class="form-control form-control-sm"
                                               name="clone_name"
                                               placeholder="Naziv klona (npr. {{ $familyName }} 2)"
                                               required>
                                        <button type="submit" class="btn btn-sm btn-outline-dark">Kloniraj temu (obje varijante)</button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>

            <div class="col-lg-7">
                @if($selectedTheme)
                    @php
                        $labels = [
                            'body_bg' => 'Pozadina stranice',
                            'body_text' => 'Boja teksta',
                            'primary' => 'Primarna',
                            'secondary' => 'Sekundarna',
                            'success' => 'Success',
                            'danger' => 'Danger',
                            'warning' => 'Warning',
                            'info' => 'Info',
                            'link' => 'Boja linkova',
                            'light' => 'Light',
                            'dark' => 'Dark',
                            'secondary_subtle' => 'Secondary subtle',
                            'dark_subtle' => 'Dark subtle',
                            'nav_solid_bg' => 'Menu osnovna pozadina',
                            'nav_gradient_start' => 'Menu gradijent start',
                            'nav_gradient_mid' => 'Menu gradijent sredina',
                            'nav_gradient_end' => 'Menu gradijent kraj',
                            'nav_item_border' => 'Menu border',
                            'nav_item_text' => 'Menu tekst',
                            'nav_item_hover_bg' => 'Menu hover pozadina',
                            'nav_dropdown_bg' => 'Submenu pozadina',
                            'nav_dropdown_hover_bg' => 'Submenu hover pozadina',
                        ];
                    @endphp

                    <div class="card shadow-sm mb-3">
                        <div class="card-header bg-dark-subtle fw-bold">
                            Uređivanje teme: {{ $selectedTheme->name }}
                            ({{ ($variantLabels[strtolower((string)($selectedTheme->variant ?? 'light'))] ?? ucfirst((string)($selectedTheme->variant ?? 'light'))) }})
                        </div>
                        <div class="card-body">
                            <form id="themeUpdateForm" action="{{ route('admin.teme.update', $selectedTheme) }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                <div class="row g-3">
                                    <div class="col-lg-7">
                                        <label for="name" class="form-label fw-semibold">Naziv teme</label>
                                        <input type="text" id="name" name="name" class="form-control" value="{{ old('name', $selectedTheme->name) }}" required>
                                        <div class="form-text">Naziv i opis primjenjuju se na obje varijante. Asseti su globalni za cijeli site: svijetli logo, tamni logo (opcionalno) i favicon.</div>
                                    </div>
                                    <div class="col-lg-5">
                                        <label class="form-label fw-semibold">Slug</label>
                                        <input type="text" class="form-control" value="{{ $selectedTheme->slug }}" disabled>
                                    </div>
                                    <div class="col-12">
                                        <label for="description" class="form-label fw-semibold">Opis</label>
                                        <input type="text" id="description" name="description" class="form-control"
                                               value="{{ old('description', $selectedTheme->description) }}">
                                    </div>
                                    <div class="col-lg-6">
                                        <label for="logo" class="form-label fw-semibold">Logo</label>
                                        <input type="file" id="logo" name="logo" class="form-control" accept=".jpg,.jpeg,.png,.webp,.svg">
                                        <small class="text-muted d-block mt-1">Upload na svijetloj varijanti postavlja globalni `logo`, upload na tamnoj postavlja globalni `logo_dark`.</small>
                                    </div>
                                    <div class="col-lg-6">
                                        <label for="favicon_source" class="form-label fw-semibold">Favicon izvor (slika)</label>
                                        <input type="file" id="favicon_source" name="favicon_source" class="form-control" accept=".jpg,.jpeg,.png,.webp">
                                        <small class="text-muted d-block mt-1">Favicon je jedan za svijetlu i tamnu varijantu ove teme.</small>
                                    </div>
                                    @if(!empty($assetPreview))
                                        @php
                                            $selectedVariantIsDark = strtolower((string)($selectedTheme->variant ?? 'light')) === 'dark';
                                            $previewVersion = $selectedTheme->updated_at?->timestamp ?? time();
                                            $lightPreview = $assetPreview['light'] ?? [];
                                            $darkPreview = $assetPreview['dark'] ?? $lightPreview;
                                            $logosAreSame = ($lightPreview['logo_path'] ?? null) === ($darkPreview['logo_path'] ?? null);
                                            $sharedFavicon = $assetPreview['favicon'] ?? [];
                                        @endphp
                                        <div class="col-12">
                                            <div class="border rounded p-3 bg-light-subtle">
                                                <div class="row g-3">
                                                    <div class="col-lg-6">
                                                        <p class="fw-semibold mb-2">Aktivni asseti</p>
                                                        <div class="border rounded p-2 mb-3 bg-body">
                                                            <div class="small text-muted mb-2">
                                                                Logo odabrane varijante ({{ $selectedVariantIsDark ? 'Tamna' : 'Svijetla' }})
                                                            </div>
                                                            <div class="d-flex align-items-center gap-3">
                                                                <img src="{{ ($assetPreview['selected']['logo_url'] ?? asset('storage/slike/logo.png')) }}?v={{ $previewVersion }}"
                                                                     alt="Trenutni logo"
                                                                     style="max-height: 56px; width: auto;">
                                                                <div>
                                                                    @if(($assetPreview['selected']['logo_inherited'] ?? false))
                                                                        <span class="badge text-bg-secondary">Naslijeđeno</span>
                                                                    @else
                                                                        <span class="badge text-bg-success">Direktno postavljeno</span>
                                                                    @endif
                                                                    <div class="small text-muted mt-1 text-break">{{ $assetPreview['selected']['logo_path'] ?? 'slike/logo.png' }}</div>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div class="border rounded p-2 bg-body">
                                                            <div class="small text-muted mb-2">Favicon (zajednički za svijetlu i tamnu)</div>
                                                            <div class="d-flex align-items-center gap-3">
                                                                <img src="{{ ($sharedFavicon['url'] ?? asset('favicon.ico')) }}?v={{ $previewVersion }}"
                                                                     alt="Trenutni favicon"
                                                                     style="height: 36px; width: 36px; object-fit: contain; border: 1px solid rgba(128, 128, 128, 0.35); border-radius: 6px; background: #fff;">
                                                                <div>
                                                                    @if(($sharedFavicon['is_default'] ?? true))
                                                                        <span class="badge text-bg-secondary">Default favicon</span>
                                                                    @else
                                                                        <span class="badge text-bg-success">Uploadan favicon</span>
                                                                    @endif
                                                                    <div class="small text-muted mt-1 text-break">{{ $sharedFavicon['path'] ?? 'favicon.ico' }}</div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-lg-6">
                                                        <p class="fw-semibold mb-2">Preview loga po varijantama</p>
                                                        <div class="row g-2">
                                                            <div class="col-12">
                                                                <div class="border rounded p-2 bg-white">
                                                                    <div class="d-flex justify-content-between align-items-center gap-2">
                                                                        <span class="badge text-bg-light border">Svijetla</span>
                                                                        @if(($lightPreview['logo_inherited'] ?? false))
                                                                            <span class="badge text-bg-secondary">Naslijeđeno</span>
                                                                        @else
                                                                            <span class="badge text-bg-success">Direktno</span>
                                                                        @endif
                                                                    </div>
                                                                    <div class="mt-2 border rounded d-flex align-items-center justify-content-center"
                                                                         style="min-height: 96px; background: #f8f9fa;">
                                                                        <img src="{{ ($lightPreview['logo_url'] ?? asset('storage/slike/logo.png')) }}?v={{ $previewVersion }}"
                                                                             alt="Logo svijetle varijante"
                                                                             style="max-height: 62px; width: auto;">
                                                                    </div>
                                                                    <div class="small text-muted mt-1 text-break">{{ $lightPreview['logo_path'] ?? 'slike/logo.png' }}</div>
                                                                </div>
                                                            </div>

                                                            <div class="col-12">
                                                                <div class="border rounded p-2" style="background: #111317;">
                                                                    <div class="d-flex justify-content-between align-items-center gap-2">
                                                                        <span class="badge text-bg-dark">Tamna</span>
                                                                        @if($logosAreSame)
                                                                            <span class="badge text-bg-secondary">Isti logo kao svijetla</span>
                                                                        @elseif(($darkPreview['logo_inherited'] ?? false))
                                                                            <span class="badge text-bg-secondary">Naslijeđeno</span>
                                                                        @else
                                                                            <span class="badge text-bg-success">Poseban tamni logo</span>
                                                                        @endif
                                                                    </div>
                                                                    <div class="mt-2 border rounded d-flex align-items-center justify-content-center"
                                                                         style="min-height: 96px; background: #1f242b;">
                                                                        <img src="{{ ($darkPreview['logo_url'] ?? ($lightPreview['logo_url'] ?? asset('storage/slike/logo.png'))) }}?v={{ $previewVersion }}"
                                                                             alt="Logo tamne varijante"
                                                                             style="max-height: 62px; width: auto;">
                                                                    </div>
                                                                    <div class="small mt-1 text-break" style="color: #ced4da;">{{ $darkPreview['logo_path'] ?? ($lightPreview['logo_path'] ?? 'slike/logo.png') }}</div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                </div>

                                <hr>

                                <div class="row g-2">
                                    @foreach($editableColorKeys as $key)
                                        @php
                                            $value = old($key, $selectedColors[$key] ?? '#000000');
                                        @endphp
                                        <div class="col-md-6">
                                            <label for="{{ $key }}" class="form-label mb-1">{{ $labels[$key] ?? $key }}</label>
                                            <div class="d-flex gap-2 align-items-center">
                                                <input type="color" id="{{ $key }}_picker" class="form-control form-control-color js-theme-color-picker"
                                                       data-target="{{ $key }}" value="{{ $value }}">
                                                <input type="text"
                                                       id="{{ $key }}"
                                                       name="{{ $key }}"
                                                       class="form-control js-theme-color"
                                                       value="{{ $value }}"
                                                       pattern="^#[0-9A-Fa-f]{6}$"
                                                       required>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>

                                <div class="mt-3 text-end">
                                    <button type="submit" class="btn btn-danger">Spremi temu</button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="card shadow-sm">
                        <div class="card-header bg-dark-subtle fw-bold">Live preview</div>
                        <div class="card-body">
                            <div id="themePreview" class="rounded border overflow-hidden">
                                <div id="themePreviewNav" class="px-3 py-2 d-flex justify-content-between align-items-center">
                                    <span id="themePreviewNavText" class="fw-semibold">Navigacija</span>
                                    <span id="themePreviewNavBadge" class="badge">Submenu</span>
                                </div>
                                <div id="themePreviewBody" class="p-3">
                                    <h6 class="mb-2">Preview sadržaja</h6>
                                    <p class="mb-3">Ovdje vidiš kako se boje ponašaju prije spremanja.</p>
                                    <p class="mb-3">
                                        Primjer linka:
                                        <a href="#" id="themePreviewLink" class="fw-semibold text-decoration-none">Profil člana</a>
                                    </p>
                                    <div class="d-flex flex-wrap gap-2 mb-3">
                                        <button type="button" id="previewPrimary" class="btn btn-sm">Primary</button>
                                        <button type="button" id="previewDanger" class="btn btn-sm">Danger</button>
                                        <button type="button" id="previewSuccess" class="btn btn-sm">Success</button>
                                        <button type="button" id="previewWarning" class="btn btn-sm">Warning</button>
                                    </div>
                                    <div id="themePreviewDropdown" class="rounded p-2 fw-semibold">Stavka iz podmenija</div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <script>
        (() => {
            const form = document.getElementById('themeUpdateForm');
            if (!form) {
                return;
            }

            const colorInputs = Array.from(document.querySelectorAll('.js-theme-color'));
            const colorPickers = Array.from(document.querySelectorAll('.js-theme-color-picker'));

            const preview = {
                root: document.getElementById('themePreview'),
                nav: document.getElementById('themePreviewNav'),
                navText: document.getElementById('themePreviewNavText'),
                navBadge: document.getElementById('themePreviewNavBadge'),
                body: document.getElementById('themePreviewBody'),
                dropdown: document.getElementById('themePreviewDropdown'),
                link: document.getElementById('themePreviewLink'),
                buttons: {
                    primary: document.getElementById('previewPrimary'),
                    danger: document.getElementById('previewDanger'),
                    success: document.getElementById('previewSuccess'),
                    warning: document.getElementById('previewWarning'),
                }
            };

            function isValidHex(hex) {
                return /^#[0-9A-Fa-f]{6}$/.test((hex || '').trim());
            }

            function normalizeHex(hex, fallback) {
                if (!isValidHex(hex)) {
                    return fallback;
                }
                return hex.toLowerCase();
            }

            function contrastColor(hex) {
                const h = normalizeHex(hex, '#000000').replace('#', '');
                const r = parseInt(h.substring(0, 2), 16);
                const g = parseInt(h.substring(2, 4), 16);
                const b = parseInt(h.substring(4, 6), 16);
                const luminance = (0.299 * r + 0.587 * g + 0.114 * b) / 255;
                return luminance > 0.58 ? '#111111' : '#ffffff';
            }

            function shiftHex(hex, delta) {
                const h = normalizeHex(hex, '#000000').replace('#', '');
                const r = Math.max(0, Math.min(255, parseInt(h.substring(0, 2), 16) + delta));
                const g = Math.max(0, Math.min(255, parseInt(h.substring(2, 4), 16) + delta));
                const b = Math.max(0, Math.min(255, parseInt(h.substring(4, 6), 16) + delta));

                return '#' + [r, g, b].map((value) => value.toString(16).padStart(2, '0')).join('');
            }

            function value(id, fallback) {
                const el = document.getElementById(id);
                return normalizeHex(el ? el.value : fallback, fallback);
            }

            function applyPreview() {
                const colors = {
                    bodyBg: value('body_bg', '#e9ecef'),
                    bodyText: value('body_text', '#212529'),
                    navStart: value('nav_gradient_start', '#ff0000'),
                    navMid: value('nav_gradient_mid', '#ffd700'),
                    navEnd: value('nav_gradient_end', '#07c818'),
                    navHoverBg: value('nav_item_hover_bg', '#62cc46'),
                    dropdownBg: value('nav_dropdown_bg', '#e0f144'),
                    dropdownHoverBg: value('nav_dropdown_hover_bg', '#62cc46'),
                    primary: value('primary', '#0d6efd'),
                    danger: value('danger', '#dc3545'),
                    success: value('success', '#198754'),
                    warning: value('warning', '#ffc107'),
                    link: value('link', '#0d6efd'),
                };

                const navTextColor = value('nav_item_text', contrastColor(colors.navMid));
                const navHoverTextColor = contrastColor(colors.navHoverBg);
                const dropdownTextColor = contrastColor(colors.dropdownBg);
                const dropdownHoverTextColor = contrastColor(colors.dropdownHoverBg);
                const linkHoverColor = shiftHex(colors.link, contrastColor(colors.link) === '#ffffff' ? 24 : -24);

                preview.root.style.backgroundColor = colors.bodyBg;
                preview.root.style.color = colors.bodyText;
                preview.nav.style.background = `linear-gradient(135deg, ${colors.navStart} 0%, ${colors.navMid} 60%, ${colors.navEnd} 100%)`;
                preview.navText.style.color = navTextColor;
                preview.navBadge.style.backgroundColor = colors.navHoverBg;
                preview.navBadge.style.color = navHoverTextColor;

                preview.body.style.backgroundColor = colors.bodyBg;
                preview.body.style.color = colors.bodyText;
                preview.dropdown.style.backgroundColor = colors.dropdownBg;
                preview.dropdown.style.color = dropdownTextColor;
                preview.dropdown.style.border = `1px solid ${colors.dropdownHoverBg}`;

                preview.dropdown.onmouseenter = () => {
                    preview.dropdown.style.backgroundColor = colors.dropdownHoverBg;
                    preview.dropdown.style.color = dropdownHoverTextColor;
                };
                preview.dropdown.onmouseleave = () => {
                    preview.dropdown.style.backgroundColor = colors.dropdownBg;
                    preview.dropdown.style.color = dropdownTextColor;
                };

                preview.link.style.color = colors.link;
                preview.link.onmouseenter = () => {
                    preview.link.style.color = linkHoverColor;
                };
                preview.link.onmouseleave = () => {
                    preview.link.style.color = colors.link;
                };

                preview.buttons.primary.style.backgroundColor = colors.primary;
                preview.buttons.primary.style.borderColor = colors.primary;
                preview.buttons.primary.style.color = contrastColor(colors.primary);

                preview.buttons.danger.style.backgroundColor = colors.danger;
                preview.buttons.danger.style.borderColor = colors.danger;
                preview.buttons.danger.style.color = contrastColor(colors.danger);

                preview.buttons.success.style.backgroundColor = colors.success;
                preview.buttons.success.style.borderColor = colors.success;
                preview.buttons.success.style.color = contrastColor(colors.success);

                preview.buttons.warning.style.backgroundColor = colors.warning;
                preview.buttons.warning.style.borderColor = colors.warning;
                preview.buttons.warning.style.color = contrastColor(colors.warning);
            }

            colorPickers.forEach((picker) => {
                picker.addEventListener('input', () => {
                    const targetId = picker.getAttribute('data-target');
                    const input = document.getElementById(targetId);
                    if (input) {
                        input.value = picker.value;
                        applyPreview();
                    }
                });
            });

            colorInputs.forEach((input) => {
                input.addEventListener('input', () => {
                    const picker = document.getElementById(input.id + '_picker');
                    if (picker && isValidHex(input.value)) {
                        picker.value = input.value;
                    }
                    applyPreview();
                });
            });

            applyPreview();
        })();
    </script>
@endsection
