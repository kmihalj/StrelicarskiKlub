<tr class="js-clan-row"
    data-ime="{{ trim((string) $clan->Ime) }}"
    data-prezime="{{ trim((string) $clan->Prezime) }}"
    data-datum-rodjenja="{{ $clan->datum_rodjenja ? strtotime($clan->datum_rodjenja) : '' }}"
    data-godina-registracije="{{ is_null($clan->clan_od) ? '' : (int) $clan->clan_od }}"
    data-lijecnicki-do="{{ $clan->lijecnicki_do ? strtotime($clan->lijecnicki_do) : '' }}">
    @php
        $showPaymentColumn = (bool)($showPaymentColumn ?? false);
        $paymentStatus = ($paymentStatusByClan[(int)$clan->id] ?? null);
    @endphp
    @auth()
        @if((int)auth()->user()->rola === 1)
            <td class="text-center align-middle" style="width: 44px;">
                <a href="{{ route('admin.clanovi.prikaz_clana', $clan) }}"
                   class="link-secondary text-decoration-none d-inline-flex align-items-center justify-content-center"
                   title="Uredi člana"
                   aria-label="Uredi člana"
                   style="line-height: 1;">
                    @include('admin.SVG.uredi')
                </a>
            </td>
        @endif
    @endauth
    <td>
        <div class="d-flex align-items-center">
            @if((empty($clan->slika_link)))
                <img src="@if( $clan->spol == "M") {{ asset('storage/slike/avatar_m.png') }} @else {{ asset('storage/slike/avatar_f.png') }} @endif" alt=""
                     style="width: 45px; height: 45px" class="rounded-circle"/>
            @else
                <img src="{{ asset('storage/slike_clanova/' . $clan->slika_link) }}" alt="" style="height: 45px" class="rounded-circle"/>
            @endif

            <div class="ms-3">
                {{--@foreach($clan->funkcijeUklubu as $funkcija)
                    @if($funkcija->funkcija == "Predsjednik kluba")
                        <p class="fw-light text-success mb-1">{{ $funkcija->funkcija }}</p>
                    @endif
                @endforeach--}}
                <a class="js-ime-prezime-link link-primary link-offset-2 link-underline-opacity-0 link-underline-opacity-0-hover fw-bold mb-1"
                   data-prezime-ime="{{ trim((string) $clan->Prezime) }} {{ trim((string) $clan->Ime) }}"
                   data-ime-prezime="{{ trim((string) $clan->Ime) }} {{ trim((string) $clan->Prezime) }}"
                   href="{{ route('javno.clanovi.prikaz_clana', $clan) }}">{{ trim((string) $clan->Prezime) }} {{ trim((string) $clan->Ime) }}</a>
                @auth()
                    @if(auth()->user()->imaPravoAdminOrMember())
                        <br><a class="text-muted mb-0" href="mailto:{{ $clan->email }}">{{ $clan->email }}
                        </a>
                    @endif
                @endauth
            </div>
        </div>
    </td>
    @auth()
        @if(auth()->user()->imaPravoAdminOrMember())
            <td>
                <p class="fw-normal mb-1"><a href="tel:{{ $clan->br_telefona }}"> {{ $clan->br_telefona }}</a>
                    @if(!is_null($clan->br_telefona))
                        <a aria-label="Chat on WhatsApp" href="https://wa.me/{{ $clan->br_telefona }}" target="_blank">@include('admin.SVG.whatsup')</a><br>
                    @endif
                </p>
            </td>
            <td>
                <p class="fw-bold mb-1">{{ date('d.m.Y.', strtotime($clan->datum_rodjenja)) }}</p>
            </td>
        @endif
    @endauth
    <td>
        <p class="fw-bold mb-1">
            @isset($clan->clan_od)
                {{ $clan->clan_od }}
            @else
                -
            @endisset</p>
        @auth()
            @if(auth()->user()->imaPravoAdminOrMember())
                <p class="fw-normal mb-1">{{ $clan->broj_licence }}</p>
            @endif
        @endauth
    </td>
    <td>
        @isset($clan->lijecnicki_do)
            <p class="fw-normal mb-1">
                {{ date('d.m.Y.', strtotime($clan->lijecnicki_do)) }}<br>
                @php
                    $from=date_create(date('Y-m-d'));
                    $to=date_create($clan->lijecnicki_do);
                    $diff=date_diff($from, $to);
                    if ($diff->format('%R') == "-") {
                        echo '<span class="text-danger fw-bold"><i>isteklo</i></span>';
                    }
                    else {
                        if ($diff->format('%a') < 30) {
                            echo '<span class="text-danger fw-bold">' . $diff->format('%a dana') . '</span>';
                        }
                        else {
                            echo $diff->format('%a dana');
                        }
                    }
                @endphp
            </p>
        @else
            <p class="fw-normal mb-1">-</p>
        @endisset
    </td>
    @if($showPaymentColumn)
        <td>
            @if(is_array($paymentStatus))
                @if(($paymentStatus['state'] ?? '') === 'paid')
                    <span class="text-success fw-bold" title="Sve podmireno">&#10003;</span>
                @elseif(($paymentStatus['state'] ?? '') === 'debt')
                    <span class="text-danger fw-bold">
                        {{ number_format((float)($paymentStatus['amount'] ?? 0), 2, ',', '.') }} EUR
                    </span>
                @endif
            @endif
        </td>
    @endif
</tr>
