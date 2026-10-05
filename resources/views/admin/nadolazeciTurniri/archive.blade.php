{{-- Arhiva prošlih turnira s pristupom prijavama i povezanim rezultatima. --}}
@extends('layouts.app')

@section('content')
    <div class="container-xxl bg-white shadow mb-3">
        <div class="row justify-content-center p-2 shadow bg-danger fw-bolder">
            <div class="col-lg-12 text-white d-flex flex-wrap justify-content-between align-items-center gap-2">
                <span>Arhivirani turniri</span>
                <a href="{{ route('admin.nadolazeci_turniri.index') }}" class="btn btn-sm btn-light">Povratak na popis turnira</a>
            </div>
        </div>
    </div>

    @include('layouts.paginationBlok', ['paginator' => $arhiviraniTurniri, 'isTop' => true])
    <div class="container-xxl bg-white shadow mb-3">
        <div class="row p-3">
            <div class="col-12 table-responsive">
                <table class="table table-hover align-middle mb-0 border">
                    <thead class="table-warning">
                    <tr>
                        <th>Datum</th>
                        <th>Naziv</th>
                        <th>Mjesto</th>
                        <th>Tip turnira</th>
                        <th>Prijave</th>
                        <th>Rezultati</th>
                        <th class="text-end">Radnje</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($arhiviraniTurniri as $turnir)
                        <tr>
                            <td class="text-nowrap">{{ $turnir->datumRasponLabel() }}</td>
                            <td>
                                <div class="fw-semibold">{{ $turnir->naziv }}</div>
                                @if($turnir->organizator)
                                    <div class="small text-muted">{{ $turnir->organizator }}</div>
                                @endif
                            </td>
                            <td>{{ $turnir->mjesto }}</td>
                            <td>{{ $turnir->tipTurnira->naziv ?? '-' }}</td>
                            <td>{{ (int) $turnir->aktivne_prijave_count }}</td>
                            <td>
                                @if($turnir->rezultatskiTurnir)
                                    <a href="{{ route('admin.rezultati.unosRezultata', $turnir->rezultatskiTurnir) }}">Otvori rezultate</a>
                                @else
                                    <span class="text-muted">Nisu kreirani</span>
                                @endif
                            </td>
                            <td class="text-end text-nowrap">
                                <div class="d-inline-flex flex-wrap justify-content-end gap-1">
                                    <a href="{{ route('admin.nadolazeci_turniri.show', $turnir) }}" class="btn btn-sm btn-primary">Prijave</a>
                                    <form action="{{ route('admin.nadolazeci_turniri.restore', $turnir) }}" method="POST" class="m-0">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-secondary">Vrati na popis</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center">Nema arhiviranih turnira.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @include('layouts.paginationBlok', ['paginator' => $arhiviraniTurniri])
@endsection
