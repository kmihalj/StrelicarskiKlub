<?php

namespace App\Http\Controllers;

use App\Models\Clanovi;
use App\Models\Kategorije;
use App\Models\RezultatiLinkovi;
use App\Models\RezultatiOpci;
use App\Models\RezultatiPoTipuTurnira;
use App\Models\RezultatiSlike;
use App\Models\Stilovi;
use App\Models\TipoviTurnira;
use App\Models\Turniri;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class TurniriController extends Controller
{
    private const STANDARDNI_LUK_STIL_ID = 7;
    private const AUTO_FACEBOOK_BLOK_START = '<!--AUTO_FACEBOOK_LINK_START-->';
    private const AUTO_FACEBOOK_BLOK_END = '<!--AUTO_FACEBOOK_LINK_END-->';

    /** @noinspection PhpMissingReturnTypeInspection */
    public function index()
    {
        $turniri = Turniri::orderByDesc('datum')->paginate(15);
        $tipoviTurnira = TipoviTurnira::orderBy('naziv')->get();
        return view('admin.rezultati.popisTurnira', ['turniri' => $turniri, 'tipoviTurnira' => $tipoviTurnira]);
    }

    public function spremiTurnir(Request $request): RedirectResponse
    {
        $turnir = new Turniri();
        return $this->SpremanjeTurnira($request, $turnir);
    }

    public function updateTurnir(Request $request): RedirectResponse
    {
        $turnir = Turniri::findOrFail((int)$request->get('turnir_id'));
        return $this->SpremanjeTurnira($request, $turnir);
    }
    public function SpremanjeTurnira(Request $request, $turnir): RedirectResponse
    {
        $stranica = $request->get('stranica') ?? 1;
        $turnir->datum = $request->get('datum_turnira');
        $turnir->naziv = $request->get('naziv_turnira');
        $turnir->lokacija = $request->get('lokacija_turnira');
        $turnir->tipovi_turnira_id = $request->get('odabir_tipa_turnira');
        $turnir->eliminacije = (bool)$request->get('eliminacije');
        $turnir->save();
        return redirect()->route('admin.rezultati.popisTurnira', ['page'=>$stranica]);
    }

    /** @noinspection PhpMissingReturnTypeInspection */
    public function urediTurnirForma(Request $request)
    {
        $stranica = $request->get('stranica') ?? 1;
        $turnir = Turniri::findOrFail((int)$request->get('turnir_id'));
        $turniri = Turniri::orderByDesc('datum')->paginate(15, ['*'], 'page', $stranica);
        $tipoviTurnira = TipoviTurnira::orderBy('naziv')->get();
        return view('admin.rezultati.popisTurnira', ['turniri' => $turniri, 'tipoviTurnira' => $tipoviTurnira, 'uredi_turnir'=>$turnir]);
    }

    public function obrisiTurnir(int $id): RedirectResponse
    {
        try {
            $turnir = Turniri::findOrFail($id);
            $turnir->delete();
            return redirect()->route('admin.rezultati.popisTurnira')->with('success', 'Brisanje turnira OK');
        } catch (Exception $e) {
            return redirect()->route('admin.rezultati.popisTurnira')->with('error', $e->getMessage());
        }
    }

    /** @noinspection PhpMissingReturnTypeInspection
     * @noinspection PhpUndefinedMethodInspection
     */
    public function unosRezultataForma(int $id)
    {
        $turnir = Turniri::findOrFail($id);
        $kategorije = Kategorije::orderBy('spol')->orderBy('naziv')->get(['id', 'spol', 'naziv']);
        $stilovi = Stilovi::where('id', '!=', self::STANDARDNI_LUK_STIL_ID)->orderBy('naziv')->get(['id', 'naziv']);
        $clanovi = Clanovi::where('aktivan', true)->orderBy('Prezime')->orderBy('Ime')->get(['id', 'Ime', 'Prezime', 'spol']);
        $opis2Editor = $this->ukloniFacebookBlokIzOpisa2($turnir->opis2);
        $facebookLinkOpis2 = $this->izvuciFacebookLinkIzOpisa2($turnir->opis2);

        return view('admin.rezultati.formaZaUnosRezultata', [
            'turnir' => $turnir,
            'kategorije' => $kategorije,
            'stilovi' => $stilovi,
            'clanovi' => $clanovi,
            'opis2Editor' => $opis2Editor,
            'facebookLinkOpis2' => $facebookLinkOpis2,
        ]);
    }

    /** @noinspection PhpMissingReturnTypeInspection */
    public function dodatniPodaciRezultat(Request $request)
    {
        $turnir = Turniri::findOrFail((int)$request->get('turnir_id'));
        $turnir->opis = $request->get('opis_turnira');
        $turnir->save();
        return redirect()->route('admin.rezultati.unosRezultata', $turnir->id);
    }

    public function dodatniPodaci2Rezultat(Request $request)
    {
        $turnir = Turniri::findOrFail((int)$request->get('turnir_id'));
        $opis2Editor = $this->ukloniFacebookBlokIzOpisa2((string)$request->get('opis_turnira2'));
        $uneseniFacebookLink = $request->get('facebook_link_opis2');
        $facebookLink = $this->normalizirajFacebookLink($uneseniFacebookLink);

        if (trim((string)$uneseniFacebookLink) !== '' && $facebookLink === null) {
            return redirect()
                ->route('admin.rezultati.unosRezultata', $turnir->id)
                ->withInput()
                ->with('error', 'Facebook link nije ispravan URL.');
        }

        $opis2Editor = trim($opis2Editor);
        if ($facebookLink !== null) {
            $facebookBlok = $this->izradiFacebookBlokZaOpis2($facebookLink);
            $turnir->opis2 = $opis2Editor === '' ? $facebookBlok : $opis2Editor . PHP_EOL . $facebookBlok;
        } else {
            $turnir->opis2 = $opis2Editor === '' ? null : $opis2Editor;
        }

        $turnir->save();
        return redirect()->route('admin.rezultati.unosRezultata', $turnir->id);
    }

    public function uploadMedija(Request $request): RedirectResponse|JsonResponse
    {
        if (!(Storage::exists('public/turniri'))) {
            Storage::makeDirectory('public/turniri');
        }
        $rules = array(
            'medij' => 'required|array|min:1',
            'medij.*' => 'required|extensions:jpg,jpeg,png,webp,mp4'
        );
        $messages = array(
            'medij.required' => 'Nije odabrana datoteka.',
            'medij.array' => 'Nije odabrana datoteka.',
            'medij.min' => 'Nije odabrana datoteka.',
            'medij.*.required' => 'Nije odabrana datoteka.',
            'medij.*.extensions' => 'Datoteka nije slika (jpg, jpeg, png, webp) niti video (mp4).'
        );
        $validator = Validator::make($request->all(), $rules, $messages);
        $turnir = Turniri::findOrFail((int)$request->get('turnir_id'));
        if (!$validator->errors()->isEmpty()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $validator->errors()->first()], 422);
            }

            return redirect()->route('admin.rezultati.unosRezultata', $turnir->id)->with('error', $validator->errors()->first());
        }

        if (!(Storage::exists('public/turniri/' . $turnir->id))) {
            Storage::makeDirectory('public/turniri/' . $turnir->id);
        }

        foreach ($request->file('medij') as $datoteka) {
            $imeDatoteke = now()->format('d_m_Y_H_i_s_u') . '_' . Str::random(8) . '.' . strtolower($datoteka->extension());
            $datoteka->storeAs('public/turniri/' . $turnir->id . '/' . $imeDatoteke);

            $medij = new RezultatiSlike();
            $medij->turnir_id = $turnir->id;
            if (strtoupper($datoteka->extension()) == "MP4") {
                $medij->vrsta = 'video';
            } else {
                $medij->vrsta = 'slika';
            }
            $medij->link = $imeDatoteke;
            $medij->save();
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Upload uspješan.']);
        }

        return redirect()->route('admin.rezultati.unosRezultata', $turnir->id);
    }

    /** @noinspection PhpUndefinedMethodInspection */
    public function brisanjeMedija(Request $request): RedirectResponse
    {
        $medij = RezultatiSlike::findOrFail((int)$request->get('medijBrisanje'));
        Storage::delete( 'public/turniri/' . $medij->turnir->id . '/' . $medij->link);
        $medij->delete();
        return redirect()->route('admin.rezultati.unosRezultata', $medij->turnir->id);
    }

    public function SpremanjeRezultata(Request $request): RedirectResponse
    {
        $turnir = Turniri::find($request->get('turnir_id'));
        if (!$turnir) {
            return redirect()->route('admin.rezultati.popisTurnira')->with('error', 'Turnir nije pronaden.');
        }

        $clan = Clanovi::find($request->get('clan'));
        $kategorija = Kategorije::find($request->get('kategorija'));
        $stil = Stilovi::where('id', $request->get('stil'))
            ->where('id', '!=', self::STANDARDNI_LUK_STIL_ID)
            ->first();

        if (!$clan || !$kategorija || !$stil) {
            return redirect()->route('admin.rezultati.unosRezultata', $turnir->id)
                ->with('error', 'Odabrani clan, stil ili kategorija nisu valjani.');
        }

        $clanSpol = $this->normalizirajSpol($clan->spol);
        $kategorijaSpol = $this->normalizirajSpol($kategorija->spol);

        if ($clanSpol === '' || $kategorijaSpol === '' || $clanSpol !== $kategorijaSpol) {
            return redirect()->route('admin.rezultati.unosRezultata', $turnir->id)
                ->with('error', 'Odabrana kategorija ne odgovara spolu clana.');
        }

        $polja_iz_forme = $request->get('polje');
        $i=0;
        foreach ($turnir->tipTurnira->polja as $polje_za_unos) {
            $rezPoTipu = new RezultatiPoTipuTurnira();
            $rezPoTipu->turnir_id = $request->get('turnir_id');
            $rezPoTipu->clan_id = $clan->id;
            $rezPoTipu->kategorija_id = $kategorija->id;
            $rezPoTipu->stil_id = $stil->id;
            $rezPoTipu->polje_za_tipove_turnira_id = $polje_za_unos->id;
            $rezPoTipu->rezultat = $polja_iz_forme[$i];
            $rezPoTipu->save();
            $i++;
        }
        $rezOpci = new RezultatiOpci();
        $rezOpci->turnir_id = $request->get('turnir_id');
        $rezOpci->clan_id = $clan->id;
        $rezOpci->kategorija_id = $kategorija->id;
        $rezOpci->stil_id = $stil->id;
        $rezOpci->plasman = $request->get('plasman');
        $rezOpci->plasman_nakon_eliminacija = ($request->get('plasman_eliminacije') !== null) ? $request->get('plasman_eliminacije') : null;
        $rezOpci->save();
        return redirect()->route('admin.rezultati.unosRezultata', $turnir->id);
    }

    /** @noinspection PhpUndefinedMethodInspection */
    public function brisanjeRezultata(int $id): RedirectResponse
    {
        $rezOpci = RezultatiOpci::findOrFail($id);
        $turnir_id = $rezOpci->turnir->id;
        $rezPoTipu = RezultatiPoTipuTurnira::where('turnir_id', $turnir_id)->where('clan_id', $rezOpci->clan->id)->get();
        $rezPoTipu->each->delete();
        $rezOpci->delete();
        return redirect()->route('admin.rezultati.unosRezultata', $turnir_id);
    }

    private function normalizirajSpol(?string $spol): string
    {
        $vrijednost = trim((string)$spol);
        if ($vrijednost === '') {
            return '';
        }

        $vrijednost = Str::ascii(mb_strtoupper($vrijednost, 'UTF-8'));

        if (str_starts_with($vrijednost, 'M')) {
            return 'M';
        }

        if (str_starts_with($vrijednost, 'Z')) {
            return 'Z';
        }

        return $vrijednost;
    }

    private function normalizirajFacebookLink(?string $link): ?string
    {
        $vrijednost = trim((string)$link);
        if ($vrijednost === '') {
            return null;
        }

        if (!preg_match('#^https?://#i', $vrijednost)) {
            $vrijednost = 'https://' . $vrijednost;
        }

        $validiranUrl = filter_var($vrijednost, FILTER_VALIDATE_URL);
        if ($validiranUrl === false) {
            return null;
        }

        $host = strtolower((string)parse_url($validiranUrl, PHP_URL_HOST));
        if ($host === '' || (!str_contains($host, 'facebook.com') && !str_contains($host, 'fb.com'))) {
            return null;
        }

        return $validiranUrl;
    }

    private function izvuciFacebookLinkIzOpisa2(?string $opis2): ?string
    {
        $sadrzaj = (string)$opis2;
        if ($sadrzaj === '') {
            return null;
        }

        $oznaceniBlokRegex = '/<!--\s*AUTO_FACEBOOK_LINK_START\s*-->(.*?)<!--\s*AUTO_FACEBOOK_LINK_END\s*-->/is';
        if (preg_match($oznaceniBlokRegex, $sadrzaj, $blokPodudaranje) === 1
            && preg_match('/href=(["\'])(.*?)\1/i', $blokPodudaranje[1], $linkPodudaranje) === 1) {
            return html_entity_decode((string)$linkPodudaranje[2], ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }

        $legacyRegex = '/<a[^>]*href=(["\'])(.*?)\1[^>]*>\s*(?:<svg[\s\S]*?<\/svg>\s*)?Facebook\s*<\/a>/iu';
        if (preg_match($legacyRegex, $sadrzaj, $legacyPodudaranje) === 1) {
            return html_entity_decode((string)$legacyPodudaranje[2], ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }

        return null;
    }

    private function ukloniFacebookBlokIzOpisa2(?string $opis2): string
    {
        $sadrzaj = (string)$opis2;
        if ($sadrzaj === '') {
            return '';
        }

        $oznaceniBlokRegex = '/<!--\s*AUTO_FACEBOOK_LINK_START\s*-->.*?<!--\s*AUTO_FACEBOOK_LINK_END\s*-->/is';
        $sadrzaj = preg_replace($oznaceniBlokRegex, '', $sadrzaj) ?? $sadrzaj;

        $legacyRegex = '/<p[^>]*>\s*(?:<a[^>]*>\s*)?(?:<svg[\s\S]*?<\/svg>)\s*Facebook\s*(?:<\/a>)?\s*<\/p>/iu';
        $sadrzaj = preg_replace($legacyRegex, '', $sadrzaj) ?? $sadrzaj;

        return trim($sadrzaj);
    }

    private function izradiFacebookBlokZaOpis2(string $facebookLink): string
    {
        $siguranLink = htmlspecialchars($facebookLink, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return self::AUTO_FACEBOOK_BLOK_START
            . '<p style="text-align:center;">'
            . '<a href="' . $siguranLink . '" target="_blank" rel="noopener noreferrer">'
            . '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" width="24px" height="24px"><path fill="#3F51B5" d="M42,37c0,2.762-2.238,5-5,5H11c-2.761,0-5-2.238-5-5V11c0-2.762,2.239-5,5-5h26c2.762,0,5,2.238,5,5V37z"></path><path fill="#FFF" d="M34.368,25H31v13h-5V25h-3v-4h3v-2.41c0.002-3.508,1.459-5.59,5.592-5.59H35v4h-2.287C31.104,17,31,17.6,31,18.723V21h4L34.368,25z"></path></svg>'
            . 'Facebook'
            . '</a>'
            . '</p>'
            . self::AUTO_FACEBOOK_BLOK_END;
    }
}


