<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\SendsPasswordResetEmails;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;

/**
 * Laravel kontroler za slanje poveznice za reset zaboravljene lozinke.
 */
class ForgotPasswordController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Password Reset Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling password reset emails and
    | includes a trait which assists in sending these notifications from
    | your application to your users. Feel free to explore this trait.
    |
    */

    use SendsPasswordResetEmails;

    /**
     * Validira e-mail prije slanja zahtjeva za reset lozinke.
     */
    protected function validateEmail(Request $request): void
    {
        $request->validate([
            'email' => 'required|email',
        ], [
            'email.required' => 'E-mail adresa je obavezna.',
            'email.email' => 'Format e-mail adrese nije ispravan.',
        ]);
    }

    /**
     * Vraća korisničku poruku nakon uspješnog slanja poveznice za reset lozinke.
     */
    protected function sendResetLinkResponse(Request $request, $response): RedirectResponse
    {
        return back()->with('status', 'Poslana je poveznica za reset lozinke na unesenu e-mail adresu.');
    }

    /**
     * Prevodi tehnički kod greške u razumljivu poruku za korisnika kada slanje nije uspjelo.
     */
    protected function sendResetLinkFailedResponse(Request $request, $response): RedirectResponse
    {
        $poruka = match ($response) {
            Password::INVALID_USER => 'Nismo pronašli korisnika s tom e-mail adresom.',
            Password::RESET_THROTTLED => 'Zahtjev je već poslan. Pričekajte prije ponovnog pokušaja.',
            default => 'Zahtjev za reset lozinke nije uspio. Pokušajte ponovno.',
        };

        return back()->withErrors(['email' => $poruka]);
    }
}
