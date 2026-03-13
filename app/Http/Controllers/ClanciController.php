<?php

namespace App\Http\Controllers;

use App\Models\Clanci;
use App\Models\MedijiClanaka;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Throwable;

class ClanciController extends Controller
{
    private const AUTO_FACEBOOK_BLOK_START = '<!--AUTO_FACEBOOK_LINK_START-->';
    private const AUTO_FACEBOOK_BLOK_END = '<!--AUTO_FACEBOOK_LINK_END-->';

    /**
     * Display a listing of the resource.
     */
    public function unos()
    {
        return view('admin.clanci.unos');
    }

    public function uredjivanje(int $id)
    {
        $clanak = Clanci::findOrFail($id);
        $sadrzajEditor = $this->ukloniFacebookBlokIzSadrzaja($clanak->sadrzaj);
        $facebookLinkSadrzaj = $this->izvuciFacebookLinkIzSadrzaja($clanak->sadrzaj);

        return view('admin.clanci.unos', [
            'clanak' => $clanak,
            'sadrzajEditor' => $sadrzajEditor,
            'facebookLinkSadrzaj' => $facebookLinkSadrzaj,
        ]);
    }

    public function brisanje(int $id): RedirectResponse
    {
        try {
            $clanak = Clanci::findOrFail($id);
            $clanak->delete();
            return redirect()->route('admin.clanci.popisClanaka')->with('success', 'Brisanje članka OK');
        } catch (Throwable $e) {
            return redirect()->route('admin.clanci.popisClanaka')->with('error', $e->getMessage());
        }
    }

    public function spremanjeClanka(Request $request)
    {
        $postojeciClanakId = $request->get('id_clanka');
        $sadrzajEditor = $this->ukloniFacebookBlokIzSadrzaja((string)$request->get('sadrzaj'));
        $uneseniFacebookLink = $request->get('facebook_link_sadrzaj');
        $facebookLink = $this->normalizirajFacebookLink($uneseniFacebookLink);

        if (trim((string)$uneseniFacebookLink) !== '' && $facebookLink === null) {
            $povratnaRuta = $postojeciClanakId
                ? route('admin.clanci.uredjivanje', (int)$postojeciClanakId)
                : route('admin.clanci.unos');

            return redirect()
                ->to($povratnaRuta)
                ->withInput()
                ->with('error', 'Facebook link nije ispravan URL.');
        }

        if ($request->get('id_clanka')) {
            $clanak = Clanci::findOrFail((int)$request->get('id_clanka'));
        } else {
            $clanak = new Clanci();
        }
        $clanak->vrsta = $request->get('vrsta');
        $clanak->naslov = $request->get('naslov');
        $clanak->datum = $request->get('datum');
        $sadrzajEditor = trim($sadrzajEditor);
        if ($facebookLink !== null) {
            $facebookBlok = $this->izradiFacebookBlokZaSadrzaj($facebookLink);
            $clanak->sadrzaj = $sadrzajEditor === '' ? $facebookBlok : $sadrzajEditor . PHP_EOL . $facebookBlok;
        } else {
            $clanak->sadrzaj = $sadrzajEditor;
        }
        $clanak->menu_naslov = $request->get('menu_naslov');
        if ($request->get('menu') !== null) {
            $clanak->menu = true;
        } else {
            $clanak->menu = false;
        }
        $clanak->save();
        return redirect()->route('admin.clanci.uredjivanje', $clanak->id);
    }

    public function galerija(Request $request)
    {
        $clanak = Clanci::findOrFail((int)$request->get('id_clanka'));
        if ($request->get('galerija') !== null) {
            $clanak->galerija = true;
        } else {
            $clanak->galerija = false;
        }
        $clanak->save();
        return redirect()->route('admin.clanci.uredjivanje', $clanak->id);
    }

    public function popisClanaka()
    {
        $clanci = Clanci::orderByDesc('datum')->paginate(15);
        return view('admin.clanci.popis', ['clanci' => $clanci]);

    }

    public function pokaziClanak(Clanci $clanak): View
    {
        return view('admin.clanci.prikazClanka', ['clanak' => $clanak]);
    }

    public function popisClanakaPoVrsti(string $vrsta)
    {
        //dd($vrsta);
        $clanci = Clanci::where('vrsta', '=', $vrsta)->orderByDesc('datum')->paginate(5);
        return view('admin.clanci.popisClanaka', ['vrsta' => $vrsta, 'clanci' => $clanci]);
    }

    public function uploadMedija(Request $request): RedirectResponse|JsonResponse
    {
        if (!(Storage::exists('public/clanci'))) {
            Storage::makeDirectory('public/clanci');
        }
        $rules = array(
            'medij' => 'required|array|min:1',
            'medij.*' => 'required|extensions:jpg,jpeg,png,webp,pdf,doc,docx,mp4'
        );
        $messages = array(
            'medij.required' => 'Nije odabrana datoteka.',
            'medij.array' => 'Nije odabrana datoteka.',
            'medij.min' => 'Nije odabrana datoteka.',
            'medij.*.required' => 'Nije odabrana datoteka.',
            'medij.*.extensions' => 'Datoteka nije slika (jpg,jpeg,png,webp), dokument (pdf,doc,docx) niti video (mp4).'
        );
        $validator = Validator::make($request->all(), $rules, $messages);
        $clanak = Clanci::findOrFail((int)$request->get('clanak_id'));
        if (!$validator->errors()->isEmpty()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $validator->errors()->first()], 422);
            }

            return redirect()->route('admin.clanci.uredjivanje', $clanak->id)->with('error', $validator->errors()->first());
        }

        if (!(Storage::exists('public/clanci/' . $clanak->id))) {
            Storage::makeDirectory('public/clanci/' . $clanak->id);
        }

        foreach ($request->file('medij') as $datoteka) {
            $imeDatoteke = now()->format('d_m_Y_H_i_s_u') . '_' . Str::random(8) . '.' . strtolower($datoteka->extension());
            $datoteka->storeAs('public/clanci/' . $clanak->id . '/' . $imeDatoteke);

            $medij = new MedijiClanaka();
            $medij->clanak_id = $clanak->id;
            switch (strtoupper($datoteka->extension())) {
                case "MP4" :
                    $medij->vrsta = 'video';
                    break;
                case "PDF" :
                case "DOC" :
                case "DOCX" :
                    $medij->vrsta = 'dokument';
                    break;
                case "JPG" :
                case "JPEG" :
                case "PNG" :
                case "WEBP" :
                    $medij->vrsta = 'slika';
                    break;
            }
            $medij->link = $imeDatoteke;
            $medij->save();
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Upload uspješan.']);
        }

        return redirect()->route('admin.clanci.uredjivanje', $clanak->id);
    }

    public function brisanjeMedija(Request $request): RedirectResponse
    {
        $medij = MedijiClanaka::findOrFail((int)$request->get('medijBrisanje'));
        Storage::delete('public/clanci/' . $medij->clanak->id . '/' . $medij->link);
        $medij->delete();
        return redirect()->route('admin.clanci.uredjivanje', $medij->clanak->id);
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

    private function izvuciFacebookLinkIzSadrzaja(?string $sadrzaj): ?string
    {
        $vrijednost = (string)$sadrzaj;
        if ($vrijednost === '') {
            return null;
        }

        $oznaceniBlokRegex = '/<!--\s*AUTO_FACEBOOK_LINK_START\s*-->(.*?)<!--\s*AUTO_FACEBOOK_LINK_END\s*-->/is';
        if (preg_match($oznaceniBlokRegex, $vrijednost, $blokPodudaranje) === 1
            && preg_match('/href=(["\'])(.*?)\1/i', $blokPodudaranje[1], $linkPodudaranje) === 1) {
            return html_entity_decode((string)$linkPodudaranje[2], ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }

        $legacyRegex = '/<a[^>]*href=(["\'])(.*?)\1[^>]*>\s*(?:<svg[\s\S]*?<\/svg>\s*)?Facebook\s*<\/a>/iu';
        if (preg_match($legacyRegex, $vrijednost, $legacyPodudaranje) === 1) {
            return html_entity_decode((string)$legacyPodudaranje[2], ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }

        return null;
    }

    private function ukloniFacebookBlokIzSadrzaja(?string $sadrzaj): string
    {
        $vrijednost = (string)$sadrzaj;
        if ($vrijednost === '') {
            return '';
        }

        $oznaceniBlokRegex = '/<!--\s*AUTO_FACEBOOK_LINK_START\s*-->.*?<!--\s*AUTO_FACEBOOK_LINK_END\s*-->/is';
        $vrijednost = preg_replace($oznaceniBlokRegex, '', $vrijednost) ?? $vrijednost;

        $legacyRegex = '/<p[^>]*>\s*(?:<a[^>]*>\s*)?(?:<svg[\s\S]*?<\/svg>)\s*Facebook\s*(?:<\/a>)?\s*<\/p>/iu';
        $vrijednost = preg_replace($legacyRegex, '', $vrijednost) ?? $vrijednost;

        return trim($vrijednost);
    }

    private function izradiFacebookBlokZaSadrzaj(string $facebookLink): string
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
