<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\KorisnikClanService;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class RegisterController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation. By default this controller uses a trait to
    | provide this functionality without requiring any additional code.
    |
    */

    use RegistersUsers;

    /**
     * Where to redirect users after registration.
     *
     * @var string
     */
    protected $redirectTo = '/';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest');
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        return Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'oib' => ['required', 'digits:11', 'unique:users,oib'],
            'br_telefona' => ['required', 'regex:/^\+385\d{8,9}$/', 'max:13', 'unique:users,br_telefona'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'oib.required' => 'OIB je obavezan.',
            'oib.digits' => 'OIB mora imati točno 11 znamenki.',
            'oib.unique' => 'OIB je već registriran.',
            'br_telefona.required' => 'Broj telefona je obavezan.',
            'br_telefona.regex' => 'Broj telefona mora biti u formatu +385xxxxxxxxx.',
            'br_telefona.unique' => 'Broj telefona je već registriran.',
        ]);
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array  $data
     * @return \App\Models\User
     */
    protected function create(array $data)
    {
        $matcher = app(KorisnikClanService::class);
        $normaliziraniTelefon = $matcher->normalizirajTelefonZaPohranu($data['br_telefona']);
        $normaliziraniOib = $matcher->normalizirajOib($data['oib']);

        $user = User::create([
            'name' => trim($data['name']),
            'email' => mb_strtolower(trim($data['email'])),
            'oib' => $normaliziraniOib,
            'br_telefona' => $normaliziraniTelefon,
            'password' => Hash::make($data['password']),
            'rola' => 3,
        ]);

        $clan = $matcher->pronadiClanaZaRegistraciju(
            (string)$user->name,
            (string)$user->email,
            (string)$user->oib,
            (string)$user->br_telefona
        );

        if ($clan !== null && $matcher->clanJeSlobodan((int)$clan->id, (int)$user->id)) {
            $matcher->poveziKorisnika($user, $clan);
        } else {
            $polaznik = $matcher->pronadiPolaznikaZaRegistraciju(
                (string)$user->name,
                (string)$user->email,
                (string)$user->oib,
                (string)$user->br_telefona
            );

            if ($polaznik !== null && $matcher->polaznikJeSlobodan((int)$polaznik->id, (int)$user->id)) {
                $matcher->poveziKorisnikaSPolaznikom($user, $polaznik);
            } else {
                $matcher->odspojiKorisnika($user);
            }
        }

        return $user;
    }
}
