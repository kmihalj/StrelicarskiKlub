{{-- Grupirani blokovi "Moji podaci" za člana/polaznika na naslovnici. --}}
@include('javno.naslovnaLijecnickiStatus', ['statusLijecnickiKorisnika' => $statusLijecnickiKorisnika ?? null])
@include('javno.naslovnaSkolaStatus', ['statusSkolaKorisnika' => $statusSkolaKorisnika ?? null])

