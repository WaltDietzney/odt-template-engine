<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Integration;

use DOMDocument;
use OdtTemplateEngine\Elements\RichTable;
use OdtTemplateEngine\Elements\RichTableCell;
use OdtTemplateEngine\Document\StyleRequirement;
use OdtTemplateEngine\OdtTemplate;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;
use ZipArchive;

final class RichTableRelativeColumnWidthSemanticOwnershipTest extends TestCase
{
    /** @var list<string> */
    private array $outputs = [];

    protected function tearDown(): void
    {
        foreach ($this->outputs as $output) {
            if (is_file($output)) {
                unlink($output);
            }
        }
    }

    public function testRatioWidthsProduceRelativeSemanticColumnRequirements(): void
    {
        $table = (new RichTable())
            ->setTableName('RatioTable');
        $table->setColumnWidthRatios([2, 1, 1]);

        $requirements = iterator_to_array($table->getOwnStyleRequirements(), false);

        self::assertCount(3, $requirements);
        foreach ($requirements as $index => $requirement) {
            self::assertInstanceOf(StyleRequirement::class, $requirement);
            self::assertSame('co' . $index, $requirement->name());
            self::assertSame('table-column', $requirement->family());
            self::assertSame(StyleRequirement::SCOPE_AUTOMATIC, $requirement->scope());
            self::assertSame(StyleRequirement::PART_CONTENT, $requirement->documentPart());
            self::assertSame(
                ['style:rel-column-width' => ['32766*', '16383*', '16386*'][$index]],
                $requirement->propertyGroups()['style:table-column-properties']
            );
        }

        $widths = array_map(
            static fn (StyleRequirement $requirement): int => (int) rtrim(
                $requirement->propertyGroups()['style:table-column-properties']['style:rel-column-width'], '*'
            ),
            $requirements
        );
        self::assertSame(65535, array_sum($widths));
    }

    public function testRelativeWidthRemainderGoesToFinalColumn(): void
    {
        $table = new RichTable();
        $table->setColumnWidthRatios([1, 1]);
        $requirements = iterator_to_array($table->getOwnStyleRequirements(), false);

        self::assertSame('32767*', $requirements[0]->propertyGroups()['style:table-column-properties']['style:rel-column-width']);
        self::assertSame('32768*', $requirements[1]->propertyGroups()['style:table-column-properties']['style:rel-column-width']);
    }

    public function testRatioWidthsProduceThreeLogicalColumnsWithoutArtificialSpans(): void
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        self::assertTrue($dom->loadXML(
            '<office:document-content xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0" xmlns:table="urn:oasis:names:tc:opendocument:xmlns:table:1.0"><office:automatic-styles/></office:document-content>'
        ));

        $table = (new RichTable())
            ->setTableName('RatioTable')
            ->addRow(['A', 'B', 'C']);
        $table->setColumnWidthRatios([2, 1, 1]);
        $dom->documentElement->appendChild($table->toDomNode($dom));

        $xml = $dom->saveXML() ?: '';
        self::assertSame(3, substr_count($xml, '<table:table-column '));
        self::assertSame(1, substr_count($xml, 'table:style-name="co0"'));
        self::assertSame(1, substr_count($xml, 'table:style-name="co1"'));
        self::assertSame(1, substr_count($xml, 'table:style-name="co2"'));
        self::assertStringNotContainsString('table:number-columns-repeated', $xml);
        self::assertStringNotContainsString('table:number-columns-spanned', $xml);
    }

    #[RunInSeparateProcess]
    public function testNormalSetElementMaterializesRelativeColumnsInContentOnly(): void
    {
        $table = (new RichTable())
            ->setTableName('RatioTable')
            ->addRow(['A', 'B', 'C']);
        $table->setColumnWidthRatios([2, 1, 1]);

        $template = new OdtTemplate($this->templatePath());
        $template->setElement('my_list', $table);
        $output = $this->outputPath();
        $template->save($output);

        $content = $this->entry($output, 'content.xml');
        $styles = $this->entry($output, 'styles.xml');

        self::assertSame(1, $this->styleCount($content, 'co0', 'table-column'));
        self::assertSame(1, $this->styleCount($content, 'co1', 'table-column'));
        self::assertSame(1, $this->styleCount($content, 'co2', 'table-column'));
        self::assertSame(0, $this->styleCount($styles, 'co0', 'table-column'));
        self::assertSame(0, $this->styleCount($styles, 'co1', 'table-column'));
        self::assertSame(0, $this->styleCount($styles, 'co2', 'table-column'));
        self::assertStringContainsString('style:rel-column-width="32766*"', $content);
        self::assertSame(1, substr_count($content, 'style:rel-column-width="16383*"'));
        self::assertSame(1, substr_count($content, 'style:rel-column-width="16386*"'));
        self::assertSame(1, substr_count($content, 'table:style-name="co0"'));
        self::assertSame(1, substr_count($content, 'table:style-name="co1"'));
        self::assertSame(1, substr_count($content, 'table:style-name="co2"'));
        self::assertStringNotContainsString('table:number-columns-repeated', $content);
        self::assertStringNotContainsString('table:number-columns-spanned', $content);
    }

    #[RunInSeparateProcess]
    public function testRepeatedSaveDoesNotDuplicateRelativeColumns(): void
    {
        $table = (new RichTable())->addRow(['A', 'B', 'C']);
        $table->setColumnWidthRatios([2, 1, 1]);
        $template = new OdtTemplate($this->templatePath());
        $template->setElement('my_list', $table);
        $this->outputs[] = $first = $this->outputPath();
        $this->outputs[] = $second = $this->outputPath();
        $template->save($first);
        $template->save($second);

        $content = $this->entry($second, 'content.xml');
        self::assertSame(1, $this->styleCount($content, 'co0', 'table-column'));
        self::assertSame(1, $this->styleCount($content, 'co1', 'table-column'));
        self::assertSame(1, $this->styleCount($content, 'co2', 'table-column'));
    }

    public function testCallerDefinedColspanRemainsStructural(): void
    {
        $spanned = (new RichTableCell('A'))->setColspan(2);
        $table = (new RichTable())
            ->addRow([$spanned, new RichTableCell('B'), new RichTableCell('C')]);
        $table->setColumnWidthRatios([2, 1, 1]);
        $dom = new DOMDocument('1.0', 'UTF-8');
        self::assertTrue($dom->loadXML(
            '<office:document-content xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0"><office:automatic-styles/></office:document-content>'
        ));

        $dom->documentElement->appendChild($table->toDomNode($dom));
        $xml = $dom->saveXML() ?: '';

        self::assertSame(1, substr_count($xml, 'table:number-columns-spanned="2"'));
        self::assertStringNotContainsString('table:number-columns-spanned="6"', $xml);
        self::assertStringNotContainsString('table:number-columns-spanned="3"', $xml);
    }

    public function testExplicitWidthsRemainAbsoluteSemanticRequirements(): void
    {
        $table = (new RichTable())->addRow(['A', 'B']);
        $table->setColumnWidths(['2cm', '10cm']);
        $requirements = iterator_to_array($table->getOwnStyleRequirements(), false);

        self::assertSame('style:column-width', array_key_first(
            $requirements[0]->propertyGroups()['style:table-column-properties']
        ));
        self::assertSame('2cm', $requirements[0]->propertyGroups()['style:table-column-properties']['style:column-width']);
        self::assertSame('10cm', $requirements[1]->propertyGroups()['style:table-column-properties']['style:column-width']);
    }

    public function testRatiosRetainExistingPrecedenceOverExplicitWidths(): void
    {
        $table = (new RichTable())->addRow(['A', 'B', 'C']);
        $table->setColumnWidths(['2cm', '10cm']);
        $table->setColumnWidthRatios([2, 1, 1]);
        $requirements = iterator_to_array($table->getOwnStyleRequirements(), false);

        self::assertCount(3, $requirements);
        self::assertSame('32766*', $requirements[0]->propertyGroups()['style:table-column-properties']['style:rel-column-width']);
    }

    private function templatePath(): string
    {
        return dirname(__DIR__, 2) . '/samples/templates/template_18_ListStyles.odt';
    }

    private function outputPath(): string
    {
        $path = sys_get_temp_dir() . '/sr07h-ratio-' . bin2hex(random_bytes(4)) . '.odt';
        $this->outputs[] = $path;
        return $path;
    }

    private function entry(string $path, string $entry): string
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

    private function styleCount(string $xml, string $name, string $family): int
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        self::assertTrue($dom->loadXML($xml));
        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('style', 'urn:oasis:names:tc:opendocument:xmlns:style:1.0');
        return $xpath->query(sprintf(
            '//style:style[@style:name="%s" and @style:family="%s"]',
            $name,
            $family
        ))->length;
    }
}
