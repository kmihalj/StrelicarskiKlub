@extends('layouts.app')

@section('content')
    <div class="row justify-content-center">
        <div class="col-lg-8 col-xl-7">
            <div class="card">
                <div class="card-header bg-danger fw-bolder text-white">Prijava</div>

                <div class="card-body bg-white shadow">
                    <form method="POST" action="{{ route('login') }}">
                        @csrf

                        <div class="row mb-3">
                            <label for="email" class="col-md-4 col-form-label text-md-end">E-mail adresa</label>
                            <div class="col-md-8">
                                <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autocomplete="email" autofocus>
                                @error('email')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="password" class="col-md-4 col-form-label text-md-end">Lozinka</label>

                            <div class="col-md-8">
                                <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required autocomplete="current-password">

                                @error('password')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-8 offset-md-4">
                                <div class="form-check">
                                    <input type="hidden" name="remember" value="0">
                                    <input class="form-check-input" type="checkbox" name="remember" id="remember" value="1"
                                           @checked(old('remember', '1') == '1')>

                                    <label class="form-check-label" for="remember">
                                        Zapamti me
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="row mb-0">
                            <div class="col-md-8 offset-md-4 d-grid gap-2 d-md-flex">
                                <button type="submit" class="btn btn-primary px-4">
                                    Prijava
                                </button>

                                @if (Route::has('password.request'))
                                    <a class="btn btn-link" href="{{ route('password.request') }}">
                                        Zaboravili ste lozinku?
                                    </a>
                                @endif
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
