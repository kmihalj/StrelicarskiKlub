<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\ResetsPasswords;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;

class ResetPasswordController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Password Reset Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling password reset requests
    | and uses a simple trait to include this behavior. You're free to
    | explore this trait and override any methods you wish to tweak.
    |
    */

    use ResetsPasswords;

    /**
     * Where to redirect users after resetting their password.
     *
     * @var string
     */
    protected $redirectTo = '/';

    protected function rules(): array
    {
        return [
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|string|min:8|confirmed',
        ];
    }

    protected function validationErrorMessages(): array
    {
        return [
            'token.required' => 'Token za reset lozinke je obavezan.',
            'email.required' => 'E-mail adresa je obavezna.',
            'email.email' => 'Format e-mail adrese nije ispravan.',
            'password.required' => 'Lozinka je obavezna.',
            'password.min' => 'Lozinka mora imati najmanje :min znakova.',
            'password.confirmed' => 'Potvrda lozinke se ne podudara.',
        ];
    }

    protected function sendResetResponse(Request $request, $response): RedirectResponse
    {
        return redirect($this->redirectPath())->with('status', 'Lozinka je uspješno promijenjena.');
    }

    protected function sendResetFailedResponse(Request $request, $response): RedirectResponse
    {
        $poruka = match ($response) {
            Password::INVALID_TOKEN => 'Poveznica za reset lozinke je nevažeća ili je istekla.',
            Password::INVALID_USER => 'Nismo pronašli korisnika s tom e-mail adresom.',
            default => 'Reset lozinke nije uspio. Pokušajte ponovno.',
        };

        return back()
            ->withInput($request->only('email'))
            ->withErrors(['email' => $poruka]);
    }
}
