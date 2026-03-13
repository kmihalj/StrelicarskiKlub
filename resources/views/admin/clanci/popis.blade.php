@extends('layouts.app')
@auth()
    @if(auth()->user()->rola <= 1)
        @section('content')
            @include('layouts.paginationBlok', ['paginator' => $clanci, 'isTop' => true])

            <div class="container-xxl bg-white shadow mb-3">
                <div class="row justify-content-center p-2 shadow bg-danger fw-bolder">
                    <div class="col-lg-12 text-white d-flex justify-content-between align-items-center">
                        <span>Popis članka</span>
                        <span>
                            <button class="btn btn-sm btn-warning" type="button" onclick="location.href='{{ route('admin.clanci.unos') }}'">Dodaj članak</button>
                        </span>
                    </div>
                </div>
                <div class="row p-2 shadow bg-white fw-bolder">
                    <div class="col-lg-12 pt-3 pb-2 mb-3 justify-content-center">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0 border">
                                <thead class="table-warning">
                                <tr>
                                    <th>Vrsta</th>
                                    <th>Naslov</th>
                                    <th>Datum</th>
                                    <th></th>
                                </tr>
                                </thead>
                                <tbody>
                                @if($clanci->count() == 0)
                                    <tr>
                                        <td colspan="4" class="text-center">
                                            <div class="ms-3">
                                                <p class="fw-bold mb-1">Nema unešenih članak</p>
                                            </div>
                                        </td>
                                    </tr>
                                @else
                                    @foreach($clanci as $clanak)
                                        <tr>
                                            <td>
                                                <p class="fw-normal mb-0">{{ $clanak->vrsta }}</p>
                                            </td>
                                            <td>
                                                <p class="fw-normal mb-0">
                                                    <a class="link-dark link-offset-2 link-underline-opacity-0 link-underline-opacity-50-hover"
                                                       href="{{ route('javno.clanci.prikaz_clanka', $clanak) }}">{{ $clanak->naslov }}</a>
                                                </p>
                                            </td>
                                            <td>
                                                <p class="fw-normal mb-0">{{ date('d.m.Y.', strtotime($clanak->datum)) }}</p>
                                            </td>
                                            <td class="text-end">
                                                <form id="prikaz{{ $clanak->id }}" action="{{ route('admin.clanci.uredjivanje', $clanak->id) }}" method="POST">
                                                    @csrf
                                                </form>
                                                <form id="brisanje{{ $clanak->id }}" action="{{ route('admin.clanci.brisanje', $clanak->id) }}" method="POST">
                                                    @csrf
                                                </form>

                                                <button type="submit" form="prikaz{{ $clanak->id }}" class="btn text-success btn-rounded" title="Uređivanje">
                                                    @include('admin.SVG.uredi')
                                                </button>
                                                <button type="submit" form="brisanje{{ $clanak->id }}" class="btn text-danger btn-rounded" title="Obriši"
                                                        onclick="return confirm('Da li ste sigurni da želite obrisati članak ?')">
                                                    @include('admin.SVG.obrisi')
                                                </button>

                                            </td>
                                        </tr>
                                    @endforeach
                                @endif

                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            @include('layouts.paginationBlok', ['paginator' => $clanci])

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
