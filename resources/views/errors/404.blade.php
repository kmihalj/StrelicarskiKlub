<!DOCTYPE html>
<html lang="hr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>404 - Stranica nije pronađena</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            text-align: center;
            padding: 50px;
        }
        .container {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            display: inline-block;
            margin: auto;
        }
        h1 {
            font-size: 36px;
            margin-bottom: 20px;
        }
        p {
            font-size: 20px;
            font-weight: bold;
            color: #333;
        }
        .error-message {
            color: red;
        }
        a {
            color: #0066cc;
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }
        .logo {
            width: auto;
            height: 100px;
        }
        .countdown {
            font-size: 24px;
        }
        .countdown.yellow {
            color: yellow;
        }
        .countdown.red {
            color: red;
        }
        .countdown.blue {
            color: blue;
        }
        .countdown.black {
            color: black;
        }
        .countdown.white {
            color: white;
            text-shadow: 1px 1px 0 #000, -1px -1px 0 #000, 1px -1px 0 #000, -1px 1px 0 #000;
        }
        .countdown.hit {
            color: red;
            font-size: 32px;
        }
    </style>
    <script>
        function updateCountdown(seconds) {
            var countdownElement = document.getElementById('countdown');
            if (seconds > 0) {
                countdownElement.innerHTML = "Automatski ćete biti preusmjereni za " + seconds + " sekundi.";
                countdownElement.className = 'countdown';

                if (seconds >= 9) {
                    countdownElement.classList.add('yellow');
                } else if (seconds >= 7) {
                    countdownElement.classList.add('red');
                } else if (seconds >= 5) {
                    countdownElement.classList.add('blue');
                } else if (seconds >= 3) {
                    countdownElement.classList.add('black');
                } else if (seconds >= 1) {
                    countdownElement.classList.add('white');
                }
            } else {
                countdownElement.innerHTML = "POGODAK!!!";
                countdownElement.className = 'countdown hit';
            }
        }

        function redirectToHomepage() {
            var seconds = 10;
            var homepageUrl = @json(url('/'));
            updateCountdown(seconds);
            var countdown = setInterval(function() {
                seconds--;
                updateCountdown(seconds);
                if (seconds < 0) {
                    clearInterval(countdown);
                    window.location.href = homepageUrl;
                }
            }, 1000);
        }
        window.onload = redirectToHomepage;
    </script>
</head>
<body>
<div class="container">
    <img src="{{ asset('storage/slike/logo.png') }}" alt="Logo" class="logo">
    <h1>404 - Stranica nije pronađena</h1>
    <p class="error-message">Izgleda da ste promašili metu... ono što ste "gađali" više nije tu.</p>
    <p>Provjerite <a href="{{ url('/') }}">{{ url('/') }}</a></p>
    <p id="countdown" class="countdown yellow">Automatski ćete biti preusmjereni za 10 sekundi.</p>
</div>
</body>
</html>
