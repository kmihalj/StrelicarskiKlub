<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;

/**
 * Laravel kontroler za prijavu i odjavu korisnika.
 */
class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected string $redirectTo = '/';

    /**
     * Omogućuje prijavu gostima, a odjavu samo već prijavljenim korisnicima.
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    /**
     * Nakon uspješne prijave preusmjerava bootstrap admina na upravljanje korisnicima.
     */
    protected function authenticated(Request $request, $user)
    {
        if ($user->is_bootstrap_admin ?? false) {
            return redirect()->route('admin.korisnici.index');
        }

        return null;
    }
}
