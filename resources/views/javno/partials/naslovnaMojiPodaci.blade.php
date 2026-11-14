{{-- Grupirani blokovi "Moji podaci" za člana/polaznika na naslovnici. --}}
@include('javno.naslovnaLijecnickiStatus', [
    'statusLijecnickiKorisnika' => $statusLijecnickiKorisnika ?? null,
    'prijaveTurniraKorisnika' => $prijaveTurniraKorisnika ?? collect(),
    'prijavljeniClanoviPoTurniru' => $prijavljeniClanoviPoTurniru ?? [],
])
@include('javno.naslovnaSkolaStatus', ['statusSkolaKorisnika' => $statusSkolaKorisnika ?? null])
@if(!isset($statusLijecnickiKorisnika) && (int) auth()->user()?->rola === 1)
    <div class="mb-3">
        @include('javno.polazniciSkole.qrProbniLink')
    </div>
@endif
