<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;

/**
 * Kontroler početnog preusmjeravanja nakon prijave korisnika.
 */
class HomeController extends Controller
{
    /**
     * Ograničava pristup početnom dashboardu samo na prijavljene korisnike.
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Prikazuje internu početnu stranicu nakon prijave.
     */
    public function index(): RedirectResponse
    {
        return redirect()->route('javno.naslovnaStranica');
    }
}
