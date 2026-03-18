<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\FacebookContentBlockSupport;
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

/**
 * Admin kontroler za unos, uređivanje i objavu klupskih članaka s medijima.
 */
class ClanciController extends Controller
{
    use FacebookContentBlockSupport;

    /**
     * Otvara formu za unos novog članka.
     */
    public function unos()
    {
        return view('admin.clanci.unos');
    }

    /**
     * Otvara uređivanje odabranog članka i svih njegovih medijskih priloga.
     */
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

    /**
     * Briše odabrani zapis i po potrebi čisti povezane podatke/datoteke.
     */
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

    /**
     * Validira ulaz i sprema promjene prema pravilima modula članaka i medijskih priloga.
     */
    public function spremanjeClanka(Request $request)
    {
        $postojeciClanakId = $request->input('id_clanka');
        $sadrzajEditor = $this->ukloniFacebookBlokIzSadrzaja((string)$request->input('sadrzaj'));
        $uneseniFacebookLink = $request->input('facebook_link_sadrzaj');
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

        if ($request->filled('id_clanka')) {
            $clanak = Clanci::findOrFail((int)$request->input('id_clanka'));
        } else {
            $clanak = new Clanci();
        }
        $clanak->vrsta = $request->input('vrsta');
        $clanak->naslov = $request->input('naslov');
        $clanak->datum = $request->input('datum');
        $sadrzajEditor = trim($sadrzajEditor);
        if ($facebookLink !== null) {
            $facebookBlok = $this->izradiFacebookBlokZaSadrzaj($facebookLink);
            $clanak->sadrzaj = $sadrzajEditor === '' ? $facebookBlok : $sadrzajEditor . PHP_EOL . $facebookBlok;
        } else {
            $clanak->sadrzaj = $sadrzajEditor;
        }
        $clanak->menu_naslov = $request->input('menu_naslov');
        if ($request->has('menu')) {
            $clanak->menu = true;
        } else {
            $clanak->menu = false;
        }
        $clanak->save();
        return redirect()->route('admin.clanci.uredjivanje', $clanak->id);
    }

    /**
     * Uključuje ili isključuje prikaz galerije na odabranom članku.
     */
    public function galerija(Request $request)
    {
        $clanak = Clanci::findOrFail((int)$request->input('id_clanka'));
        if ($request->has('galerija')) {
            $clanak->galerija = true;
        } else {
            $clanak->galerija = false;
        }
        $clanak->save();
        return redirect()->route('admin.clanci.uredjivanje', $clanak->id);
    }

    /**
     * Prikazuje administrativni popis svih članaka (bez obzira na vrstu).
     */
    public function popisClanaka()
    {
        $clanci = Clanci::orderByDesc('datum')->paginate(15);
        return view('admin.clanci.popis', ['clanci' => $clanci]);

    }

    /**
     * Prikazuje pojedini članak u administratorskom prikazu s punim sadržajem.
     */
    public function pokaziClanak(Clanci $clanak): View
    {
        return view('admin.clanci.prikazClanka', ['clanak' => $clanak]);
    }

    /**
     * Prikazuje popis članaka filtriran po vrsti (npr. Obavijest, O nama).
     */
    public function popisClanakaPoVrsti(string $vrsta)
    {
        $clanci = Clanci::where('vrsta', '=', $vrsta)->orderByDesc('datum')->paginate(5);
        return view('admin.clanci.popisClanaka', ['vrsta' => $vrsta, 'clanci' => $clanci]);
    }

    /**
     * Validira upload datoteka, sprema ih u storage i upisuje metapodatke u bazu.
     */
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
        $clanak = Clanci::findOrFail((int)$request->input('clanak_id'));
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

    /**
     * Briše odabrani zapis i po potrebi čisti povezane podatke/datoteke.
     */
    public function brisanjeMedija(Request $request): RedirectResponse
    {
        $medij = MedijiClanaka::findOrFail((int)$request->input('medijBrisanje'));
        Storage::delete('public/clanci/' . $medij->clanak->id . '/' . $medij->link);
        $medij->delete();
        return redirect()->route('admin.clanci.uredjivanje', $medij->clanak->id);
    }

    /**
     * Normalizira i validira Facebook URL prije spremanja u sadržaj.
     */
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

    /**
     * Iz postojećeg HTML sadržaja izdvaja spremljeni Facebook link ako postoji.
     */
    private function izvuciFacebookLinkIzSadrzaja(?string $sadrzaj): ?string
    {
        return $this->extractFacebookLinkFromHtml($sadrzaj);
    }

    /**
     * Uklanja automatski Facebook blok iz sadržaja kako bi se zapis mogao ponovno sigurno spremiti.
     */
    private function ukloniFacebookBlokIzSadrzaja(?string $sadrzaj): string
    {
        return $this->stripFacebookBlockFromHtml($sadrzaj);
    }

    /**
     * Generira HTML blok s Facebook poveznicom koji se umeće u sadržaj članka/turnira.
     */
    private function izradiFacebookBlokZaSadrzaj(string $facebookLink): string
    {
        return $this->buildFacebookBlockHtml($facebookLink);
    }

}
