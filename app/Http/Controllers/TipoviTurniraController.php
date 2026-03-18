<?php

namespace App\Http\Controllers;

use App\Models\Kategorije;
use App\Models\PoljaZaTipoveTurnira;
use App\Models\Stilovi;
use App\Models\TipoviTurnira;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Throwable;

/**
 * Admin kontroler za tipove turnira, stilove, kategorije i polja unosa rezultata.
 */
class TipoviTurniraController extends Controller
{
    /**
     * Prikazuje administracijski pregled tipova turnira, kategorija, stilova i pripadajućih polja.
     * @noinspection PhpMissingReturnTypeInspection
     * @noinspection PhpUndefinedMethodInspection
     */
    public function index()
    {
        $tipoviTurnira = TipoviTurnira::orderBy('naziv')->get();
        $kategorije = Kategorije::orderBy('spol')->orderBy('naziv')->get();
        $stilovi = Stilovi::get();
        return view('admin.turniri.pocetna', ['tipoviTurnira' => $tipoviTurnira, 'kategorije' => $kategorije, 'stilovi'=>$stilovi]);
    }

    /**
     * Sprema novi tip turnira ili izmjene postojećeg tipa.
     */
    public function spremi_tipoviTurnira(Request $request)
    {
        $rules = array('naziv_tipa_turnira_za_unos' => 'required');
        $messages = array('naziv_tipa_turnira_za_unos.required' => 'Naziv tipa turnira ne može biti prazan.');
        $validator = Validator::make($request->all(), $rules, $messages);
        if ($validator->errors()->isEmpty()) {
            try {
                $tipTurnira = new TipoviTurnira();
                $tipTurnira->naziv = $request->input('naziv_tipa_turnira_za_unos');
                $tipTurnira->save();
                return redirect()->route('admin.turniri.naslovna');
            } catch (Throwable $e) {
                return redirect()->route('admin.turniri.naslovna')->with('error', $e->getMessage());
            }
        } else {
            return redirect()->route('admin.turniri.naslovna')->with('error', $validator->errors()->first());
        }
    }

    /** @noinspection PhpUndefinedMethodInspection */
    public function obrisi_tipoviTurnira(int $id): RedirectResponse
    {
        $tipTurnira = TipoviTurnira::findOrFail($id);
        $tipTurnira->delete();
        return redirect()->route('admin.turniri.naslovna');
    }

    /** @noinspection PhpMissingReturnTypeInspection
     * @noinspection PhpUndefinedMethodInspection
     */
    public function odabir_tipa_turnira(Request $request)
    {
        $tipoviTurnira = TipoviTurnira::orderBy('naziv')->get();
        $kategorije = Kategorije::orderBy('spol')->orderBy('naziv')->get();
        $stilovi = Stilovi::get();
        $tipTurnira = TipoviTurnira::findOrFail((int)$request->input('odabir_tipa_turnira'));
        return view('admin.turniri.pocetna', ['tipoviTurnira' => $tipoviTurnira, 'kategorije' => $kategorije, 'stilovi'=>$stilovi, 'odabraniTipTurnira' => $tipTurnira]);
    }

    /** @noinspection PhpMissingReturnTypeInspection
     * @noinspection PhpUndefinedMethodInspection
     */
    public function spremi_poljeZatipTurnira(Request $request)
    {
        $tipoviTurnira = TipoviTurnira::orderBy('naziv')->get();
        $kategorije = Kategorije::orderBy('spol')->orderBy('naziv')->get();
        $stilovi = Stilovi::get();
        $tipTurnira = TipoviTurnira::findOrFail((int)$request->input('odabir_tipa_turnira'));
        $rules = array('naziv_polja_za_tip_turnira_unos' => 'required');
        $messages = array('naziv_polja_za_tip_turnira_unos.required' => 'Naziv polja za tip turnira ne može biti prazan.');
        $validator = Validator::make($request->all(), $rules, $messages);
        if ($validator->errors()->isEmpty()) {
            try {
                $poljeZatipTurnira = new PoljaZaTipoveTurnira();
                $poljeZatipTurnira->naziv = $request->input('naziv_polja_za_tip_turnira_unos');
                $poljeZatipTurnira->tipovi_turnira_id = $request->input('odabir_tipa_turnira');
                $poljeZatipTurnira->save();
                return view('admin.turniri.pocetna', ['tipoviTurnira' => $tipoviTurnira, 'kategorije' => $kategorije, 'stilovi'=>$stilovi, 'odabraniTipTurnira' => $tipTurnira]);
            } catch (Throwable $e) {
                return view('admin.turniri.pocetna', ['tipoviTurnira' => $tipoviTurnira, 'kategorije' => $kategorije, 'stilovi'=>$stilovi, 'odabraniTipTurnira' => $tipTurnira, 'error' => $e->getMessage()]);
            }
        } else {
            return view('admin.turniri.pocetna', ['tipoviTurnira' => $tipoviTurnira, 'kategorije' => $kategorije, 'stilovi'=>$stilovi, 'odabraniTipTurnira' => $tipTurnira, 'error' => $validator->errors()->first()]);
        }
    }

    /** @noinspection PhpMissingReturnTypeInspection
     * @noinspection PhpUndefinedMethodInspection
     */
    public function obrisi_polje_za_tipTurnira(Request $request)
    {
        $tipoviTurnira = TipoviTurnira::orderBy('naziv')->get();
        $kategorije = Kategorije::orderBy('spol')->orderBy('naziv')->get();
        $stilovi = Stilovi::get();
        $polje = PoljaZaTipoveTurnira::findOrFail((int)$request->input('zadnje_polje'));
        $tipTurnira = TipoviTurnira::findOrFail((int)$polje->tipovi_turnira_id);
        $polje->delete();
        return view('admin.turniri.pocetna', ['tipoviTurnira' => $tipoviTurnira, 'kategorije' => $kategorije, 'stilovi'=>$stilovi, 'odabraniTipTurnira' => $tipTurnira]);
    }

    /**
     * Sprema novu kategoriju natjecanja ili izmjene postojeće kategorije.
     */
    public function spremi_kategoriju(Request $request)
    {
        $rules = ['naziv_kategorije' => 'required', 'spol_kategorija' => 'required'];
        $messages = [
            'naziv_kategorije.required' => 'Mora biti unesen naziv kategorije.',
            'spol_kategorija.required' => 'Mora biti odabran spol za kategoriju.'
            ];
        $validator = Validator::make($request->all(), $rules, $messages);
        if ($validator->errors()->isEmpty()) {
            try {
                $kategorija = new Kategorije();
                $kategorija->spol = $request->input('spol_kategorija');
                $kategorija->naziv = $request->input('naziv_kategorije');
                $kategorija->save();
                return redirect()->route('admin.turniri.naslovna');
            } catch (Throwable $e) {
                return redirect()->route('admin.turniri.naslovna')->with('error', $e->getMessage());
            }
        } else {
            return redirect()->route('admin.turniri.naslovna')->with('error', $validator->errors()->all());
        }
    }

    /** @noinspection PhpUndefinedMethodInspection */
    public function obrisi_kategoriju(int $id): RedirectResponse
    {
        $kategorija = Kategorije::findOrFail($id);
        $kategorija->delete();
        return redirect()->route('admin.turniri.naslovna');
    }

    /**
     * Sprema stil luka koji se koristi u unosu rezultata i filtriranju prikaza.
     */
    public function spremi_stil_luka(Request $request)
    {
        $rules = array('naziv_stila_luka_za_unos' => 'required');
        $messages = array('naziv_stila_luka_za_unos.required' => 'Naziv stila luka ne može biti prazan.');
        $validator = Validator::make($request->all(), $rules, $messages);
        if ($validator->errors()->isEmpty()) {
            try {
                $stilLuka = new Stilovi();
                $stilLuka->naziv = $request->input('naziv_stila_luka_za_unos');
                $stilLuka->save();
                return redirect()->route('admin.turniri.naslovna');
            } catch (Throwable $e) {
                return redirect()->route('admin.turniri.naslovna')->with('error', $e->getMessage());
            }
        } else {
            return redirect()->route('admin.turniri.naslovna')->with('error', $validator->errors()->first());
        }
    }

    /** @noinspection PhpUndefinedMethodInspection */
    public function obrisi_stil(int $id): RedirectResponse
    {
        $stilLuka = Stilovi::findOrFail($id);
        $stilLuka->delete();
        return redirect()->route('admin.turniri.naslovna');
    }
}
