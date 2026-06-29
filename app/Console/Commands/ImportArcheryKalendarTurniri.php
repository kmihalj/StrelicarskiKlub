<?php

namespace App\Console\Commands;

use App\Models\NadolazeciTurnir;
use App\Models\TipoviTurnira;
use Carbon\Carbon;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class ImportArcheryKalendarTurniri extends Command
{
    private const DEFAULT_SOURCE_URL = 'https://www.archery.hr/kalendar.php?lang=hr&module=1';

    private const TITLE_UPDATE_SIMILARITY_THRESHOLD = 50.0;

    protected $signature = 'turniri:import-archery
        {--url= : URL izvora kalendara}
        {--year=* : Godina(e) za import, npr. --year=2026}
        {--include-past : Uključi i prošle turnire}
        {--dry-run : Samo pregled bez spremanja u bazu}
        {--skip-existing : Ne mijenja postojeće zapise (uvozi samo nove)}
        {--tip-fallback-id= : Fallback tip turnira ID ako disciplina nije prepoznata}';

    protected $description = 'Uvozi nadolazeće turnire s archery.hr kalendara';

    public function handle(): int
    {
        $url = trim((string) ($this->option('url') ?: self::DEFAULT_SOURCE_URL));
        if ($url === '') {
            $this->error('URL izvora ne može biti prazan.');

            return self::FAILURE;
        }

        $years = collect((array) $this->option('year'))
            ->map(static fn (mixed $value): int => (int) $value)
            ->filter(static fn (int $value): bool => $value > 0)
            ->unique()
            ->values();
        $yearFilter = $years->isNotEmpty() ? $years->all() : null;

        $includePast = (bool) $this->option('include-past');
        $dryRun = (bool) $this->option('dry-run');
        $skipExisting = (bool) $this->option('skip-existing');
        $fallbackTipId = (int) ($this->option('tip-fallback-id') ?? 0);
        if ($fallbackTipId <= 0) {
            $fallbackTipId = null;
        }

        $this->info('Dohvaćam kalendar s: '.$url);
        $response = Http::timeout(30)->retry(2, 300)->get($url);
        if (! $response->successful()) {
            $this->error('Neuspješno dohvaćanje kalendara. HTTP: '.$response->status());

            return self::FAILURE;
        }

        $records = $this->parseRowsFromHtml($response->body(), $yearFilter);
        if (count($records) === 0) {
            $this->warn('Nema pronađenih redaka za zadane kriterije.');

            return self::SUCCESS;
        }

        $tipovi = TipoviTurnira::query()->get(['id', 'naziv']);
        if ($tipovi->isEmpty()) {
            $this->error('Nema dostupnih tipova turnira (tablica tipovi_turniras je prazna).');

            return self::FAILURE;
        }
        if ($fallbackTipId !== null && ! $tipovi->contains(static fn (TipoviTurnira $tip): bool => (int) $tip->id === $fallbackTipId)) {
            $this->error('Neispravan --tip-fallback-id: ne postoji tip turnira s ID-om '.$fallbackTipId.'.');

            return self::FAILURE;
        }

        $today = now()->startOfDay();
        $created = 0;
        $updated = 0;
        $skippedPast = 0;
        $skippedNoTip = 0;
        $skippedInvalidDate = 0;
        $skippedExisting = 0;
        $processed = 0;
        $skippedNoTipDetails = [];
        $skippedInvalidDateDetails = [];
        $similarityUpdateDetails = [];

        foreach ($records as $record) {
            $parsedDate = $this->parseDateRange((string) $record['datum_raw'], (int) $record['year']);
            if ($parsedDate === null) {
                $skippedInvalidDate++;
                $detail = $record['naziv'].' | '.$record['mjesto'].' | datum: '.$record['datum_raw'];
                $skippedInvalidDateDetails[] = $detail;
                $this->warn('Preskočeno (nevažeći datum): '.$detail);

                continue;
            }

            $datumOd = $parsedDate['datum_od'];
            $datumDo = $parsedDate['datum_do'];
            $napomena = $parsedDate['napomena'];
            $krajTurnira = $datumDo ?? $datumOd;

            if (! $includePast && $krajTurnira->lt($today)) {
                $skippedPast++;

                continue;
            }

            $tipId = $this->resolveTipTurniraId((string) $record['disciplina'], $tipovi, (string) $record['naziv']);
            if ($tipId === null && $fallbackTipId !== null) {
                $tipId = $fallbackTipId;
            }

            if ($tipId === null) {
                $skippedNoTip++;
                $detail = $record['naziv'].' | '.$record['mjesto'].' | disciplina: '.$record['disciplina'];
                $skippedNoTipDetails[] = $detail;
                $this->warn('Preskočeno (nepoznata disciplina): '.$detail);

                continue;
            }

            $processed++;
            $match = $this->findExistingTurnirForImport(
                $datumOd,
                (string) $record['naziv'],
                (string) $record['mjesto'],
            );
            $turnir = $match['turnir'];
            $matchType = $match['type'];
            $matchSimilarity = $match['similarity'];

            if ($dryRun) {
                $this->line('[DRY-RUN] '.$this->describeImportAction(
                    $turnir,
                    $matchType,
                    $matchSimilarity,
                    $skipExisting,
                ).' | '.$datumOd->format('d.m.Y.')
                    .($datumDo ? ' - '.$datumDo->format('d.m.Y.') : '')
                    .' | '.$record['naziv'].' | '.$record['disciplina'].' -> tip ID '.$tipId);

                continue;
            }

            if (! $turnir instanceof NadolazeciTurnir) {
                $turnir = new NadolazeciTurnir;
                $created++;
            } else {
                if ($skipExisting) {
                    $skippedExisting++;

                    continue;
                }

                $updated++;
                if ($matchType === 'similarity') {
                    $similarityUpdateDetails[] = $this->formatSimilarityUpdateDetail($turnir, (string) $record['naziv'], $matchSimilarity);
                }
            }

            $turnir->naziv = trim((string) $record['naziv']);
            $turnir->organizator = $this->normalizeText((string) $record['organizator']);
            $turnir->mjesto = trim((string) $record['mjesto']);
            $turnir->datum = $datumOd->toDateString();
            $turnir->datum_do = $datumDo?->toDateString();
            $turnir->napomena = $napomena;
            $turnir->tipovi_turnira_id = (int) $tipId;
            $turnir->updated_by = null;
            $turnir->save();
        }

        $this->newLine();
        $this->info('Import završen.');
        $this->line('Ukupno redaka izvora: '.count($records));
        $this->line('Obrađeno: '.$processed.($dryRun ? ' (dry-run)' : ''));
        $this->line('Kreirano: '.$created);
        $this->line('Ažurirano: '.$updated);
        $this->line('Preskočeno (prošli): '.$skippedPast);
        $this->line('Preskočeno (disciplina nepoznata): '.$skippedNoTip);
        $this->line('Preskočeno (datum nevažeći): '.$skippedInvalidDate);
        $this->line('Preskočeno (postojeći zapisi): '.$skippedExisting);

        if (count($similarityUpdateDetails) > 0) {
            $this->newLine();
            $this->warn('Ažurirano po istom datumu i sličnosti naziva:');
            foreach ($similarityUpdateDetails as $detail) {
                $this->line('- '.$detail);
            }
        }

        if (count($skippedNoTipDetails) > 0) {
            $this->newLine();
            $this->warn('Popis turnira preskočenih zbog nepoznate discipline:');
            foreach (array_values(array_unique($skippedNoTipDetails)) as $detail) {
                $this->line('- '.$detail);
            }
        }

        if (count($skippedInvalidDateDetails) > 0) {
            $this->newLine();
            $this->warn('Popis turnira preskočenih zbog nevažećeg datuma:');
            foreach (array_values(array_unique($skippedInvalidDateDetails)) as $detail) {
                $this->line('- '.$detail);
            }
        }

        return self::SUCCESS;
    }

    /**
     * @return array{turnir:?NadolazeciTurnir,type:?string,similarity:float}
     */
    private function findExistingTurnirForImport(Carbon $datumOd, string $naziv, string $mjesto): array
    {
        $exact = NadolazeciTurnir::query()
            ->whereDate('datum', $datumOd->toDateString())
            ->where('naziv', $naziv)
            ->where('mjesto', $mjesto)
            ->first();
        if ($exact instanceof NadolazeciTurnir) {
            return [
                'turnir' => $exact,
                'type' => 'exact',
                'similarity' => 100.0,
            ];
        }

        $sourceTitle = $this->normalizeTitleForMatch($naziv);
        if ($sourceTitle === '') {
            return [
                'turnir' => null,
                'type' => null,
                'similarity' => 0.0,
            ];
        }

        $bestTurnir = null;
        $bestSimilarity = 0.0;
        $candidates = NadolazeciTurnir::query()
            ->whereDate('datum', $datumOd->toDateString())
            ->get(['id', 'naziv', 'mjesto', 'datum']);

        foreach ($candidates as $candidate) {
            if (! $candidate instanceof NadolazeciTurnir) {
                continue;
            }

            $candidateTitle = $this->normalizeTitleForMatch((string) $candidate->naziv);
            if ($candidateTitle === '') {
                continue;
            }

            $similarity = $this->titleSimilarityPercent($sourceTitle, $candidateTitle);
            if ($similarity > $bestSimilarity) {
                $bestSimilarity = $similarity;
                $bestTurnir = $candidate;
            }
        }

        if ($bestTurnir instanceof NadolazeciTurnir && $bestSimilarity >= self::TITLE_UPDATE_SIMILARITY_THRESHOLD) {
            return [
                'turnir' => $bestTurnir,
                'type' => 'similarity',
                'similarity' => $bestSimilarity,
            ];
        }

        return [
            'turnir' => null,
            'type' => null,
            'similarity' => $bestSimilarity,
        ];
    }

    private function describeImportAction(?NadolazeciTurnir $turnir, ?string $matchType, float $similarity, bool $skipExisting): string
    {
        if (! $turnir instanceof NadolazeciTurnir) {
            return 'CREATE';
        }

        if ($skipExisting) {
            return 'SKIP existing';
        }

        if ($matchType === 'similarity') {
            return 'UPDATE similarity '.round($similarity).'% (postojeći ID '.$turnir->id.': '.$turnir->naziv.')';
        }

        return 'UPDATE exact (postojeći ID '.$turnir->id.')';
    }

    private function formatSimilarityUpdateDetail(NadolazeciTurnir $turnir, string $sourceTitle, float $similarity): string
    {
        return round($similarity).'% | postojeći ID '.$turnir->id.': '.$turnir->naziv.' -> '.$sourceTitle;
    }

    /**
     * @return array<int, array{year:int,naziv:string,organizator:string,mjesto:string,datum_raw:string,disciplina:string}>
     */
    private function parseRowsFromHtml(string $html, ?array $yearFilter = null): array
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">'.$html);
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);
        $rows = $this->parseRowsFromKalendarModule($xpath, $yearFilter);
        if (count($rows) > 0) {
            return $rows;
        }

        return $this->parseRowsFromLegacyTables($xpath, $dom, $yearFilter);
    }

    /**
     * @return array<int, array{year:int,naziv:string,organizator:string,mjesto:string,datum_raw:string,disciplina:string}>
     */
    private function parseRowsFromKalendarModule(DOMXPath $xpath, ?array $yearFilter = null): array
    {
        $yearBlocks = $xpath->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' kal-godina-blok ')]");
        if ($yearBlocks === false || $yearBlocks->length === 0) {
            return [];
        }

        $rows = [];
        /** @var DOMElement $yearBlock */
        foreach ($yearBlocks as $yearBlock) {
            $year = (int) $yearBlock->getAttribute('data-godina');
            if ($year <= 0) {
                continue;
            }

            if (is_array($yearFilter) && ! in_array($year, $yearFilter, true)) {
                continue;
            }

            $items = $xpath->query(".//*[contains(concat(' ', normalize-space(@class), ' '), ' kal-accordion-item ')]", $yearBlock);
            if ($items === false) {
                continue;
            }

            /** @var DOMElement $item */
            foreach ($items as $item) {
                $titleNodes = $xpath->query(".//*[contains(concat(' ', normalize-space(@class), ' '), ' kal-accordion-title ')]", $item);
                $titleNode = $titleNodes !== false ? $titleNodes->item(0) : null;
                $naziv = $titleNode instanceof DOMElement ? $this->extractKalendarModuleTitle($titleNode) : null;
                $meta = $this->extractKalendarModuleMeta($xpath, $item);

                $organizator = $meta['organizator'] ?? '';
                $mjesto = $meta['mjesto'] ?? null;
                $datumRaw = $meta['datum'] ?? null;
                $disciplina = $meta['format'] ?? $meta['disciplina'] ?? null;

                if ($naziv === null || $mjesto === null || $datumRaw === null || $disciplina === null) {
                    continue;
                }

                $rows[] = [
                    'year' => $year,
                    'naziv' => $naziv,
                    'organizator' => $organizator,
                    'mjesto' => $mjesto,
                    'datum_raw' => $datumRaw,
                    'disciplina' => $disciplina,
                ];
            }
        }

        return $rows;
    }

    /**
     * @return array<string, string>
     */
    private function extractKalendarModuleMeta(DOMXPath $xpath, DOMElement $item): array
    {
        $meta = [];
        $metaItems = $xpath->query(".//*[contains(concat(' ', normalize-space(@class), ' '), ' kal-meta-item ')]", $item);
        if ($metaItems === false) {
            return $meta;
        }

        /** @var DOMElement $metaItem */
        foreach ($metaItems as $metaItem) {
            $labelNodes = $xpath->query(".//*[contains(concat(' ', normalize-space(@class), ' '), ' kal-meta-label ')]", $metaItem);
            $valueNodes = $xpath->query(".//*[contains(concat(' ', normalize-space(@class), ' '), ' kal-meta-value ')]", $metaItem);
            $labelNode = $labelNodes !== false ? $labelNodes->item(0) : null;
            $valueNode = $valueNodes !== false ? $valueNodes->item(0) : null;

            if (! $labelNode instanceof DOMElement || ! $valueNode instanceof DOMElement) {
                continue;
            }

            $label = $this->normalizeMetaLabel((string) $labelNode->textContent);
            $value = $this->normalizeText((string) $valueNode->textContent);
            if ($label === '' || $value === null) {
                continue;
            }

            if (str_contains($label, 'organizator')) {
                $meta['organizator'] = $value;
            } elseif (str_contains($label, 'mjesto')) {
                $meta['mjesto'] = $value;
            } elseif (str_contains($label, 'datum')) {
                $meta['datum'] = $value;
            } elseif (str_contains($label, 'format')) {
                $meta['format'] = $value;
            } elseif (str_contains($label, 'disciplina')) {
                $meta['disciplina'] = $value;
            }
        }

        return $meta;
    }

    private function extractKalendarModuleTitle(DOMElement $titleNode): ?string
    {
        $text = '';
        foreach ($titleNode->childNodes as $childNode) {
            if ($childNode->nodeType === XML_TEXT_NODE) {
                $text .= ' '.$childNode->textContent;
            }
        }

        return $this->normalizeText($text);
    }

    /**
     * @return array<int, array{year:int,naziv:string,organizator:string,mjesto:string,datum_raw:string,disciplina:string}>
     */
    private function parseRowsFromLegacyTables(DOMXPath $xpath, DOMDocument $dom, ?array $yearFilter = null): array
    {
        $tables = $xpath->query("//table[starts-with(@id, 'tbl_')]");
        if ($tables === false) {
            return [];
        }

        $rows = [];
        /** @var DOMElement $table */
        foreach ($tables as $table) {
            $tableId = (string) $table->getAttribute('id');
            if (! preg_match('/^tbl_(\d{4})$/', $tableId, $matches)) {
                continue;
            }

            $year = (int) $matches[1];
            if (is_array($yearFilter) && ! in_array($year, $yearFilter, true)) {
                continue;
            }

            $trNodes = $xpath->query('.//tbody/tr', $table);
            if ($trNodes === false) {
                continue;
            }

            /** @var DOMElement $tr */
            foreach ($trNodes as $tr) {
                $cellNodes = $xpath->query('./td', $tr);
                if ($cellNodes === false || $cellNodes->length < 6) {
                    continue;
                }

                $naziv = $this->normalizeText((string) $cellNodes->item(1)?->textContent);
                $organizator = $this->normalizeText((string) $cellNodes->item(2)?->textContent);
                $mjesto = $this->normalizeText((string) $cellNodes->item(3)?->textContent);
                $datumRaw = $this->extractDateCellText($cellNodes->item(4), $dom);
                $disciplina = $this->normalizeText((string) $cellNodes->item(5)?->textContent);

                if ($naziv === null || $mjesto === null || $datumRaw === null || $disciplina === null) {
                    continue;
                }

                $rows[] = [
                    'year' => $year,
                    'naziv' => $naziv,
                    'organizator' => $organizator ?? '',
                    'mjesto' => $mjesto,
                    'datum_raw' => $datumRaw,
                    'disciplina' => $disciplina,
                ];
            }
        }

        return $rows;
    }

    private function normalizeMetaLabel(string $value): string
    {
        $normalized = $this->normalizeText($value);
        if ($normalized === null) {
            return '';
        }

        return mb_strtolower($normalized, 'UTF-8');
    }

    private function extractDateCellText(?\DOMNode $node, DOMDocument $dom): ?string
    {
        if ($node === null) {
            return null;
        }

        $html = $dom->saveHTML($node);
        if (! is_string($html) || $html === '') {
            return null;
        }

        // U ćeliji datuma postoji skriveni redni broj u <span>; uklanjamo ga.
        $withoutSpans = preg_replace('/<span\b[^>]*>.*?<\/span>/isu', '', $html);
        $text = strip_tags((string) $withoutSpans);

        return $this->normalizeText($text);
    }

    /**
     * @return array{datum_od:Carbon,datum_do:?Carbon,napomena:?string}|null
     */
    private function parseDateRange(string $rawDate, int $yearFromTable): ?array
    {
        $value = trim($rawDate);
        if ($value === '') {
            return null;
        }
        $value = str_replace(['–', '—'], '-', $value);
        $value = preg_replace('/\s+/u', ' ', $value);
        if (! is_string($value) || trim($value) === '') {
            return null;
        }
        $value = trim($value);

        // 07. i 08.03.2026.
        if (preg_match('/^(\d{1,2})\.\s*i\s*(\d{1,2})\.(\d{1,2})\.(\d{4})\.?$/u', $value, $m) === 1) {
            $od = $this->createDate((int) $m[4], (int) $m[3], (int) $m[1]);
            $do = $this->createDate((int) $m[4], (int) $m[3], (int) $m[2]);

            return $this->buildDatePayload($od, $do, $value);
        }

        // 30.06. i 01.07.2026.
        if (preg_match('/^(\d{1,2})\.(\d{1,2})\.\s*i\s*(\d{1,2})\.(\d{1,2})\.(\d{4})\.?$/u', $value, $m) === 1) {
            $od = $this->createDate((int) $m[5], (int) $m[2], (int) $m[1]);
            $do = $this->createDate((int) $m[5], (int) $m[4], (int) $m[3]);

            return $this->buildDatePayload($od, $do, $value);
        }

        // 07. - 08.03.2026.
        if (preg_match('/^(\d{1,2})\.\s*-\s*(\d{1,2})\.(\d{1,2})\.(\d{4})\.?$/u', $value, $m) === 1) {
            $od = $this->createDate((int) $m[4], (int) $m[3], (int) $m[1]);
            $do = $this->createDate((int) $m[4], (int) $m[3], (int) $m[2]);

            return $this->buildDatePayload($od, $do, $value);
        }

        // 30.06. - 01.07.2026.
        if (preg_match('/^(\d{1,2})\.(\d{1,2})\.?\s*-\s*(\d{1,2})\.(\d{1,2})\.(\d{4})\.?$/u', $value, $m) === 1) {
            $od = $this->createDate((int) $m[5], (int) $m[2], (int) $m[1]);
            $do = $this->createDate((int) $m[5], (int) $m[4], (int) $m[3]);

            return $this->buildDatePayload($od, $do, $value);
        }

        // 07.03.2026. - 08.03.2026.
        if (preg_match('/^(\d{1,2})\.(\d{1,2})\.(\d{4})\.?\s*-\s*(\d{1,2})\.(\d{1,2})\.(\d{4})\.?$/u', $value, $m) === 1) {
            $od = $this->createDate((int) $m[3], (int) $m[2], (int) $m[1]);
            $do = $this->createDate((int) $m[6], (int) $m[5], (int) $m[4]);

            return $this->buildDatePayload($od, $do, $value);
        }

        // 07.03.2026.
        if (preg_match('/^(\d{1,2})\.(\d{1,2})\.(\d{4})\.?$/u', $value, $m) === 1) {
            $od = $this->createDate((int) $m[3], (int) $m[2], (int) $m[1]);
            if (! $od instanceof Carbon) {
                return null;
            }

            return [
                'datum_od' => $od,
                'datum_do' => null,
                'napomena' => null,
            ];
        }

        // Fallback raspon bez godine u ćeliji (uzmi godinu tablice): 07. i 08.03.
        if (preg_match('/^(\d{1,2})\.\s*i\s*(\d{1,2})\.(\d{1,2})\.?$/u', $value, $m) === 1 && $yearFromTable > 0) {
            $od = $this->createDate($yearFromTable, (int) $m[3], (int) $m[1]);
            $do = $this->createDate($yearFromTable, (int) $m[3], (int) $m[2]);

            return $this->buildDatePayload($od, $do, $value);
        }

        // Fallback raspon bez godine u ćeliji (uzmi godinu tablice): 07. - 08.03.
        if (preg_match('/^(\d{1,2})\.\s*-\s*(\d{1,2})\.(\d{1,2})\.?$/u', $value, $m) === 1 && $yearFromTable > 0) {
            $od = $this->createDate($yearFromTable, (int) $m[3], (int) $m[1]);
            $do = $this->createDate($yearFromTable, (int) $m[3], (int) $m[2]);

            return $this->buildDatePayload($od, $do, $value);
        }

        // Fallback raspon bez godine u ćeliji (uzmi godinu tablice): 30.06. - 01.07.
        if (preg_match('/^(\d{1,2})\.(\d{1,2})\.?\s*-\s*(\d{1,2})\.(\d{1,2})\.?$/u', $value, $m) === 1 && $yearFromTable > 0) {
            $od = $this->createDate($yearFromTable, (int) $m[2], (int) $m[1]);
            $do = $this->createDate($yearFromTable, (int) $m[4], (int) $m[3]);

            return $this->buildDatePayload($od, $do, $value);
        }

        // Fallback za stare zapise bez godine u ćeliji (uzmi godinu tablice).
        if (preg_match('/^(\d{1,2})\.(\d{1,2})\.?$/u', $value, $m) === 1 && $yearFromTable > 0) {
            $od = $this->createDate($yearFromTable, (int) $m[2], (int) $m[1]);
            if (! $od instanceof Carbon) {
                return null;
            }

            return [
                'datum_od' => $od,
                'datum_do' => null,
                'napomena' => null,
            ];
        }

        return null;
    }

    /**
     * @return array{datum_od:Carbon,datum_do:?Carbon,napomena:?string}|null
     */
    private function buildDatePayload(?Carbon $od, ?Carbon $do, string $rawValue): ?array
    {
        if (! $od instanceof Carbon || ! $do instanceof Carbon) {
            return null;
        }

        if ($do->lt($od)) {
            [$od, $do] = [$do, $od];
        }

        $days = $od->diffInDays($do);
        $notePrefix = $days === 1 ? 'Turnir traje dva dana: ' : 'Višednevni turnir: ';

        return [
            'datum_od' => $od,
            'datum_do' => $do->isSameDay($od) ? null : $do,
            'napomena' => $do->isSameDay($od) ? null : $notePrefix.$this->normalizeDateText($rawValue),
        ];
    }

    private function createDate(int $year, int $month, int $day): ?Carbon
    {
        try {
            return Carbon::createSafe($year, $month, $day)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }

    private function resolveTipTurniraId(string $disciplina, Collection $tipovi, string $naziv = ''): ?int
    {
        $disciplineNorm = $this->normalizeDiscipline($disciplina);
        if ($disciplineNorm === '') {
            return null;
        }

        $tipNormList = $tipovi->map(static function (TipoviTurnira $tip): array {
            return [
                'id' => (int) $tip->id,
                'norm' => self::normalizeDiscipline((string) $tip->naziv),
            ];
        })->values();

        $findByContains = static function (string $needle) use ($tipNormList): ?int {
            foreach ($tipNormList as $tip) {
                if (str_contains((string) $tip['norm'], $needle)) {
                    return (int) $tip['id'];
                }
            }

            return null;
        };

        if (str_contains($disciplineNorm, '2X25') && str_contains($disciplineNorm, '2X18')) {
            return $findByContains('2X25+2X18') ?? $findByContains('2X25') ?? $findByContains('2X18');
        }

        if ($disciplineNorm === 'WA' && str_contains($this->normalizeDiscipline($naziv), 'BOŽI')) {
            return $findByContains('2X18');
        }

        if (str_contains($disciplineNorm, '1440')) {
            return $findByContains('1440');
        }

        if (str_contains($disciplineNorm, '900')) {
            return $findByContains('900');
        }

        if (str_contains($disciplineNorm, 'FIELD') || str_contains($disciplineNorm, 'AH12+12') || str_contains($disciplineNorm, '12+12')) {
            return $findByContains('FIELD') ?? $findByContains('12+12');
        }

        if (str_contains($disciplineNorm, '3D')) {
            return $findByContains('3D');
        }

        if (str_contains($disciplineNorm, '720')) {
            return $findByContains('720');
        }

        if (str_contains($disciplineNorm, '2X18')) {
            return $findByContains('2X18');
        }

        foreach ($tipNormList as $tip) {
            if ((string) $tip['norm'] === $disciplineNorm) {
                return (int) $tip['id'];
            }
        }

        return null;
    }

    private function normalizeText(string $value): ?string
    {
        $normalized = preg_replace('/\s+/u', ' ', trim($value));
        $normalized = is_string($normalized) ? trim($normalized) : '';

        return $normalized === '' ? null : $normalized;
    }

    private function normalizeTitleForMatch(string $value): string
    {
        $ascii = Str::ascii($value, 'hr');
        $upper = mb_strtoupper($ascii, 'UTF-8');
        $upper = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $upper);
        $upper = is_string($upper) ? preg_replace('/\s+/u', ' ', trim($upper)) : '';

        return is_string($upper) ? trim($upper) : '';
    }

    private function titleSimilarityPercent(string $left, string $right): float
    {
        if ($left === '' || $right === '') {
            return 0.0;
        }

        if ($left === $right) {
            return 100.0;
        }

        similar_text($left, $right, $sequencePercent);

        $leftTokens = array_values(array_unique(array_filter(explode(' ', $left))));
        $rightTokens = array_values(array_unique(array_filter(explode(' ', $right))));
        $tokenPercent = 0.0;
        if (count($leftTokens) > 0 && count($rightTokens) > 0) {
            $intersection = count(array_intersect($leftTokens, $rightTokens));
            $tokenPercent = (2 * $intersection / (count($leftTokens) + count($rightTokens))) * 100;
        }

        return max((float) $sequencePercent, $tokenPercent);
    }

    private static function normalizeDiscipline(string $value): string
    {
        $upper = mb_strtoupper($value, 'UTF-8');
        $upper = str_replace([' ', ' '], '', $upper);
        $upper = preg_replace('/[^\p{L}\p{N}\+\-]/u', '', $upper);

        return is_string($upper) ? $upper : '';
    }

    private function normalizeDateText(string $value): string
    {
        $normalized = preg_replace('/\s+/u', ' ', trim($value));

        return is_string($normalized) ? $normalized : trim($value);
    }
}
