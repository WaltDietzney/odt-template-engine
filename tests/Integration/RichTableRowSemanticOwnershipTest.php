<?php

declare(strict_types=1);

namespace OdtTemplateEngineTests\Integration;

use DOMDocument;
use OdtTemplateEngine\Document\StyleRequirement;
use OdtTemplateEngine\Document\StyleRequirementCollector;
use OdtTemplateEngine\Elements\RichTable;
use OdtTemplateEngine\Elements\RichTableCell;
use OdtTemplateEngine\OdtTemplate;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;
use ZipArchive;

final class RichTableRowSemanticOwnershipTest extends TestCase
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

    public function testSupportedRowPropertyProducesSemanticRequirement(): void
    {
        $table = (new RichTable())
            ->setTableName('TestTable')
            ->addRow(['A'], ['min-row-height' => '1cm']);

        $requirements = array_values(array_filter(
            iterator_to_array($table->getOwnStyleRequirements(), false),
            static fn (StyleRequirement $requirement): bool => $requirement->family() === 'table-row'
        ));

        self::assertCount(1, $requirements);
        self::assertSame(StyleRequirement::KIND_DEFINITION, $requirements[0]->kind());
        self::assertSame(StyleRequirement::SCOPE_AUTOMATIC, $requirements[0]->scope());
        self::assertSame(StyleRequirement::PART_CONTENT, $requirements[0]->documentPart());
        self::assertSame('TestTable_ro0', $requirements[0]->name());
        self::assertNull($requirements[0]->parentStyleName());
        self::assertSame(
            ['style:table-row-properties' => ['style:min-row-height' => '1cm']],
            $requirements[0]->propertyGroups()
        );
    }

    public function testUnsupportedOnlyRowsProduceNoRequirement(): void
    {
        $table = (new RichTable())
            ->setTableName('TestTable')
            ->addRow(['A'])
            ->addRow(['B'], ['height' => '2cm', 'foo' => 'bar']);

        self::assertSame([], array_values(array_filter(
            iterator_to_array($table->getOwnStyleRequirements(), false),
            static fn (StyleRequirement $requirement): bool => $requirement->family() === 'table-row'
        )));
    }

    public function testMixedRowStyleProjectsOnlySupportedProperty(): void
    {
        $table = (new RichTable())
            ->setTableName('TestTable')
            ->addRow(['A'], ['min-row-height' => '1cm', 'height' => '2cm', 'foo' => 'bar']);

        $requirements = array_values(array_filter(
            iterator_to_array($table->getOwnStyleRequirements(), false),
            static fn (StyleRequirement $requirement): bool => $requirement->family() === 'table-row'
        ));

        self::assertSame(
            ['style:table-row-properties' => ['style:min-row-height' => '1cm']],
            $requirements[0]->propertyGroups()
        );
    }

    public function testRowNamesUseActualIndicesAndTableName(): void
    {
        $table = (new RichTable())
            ->setTableName('InvoiceItems')
            ->addRow(['A'])
            ->addRow(['B'], ['min-row-height' => '1cm'])
            ->addRow(['C'], ['min-row-height' => '2cm']);

        $requirements = array_values(array_filter(
            iterator_to_array($table->getOwnStyleRequirements(), false),
            static fn (StyleRequirement $requirement): bool => $requirement->family() === 'table-row'
        ));

        self::assertSame(['InvoiceItems_ro1', 'InvoiceItems_ro2'], array_map(
            static fn (StyleRequirement $requirement): string => $requirement->name(),
            $requirements
        ));
    }

    public function testSemanticRequirementsAreCollectedTransitivelyWithColumnsAndCells(): void
    {
        $cell = new RichTableCell('A', ['background' => '#ddeeff']);
        $table = (new RichTable())
            ->setTableName('TestTable')
            ->addRow([$cell], ['min-row-height' => '1cm']);
        $table->setColumnWidths(['2cm']);

        $requirements = iterator_to_array((new StyleRequirementCollector())->collectSemantic($table), false);
        $families = array_map(static fn (StyleRequirement $requirement): string => $requirement->family(), $requirements);

        self::assertContains('table-column', $families);
        self::assertContains('table-row', $families);
        self::assertContains('table-cell', $families);
    }

    #[RunInSeparateProcess]
    public function testNormalSetElementMaterializesOneAutomaticRowDefinitionAndReference(): void
    {
        $table = (new RichTable())
            ->setTableName('TestTable')
            ->addRow(['A'], ['min-row-height' => '1cm'])
            ->addRow(['B']);
        $template = new OdtTemplate($this->templatePath('template_18_ListStyles.odt'));
        $template->setElement('my_list', $table);
        $output = $this->outputPath('semantic-row');
        $template->save($output);

        $content = $this->entry($output, 'content.xml');
        $styles = $this->entry($output, 'styles.xml');
        self::assertSame(1, $this->styleCount($content, 'TestTable_ro0', 'table-row'));
        self::assertSame(0, $this->styleCount($styles, 'TestTable_ro0', 'table-row'));
        self::assertSame(1, substr_count($content, 'table:style-name="TestTable_ro0"'));
        self::assertStringContainsString('style:min-row-height="1cm"', $content);
        self::assertSame(0, substr_count($content, 'table:style-name="TestTable_ro1"'));
    }

    #[RunInSeparateProcess]
    public function testRepeatedSemanticSaveDoesNotDuplicateRowDefinition(): void
    {
        $table = (new RichTable())
            ->setTableName('TestTable')
            ->addRow(['A'], ['min-row-height' => '1cm']);
        $template = new OdtTemplate($this->templatePath('template_18_ListStyles.odt'));
        $template->setElement('my_list', $table);
        $first = $this->outputPath('row-first');
        $second = $this->outputPath('row-second');
        $template->save($first);
        $template->save($second);

        self::assertSame(1, $this->styleCount($this->entry($second, 'content.xml'), 'TestTable_ro0', 'table-row'));
    }

    public function testDirectDomRenderingReferencesRowWithoutMaterializingSemanticDefinition(): void
    {
        $dom = $this->contentDom();
        $table = (new RichTable())
            ->setTableName('TestTable')
            ->addRow(['A'], ['min-row-height' => '1cm']);
        $dom->documentElement->appendChild($table->toDomNode($dom));

        $xml = $dom->saveXML() ?: '';
        self::assertStringContainsString('table:style-name="TestTable_ro0"', $xml);
        self::assertSame(0, $this->styleCount($xml, 'TestTable_ro0', 'table-row'));
    }

    private const OFFICE_NS = 'urn:oasis:names:tc:opendocument:xmlns:office:1.0';
    private const STYLE_NS = 'urn:oasis:names:tc:opendocument:xmlns:style:1.0';

    private function contentDom(): DOMDocument
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->loadXML('<office:document-content xmlns:office="' . self::OFFICE_NS . '" xmlns:style="' . self::STYLE_NS . '" xmlns:table="urn:oasis:names:tc:opendocument:xmlns:table:1.0"><office:automatic-styles/></office:document-content>');
        return $dom;
    }

    private function templatePath(string $name): string
    {
        return dirname(__DIR__, 2) . '/samples/templates/' . $name;
    }

    private function outputPath(string $name): string
    {
        $path = sys_get_temp_dir() . '/sr07e2-' . $name . '-' . bin2hex(random_bytes(4)) . '.odt';
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
        $xpath->registerNamespace('style', self::STYLE_NS);
        return $xpath->query(sprintf(
            '//style:style[@style:name="%s" and @style:family="%s"]',
            $name,
            $family
        ))->length;
    }
}
