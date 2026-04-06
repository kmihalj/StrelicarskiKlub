<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class UputeController extends Controller
{
    /**
     * Prikazuje listu dostupnih markdown uputa i sadržaj odabranog dokumenta.
     */
    public function index(Request $request): View
    {
        $docsRoot = $this->docsRoot();
        if (!is_dir($docsRoot)) {
            abort(404);
        }

        $dokumenti = collect(File::files($docsRoot))
            ->filter(fn (\SplFileInfo $file) => strtolower((string) $file->getExtension()) === 'md')
            ->sortBy(function (\SplFileInfo $file): array {
                $filename = $file->getFilename();

                return [$filename !== 'README.md', $filename];
            })
            ->values()
            ->map(function (\SplFileInfo $file): array {
                $putanja = $file->getFilename();
                $sadrzaj = $this->ucitajDatoteku($putanja);

                return [
                    'putanja' => $putanja,
                    'naslov' => $this->izvuciNaslov($sadrzaj, $putanja),
                ];
            });

        if ($dokumenti->isEmpty()) {
            abort(404);
        }

        $zadanaPutanja = $dokumenti->contains(fn (array $item) => $item['putanja'] === 'README.md')
            ? 'README.md'
            : (string) $dokumenti->first()['putanja'];

        $trazenaPutanja = $this->normalizirajRelativnuPutanju((string) $request->query('dok', $zadanaPutanja));
        $postojiDokument = $dokumenti->contains(fn (array $item) => $item['putanja'] === $trazenaPutanja);
        if (!$postojiDokument) {
            abort(404);
        }

        $markdown = $this->ucitajDatoteku((string) $trazenaPutanja);
        $naslov = $dokumenti
            ->first(fn (array $item) => $item['putanja'] === $trazenaPutanja)['naslov']
            ?? $trazenaPutanja;

        return view('javno.upute.index', [
            'uputeDokumenti' => $dokumenti,
            'uputeAktivniDokument' => $trazenaPutanja,
            'uputeNaslov' => $naslov,
            'uputeSadrzajHtml' => $this->renderirajMarkdown($markdown, (string) $trazenaPutanja),
        ]);
    }

    /**
     * Poslužuje statičke datoteke korištene unutar markdown uputa (npr. screenshotove).
     */
    public function asset(string $path): BinaryFileResponse
    {
        $relativnaPutanja = $this->normalizirajRelativnuPutanju($path);
        if ($relativnaPutanja === null) {
            abort(404);
        }

        $apsolutnaPutanja = $this->apsolutnaPutanja($relativnaPutanja);
        if ($apsolutnaPutanja === null || !is_file($apsolutnaPutanja)) {
            abort(404);
        }

        return response()->file($apsolutnaPutanja);
    }

    private function renderirajMarkdown(string $markdown, string $trenutniDokument): string
    {
        $html = Str::markdown($markdown, [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);

        return $this->prepraviRelativnePoveznice($html, $trenutniDokument);
    }

    private function prepraviRelativnePoveznice(string $html, string $trenutniDokument): string
    {
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $libxmlErrors = libxml_use_internal_errors(true);
        $wrapped = '<!DOCTYPE html><html><body>'.$html.'</body></html>';
        $dom->loadHTML(mb_convert_encoding($wrapped, 'HTML-ENTITIES', 'UTF-8'));
        libxml_clear_errors();
        libxml_use_internal_errors($libxmlErrors);

        foreach ($dom->getElementsByTagName('img') as $img) {
            $src = (string) $img->getAttribute('src');
            $resolved = $this->razrijesiRelativnuPutanju($src, $trenutniDokument);
            if ($resolved === null) {
                continue;
            }

            if ($this->apsolutnaPutanja($resolved) !== null) {
                $img->setAttribute('src', route('javno.upute.asset', ['path' => $resolved]));
            }
        }

        foreach ($dom->getElementsByTagName('a') as $a) {
            $href = (string) $a->getAttribute('href');
            [$hrefBezSufiksa, $sufiks] = $this->odvojiSufiks($href);
            $resolved = $this->razrijesiRelativnuPutanju($hrefBezSufiksa, $trenutniDokument);
            if ($resolved === null) {
                continue;
            }

            if ($this->apsolutnaPutanja($resolved) === null) {
                continue;
            }

            $novaPoveznica = strtolower((string) pathinfo($resolved, PATHINFO_EXTENSION)) === 'md'
                ? route('javno.upute', ['dok' => $resolved]).$sufiks
                : route('javno.upute.asset', ['path' => $resolved]).$sufiks;

            $a->setAttribute('href', $novaPoveznica);
        }

        $body = $dom->getElementsByTagName('body')->item(0);
        if (!$body instanceof \DOMElement) {
            return $html;
        }

        $rezultat = '';
        foreach ($body->childNodes as $node) {
            $rezultat .= $dom->saveHTML($node);
        }

        return $rezultat;
    }

    /**
     * Vraća [putanja_bez_upita_fragmenta, suffix(upit+fragment)].
     */
    private function odvojiSufiks(string $url): array
    {
        $fragment = '';
        $query = '';
        $base = $url;

        $hashPos = strpos($base, '#');
        if ($hashPos !== false) {
            $fragment = substr($base, $hashPos);
            $base = substr($base, 0, $hashPos);
        }

        $queryPos = strpos($base, '?');
        if ($queryPos !== false) {
            $query = substr($base, $queryPos);
            $base = substr($base, 0, $queryPos);
        }

        return [$base, $query.$fragment];
    }

    private function razrijesiRelativnuPutanju(string $url, string $trenutniDokument): ?string
    {
        $url = trim($url);
        if ($url === '' || $this->jeVanjskaPoveznica($url)) {
            return null;
        }

        $osnovniDirektorij = dirname($trenutniDokument);
        $osnovniDirektorij = $osnovniDirektorij === '.' ? '' : $osnovniDirektorij;
        $kombinirana = ltrim($osnovniDirektorij.'/'.$url, '/');

        return $this->normalizirajRelativnuPutanju($kombinirana);
    }

    private function jeVanjskaPoveznica(string $url): bool
    {
        if ($url === '' || str_starts_with($url, '#')) {
            return true;
        }

        if (str_starts_with($url, '/')) {
            return true;
        }

        return (bool) preg_match('/^[a-z][a-z0-9+\-.]*:/i', $url);
    }

    private function ucitajDatoteku(string $relativnaPutanja): string
    {
        $apsolutnaPutanja = $this->apsolutnaPutanja($relativnaPutanja);
        if ($apsolutnaPutanja === null || !is_file($apsolutnaPutanja)) {
            abort(404);
        }

        $sadrzaj = (string) File::get($apsolutnaPutanja);
        if (str_starts_with($sadrzaj, "\xEF\xBB\xBF")) {
            $sadrzaj = substr($sadrzaj, 3);
        }

        return $sadrzaj;
    }

    private function apsolutnaPutanja(string $relativnaPutanja): ?string
    {
        $normalizirana = $this->normalizirajRelativnuPutanju($relativnaPutanja);
        if ($normalizirana === null) {
            return null;
        }

        $root = $this->docsRoot();
        $target = realpath($root.'/'.$normalizirana);
        if ($target === false) {
            return null;
        }

        $realRoot = realpath($root);
        if ($realRoot === false) {
            return null;
        }

        $prefiks = rtrim($realRoot, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
        if (!str_starts_with($target, $prefiks) && $target !== $realRoot) {
            return null;
        }

        return $target;
    }

    private function docsRoot(): string
    {
        return base_path('docs');
    }

    private function normalizirajRelativnuPutanju(?string $putanja): ?string
    {
        $putanja = trim((string) $putanja);
        if ($putanja === '') {
            return null;
        }

        $putanja = str_replace('\\', '/', rawurldecode($putanja));
        $putanja = ltrim($putanja, '/');

        $dijelovi = array_filter(explode('/', $putanja), static fn (string $dio): bool => $dio !== '' && $dio !== '.');
        $normalizirani = [];
        foreach ($dijelovi as $dio) {
            if ($dio === '..') {
                if (empty($normalizirani)) {
                    return null;
                }
                array_pop($normalizirani);
                continue;
            }
            $normalizirani[] = $dio;
        }

        if (empty($normalizirani)) {
            return null;
        }

        return implode('/', $normalizirani);
    }

    private function izvuciNaslov(string $markdown, string $fallback): string
    {
        if (preg_match('/^\h*#\h+(.+)\h*$/m', $markdown, $matches) === 1) {
            return trim((string) $matches[1]);
        }

        $ime = pathinfo($fallback, PATHINFO_FILENAME);

        return Str::of($ime)->replace(['-', '_'], ' ')->title()->value();
    }
}

