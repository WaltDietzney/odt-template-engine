<?php

declare(strict_types=1);

namespace OdtTemplateEngineTests\Integration;

use DOMDocument;
use OdtTemplateEngine\Document\StyleRequirement;
use OdtTemplateEngine\Document\StyleRequirementMaterializer;
use OdtTemplateEngine\Elements\RichTable;
use OdtTemplateEngine\OdtDocumentContext;
use OdtTemplateEngine\OdtTemplate;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;
use ZipArchive;

final class RichTableSemanticOwnershipTest extends TestCase
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

    public function testTableWithoutStyleNameProducesNoTableRequirement(): void
    {
        $requirements = iterator_to_array(
            (new RichTable())->addRow(['A'])->getOwnStyleRequirements(),
            false
        );

        self::assertSame([], array_values(array_filter(
            $requirements,
            static fn (StyleRequirement $requirement): bool => $requirement->family() === 'table'
        )));
    }

    public function testUnknownTableStyleNameProducesReferenceOnlyRequirement(): void
    {
        $table = (new RichTable())
            ->setTableName('TestTable')
            ->setTableStyleName('ExternalStyle')
            ->addRow(['A']);
        $requirements = array_values(array_filter(
            iterator_to_array($table->getOwnStyleRequirements(), false),
            static fn (StyleRequirement $requirement): bool => $requirement->family() === 'table'
        ));

        self::assertCount(1, $requirements);
        self::assertSame(StyleRequirement::KIND_REFERENCE, $requirements[0]->kind());
        self::assertSame('table', $requirements[0]->family());
        self::assertSame('ExternalStyle', $requirements[0]->name());
        self::assertNull($requirements[0]->scope());
        self::assertNull($requirements[0]->documentPart());
        self::assertSame([], $requirements[0]->propertyGroups());
    }

    #[RunInSeparateProcess]
    public function testReferenceOnlyInsertionPreservesReferenceWithoutFabricatingDefinition(): void
    {
        $table = (new RichTable())
            ->setTableStyleName('UnknownStyle')
            ->addRow(['A']);
        $template = new OdtTemplate($this->templatePath('template_11_table.odt'));
        $template->setElement('tableblock', $table);
        $output = $this->outputPath('reference-only');
        $template->save($output);

        $content = $this->entry($output, 'content.xml');
        $styles = $this->entry($output, 'styles.xml');
        self::assertStringContainsString('table:style-name="UnknownStyle"', $content);
        self::assertSame(0, $this->styleCount($content, 'UnknownStyle', 'table'));
        self::assertSame(0, $this->styleCount($styles, 'UnknownStyle', 'table'));
    }

    public function testAuthoredCommonTableDefinitionRemainsAuthoritative(): void
    {
        $context = $this->context();
        $styles = $context->stylesDom()->getElementsByTagNameNS(self::OFFICE_NS, 'styles')->item(0);
        self::assertNotNull($styles);
        $existing = $context->stylesDom()->createElementNS(self::STYLE_NS, 'style:style');
        $existing->setAttributeNS(self::STYLE_NS, 'style:name', 'AuthoredTable');
        $existing->setAttributeNS(self::STYLE_NS, 'style:family', 'table');
        $properties = $context->stylesDom()->createElementNS(self::STYLE_NS, 'style:table-properties');
        $properties->setAttribute('table:align', 'center');
        $existing->appendChild($properties);
        $styles->appendChild($existing);

        $table = (new RichTable())->setTableStyleName('AuthoredTable');
        $requirement = array_values(array_filter(
            iterator_to_array($table->getOwnStyleRequirements(), false),
            static fn (StyleRequirement $item): bool => $item->family() === 'table'
        ))[0];
        (new StyleRequirementMaterializer())->materialize($context, $requirement);

        $stylesXml = $context->stylesDom()->saveXML() ?: '';
        self::assertSame(1, $this->styleCount($stylesXml, 'AuthoredTable', 'table'));
        self::assertStringContainsString('table:align="center"', $stylesXml);
    }

    public function testDirectTableDomRemainsStructuralOnly(): void
    {
        $dom = $this->contentDom();
        $table = (new RichTable())->setTableStyleName('DirectStyle')->addRow(['A']);
        $dom->documentElement->appendChild($table->toDomNode($dom));

        $xml = $dom->saveXML() ?: '';
        self::assertStringContainsString('table:style-name="DirectStyle"', $xml);
        self::assertSame(0, $this->styleCount($xml, 'DirectStyle', 'table'));
    }

    private const OFFICE_NS = 'urn:oasis:names:tc:opendocument:xmlns:office:1.0';
    private const STYLE_NS = 'urn:oasis:names:tc:opendocument:xmlns:style:1.0';

    private function templatePath(string $name): string
    {
        return dirname(__DIR__, 2) . '/samples/templates/' . $name;
    }

    private function outputPath(string $name): string
    {
        $path = sys_get_temp_dir() . '/sr07f-' . $name . '-' . bin2hex(random_bytes(4)) . '.odt';
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

    private function contentDom(): DOMDocument
    {
        return $this->dom('<office:document-content xmlns:office="' . self::OFFICE_NS . '" xmlns:style="' . self::STYLE_NS . '" xmlns:table="urn:oasis:names:tc:opendocument:xmlns:table:1.0"><office:automatic-styles/></office:document-content>');
    }

    private function stylesDom(): DOMDocument
    {
        return $this->dom('<office:document-styles xmlns:office="' . self::OFFICE_NS . '" xmlns:style="' . self::STYLE_NS . '" xmlns:table="urn:oasis:names:tc:opendocument:xmlns:table:1.0"><office:styles/></office:document-styles>');
    }

    private function context(): OdtDocumentContext
    {
        return new OdtDocumentContext($this->contentDom(), $this->stylesDom(), $this->dom('<office:document-meta xmlns:office="' . self::OFFICE_NS . '"/>'));
    }

    private function dom(string $xml): DOMDocument
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        self::assertTrue($dom->loadXML($xml));
        return $dom;
    }
}
