{{-- Administratorski ekran za uređivanje podataka kluba (naziv, adresa, račun, kontakti). --}}
@extends('layouts.app')
@auth()
    @if(auth()->user()->rola <= 1)
        @section('content')
            <div class="row">
                <div class="col-lg-12 col-md-12 col-sm-12">
                    <div class="container-xxl bg-white shadow">
                        <div class="row justify-content-center p-2 shadow bg-danger fw-bolder">
                            <div class="col-lg-12 text-white d-flex justify-content-between align-items-center">
                                <span>Podaci o klubu</span>
                                <span>
                            <button class="btn btn-sm btn-warning" type="button" data-bs-toggle="modal" data-bs-target="#UnosPodataka">
                                @if(is_null($klub))
                                    Dodaj
                                @else
                                    Uredi
                                @endif podatke
                            </button>
                        </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-lg-12 col-md-12 col-sm-12">
                    <div class="container-xxl bg-white shadow">
                        <div class="row justify-content-start pt-3 shadow">
                            @if(is_null($klub))
                                <div class="col-lg-12 justify-content-start">
                                    <p class="h3">Unesite podatke o klubu</p>
                                </div>
                            @else
                                <div class="col-lg-3 justify-content-start">
                                    <p class="h3">{{$klub->naziv}}</p>
                                    <p class="fw-normal mb-1">
                                        {{$klub->adresa}}<br>
                                        <a class="text-black" href="tel:{{ $klub->telefon }}"> {{ $klub->telefon }}</a>
                                        <a aria-label="Chat on WhatsApp" href="https://wa.me/{{ $klub->telefon }}" target="_blank">@include('admin.SVG.whatsup')</a><br>
                                        <a href="mailto:{{ $klub->email }}">{{ $klub->email }}</a><br>
                                        <span class="link-primary" style="cursor: pointer;" data-bs-toggle="modal" data-bs-target="#QRCode">{{ $klub->racun }}</span>
                                    </p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            @if(!is_null($klub))
                <div class="row">
                    <div class="col-lg-12 col-md-12 col-sm-12">
                        <div class="container-xxl bg-white shadow">
                            <div class="row justify-content-center p-2 shadow bg-danger fw-bolder">
                                <div class="col-lg-12 text-white d-flex justify-content-between align-items-center">
                                    <span>Funkcije u klubu</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-lg-12 col-md-12 col-sm-12">
                        <div class="container-xxl bg-white shadow">
                            <div class="row justify-content-start shadow">
                                <form id="SpremanjeFunkcija" action="{{ route('admin.klub.spremanjeFunkcija') }}" method="POST">
                                    @csrf
                                    <input type="hidden" id="klub_id" name="klub_id" value="{{$klub->id}}">
                                </form>
                                <div class="col-lg-12 mb-2">
                                    <p style="text-align: justify; text-justify: inter-word;">Skupštinu Kluba sačinjavaju svi poslovno sposobni članovi Kluba, te
                                        predstavnik pravne osobe članice Kluba kojeg imenuje osoba ovlaštena za zastupanje pravne osobe ako unutarnjim aktom pravne
                                        osobe nije propisan
                                        drukčiji uvjet imenovanja.</p>
                                </div>
                                <div class="col-lg-6 mb-5">
                                    <label for="predsjednik" class="fw-semibold">Predsjednik kluba (član Upravnog odbora):</label>
                                    <select class="form-select" form="SpremanjeFunkcija" id="predsjednik" name="predsjednik" aria-label="Odabir Predsjednike" required>
                                        <option selected></option>
                                        @foreach($clanovi as $clan)
                                            <option value={{ $clan->id }}
                                     @foreach($clan->funkcijeUklubu as $funkcija)
                                        @if($funkcija->funkcija == "Predsjednik kluba") selected @endif
                                                @endforeach >{{ $clan->Prezime }} {{ $clan->Ime }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-lg-6 mb-5">
                                    <label for="tajnik" class="fw-semibold">Tajnik kluba:</label>
                                    <select class="form-select" form="SpremanjeFunkcija" id="tajnik" name="tajnik" aria-label="Odabir Tajnika" required>
                                        <option selected></option>
                                        @foreach($clanovi as $clan)
                                            <option value={{ $clan->id }}
                                     @foreach($clan->funkcijeUklubu as $funkcija)
                                        @if($funkcija->funkcija == "Tajnik") selected @endif
                                                @endforeach >{{ $clan->Prezime }} {{ $clan->Ime }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                @php $clanUO = 0; @endphp
                                <div class="col-lg-4 mb-2">
                                    <label for="upravni1" class="fw-semibold">Upravni odbor (2 člana):</label>
                                    <select class="form-select" form="SpremanjeFunkcija" id="upravni1" name="upravni1" aria-label="Odabir člana odbora" required>
                                        <option selected></option>
                                        @foreach($clanovi as $clan)
                                            <option value={{ $clan->id }}
                                    @foreach($clan->funkcijeUklubu as $funkcija)
                                        @if($funkcija->funkcija == "Upravni odbor" && $funkcija->redniBroj == 1) selected @endif
                                                @endforeach
                                            >{{ $clan->Prezime }} {{ $clan->Ime }}</option>
                                        @endforeach
                                    </select>
                                    <select class="form-select mt-1" form="SpremanjeFunkcija" id="upravni2" name="upravni2" aria-label="Odabir člana odbora" required>
                                        <option selected></option>
                                        @foreach($clanovi as $clan)
                                            <option value={{ $clan->id }}
                                    @foreach($clan->funkcijeUklubu as $funkcija)
                                        @if($funkcija->funkcija == "Upravni odbor" && $funkcija->redniBroj == 2) selected @endif
                                                @endforeach
                                            >{{ $clan->Prezime }} {{ $clan->Ime }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-lg-4 mb-2">
                                    <label for="nadzorni1" class="fw-semibold">Nadzorni odbor (predsjednik + 2 člana):</label>
                                    <select class="form-select" form="SpremanjeFunkcija" id="nadzorni1" name="nadzorni1" aria-label="Odabir člana odbora" required>
                                        <option selected></option>
                                        @foreach($clanovi as $clan)
                                            <option value={{ $clan->id }}
                                    @foreach($clan->funkcijeUklubu as $funkcija)
                                        @if($funkcija->funkcija == "Nadzorni odbor" && $funkcija->redniBroj == 1) selected @endif
                                                @endforeach
                                            >{{ $clan->Prezime }} {{ $clan->Ime }}</option>
                                        @endforeach
                                    </select>
                                    <select class="form-select mt-1" form="SpremanjeFunkcija" id="nadzorni2" name="nadzorni2" aria-label="Odabir člana odbora" required>
                                        <option selected></option>
                                        @foreach($clanovi as $clan)
                                            <option value={{ $clan->id }}
                                    @foreach($clan->funkcijeUklubu as $funkcija)
                                        @if($funkcija->funkcija == "Nadzorni odbor" && $funkcija->redniBroj == 2) selected @endif
                                                @endforeach
                                            >{{ $clan->Prezime }} {{ $clan->Ime }}</option>
                                        @endforeach
                                    </select>
                                    <select class="form-select mt-1" form="SpremanjeFunkcija" id="nadzorni3" name="nadzorni3" aria-label="Odabir člana odbora" required>
                                        <option selected></option>
                                        @foreach($clanovi as $clan)
                                            <option value={{ $clan->id }}
                                    @foreach($clan->funkcijeUklubu as $funkcija)
                                        @if($funkcija->funkcija == "Nadzorni odbor" && $funkcija->redniBroj == 3) selected @endif
                                                @endforeach
                                            >{{ $clan->Prezime }} {{ $clan->Ime }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-lg-4 mb-3">
                                    <label for="arbitrazni1" class="fw-semibold">Arbitražno vijeće (3 člana):</label>
                                    <select class="form-select" form="SpremanjeFunkcija" id="arbitrazni1" name="arbitrazni1" aria-label="Odabir člana odbora" required>
                                        <option selected></option>
                                        @foreach($clanovi as $clan)
                                            <option value={{ $clan->id }}
                                    @foreach($clan->funkcijeUklubu as $funkcija)
                                        @if($funkcija->funkcija == "Arbitražno vijeće" && $funkcija->redniBroj == 1) selected @endif
                                                @endforeach
                                            >{{ $clan->Prezime }} {{ $clan->Ime }}</option>
                                        @endforeach
                                    </select>
                                    <select class="form-select mt-1" form="SpremanjeFunkcija" id="arbitrazni2" name="arbitrazni2" aria-label="Odabir člana odbora"
                                            required>
                                        <option selected></option>
                                        @foreach($clanovi as $clan)
                                            <option value={{ $clan->id }}
                                    @foreach($clan->funkcijeUklubu as $funkcija)
                                        @if($funkcija->funkcija == "Arbitražno vijeće" && $funkcija->redniBroj == 2) selected @endif
                                                @endforeach
                                            >{{ $clan->Prezime }} {{ $clan->Ime }}</option>
                                        @endforeach
                                    </select>
                                    <select class="form-select mt-1" form="SpremanjeFunkcija" id="arbitrazni3" name="arbitrazni3" aria-label="Odabir člana odbora"
                                            required>
                                        <option selected></option>
                                        @foreach($clanovi as $clan)
                                            <option value={{ $clan->id }}
                                    @foreach($clan->funkcijeUklubu as $funkcija)
                                        @if($funkcija->funkcija == "Arbitražno vijeće" && $funkcija->redniBroj == 3) selected @endif
                                                @endforeach
                                            >{{ $clan->Prezime }} {{ $clan->Ime }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-lg-12 col-md-12 col-12 mb-2 text-end">
                                    <button type="submit" form="SpremanjeFunkcija" class="btn btn-danger">Spremi</button>
                                </div>
                                <!-- TRENERI -->
                                <hr>
                                <div class="col-lg-4 mb-3">
                                    <form id="SpremanjeTrenera" action="{{ route('admin.klub.spremanjeTrenera') }}" method="POST">
                                        @csrf
                                        <input type="hidden" id="klub_id" name="klub_id" value="{{$klub->id}}">
                                    </form>
                                    <label for="trener" class="fw-semibold">Treneri (dodavanje):</label>
                                    <div class="input-group mb-3">
                                        <select class="form-select" form="SpremanjeTrenera" id="trener" name="trener" aria-label="Odabir člana odbora"
                                                aria-describedby="button-addon2" required>
                                            <option selected></option>
                                            @foreach($clanovi as $clan)
                                                <option value={{ $clan->id }}>{{ $clan->Prezime }} {{ $clan->Ime }}</option>
                                            @endforeach
                                        </select>
                                        <button class="btn btn-outline-danger" type="submit" form="SpremanjeTrenera" d="button-addon2">Spremi</button>
                                    </div>
                                </div>
                                <div class="col-lg-4 mb-3 align-self-center">
                                    <div class="table-responsive">
                                        <table class="table table-sm align-middle table-borderless">
                                            @foreach($treneri as $trener)
                                                <form id="brisanje{{ $trener->id }}" action="{{ route('admin.klub.brisanjeTrenera', $trener->id) }}" method="POST">
                                                    @csrf
                                                </form>
                                                <tr>
                                                    <td class="bg-white"><a class="link-dark link-offset-2 link-underline-opacity-0 link-underline-opacity-50-hover fw-bold mb-1"
                                                                            href="{{ route('javno.clanovi.prikaz_clana', $trener->clan) }}">{{ $trener->clan->Ime }}  {{ $trener->clan->Prezime }}</a>
                                                    </td>
                                                    <td class="bg-white">
                                                        <button type="submit" form="brisanje{{ $trener->id }}" class="btn text-danger btn-rounded" title="Obriši"
                                                                onclick="return confirm('Da li ste sigurni da želite obrisati trenera ?')">
                                                            @include('admin.SVG.obrisi')
                                                        </button>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </table>
                                    </div>
                                </div>
                                <!-- 'Likvidator' -->

                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-12 col-md-12 col-sm-12">
                        <div class="container-xxl bg-white shadow">
                            <div class="row justify-content-center p-2 shadow bg-danger fw-bolder">
                                <div class="col-lg-12 text-white d-flex justify-content-between align-items-center">
                                    <span>Dokumenti kluba</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-12 col-md-12 col-sm-12">
                        <div class="container-xxl bg-white shadow">
                            <div class="row justify-content-start shadow">
                                @if($klub->dokumenti->count() == 0)
                                    <div class="col-lg-12 justify-content-start pt-3">
                                        <p style="text-align: justify; text-justify: inter-word;">Nema spremljenih dokumenata</p>
                                    </div>
                                @else
                                    @foreach($klub->dokumenti as $dokument)
                                        <div class="row pt-3">
                                            <div class="col-auto mb-2 align-self-center">
                                                <div class="form-check form-switch ">
                                                    <input class="form-check-input" form="spremi{{ $dokument->id }}" type="checkbox" id="javno" name="javno" @if($dokument->javno) value=true checked @else value=false @endif onchange="spremi{{ $dokument->id }}.submit()">
                                                    <label class="form-check-label" for="javno">Javno dostupno</label>
                                                </div>
                                            </div>
                                            <div class="col-auto mb-2 align-self-center">
                                                <label for="opis">Opis:</label>
                                            </div>
                                            <div class="col-6 mb-2 align-self-center">
                                                <input type="text" form="spremi{{ $dokument->id }}" class="form-control" name="opis" id="opis" value="{{$dokument->opis}}">
                                            </div>
                                            <div class="col-auto mb-2 align-self-center">
                                                <a href="{{ asset('storage/klub/' . $dokument->link_text) }}" target="_blank">{{$dokument->link_text}}</a>
                                            </div>
                                            <div class="col mb-2 float-end text-end">
                                                <form id="spremi{{ $dokument->id }}" action="{{ route('admin.klub.updateMedij') }}" method="POST">
                                                    @csrf
                                                    <input type="hidden" id="dokument_id" name="dokument_id" value="{{$dokument->id}}">
                                                </form>
                                                <form id="brisanje{{ $dokument->id }}" action="{{ route('admin.klub.brisanjeMedija') }}" method="POST">
                                                    @csrf
                                                    <input type="hidden" id="medijBrisanje" name="medijBrisanje" value="{{$dokument->id}}">
                                                </form>
                                                <button type="submit" form="spremi{{ $dokument->id }}" class="btn text-success btn-rounded" title="Spremi">
                                                    @include('admin.SVG.unos')
                                                </button>
                                                <button type="submit" form="brisanje{{ $dokument->id }}" class="btn text-danger btn-rounded" title="Obriši"
                                                        onclick="return confirm('Da li ste sigurni da želite obrisati dokument ?')">
                                                    @include('admin.SVG.obrisi')
                                                </button>
                                            </div>
                                        </div>
                                    @endforeach
                                @endif

                            </div>
                            <div class="row justify-content-center p-2 shadow bg-warning fw-bolder">
                                <div class="col-lg-12 text-white d-flex justify-content-between align-items-center">
                                    <span>Dodavanje slika <i>(.jpg, .jpeg, .png, .webp)</i> dokumenata <i>(.doc, .docx, .pdf, .xls, .xlsx)</i> i/ili videa <i>(.mp4)</i></span>
                                </div>
                            </div>
                            <div class="row p-2 bg-white">
                                <div class="col-lg-12">
                                    <form id="uploadMedija" action="{{ route('admin.klub.uploadMedija') }}" enctype="multipart/form-data" method="POST">
                                        @csrf
                                        <input type="hidden" id="klub_id" name="klub_id" value="{{$klub->id}}">
                                        <div class="row align-items-center mt-3">
                                            <div class="col-lg-12 mb-2 align-self-end">
                                                <div class="form-check form-switch ">
                                                    <input class="form-check-input" type="checkbox" id="javno" name="javno" value=true>
                                                    <label class="form-check-label" for="javno">Javno dostupno</label>
                                                </div>
                                            </div>
                                            <div class="col-lg-auto mb-2">
                                                <label for="opis">Opis:</label>
                                            </div>
                                            <div class="col-lg-6 mb-2">
                                                <input type="text" class="form-control" name="opis" id="opis" required>
                                            </div>
                                            <div class="col-lg-auto pb-2">
                                                <input class="form-control" type="file" id="medij" name="medij">
                                            </div>
                                            <div class="col-lg-auto pb-2">
                                                <button type="submit" class="btn btn-primary float-end">Upload</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
            @include('admin.klub.QRCodeModal')
            @include('admin.klub.unosPodatakaZaKlubModal')
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



