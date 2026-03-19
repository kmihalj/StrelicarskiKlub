<?php

use App\Http\Controllers\ClanciController;
use App\Http\Controllers\ClanoviController;
use App\Http\Controllers\JavnoController;
use App\Http\Controllers\KorisniciController;
use App\Http\Controllers\KlubController;
use App\Http\Controllers\AdminThemeModePolicyController;
use App\Http\Controllers\PlacanjaController;
use App\Http\Controllers\PolazniciSkoleController;
use App\Http\Controllers\TipoviTurniraController;
use App\Http\Controllers\ThemeController;
use App\Http\Controllers\TreninziController;
use App\Http\Controllers\TurniriController;
use App\Http\Controllers\UserThemePreferenceController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::match(['get', 'post', 'put', 'patch', 'delete', 'options'], '/', [JavnoController::class, 'naslovnaStranica'])->name('javno.naslovnaStranica');
Route::get('/naslovna', [JavnoController::class, 'naslovnaStranica'])->name('javno.naslovna');

//Auth::routes(['register' => false]);
Auth::routes();

Route::get('/logout', function (Request $request) {
    if (Auth::check()) {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }

    return redirect('/');
})->name('logout.get');

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

Route::prefix('admin/')->group(function () {
    Route::post('clanovi', [ClanoviController::class, 'store'])->name('admin.clanovi.spremanje_clana')->middleware(['auth', 'admin']);
    Route::match(['get', 'post'], 'clanovi/{clan}', [ClanoviController::class, 'edit'])->name('admin.clanovi.prikaz_clana')->middleware(['auth', 'admin']);
    Route::post('clanovi/{clan}/update', [ClanoviController::class, 'update'])->name('admin.clanovi.update')->middleware(['auth', 'admin']);
    Route::post('clanovi/{clan}/obrisi', [ClanoviController::class, 'destroy'])->name('admin.clanovi.brisanje_clana')->middleware(['auth', 'admin']);
    Route::post('clanovi/{clan}/dodajSliku', [ClanoviController::class, 'upload_slike_clana'])->name('admin.clanovi.upload_slike_clana')->middleware(['auth', 'admin']);
    Route::post('clanovi/{clan}/obrisiSliku', [ClanoviController::class, 'brisanje_slike_clana'])->name('admin.clanovi.brisanje_slike_clana')->middleware(['auth', 'admin']);

    Route::post('clanovi/{clan}/lijecnicki/spremi', [ClanoviController::class, 'spremi_lijecnicki_pregled'])->name('admin.clanovi.spremi_lijecnicki_pregled')->middleware(['auth', 'admin']);
    Route::post('clanovi/{clan}/lijecnicki/{pregled}/update', [ClanoviController::class, 'update_lijecnicki_pregled'])->name('admin.clanovi.update_lijecnicki_pregled')->middleware(['auth', 'admin']);
    Route::post('clanovi/{clan}/lijecnicki/{pregled}/obrisi', [ClanoviController::class, 'obrisi_lijecnicki_pregled'])->name('admin.clanovi.obrisi_lijecnicki_pregled')->middleware(['auth', 'admin']);
    Route::get('clanovi/{clan}/lijecnicki/{pregled}/download', [ClanoviController::class, 'preuzmi_lijecnicki_pregled'])->name('admin.clanovi.preuzmi_lijecnicki_pregled')->middleware(['auth', 'admin']);

    Route::post('clanovi/{clan}/dokumenti/spremi', [ClanoviController::class, 'spremi_dokument'])->name('admin.clanovi.spremi_dokument')->middleware(['auth', 'admin']);
    Route::post('clanovi/{clan}/dokumenti/{dokument}/update', [ClanoviController::class, 'update_dokument'])->name('admin.clanovi.update_dokument')->middleware(['auth', 'admin']);
    Route::post('clanovi/{clan}/dokumenti/{dokument}/obrisi', [ClanoviController::class, 'obrisi_dokument'])->name('admin.clanovi.obrisi_dokument')->middleware(['auth', 'admin']);
    Route::get('clanovi/{clan}/dokumenti/{dokument}/download', [ClanoviController::class, 'preuzmi_dokument'])->name('admin.clanovi.preuzmi_dokument')->middleware(['auth', 'admin']);
    Route::post('clanovi/{clan}/placanja/profil', [PlacanjaController::class, 'saveClanProfile'])->name('admin.clanovi.placanja.profil')->middleware(['auth', 'admin']);
    Route::post('clanovi/{clan}/placanja/manualno', [PlacanjaController::class, 'addManualCharge'])->name('admin.clanovi.placanja.manual')->middleware(['auth', 'admin']);
    Route::post('clanovi/{clan}/placanja/{charge}/status', [PlacanjaController::class, 'updateChargeStatus'])->name('admin.clanovi.placanja.status')->middleware(['auth', 'admin']);
    Route::post('clanovi/{clan}/placanja/{charge}/obrisi', [PlacanjaController::class, 'destroyCharge'])->name('admin.clanovi.placanja.destroy')->middleware(['auth', 'admin']);

    Route::get('korisnici', [KorisniciController::class, 'index'])->name('admin.korisnici.index')->middleware(['auth', 'admin']);
    Route::get('korisnici/{user}', [KorisniciController::class, 'edit'])->name('admin.korisnici.edit')->middleware(['auth', 'admin']);
    Route::post('korisnici/{user}/update', [KorisniciController::class, 'update'])->name('admin.korisnici.update')->middleware(['auth', 'admin']);
    Route::post('korisnici/{user}/obrisi', [KorisniciController::class, 'destroy'])->name('admin.korisnici.destroy')->middleware(['auth', 'admin']);
    Route::get('teme', [ThemeController::class, 'index'])->name('admin.teme.index')->middleware(['auth', 'admin']);
    Route::post('teme/{theme}/aktiviraj', [ThemeController::class, 'activate'])->name('admin.teme.activate')->middleware(['auth', 'admin']);
    Route::post('teme/{theme}/kloniraj', [ThemeController::class, 'clone'])->name('admin.teme.clone')->middleware(['auth', 'admin']);
    Route::post('teme/{theme}/spremi', [ThemeController::class, 'update'])->name('admin.teme.update')->middleware(['auth', 'admin']);
    Route::get('payments', [PlacanjaController::class, 'adminIndex'])->name('admin.placanja.index')->middleware(['auth', 'admin']);
    Route::get('payments/export/csv', [PlacanjaController::class, 'exportCsv'])->name('admin.placanja.export.csv')->middleware(['auth', 'admin']);

    Route::get('setup', [TipoviTurniraController::class, 'index'])->name('admin.turniri.naslovna')->middleware(['auth', 'admin_or_member']);
    Route::match(['get', 'post'], 'setup/kreiranjePolja', [TipoviTurniraController::class, 'odabir_tipa_turnira'])->name('admin.turniri.odabir_tipa_turnira')->middleware(['auth', 'admin_or_member']);
    Route::post('setup', [TipoviTurniraController::class, 'spremi_tipoviTurnira'])->name('admin.turniri.spremanje_tipaTurnira')->middleware(['auth', 'admin']);
    Route::post('setup/kreiranjePolja/spremi', [TipoviTurniraController::class, 'spremi_poljeZatipTurnira'])->name('admin.turniri.spremi_poljeZatipTurnira')->middleware(['auth', 'admin']);
    Route::post('setup/kreiranjePolja/obrisi', [TipoviTurniraController::class, 'obrisi_polje_za_tipTurnira'])->name('admin.turniri.obrisi_polje_za_tipTurnira')->middleware(['auth', 'admin']);
    Route::post('setup/{tipTurnira}/obrisi', [TipoviTurniraController::class, 'obrisi_tipoviTurnira'])->name('admin.turniri.brisanje_tipaTurnira')->middleware(['auth', 'admin']);
    Route::post('setup/kategorija/spremi', [TipoviTurniraController::class, 'spremi_kategoriju'])->name('admin.turniri.spremi_kategoriju')->middleware(['auth', 'admin']);
    Route::post('setup/kategorija/{kategorija}/obrisi', [TipoviTurniraController::class, 'obrisi_kategoriju'])->name('admin.turniri.obrisi_kategoriju')->middleware(['auth', 'admin']);
    Route::post('setup/stil/spremi', [TipoviTurniraController::class, 'spremi_stil_luka'])->name('admin.turniri.spremi_stil_luka')->middleware(['auth', 'admin']);
    Route::post('setup/stil/{stilovi}/obrisi', [TipoviTurniraController::class, 'obrisi_stil'])->name('admin.turniri.obrisi_stil')->middleware(['auth', 'admin']);
    Route::post('setup/tema-prikaz', [AdminThemeModePolicyController::class, 'update'])->name('admin.tema_mode_policy.update')->middleware(['auth', 'admin']);
    Route::post('setup/placanja', [PlacanjaController::class, 'updateSetup'])->middleware(['auth', 'admin']); // legacy path
    Route::post('setup/placanja/opcije', [PlacanjaController::class, 'addOption'])->middleware(['auth', 'admin']); // legacy path
    Route::post('payments/setup', [PlacanjaController::class, 'updateSetup'])->name('admin.placanja.setup.update')->middleware(['auth', 'admin']);
    Route::post('payments/setup/opcije', [PlacanjaController::class, 'addOption'])->name('admin.placanja.setup.option.add')->middleware(['auth', 'admin']);

    Route::match(['get', 'post'], 'rezultati/unos/{turnir}', [TurniriController::class, 'unosRezultataForma'])->name('admin.rezultati.unosRezultata')->middleware(['auth', 'admin_or_member']);
    Route::post('turniri', [TurniriController::class, 'spremiTurnir'])->name('admin.rezultati.kreirajTurnir')->middleware(['auth', 'admin']);
    Route::post('turniri/urediTurnir', [TurniriController::class, 'urediTurnirForma'])->name('admin.rezultati.urediTurnir')->middleware(['auth', 'admin']);
    Route::post('turniri/urediTurnir/spremi', [TurniriController::class, 'updateTurnir'])->name('admin.rezultati.updateTurnir')->middleware(['auth', 'admin']);
    Route::post('turniri/turnir/{turnir}/obrisi', [TurniriController::class, 'obrisiTurnir'])->name('admin.rezultati.obrisiTurnir')->middleware(['auth', 'admin']);
    Route::post('rezultati/unos/{turnir}/obrisi', [TurniriController::class, 'brisanjeRezultata'])->name('admin.rezultati.brisanjeRezultata')->middleware(['auth', 'admin']);
    Route::post('rezultati/unos/{turnir}/timovi/aktivno', [TurniriController::class, 'postaviTimoveAktivno'])->name('admin.rezultati.timovi.aktivno')->middleware(['auth', 'admin']);
    Route::post('rezultati/unos/tim/spremi', [TurniriController::class, 'spremiTimskiRezultat'])->name('admin.rezultati.timovi.spremi')->middleware(['auth', 'admin']);
    Route::post('rezultati/unos/tim/{tim}/obrisi', [TurniriController::class, 'brisanjeTimskogRezultata'])->name('admin.rezultati.timovi.brisanje')->middleware(['auth', 'admin']);
    Route::post('rezultati/unos/rezultat/spremi', [TurniriController::class, 'SpremanjeRezultata'])->name('admin.rezultati.SpremanjeRezultata')->middleware(['auth', 'admin']);
    Route::post('rezultati/unos/rezultat/{rezultat}/update', [TurniriController::class, 'updateRezultat'])->name('admin.rezultati.updateRezultat')->middleware(['auth', 'admin']);
    Route::post('rezultati/unos/rezultat/spremiDodatno', [TurniriController::class, 'dodatniPodaciRezultat'])->name('admin.rezultati.dodatniPodaciRezultat')->middleware(['auth', 'admin']);
    Route::post('rezultati/unos/rezultat/spremiDodatno2', [TurniriController::class, 'dodatniPodaci2Rezultat'])->name('admin.rezultati.dodatniPodaci2Rezultat')->middleware(['auth', 'admin']);
    Route::post('rezultati/unos/rezultat/spremiMedij', [TurniriController::class, 'uploadMedija'])->name('admin.rezultati.uploadMedija')->middleware(['auth', 'admin']);
    Route::post('rezultati/unos/rezultat/obrisiMedij', [TurniriController::class, 'brisanjeMedija'])->name('admin.rezultati.brisanjeMedija')->middleware(['auth', 'admin']);

    Route::get('klub', [KlubController::class, 'index'])->name('admin.klub.naslovna')->middleware(['auth', 'admin_or_member']);
    Route::post('klub', [KlubController::class, 'spremanjePodataka'])->name('admin.klub.spremanjePodataka')->middleware(['auth', 'admin']);
    Route::post('klub/spremanjeFunkcija', [KlubController::class, 'spremanjeFunkcija'])->name('admin.klub.spremanjeFunkcija')->middleware(['auth', 'admin']);
    Route::post('klub/spremanjeTrenera', [KlubController::class, 'spremanjeTrenera'])->name('admin.klub.spremanjeTrenera')->middleware(['auth', 'admin']);
    Route::post('klub/unos/spremiMedij', [KlubController::class, 'uploadMedija'])->name('admin.klub.uploadMedija')->middleware(['auth', 'admin']);
    Route::post('klub/unos/obrisiMedij', [KlubController::class, 'brisanjeMedija'])->name('admin.klub.brisanjeMedija')->middleware(['auth', 'admin']);
    Route::post('klub/unos/updateMedij', [KlubController::class, 'updateMedija'])->name('admin.klub.updateMedij')->middleware(['auth', 'admin']);
    Route::post('klub/brisanjeTrenera/{clanoviFunkcije}', [KlubController::class, 'obrisiTrenera'])->name('admin.klub.brisanjeTrenera')->middleware(['auth', 'admin']);

    Route::get('clanci', [ClanciController::class, 'popisClanaka'])->name('admin.clanci.popisClanaka')->middleware(['auth', 'admin_or_member']);
    Route::get('clanci/unos', [ClanciController::class, 'unos'])->name('admin.clanci.unos')->middleware(['auth', 'admin']);
    Route::match(['get', 'post'], 'clanci/{clanak}/uredi', [ClanciController::class, 'uredjivanje'])->name('admin.clanci.uredjivanje')->middleware(['auth', 'admin']);
    Route::match(['get', 'post'], 'clanci/{clanak}/obrisi', [ClanciController::class, 'brisanje'])->name('admin.clanci.brisanje')->middleware(['auth', 'admin']);
    Route::post('clanci/uredjivanje', [ClanciController::class, 'spremanjeClanka'])->name('admin.clanci.spremanjeClanka')->middleware(['auth', 'admin']);
    Route::post('clanci/galerija', [ClanciController::class, 'galerija'])->name('admin.clanci.galerija')->middleware(['auth', 'admin']);
    Route::post('clanci/unos/spremiMedij', [ClanciController::class, 'uploadMedija'])->name('admin.clanci.uploadMedija')->middleware(['auth', 'admin']);
    Route::post('clanci/unos/obrisiMedij', [ClanciController::class, 'brisanjeMedija'])->name('admin.clanci.brisanjeMedija')->middleware(['auth', 'admin']);

    Route::get('clanovi/{clan}/treninzi', [TreninziController::class, 'adminIndex'])->name('admin.treninzi.index')->middleware(['auth', 'admin']);
    Route::post('clanovi/{clan}/treninzi/dvoranski/{trening}/obrisi', [TreninziController::class, 'destroyDvoranskiAdmin'])->name('admin.treninzi.dvoranski.destroy')->middleware(['auth', 'admin']);
    Route::post('clanovi/{clan}/treninzi/vanjski/{trening}/obrisi', [TreninziController::class, 'destroyVanjskiAdmin'])->name('admin.treninzi.vanjski.destroy')->middleware(['auth', 'admin']);
});

Route::get('klub', [KlubController::class, 'oKlubu'])->name('javno.klub');
Route::get('turniri', [TurniriController::class, 'index'])->name('admin.rezultati.popisTurnira');
Route::get('admin/turniri', [TurniriController::class, 'index'])->name('admin.rezultati.popisTurnira.legacy');
Route::get('rezultati', [JavnoController::class, 'prikazRezultata'])->name('javno.rezultati');
Route::get('rezultati/{turnir}', [JavnoController::class, 'pokaziTurnir'])->name('javno.rezultati.prikaz_turnira');
Route::get('clanovi', [JavnoController::class, 'popisClanova'])->name('javno.clanovi');
Route::middleware(['auth', 'admin_member_or_school'])->group(function () {
    Route::get('skola/polaznici', [PolazniciSkoleController::class, 'index'])->name('javno.skola.polaznici.index');
    Route::get('skola/polaznici/{polaznik}', [PolazniciSkoleController::class, 'show'])->name('javno.skola.polaznici.show');
    Route::get('skola/polaznici/{polaznik}/dokumenti/{dokument}/pregled', [PolazniciSkoleController::class, 'preuzmiDokument'])->name('javno.skola.polaznici.preuzmi_dokument');
});
Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('skola/evidencija-dolazaka', [PolazniciSkoleController::class, 'evidencijaDolasaka'])->name('javno.skola.evidencija.index');
});
Route::middleware('auth')->group(function () {
    Route::get('clanovi/{clan}/treninzi', [TreninziController::class, 'pregledClana'])->name('javno.treninzi.clan.index');
    Route::get('moji-treninzi', [TreninziController::class, 'index'])->name('javno.treninzi.index');
    Route::get('moji-treninzi/dvoranski/novi', [TreninziController::class, 'createDvoranski'])->name('javno.treninzi.dvoranski.create');
    Route::post('moji-treninzi/dvoranski', [TreninziController::class, 'storeDvoranski'])->name('javno.treninzi.dvoranski.store');
    Route::get('moji-treninzi/dvoranski/{trening}/uredi', [TreninziController::class, 'editDvoranski'])->name('javno.treninzi.dvoranski.edit');
    Route::put('moji-treninzi/dvoranski/{trening}', [TreninziController::class, 'updateDvoranski'])->name('javno.treninzi.dvoranski.update');
    Route::post('moji-treninzi/dvoranski/{trening}/obrisi', [TreninziController::class, 'destroyDvoranski'])->name('javno.treninzi.dvoranski.destroy');
    Route::get('moji-treninzi/vanjski/novi', [TreninziController::class, 'createVanjski'])->name('javno.treninzi.vanjski.create');
    Route::post('moji-treninzi/vanjski', [TreninziController::class, 'storeVanjski'])->name('javno.treninzi.vanjski.store');
    Route::get('moji-treninzi/vanjski/{trening}/uredi', [TreninziController::class, 'editVanjski'])->name('javno.treninzi.vanjski.edit');
    Route::put('moji-treninzi/vanjski/{trening}', [TreninziController::class, 'updateVanjski'])->name('javno.treninzi.vanjski.update');
    Route::post('moji-treninzi/vanjski/{trening}/obrisi', [TreninziController::class, 'destroyVanjski'])->name('javno.treninzi.vanjski.destroy');
    Route::get('clanovi/{clan}/placanja', [PlacanjaController::class, 'showMemberPayments'])->name('javno.clanovi.placanja');
    Route::post('clanovi/{clan}/placanja/{charge}/odabir', [PlacanjaController::class, 'updatePreferredVariant'])->name('javno.clanovi.placanja.odabir');

    Route::post('skola/polaznici', [PolazniciSkoleController::class, 'store'])->name('admin.skola.polaznici.store')->middleware('admin');
    Route::post('skola/polaznici/{polaznik}/update', [PolazniciSkoleController::class, 'update'])->name('admin.skola.polaznici.update')->middleware('admin');
    Route::post('skola/polaznici/{polaznik}/obrisi', [PolazniciSkoleController::class, 'destroy'])->name('admin.skola.polaznici.destroy')->middleware('admin');
    Route::post('skola/polaznici/{polaznik}/prebaci-u-clana', [PolazniciSkoleController::class, 'prebaciUClana'])->name('admin.skola.polaznici.prebaci_u_clana')->middleware('admin');
    Route::post('skola/evidencija-dolazaka', [PolazniciSkoleController::class, 'spremiEvidencijuDolasaka'])->name('admin.skola.evidencija.spremi')->middleware('admin');
    Route::post('skola/polaznici/{polaznik}/dokumenti/spremi', [PolazniciSkoleController::class, 'spremiDokument'])->name('admin.skola.polaznici.spremi_dokument')->middleware('admin');
    Route::post('skola/polaznici/{polaznik}/dokumenti/{dokument}/obrisi', [PolazniciSkoleController::class, 'obrisiDokument'])->name('admin.skola.polaznici.obrisi_dokument')->middleware('admin');
    Route::post('skola/polaznici/{polaznik}/placanja/profil', [PolazniciSkoleController::class, 'spremiSkolarinaProfil'])->name('admin.skola.polaznici.placanja.profil')->middleware('admin');
    Route::post('skola/polaznici/{polaznik}/placanja/{charge}/status', [PolazniciSkoleController::class, 'updateSkolarinaStatus'])->name('admin.skola.polaznici.placanja.status')->middleware('admin');

    Route::post('profil/tema-prikaz', [UserThemePreferenceController::class, 'update'])->name('user.theme_mode.update');
});
Route::get('clanovi/csv-export', [JavnoController::class, 'exportAktivnihClanovaCsv'])->name('javno.clanovi.csv_export')->middleware(['auth', 'admin']);
Route::get('clanovi/{clan}/lijecnicki/{pregled}/pregled', [JavnoController::class, 'preuzmi_lijecnicki_pregled'])->name('javno.clanovi.preuzmi_lijecnicki')->middleware('auth');
Route::get('clanovi/{clan}/dokumenti/{dokument}/pregled', [JavnoController::class, 'preuzmi_dokument'])->name('javno.clanovi.preuzmi_dokument')->middleware('auth');
Route::get('clanovi/{clan}', [JavnoController::class, 'pregledClana'])->name('javno.clanovi.prikaz_clana');
Route::get('clanak/{clanak}', [ClanciController::class, 'pokaziClanak'])->name('javno.clanci.prikaz_clanka');
Route::get('clanci/{vrsta}', [ClanciController::class, 'popisClanakaPoVrsti'])->name('javno.clanci.popisClanaka');
