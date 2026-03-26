{{-- Ekran registracije novog korisničkog računa. --}}
@extends('layouts.app')

@section('content')
    <div class="row justify-content-center">
        <div class="col-lg-8 col-xl-7">
            <div class="card">
                <div class="card-header bg-danger fw-bolder text-white">Registracija</div>

                <div class="card-body bg-white shadow">
                    <p class="mb-4">
                        Registracija je za članove kluba, polaznike škole streličarstva i roditelje maloljetnih članova i polaznika.<br><br>
                        Molimo roditelje da se registiraju sa svojim podacima, ne sa podacima djece (OIB, br. telefona, ime i prezime, e-mail). Administrator će Vaš račun povezati sa podacima Vaše djece.<br><br>
                        Ako se podaci podudaraju sa postojećim članom/polaznikom (OIB, e-mail, ime i prezime, telefon), račun automatski dobiva odgovarajuća prava.<br><br>
                        Ako podaci nisu potpuno usklađeni, registracija je uspješna, ali prava dodjeljuje administrator.
                    </p>

                    <form method="POST" action="{{ route('register') }}">
                        @csrf

                        <div class="row mb-3">
                            <label for="name" class="col-md-4 col-form-label text-md-end">Ime i prezime</label>
                            <div class="col-md-8">
                                <input id="name" type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name') }}" required autocomplete="name" autofocus>
                                @error('name')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="email" class="col-md-4 col-form-label text-md-end">E-mail adresa</label>
                            <div class="col-md-8">
                                <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autocomplete="email">
                                @error('email')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="oib" class="col-md-4 col-form-label text-md-end">OIB</label>
                            <div class="col-md-8">
                                <input id="oib" type="text" class="form-control @error('oib') is-invalid @enderror" name="oib" value="{{ old('oib') }}" required autocomplete="off" maxlength="11">
                                @error('oib')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="br_telefona" class="col-md-4 col-form-label text-md-end">Broj telefona</label>
                            <div class="col-md-8">
                                <input id="br_telefona" type="text" class="form-control @error('br_telefona') is-invalid @enderror" name="br_telefona" value="{{ old('br_telefona') }}" required autocomplete="tel" placeholder="+385xxxxxxxxx">
                                @error('br_telefona')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="password" class="col-md-4 col-form-label text-md-end">Lozinka</label>
                            <div class="col-md-8">
                                <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required autocomplete="new-password">
                                @error('password')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-4">
                            <label for="password-confirm" class="col-md-4 col-form-label text-md-end">Potvrda lozinke</label>
                            <div class="col-md-8">
                                <input id="password-confirm" type="password" class="form-control" name="password_confirmation" required autocomplete="new-password">
                            </div>
                        </div>

                        <div class="row mb-0">
                            <div class="col-md-8 offset-md-4 d-grid d-md-flex">
                                <button type="submit" class="btn btn-primary px-4">
                                    Registriraj se
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
