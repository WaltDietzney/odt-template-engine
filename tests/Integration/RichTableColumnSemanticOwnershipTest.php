<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Integration;

use DOMDocument;
use DOMElement;
use OdtTemplateEngine\Document\StyleRequirement;
use OdtTemplateEngine\Document\StyleRequirementMaterializer;
use OdtTemplateEngine\Elements\RichTable;
use OdtTemplateEngine\OdtDocumentContext;
use OdtTemplateEngine\OdtTemplate;
use OdtTemplateEngine\Utils\StyleWriter;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;
use ZipArchive;

final class RichTableColumnSemanticOwnershipTest extends TestCase
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

    public function testExplicitWidthsProducePositionalSemanticColumnRequirements(): void
    {
        $table = (new RichTable())->addRow(['A', 'B']);
        $table->setColumnWidths(['2cm', '4cm']);
        $requirements = iterator_to_array($table->getOwnStyleRequirements(), false);

        self::assertCount(2, $requirements);
        self::assertSame(['co0', 'co1'], array_map(
            static fn (StyleRequirement $requirement): string => $requirement->name(),
            $requirements
        ));
        self::assertSame('table-column', $requirements[0]->family());
        self::assertSame(StyleRequirement::SCOPE_AUTOMATIC, $requirements[0]->scope());
        self::assertSame(StyleRequirement::PART_CONTENT, $requirements[0]->documentPart());
        self::assertSame('2cm', $requirements[0]->propertyGroups()['style:table-column-properties']['style:column-width']);
        self::assertSame('4cm', $requirements[1]->propertyGroups()['style:table-column-properties']['style:column-width']);
    }

    public function testTablesWithoutColumnWidthsProduceNoColumnRequirements(): void
    {
        self::assertSame([], iterator_to_array((new RichTable())->addRow(['A'])->getOwnStyleRequirements(), false));

        $ratioTable = (new RichTable())->addRow(['A', 'B']);
        $ratioTable->setColumnWidthRatios([1, 2]);
        $requirements = iterator_to_array($ratioTable->getOwnStyleRequirements(), false);
        self::assertCount(2, $requirements);
        self::assertSame('21845*', $requirements[0]->propertyGroups()['style:table-column-properties']['style:rel-column-width']);
        self::assertSame('43690*', $requirements[1]->propertyGroups()['style:table-column-properties']['style:rel-column-width']);
    }

    public function testRatioColumnsUseSemanticReferencesWithoutVirtualRepeats(): void
    {
        $dom = $this->dom($this->contentXml());
        $table = (new RichTable())->addRow(['A', 'B']);
        $table->setColumnWidthRatios([1, 2]);
        $dom->documentElement->appendChild($table->toDomNode($dom));

        $xml = $dom->saveXML() ?: '';
        self::assertSame(2, substr_count($xml, '<table:table-column '));
        self::assertSame(1, substr_count($xml, 'table:style-name="co0"'));
        self::assertSame(1, substr_count($xml, 'table:style-name="co1"'));
        self::assertStringNotContainsString('table:number-columns-repeated', $xml);
    }

    #[RunInSeparateProcess]
    public function testNormalSetElementMaterializesEachSemanticColumnOnce(): void
    {
        $table = (new RichTable())->addRow(['A', 'B']);
        $table->setColumnWidths(['2cm', '4cm']);
        $template = new OdtTemplate($this->templatePath('template_18_ListStyles.odt'));
        $template->setElement('my_list', $table);
        $output = $this->outputPath('semantic-columns');
        $template->save($output);

        $content = $this->entry($output, 'content.xml');
        $styles = $this->entry($output, 'styles.xml');
        self::assertSame(1, $this->styleCount($content, 'co0', 'table-column'));
        self::assertSame(1, $this->styleCount($content, 'co1', 'table-column'));
        self::assertSame(0, $this->styleCount($styles, 'co0', 'table-column'));
        self::assertSame(0, $this->styleCount($styles, 'co1', 'table-column'));
        self::assertSame(1, substr_count($content, 'table:style-name="co0"'));
        self::assertSame(1, substr_count($content, 'table:style-name="co1"'));
        self::assertStringContainsString('style:column-width="2cm"', $content);
        self::assertStringContainsString('style:column-width="4cm"', $content);
    }

    public function testAuthoredAutomaticColumnIdentityRemainsAuthoritative(): void
    {
        $context = $this->context();
        $automatic = $context->contentDom()->getElementsByTagNameNS(self::OFFICE_NS, 'automatic-styles')->item(0);
        self::assertInstanceOf(DOMElement::class, $automatic);
        $existing = $context->contentDom()->createElementNS(self::STYLE_NS, 'style:style');
        $existing->setAttributeNS(self::STYLE_NS, 'style:name', 'co0');
        $existing->setAttributeNS(self::STYLE_NS, 'style:family', 'table-column');
        $properties = $context->contentDom()->createElementNS(self::STYLE_NS, 'style:table-column-properties');
        $properties->setAttributeNS(self::STYLE_NS, 'style:column-width', '9cm');
        $existing->appendChild($properties);
        $automatic->appendChild($existing);

        $requirement = new StyleRequirement(
            StyleRequirement::KIND_DEFINITION,
            StyleRequirement::SCOPE_AUTOMATIC,
            'table-column',
            StyleRequirement::PART_CONTENT,
            'co0',
            null,
            ['style:table-column-properties' => ['style:column-width' => '2cm']]
        );
        (new StyleRequirementMaterializer())->materialize($context, $requirement);

        self::assertSame(1, $this->styleCount($context->contentDom()->saveXML() ?: '', 'co0', 'table-column'));
        self::assertSame('9cm', $properties->getAttributeNS(self::STYLE_NS, 'column-width'));
    }

    public function testDirectRichTableRenderingKeepsColumnCompatibility(): void
    {
        $dom = $this->dom($this->contentXml());
        $table = (new RichTable())->addRow(['A', 'B']);
        $table->setColumnWidths(['2cm', '4cm']);
        $dom->documentElement->appendChild($table->toDomNode($dom));

        $xml = $dom->saveXML() ?: '';
        self::assertSame(1, $this->styleCount($xml, 'co0', 'table-column'));
        self::assertSame(1, $this->styleCount($xml, 'co1', 'table-column'));
        self::assertStringContainsString('table:style-name="co0"', $xml);
        self::assertStringContainsString('table:style-name="co1"', $xml);
    }

    #[RunInSeparateProcess]
    public function testRepeatedSemanticSaveDoesNotDuplicateColumns(): void
    {
        $table = (new RichTable())->addRow(['A', 'B']);
        $table->setColumnWidths(['2cm', '4cm']);
        $template = new OdtTemplate($this->templatePath('template_18_ListStyles.odt'));
        $template->setElement('my_list', $table);
        $first = $this->outputPath('columns-first');
        $second = $this->outputPath('columns-second');
        $template->save($first);
        $template->save($second);

        self::assertSame(1, $this->styleCount($this->entry($second, 'content.xml'), 'co0', 'table-column'));
        self::assertSame(1, $this->styleCount($this->entry($second, 'content.xml'), 'co1', 'table-column'));
    }

    private const OFFICE_NS = 'urn:oasis:names:tc:opendocument:xmlns:office:1.0';
    private const STYLE_NS = 'urn:oasis:names:tc:opendocument:xmlns:style:1.0';

    private function contentXml(): string
    {
        return '<office:document-content xmlns:office="' . self::OFFICE_NS . '" xmlns:style="' . self::STYLE_NS . '" xmlns:table="urn:oasis:names:tc:opendocument:xmlns:table:1.0"><office:automatic-styles/></office:document-content>';
    }

    private function context(): OdtDocumentContext
    {
        return new OdtDocumentContext(
            $this->dom($this->contentXml()),
            $this->dom('<office:document-styles xmlns:office="' . self::OFFICE_NS . '" xmlns:style="' . self::STYLE_NS . '"><office:styles/></office:document-styles>'),
            $this->dom('<office:document-meta xmlns:office="' . self::OFFICE_NS . '"/>')
        );
    }

    private function templatePath(string $name): string
    {
        return dirname(__DIR__, 2) . '/samples/templates/' . $name;
    }

    private function outputPath(string $name): string
    {
        $path = sys_get_temp_dir() . '/sr07d-' . $name . '-' . bin2hex(random_bytes(4)) . '.odt';
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

    private function dom(string $xml): DOMDocument
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        self::assertTrue($dom->loadXML($xml));
        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('table', 'urn:oasis:names:tc:opendocument:xmlns:table:1.0');
        $xpath->registerNamespace('style', self::STYLE_NS);
        return $dom;
    }

    private function styleCount(string $xml, string $name, string $family): int
    {
        $dom = $this->dom($xml);
        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('style', self::STYLE_NS);
        return $xpath->query(sprintf(
            '//style:style[@style:name="%s" and @style:family="%s"]',
            $name,
            $family
        ))->length;
    }
}
