<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Integration;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;
use OdtTemplateEngine\Elements\Paragraph;
use OdtTemplateEngine\Elements\SectionElement;
use OdtTemplateEngine\OdtTemplate;
use PHPUnit\Framework\TestCase;
use ZipArchive;

/** H01 closeout: engine collection lifecycle and optional Writer round-trip. */
final class H01SectionAuthoringClosureTest extends TestCase
{
    /** @var list<string> */
    private array $paths = [];

    protected function tearDown(): void
    {
        foreach (array_reverse($this->paths) as $path) {
            if (is_file($path)) {
                unlink($path);
            }
            if (is_dir($path)) {
                $this->removeDirectory($path);
            }
        }
    }

    public function testEngineLifecyclePopulatesZeroOneAndThreeStationsAfterSaveAndReopen(): void
    {
        $generated = $this->createGeneratedTemplate();
        foreach ([0, 1, 3] as $count) {
            $template = new OdtTemplate($generated);
            $this->assertSectionStructure($template);
            $this->populateAndAssertStations($template, $count);
            $output = $this->temporaryPath('h01-engine-' . $count, '.odt');
            $template->save($output);
            $reopened = new OdtTemplate($output);
            $this->assertFinalStations($reopened, $count);
            self::assertSame([], $reopened->inspect()->diagnostics());
            $this->assertNoUnresolvedPlaceholdersInBody($output);
            $this->writeVisualCandidate($output, $count);
        }

        $emptyGroup = new OdtTemplate($generated);
        $emptyGroup->section('H01StationsGroup')->instantiateMany([]);
        self::assertSame([], array_filter(
            $emptyGroup->inspect()->sections(),
            static fn ($section): bool => str_starts_with($section->name(), 'H01StationsGroup')
        ));
    }

    public function testWriterRoundTripResolvesActualSectionStylesAndRetainsCollectionBehavior(): void
    {
        $writerRoundTrip = $this->writerRoundTrip($this->createGeneratedTemplate());
        $template = new OdtTemplate($writerRoundTrip);
        $this->assertSectionStructure($template);
        $this->assertActualSectionStyles($writerRoundTrip);

        foreach ([0, 1, 3] as $count) {
            $case = new OdtTemplate($writerRoundTrip);
            $this->populateAndAssertStations($case, $count);
            $output = $this->temporaryPath('h01-writer-' . $count, '.odt');
            $case->save($output);
            $reopened = new OdtTemplate($output);
            $this->assertFinalStations($reopened, $count);
            self::assertSame([], $reopened->inspect()->diagnostics());
            $this->assertNoUnresolvedPlaceholdersInBody($output);
            $this->writeVisualCandidate($output, $count, 'writer');
        }
    }

    private function createGeneratedTemplate(): string
    {
        $output = $this->temporaryPath('h01-generated', '.odt');
        $template = new OdtTemplate(__DIR__ . '/../../samples/templates/template_C06_section_authoring.odt');
        $template->styles()->defineSection('H01GroupColumns', [
            'background-color' => '#F5F7FA',
            'columns' => ['count' => 2, 'relative-widths' => [1, 1], 'gap' => '0.25cm', 'balance' => true],
        ]);
        $template->styles()->defineSection('H01StationColumns', [
            'columns' => ['count' => 3, 'relative-widths' => [1, 2, 1], 'gap' => '0.2cm', 'balance' => false],
        ]);

        // The public API provides a styled paragraph, not a text:h constructor.
        $station = (new SectionElement('H01Station'))
            ->setSectionStyle('H01StationColumns')
            ->addElement((new Paragraph('Heading 1'))->addText('STATION HEADING {{station}}'))
            ->addElement((new Paragraph())->addText('{{detail}}'));
        $group = (new SectionElement('H01StationsGroup'))
            ->setSectionStyle('H01GroupColumns')
            ->addSection($station);
        $template->appendSection($group);
        $template->save($output);
        return $output;
    }

    private function writerRoundTrip(string $generated): string
    {
        $directory = $this->temporaryDirectory('h01-writer-roundtrip');
        $profile = $this->temporaryDirectory('h01-writer-profile');
        $command = sprintf(
            'soffice --headless --nologo --nodefault --nofirststartwizard -env:UserInstallation=%s --convert-to odt --outdir %s %s 2>&1',
            escapeshellarg('file://' . $profile), escapeshellarg($directory), escapeshellarg($generated)
        );
        exec($command, $output, $exitCode);
        $roundTrip = $directory . '/' . basename($generated);
        if ($exitCode !== 0 || !is_file($roundTrip)) {
            self::markTestSkipped('LibreOffice Writer round-trip unavailable in this environment: ' . implode("\n", $output));
        }
        return $roundTrip;
    }

    private function populateAndAssertStations(OdtTemplate $template, int $count): void
    {
        $groups = $template->section('H01StationsGroup')->instantiateMany([[]]);
        self::assertCount(1, $groups);
        $stations = [];
        for ($index = 1; $index <= $count; ++$index) {
            $stations[] = ['station' => 'Station ' . $index, 'detail' => 'Detail ' . $index];
        }
        $instances = $groups[0]->section('H01Station')->instantiateMany($stations);
        self::assertCount($count, $instances);
        self::assertSame($count, substr_count($groups[0]->text(), 'STATION HEADING'));
        if ($count === 0) {
            self::assertStringNotContainsString('STATION HEADING', $groups[0]->text());
        }
        for ($index = 1; $index <= $count; ++$index) {
            self::assertStringContainsString('STATION HEADING Station ' . $index, $groups[0]->text());
            self::assertStringContainsString('Detail ' . $index, $groups[0]->text());
        }
    }

    private function assertFinalStations(OdtTemplate $template, int $count): void
    {
        $sections = $template->inspect()->sections();
        $groups = array_values(array_filter($sections, static fn ($section): bool => str_starts_with($section->name(), 'H01StationsGroup_')));
        self::assertCount(1, $groups);
        $groupText = $template->section($groups[0]->name())->text();
        self::assertSame($count, substr_count($groupText, 'STATION HEADING'));
        if ($count === 0) {
            self::assertStringNotContainsString('STATION HEADING', $groupText);
        }
        for ($index = 1; $index <= $count; ++$index) {
            self::assertStringContainsString('STATION HEADING Station ' . $index, $groupText);
            self::assertStringContainsString('Detail ' . $index, $groupText);
        }
    }

    private function assertSectionStructure(OdtTemplate $template): void
    {
        $sections = array_map(static fn ($section): string => $section->name(), $template->inspect()->sections());
        self::assertContains('H01StationsGroup', $sections);
        self::assertContains('H01Station', $sections);
        $this->assertActualSectionStyles($this->saveForInspection($template));
    }

    private function assertActualSectionStyles(string $path): void
    {
        $xpath = $this->contentXPath($this->zipEntry($path, 'content.xml'));
        $groupStyle = $this->sectionStyleName($xpath, 'H01StationsGroup');
        $stationStyle = $this->sectionStyleName($xpath, 'H01Station');
        self::assertNotSame('', $groupStyle);
        self::assertNotSame('', $stationStyle);
        self::assertSame('2', $this->styleValue($xpath, $groupStyle, 'string(style:section-properties/style:columns/@fo:column-count)'));
        self::assertSame('#f5f7fa', strtolower($this->styleValue($xpath, $groupStyle, 'string(style:section-properties/@fo:background-color)')));
        self::assertSame('false', $this->styleValue($xpath, $groupStyle, 'string(style:section-properties/@text:dont-balance-text-columns)'));
        self::assertSame(['32767*', '32768*'], $this->styleValues($xpath, $groupStyle, 'style:section-properties/style:columns/style:column/@style:rel-width'));
        self::assertSame('0.25cm', $this->styleValue($xpath, $groupStyle, 'string(style:section-properties/style:columns/@fo:column-gap)'));
        self::assertSame('3', $this->styleValue($xpath, $stationStyle, 'string(style:section-properties/style:columns/@fo:column-count)'));
        self::assertSame('true', $this->styleValue($xpath, $stationStyle, 'string(style:section-properties/@text:dont-balance-text-columns)'));
        self::assertSame(['16383*', '32767*', '16385*'], $this->styleValues($xpath, $stationStyle, 'style:section-properties/style:columns/style:column/@style:rel-width'));
        foreach (['fo:start-indent', 'fo:end-indent'] as $attribute) {
            $indents = $this->styleValues($xpath, $stationStyle, 'style:section-properties/style:columns/style:column/@' . $attribute);
            self::assertCount(3, $indents);
            $expected = $attribute === 'fo:start-indent' ? [0.0, 0.1, 0.1] : [0.1, 0.1, 0.0];
            foreach ($indents as $index => $indent) {
                self::assertStringEndsWith('cm', $indent);
                self::assertEqualsWithDelta($expected[$index], (float) $indent, 0.002);
            }
        }
        self::assertSame(1.0, $xpath->evaluate('count(//text:section[@text:name="H01StationsGroup"]//text:section[@text:name="H01Station"])'));
    }

    private function sectionStyleName(DOMXPath $xpath, string $sectionName): string
    {
        return (string) $xpath->evaluate('string((//text:section[@text:name=' . $this->xpathLiteral($sectionName) . ']/@text:style-name)[1])');
    }

    private function styleValue(DOMXPath $xpath, string $styleName, string $expression): string
    {
        return (string) $xpath->evaluate($expression, $this->styleNode($xpath, $styleName));
    }

    /** @return list<string> */
    private function styleValues(DOMXPath $xpath, string $styleName, string $expression): array
    {
        $nodes = $xpath->query($expression, $this->styleNode($xpath, $styleName));
        return array_map(static fn (DOMNode $node): string => $node->nodeValue ?? '', iterator_to_array($nodes));
    }

    private function styleNode(DOMXPath $xpath, string $styleName): DOMElement
    {
        $node = $xpath->query('//office:automatic-styles/style:style[@style:name=' . $this->xpathLiteral($styleName) . ' and @style:family="section"]')->item(0);
        self::assertInstanceOf(DOMElement::class, $node);
        return $node;
    }

    private function assertNoUnresolvedPlaceholdersInBody(string $path): void
    {
        $xpath = $this->contentXPath($this->zipEntry($path, 'content.xml'));
        self::assertStringNotContainsString('{{', (string) $xpath->evaluate('string(//office:text)'));
    }

    private function saveForInspection(OdtTemplate $template): string
    {
        return $this->temporaryPath('h01-inspection', '.odt', $template);
    }

    private function temporaryPath(string $prefix, string $suffix, ?OdtTemplate $template = null): string
    {
        $path = sys_get_temp_dir() . '/' . $prefix . '-' . uniqid('', true) . $suffix;
        $this->paths[] = $path;
        if ($template !== null) {
            $template->save($path);
        }
        return $path;
    }

    private function writeVisualCandidate(string $source, int $count, string $kind = 'engine'): void
    {
        $directory = getenv('H01_VISUAL_DIR');
        if ($directory === false || $directory === '') {
            return;
        }
        if (!is_dir($directory)) {
            mkdir($directory, 0700, true);
        }
        $target = rtrim($directory, '/') . '/h01-' . $kind . '-' . $count . '-' . date('YmdHis') . '-' . bin2hex(random_bytes(3)) . '.odt';
        copy($source, $target);
    }

    private function zipEntry(string $path, string $entry): string
    {
        $zip = new ZipArchive();
        self::assertTrue($zip->open($path) === true);
        try {
            $value = $zip->getFromName($entry);
            self::assertIsString($value);
            return $value;
        } finally {
            $zip->close();
        }
    }

    private function contentXPath(string $content): DOMXPath
    {
        $dom = new DOMDocument();
        self::assertTrue($dom->loadXML($content));
        $xpath = new DOMXPath($dom);
        foreach ([
            'office' => 'urn:oasis:names:tc:opendocument:xmlns:office:1.0',
            'style' => 'urn:oasis:names:tc:opendocument:xmlns:style:1.0',
            'fo' => 'urn:oasis:names:tc:opendocument:xmlns:xsl-fo-compatible:1.0',
            'text' => 'urn:oasis:names:tc:opendocument:xmlns:text:1.0',
        ] as $prefix => $namespace) {
            $xpath->registerNamespace($prefix, $namespace);
        }
        return $xpath;
    }

    private function xpathLiteral(string $value): string
    {
        return str_contains($value, '"') ? "'" . $value . "'" : '"' . $value . '"';
    }

    private function temporaryDirectory(string $prefix): string
    {
        $path = sys_get_temp_dir() . '/' . $prefix . '-' . uniqid('', true);
        mkdir($path, 0700, true);
        $this->paths[] = $path;
        return $path;
    }

    private function removeDirectory(string $directory): void
    {
        foreach (scandir($directory) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $path = $directory . '/' . $entry;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }
        rmdir($directory);
    }
}
