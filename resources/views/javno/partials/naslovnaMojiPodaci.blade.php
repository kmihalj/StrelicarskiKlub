{{-- Grupirani blokovi "Moji podaci" za člana/polaznika na naslovnici. --}}
@include('javno.naslovnaLijecnickiStatus', [
    'statusLijecnickiKorisnika' => $statusLijecnickiKorisnika ?? null,
    'prijaveTurniraKorisnika' => $prijaveTurniraKorisnika ?? collect(),
    'prijavljeniClanoviPoTurniru' => $prijavljeniClanoviPoTurniru ?? [],
])
@include('javno.naslovnaSkolaStatus', ['statusSkolaKorisnika' => $statusSkolaKorisnika ?? null])
