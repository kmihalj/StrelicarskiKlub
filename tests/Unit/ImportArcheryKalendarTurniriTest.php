<?php

namespace Tests\Unit;

use App\Console\Commands\ImportArcheryKalendarTurniri;
use App\Models\TipoviTurnira;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class ImportArcheryKalendarTurniriTest extends TestCase
{
    public function test_parses_current_archery_calendar_module_html(): void
    {
        $html = <<<'HTML'
<div class="kal-godina-blok" data-godina="2026">
    <div class="kal-accordion-item">
        <button class="kal-accordion-toggle">
            <span class="kal-accordion-title">
                PRVENSTVO HRVATSKE - DVORANA
                <span class="kal-datum-pill">07.03.2026.</span>
                <span class="kal-status-pill najavljeno">Najavljeno</span>
            </span>
        </button>
        <div class="kal-turnir-body">
            <div class="kal-meta-grid">
                <div class="kal-meta-item">
                    <span class="kal-meta-label">Organizator</span>
                    <span class="kal-meta-value">SK Rijeka</span>
                </div>
                <div class="kal-meta-item">
                    <span class="kal-meta-label">Mjesto</span>
                    <span class="kal-meta-value">Rijeka</span>
                </div>
                <div class="kal-meta-item">
                    <span class="kal-meta-label">Datum</span>
                    <span class="kal-meta-value">07.03.2026. – 08.03.2026.</span>
                </div>
                <div class="kal-meta-item">
                    <span class="kal-meta-label">Format</span>
                    <span class="kal-meta-value">WA 2X18+OR</span>
                </div>
            </div>
        </div>
    </div>
</div>
HTML;

        $records = $this->parseRows($html, [2026]);

        $this->assertSame([
            [
                'year' => 2026,
                'naziv' => 'PRVENSTVO HRVATSKE - DVORANA',
                'organizator' => 'SK Rijeka',
                'mjesto' => 'Rijeka',
                'datum_raw' => '07.03.2026. – 08.03.2026.',
                'disciplina' => 'WA 2X18+OR',
            ],
        ], $records);

        $this->assertSame([], $this->parseRows($html, [2027]));
    }

    public function test_keeps_legacy_archery_table_parser_for_explicit_old_sources(): void
    {
        $html = <<<'HTML'
<table id="tbl_2026">
    <tbody>
        <tr>
            <td>1</td>
            <td>CEC 4th Leg</td>
            <td>SK</td>
            <td>Vinicène, Slovačka</td>
            <td><span>20260704</span>04.07.2026.</td>
            <td>WA 720</td>
        </tr>
    </tbody>
</table>
HTML;

        $records = $this->parseRows($html, [2026]);

        $this->assertSame([
            [
                'year' => 2026,
                'naziv' => 'CEC 4th Leg',
                'organizator' => 'SK',
                'mjesto' => 'Vinicène, Slovačka',
                'datum_raw' => '04.07.2026.',
                'disciplina' => 'WA 720',
            ],
        ], $records);
    }

    public function test_resolves_generic_wa_christmas_tournament_as_indoor_2x18(): void
    {
        $tip2x18 = new TipoviTurnira;
        $tip2x18->id = 1;
        $tip2x18->naziv = 'WA 2x18';

        $tip720 = new TipoviTurnira;
        $tip720->id = 4;
        $tip720->naziv = 'WA 720';

        $command = new ImportArcheryKalendarTurniri;
        $method = new ReflectionMethod(ImportArcheryKalendarTurniri::class, 'resolveTipTurniraId');
        $tipovi = collect([$tip2x18, $tip720]);

        $this->assertSame(1, $method->invoke($command, 'WA', $tipovi, 'XXXIII Božićni turnir'));
        $this->assertNull($method->invoke($command, 'WA', $tipovi, 'Generički vanjski turnir'));
    }

    public function test_title_similarity_handles_minor_archery_calendar_name_changes(): void
    {
        $command = new ImportArcheryKalendarTurniri;
        $normalize = new ReflectionMethod(ImportArcheryKalendarTurniri::class, 'normalizeTitleForMatch');
        $similarity = new ReflectionMethod(ImportArcheryKalendarTurniri::class, 'titleSimilarityPercent');

        $source = $normalize->invoke($command, 'Samobor Grand Prix 2026 WA STAR');
        $existing = $normalize->invoke($command, 'SAMOBOR GRAND PRIX 2026. -WA STAR');
        $unrelated = $normalize->invoke($command, 'CEC Finale');

        $this->assertGreaterThanOrEqual(50.0, $similarity->invoke($command, $source, $existing));
        $this->assertLessThan(50.0, $similarity->invoke($command, $source, $unrelated));
    }

    /**
     * @return array<int, array{year:int,naziv:string,organizator:string,mjesto:string,datum_raw:string,disciplina:string}>
     */
    private function parseRows(string $html, ?array $yearFilter): array
    {
        $command = new ImportArcheryKalendarTurniri;
        $method = new ReflectionMethod(ImportArcheryKalendarTurniri::class, 'parseRowsFromHtml');

        return $method->invoke($command, $html, $yearFilter);
    }
}
