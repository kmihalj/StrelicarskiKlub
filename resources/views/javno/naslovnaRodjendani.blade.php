@if(isset($rodendaniDanas) && $rodendaniDanas->isNotEmpty())
    <div class="row justify-content-center p-2 shadow bg-danger fw-bolder">
        <div class="col-lg-12 text-white">
            Ro&#273;endanska &#269;estitka
        </div>
    </div>
    <div class="row justify-content-center mb-3 shadow birthday-card-wrap">
        <div class="col-lg-12 birthday-card-body">
            <div class="birthday-card-balloons" aria-hidden="true">
                <span class="birthday-balloon birthday-balloon-1"></span>
                <span class="birthday-balloon birthday-balloon-2"></span>
                <span class="birthday-balloon birthday-balloon-3"></span>
                <span class="birthday-balloon birthday-balloon-4"></span>
                <span class="birthday-balloon birthday-balloon-5"></span>
            </div>

            <p class="birthday-card-text text-center">
                @foreach($rodendaniDanas as $clan)
                    <a class="birthday-card-link" href="{{ route('javno.clanovi.prikaz_clana', $clan) }}">
                        {{ trim((string)$clan->Ime) }} {{ trim((string)$clan->Prezime) }}
                    </a>@if(!$loop->last), @endif
                @endforeach
            </p>

            <p class="birthday-card-greeting mb-0 text-center" aria-label="Sretan rodendan">
                <span class="birthday-letter">S</span><span class="birthday-letter">r</span><span class="birthday-letter">E</span><span class="birthday-letter">t</span><span class="birthday-letter">A</span><span class="birthday-letter">n</span>
                <span class="birthday-letter birthday-space">&nbsp;</span>
                <span class="birthday-letter">R</span><span class="birthday-letter">o</span><span class="birthday-letter">&#272;</span><span class="birthday-letter">e</span><span class="birthday-letter">N</span><span class="birthday-letter">d</span><span class="birthday-letter">A</span><span class="birthday-letter">n</span>
                <span class="birthday-letter birthday-space">&nbsp;</span>
                <span class="birthday-letter">!</span><span class="birthday-letter">!</span><span class="birthday-letter">!</span>
            </p>
        </div>
    </div>
@endif

@once
    <style>
        .birthday-card-wrap {
            position: relative;
            overflow: hidden;
            background: var(--bs-body-bg, #ffffff);
            color: var(--bs-body-color, #212529);
        }

        .birthday-card-body {
            position: relative;
            min-height: 7rem;
            padding: .9rem .8rem .95rem;
            background:
                radial-gradient(circle at 15% 18%, rgba(255, 209, 102, .3), rgba(255, 255, 255, 0) 42%),
                radial-gradient(circle at 85% 30%, rgba(86, 204, 242, .24), rgba(255, 255, 255, 0) 44%),
                var(--bs-body-bg, #ffffff);
            border-radius: .2rem;
        }

        .birthday-card-text {
            position: relative;
            z-index: 2;
            margin-bottom: .45rem;
            font-size: 1.15rem;
            font-weight: 600;
            line-height: 1.45;
        }

        .birthday-card-link {
            text-decoration: none;
            font-weight: 700;
            color: var(--theme-link-color, var(--bs-primary));
        }

        .birthday-card-link:hover {
            color: var(--theme-link-hover-color, var(--bs-primary));
            text-decoration: underline;
        }

        .birthday-card-greeting {
            position: relative;
            z-index: 2;
            font-size: 1.8rem;
            font-weight: 800;
            letter-spacing: .06em;
            line-height: 1.25;
        }

        .birthday-letter {
            display: inline-block;
            animation: birthday-letter-hop 1.8s ease-in-out infinite;
            text-shadow: 0 2px 0 rgba(0, 0, 0, .08);
        }

        .birthday-space {
            width: .18em;
        }

        .birthday-letter:nth-child(8n + 1) { color: #ff4757; animation-delay: .0s; }
        .birthday-letter:nth-child(8n + 2) { color: #ffa502; animation-delay: .1s; }
        .birthday-letter:nth-child(8n + 3) { color: #2ed573; animation-delay: .2s; }
        .birthday-letter:nth-child(8n + 4) { color: #1e90ff; animation-delay: .3s; }
        .birthday-letter:nth-child(8n + 5) { color: #3742fa; animation-delay: .4s; }
        .birthday-letter:nth-child(8n + 6) { color: #e84393; animation-delay: .5s; }
        .birthday-letter:nth-child(8n + 7) { color: #00b894; animation-delay: .6s; }
        .birthday-letter:nth-child(8n + 8) { color: #ff6b81; animation-delay: .7s; }

        .birthday-card-balloons {
            position: absolute;
            inset: 0;
            pointer-events: none;
            z-index: 1;
            opacity: .78;
        }

        .birthday-balloon {
            position: absolute;
            bottom: .3rem;
            width: 1.8rem;
            height: 2.2rem;
            border-radius: 48% 52% 44% 56% / 56% 56% 44% 44%;
            animation: birthday-balloon-float 5.8s ease-in-out infinite;
        }

        .birthday-balloon::after {
            content: "";
            position: absolute;
            left: 50%;
            top: 100%;
            width: 1px;
            height: 1.1rem;
            background: rgba(0, 0, 0, .2);
            transform: translateX(-50%);
        }

        .birthday-balloon-1 { left: 4%; background: #ff6b81; animation-delay: .0s; }
        .birthday-balloon-2 { left: 18%; background: #feca57; animation-delay: .8s; }
        .birthday-balloon-3 { left: 74%; background: #1dd1a1; animation-delay: 1.2s; }
        .birthday-balloon-4 { left: 86%; background: #54a0ff; animation-delay: .4s; }
        .birthday-balloon-5 { left: 58%; background: #ff9f43; animation-delay: 1.6s; }

        .theme-dark .birthday-card-body {
            background:
                radial-gradient(circle at 15% 18%, rgba(255, 209, 102, .14), rgba(0, 0, 0, 0) 42%),
                radial-gradient(circle at 85% 30%, rgba(86, 204, 242, .12), rgba(0, 0, 0, 0) 44%),
                var(--bs-dark-bg-subtle, #1f2329);
        }

        .theme-dark .birthday-balloon::after {
            background: rgba(255, 255, 255, .36);
        }

        @keyframes birthday-letter-hop {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-.28rem); }
        }

        @keyframes birthday-balloon-float {
            0%, 100% { transform: translateY(0) rotate(-2deg); }
            50% { transform: translateY(-1rem) rotate(3deg); }
        }

        @media (max-width: 767.98px) {
            .birthday-card-text {
                font-size: 1rem;
            }

            .birthday-card-greeting {
                font-size: 1.35rem;
                letter-spacing: .03em;
            }
        }
    </style>
@endonce
