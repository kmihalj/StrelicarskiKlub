{{-- Detaljni prikaz jednog turnira s rezultatima pojedinačno/timski, opisom i galerijom medija. --}}
@extends('layouts.app')
@section('content')
    <div class="container-xxl bg-white shadow">
        @include('admin.rezultati.prikazRezultata')
    </div>
@endsection
