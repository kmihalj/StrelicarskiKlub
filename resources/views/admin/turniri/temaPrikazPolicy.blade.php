<div class="card">
    <div class="card-header bg-danger fw-bolder text-white">
        Prikaz teme na cijelom siteu
    </div>
    <div class="card-body bg-secondary-subtle shadow">
        <form action="{{ route('admin.tema_mode_policy.update') }}" method="POST">
            @csrf
            <div class="row g-2 align-items-end">
                <div class="col-lg-6">
                    <label for="theme_mode_policy" class="form-label fw-semibold">Globalna politika prikaza</label>
                    <select id="theme_mode_policy" name="theme_mode_policy" class="form-select">
                        @php
                            $globalPolicy = $siteThemeModePolicy ?? 'auto';
                        @endphp
                        <option value="auto" @if($globalPolicy === 'auto') selected @endif>Automatski (korisnik može birati)</option>
                        <option value="light" @if($globalPolicy === 'light') selected @endif>Forsiraj svijetlu temu</option>
                        <option value="dark" @if($globalPolicy === 'dark') selected @endif>Forsiraj tamnu temu</option>
                    </select>
                </div>
                <div class="col-lg-3">
                    <button type="submit" class="btn btn-outline-danger w-100">Spremi</button>
                </div>
                <div class="col-lg-12">
                    <small class="text-muted">
                        Kad je uključeno forsiranje svijetle/tamne teme, korisnički odabir na profilu i u meniju se skriva.
                    </small>
                </div>
            </div>
        </form>
    </div>
</div>
