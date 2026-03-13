@extends('layouts.app')

@section('content')
    <div class="row justify-content-center">
        <div class="col-lg-8 col-xl-7">
            <div class="card">
                <div class="card-header bg-danger fw-bolder text-white">Reset lozinke</div>

                <div class="card-body bg-white shadow">
                    @if (session('status'))
                        <div class="alert alert-success mb-4" role="alert">
                            {{ session('status') }}
                        </div>
                    @endif

                    <p class="mb-4">
                        Unesite svoju e-mail adresu i poslat ćemo vam poveznicu za postavljanje nove lozinke.
                    </p>

                    <form method="POST" action="{{ route('password.email') }}">
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

                        <div class="row mb-0">
                            <div class="col-md-8 offset-md-4 d-grid d-md-flex">
                                <button type="submit" class="btn btn-primary px-4">
                                    Pošalji poveznicu
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
