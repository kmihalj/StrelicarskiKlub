<?php

namespace App\Services;

use App\Models\Clanovi;
use App\Models\RezultatiOpci;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

/**
 * Dinamičke metrike za članak 40 (poziv na suradnju).
 */
class Article40DynamicStatsService
{
    public const ARTICLE_ID = 40;
    private const DYNAMIC_ALLOWED_HOSTS = [
        'piko.webhop.me',
        'pc-kmihalj.srce.hr',
        'skdubrava.hr',
    ];

    private const RANGE_YEAR_FROM = 2022;
    private const RANGE_YEAR_TO = 2026;
    private const CACHE_KEY = 'clanci.article40.metrics.v1';
    private const CACHE_TTL_MINUTES = 30;

    /**
     * Vraća HTML sadržaj s ažuriranim brojkama za članak 40.
     */
    public function renderArticleContent(int $articleId, ?string $content): string
    {
        $html = (string) $content;
        if (
            $articleId !== self::ARTICLE_ID
            || trim($html) === ''
            || ! $this->isDynamicEnabledForCurrentHost()
        ) {
            return $html;
        }

        $metrics = $this->metrics();

        return $this->replaceMetricsInTable($html, $metrics);
    }

    /**
     * Dohvaća izračunate metrike (uz cache).
     *
     * @return array{active_members:int,licensed_active_members:int,individual_appearances:int,total_medals:int}
     */
    public function metrics(): array
    {
        return Cache::remember(
            self::CACHE_KEY,
            now()->addMinutes(self::CACHE_TTL_MINUTES),
            fn (): array => $this->calculateMetrics()
        );
    }

    /**
     * Računa brojke po pravilima iz zahtjeva.
     *
     * @return array{active_members:int,licensed_active_members:int,individual_appearances:int,total_medals:int}
     */
    private function calculateMetrics(): array
    {
        $activeMembers = Clanovi::query()
            ->where('aktivan', true)
            ->count();

        $licensedActiveMembers = Clanovi::query()
            ->where('aktivan', true)
            ->whereNotNull('broj_licence')
            ->whereRaw("TRIM(broj_licence) REGEXP '^[0-9]+$'")
            ->count();

        $individualAppearances = (int) RezultatiOpci::query()
            ->join('turniris', 'turniris.id', '=', 'rezultati_opcis.turnir_id')
            ->whereYear('turniris.datum', '>=', self::RANGE_YEAR_FROM)
            ->whereYear('turniris.datum', '<=', self::RANGE_YEAR_TO)
            ->select(['rezultati_opcis.clan_id', 'rezultati_opcis.turnir_id'])
            ->distinct()
            ->count();

        $individualResults = RezultatiOpci::query()
            ->join('turniris', 'turniris.id', '=', 'rezultati_opcis.turnir_id')
            ->whereYear('turniris.datum', '>=', self::RANGE_YEAR_FROM)
            ->whereYear('turniris.datum', '<=', self::RANGE_YEAR_TO)
            ->select([
                'turniris.eliminacije',
                'rezultati_opcis.plasman',
                'rezultati_opcis.plasman_nakon_eliminacija',
                'rezultati_opcis.bez_eliminacija',
            ])
            ->get();

        $individualMedals = 0;
        foreach ($individualResults as $result) {
            $placementForMedal = $this->resolveIndividualPlacementForMedal(
                (int) data_get($result, 'eliminacije', 0) === 1,
                (int) data_get($result, 'plasman', 0),
                data_get($result, 'plasman_nakon_eliminacija') === null
                    ? null
                    : (int) data_get($result, 'plasman_nakon_eliminacija', 0),
                (int) data_get($result, 'bez_eliminacija', 0) === 1
            );

            if (in_array($placementForMedal, [1, 2, 3], true)) {
                $individualMedals++;
            }
        }

        $teamMedals = 0;
        if (Schema::hasTable('rezultati_timovi') && Schema::hasTable('rezultati_tim_clanovi')) {
            $teamMedals = (int) DB::table('rezultati_tim_clanovi as rtc')
                ->join('rezultati_timovi as rt', 'rt.id', '=', 'rtc.rezultati_tim_id')
                ->join('turniris as t', 't.id', '=', 'rt.turnir_id')
                ->whereYear('t.datum', '>=', self::RANGE_YEAR_FROM)
                ->whereYear('t.datum', '<=', self::RANGE_YEAR_TO)
                ->whereIn('rt.plasman', [1, 2, 3])
                ->count();
        }

        return [
            'active_members' => (int) $activeMembers,
            'licensed_active_members' => (int) $licensedActiveMembers,
            'individual_appearances' => (int) $individualAppearances,
            'total_medals' => (int) ($individualMedals + $teamMedals),
        ];
    }

    /**
     * Isto pravilo kao u statistici rezultata.
     */
    private function resolveIndividualPlacementForMedal(
        bool $hasEliminations,
        int $placement,
        ?int $placementAfterEliminations,
        bool $withoutEliminations
    ): int {
        if (! $hasEliminations || $withoutEliminations) {
            return $placement;
        }

        return $placementAfterEliminations ?? 0;
    }

    /**
     * Mijenja numeričke ćelije u tablici metrika unutar članka 40.
     *
     * @param  array{active_members:int,licensed_active_members:int,individual_appearances:int,total_medals:int}  $metrics
     */
    private function replaceMetricsInTable(string $html, array $metrics): string
    {
        $libXmlInternalErrors = libxml_use_internal_errors(true);

        try {
            $dom = new DOMDocument('1.0', 'UTF-8');
            $wrappedHtml = '<div id="article40-metrics-wrapper">'.$html.'</div>';
            $loaded = $dom->loadHTML(
                '<?xml encoding="UTF-8">'.$wrappedHtml,
                LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
            );

            if (! $loaded) {
                return $html;
            }

            $xpath = new DOMXPath($dom);
            $tableNodeList = $xpath->query('//div[@id="article40-metrics-wrapper"]//table[.//th]');
            if (! $tableNodeList || $tableNodeList->length === 0) {
                return $html;
            }

            $targetTable = null;
            foreach ($tableNodeList as $tableNode) {
                if (! ($tableNode instanceof DOMElement)) {
                    continue;
                }

                $headerIndexes = $this->detectMetricColumnIndexes($xpath, $tableNode);
                if (count($headerIndexes) === 4) {
                    $targetTable = [$tableNode, $headerIndexes];
                    break;
                }
            }

            if ($targetTable === null) {
                return $html;
            }

            /** @var DOMElement $tableElement */
            $tableElement = $targetTable[0];
            /** @var array<string, int> $headerIndexes */
            $headerIndexes = $targetTable[1];

            $dataRow = $xpath->query('.//tbody/tr[1]', $tableElement)?->item(0);
            if (! ($dataRow instanceof DOMElement)) {
                return $html;
            }

            $cells = $xpath->query('./td', $dataRow);
            if (! $cells || $cells->length === 0) {
                return $html;
            }

            $this->writeMetricValue($cells, $headerIndexes['active_members'] ?? null, $metrics['active_members']);
            $this->writeMetricValue($cells, $headerIndexes['licensed_active_members'] ?? null, $metrics['licensed_active_members']);
            $this->writeMetricValue($cells, $headerIndexes['individual_appearances'] ?? null, $metrics['individual_appearances']);
            $this->writeMetricValue($cells, $headerIndexes['total_medals'] ?? null, $metrics['total_medals']);

            $wrapper = $xpath->query('//div[@id="article40-metrics-wrapper"]')->item(0);
            if (! ($wrapper instanceof DOMElement)) {
                return $html;
            }

            $output = '';
            foreach ($wrapper->childNodes as $childNode) {
                $output .= $dom->saveHTML($childNode);
            }

            return $output !== '' ? $output : $html;
        } catch (Throwable) {
            return $html;
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($libXmlInternalErrors);
        }
    }

    /**
     * @return array<string, int>
     */
    private function detectMetricColumnIndexes(DOMXPath $xpath, DOMElement $table): array
    {
        $headers = $xpath->query('.//thead/tr[1]/th', $table);
        if (! $headers || $headers->length === 0) {
            return [];
        }

        $indexes = [];
        foreach ($headers as $index => $headerNode) {
            $normalized = $this->normalizeLabel((string) $headerNode->textContent);

            if (str_contains($normalized, 'aktivni clanovi')) {
                $indexes['active_members'] = $index;
                continue;
            }

            if (str_contains($normalized, 'licencirani clanovi')) {
                $indexes['licensed_active_members'] = $index;
                continue;
            }

            if (str_contains($normalized, 'nastupi')) {
                $indexes['individual_appearances'] = $index;
                continue;
            }

            if (str_contains($normalized, 'medalje')) {
                $indexes['total_medals'] = $index;
            }
        }

        return $indexes;
    }

    private function writeMetricValue(\DOMNodeList $cells, ?int $index, int $value): void
    {
        if ($index === null || $index < 0 || $index >= $cells->length) {
            return;
        }

        $cell = $cells->item($index);
        if (! ($cell instanceof DOMElement)) {
            return;
        }

        foreach ($cell->childNodes as $childNode) {
            if ($childNode instanceof DOMElement && Str::lower($childNode->tagName) === 'strong') {
                $childNode->nodeValue = (string) $value;
                return;
            }
        }

        $cell->nodeValue = (string) $value;
    }

    private function normalizeLabel(string $label): string
    {
        return (string) Str::of($label)
            ->ascii()
            ->lower()
            ->replaceMatches('/\s+/', ' ')
            ->trim();
    }

    private function isDynamicEnabledForCurrentHost(): bool
    {
        $host = strtolower(trim((string) request()->getHost()));
        if ($host === '') {
            return false;
        }

        foreach (self::DYNAMIC_ALLOWED_HOSTS as $allowedHost) {
            if ($host === $allowedHost || str_ends_with($host, '.'.$allowedHost)) {
                return true;
            }
        }

        return false;
    }
}
