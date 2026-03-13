@extends('layouts.app')
@auth()
    @if(auth()->user()->rola <= 1)
        @section('content')
            <div class="row">
                <div class="col-lg-12 mb-4">
                    @include('admin.turniri.poljaZaTipoveTurnira')
                </div>

                <div class="col-lg-4 mb-4">
                    @include('admin.turniri.tipoviTurnira')
                </div>

                <div class="col-lg-4 mb-4">
                    @include('admin.turniri.stilovi')
                </div>

                <div class="col-lg-4 mb-4">
                    @include(('admin.turniri.kategorije'))
                </div>
            </div>
        @endsection
    @else
        @section('content')
            @include('layouts.neovlasteno')
        @endsection
    @endif
@endauth
@guest()
    @section('content')
        @include('layouts.neovlasteno')
    @endsection
@endguest
