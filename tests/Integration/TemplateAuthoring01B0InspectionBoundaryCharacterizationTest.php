<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Integration;

use DOMDocument;
use OdtTemplateEngine\Document\DocumentInspector;
use OdtTemplateEngine\OdtTemplate;
use OdtTemplateEngine\Template\TemplateStructureInspector;
use PHPUnit\Framework\TestCase;
use ZipArchive;

final class TemplateAuthoring01B0InspectionBoundaryCharacterizationTest extends TestCase
{
    /** @var list<string> */
    private array $paths = [];

    protected function tearDown(): void
    {
        foreach ($this->paths as $path) {
            if (is_file($path)) {
                unlink($path);
            }
        }
    }

    public function testTemplateStructureInspectionReadsOriginalContentXmlAcrossRenderLifecycle(): void
    {
        $template = new OdtTemplate(
            dirname(__DIR__, 2) . '/samples/templates/template_01_simple_variables.odt'
        );

        $before = $template->inspectTemplateStructure()->toArray();

        $template->assign([
            'name' => 'Ada Lovelace',
            'street' => 'Example Street 1',
            'city' => 'London',
        ]);
        $template->render();

        $after = $template->inspectTemplateStructure()->toArray();

        self::assertSame($before, $after);
        self::assertNotEmpty($before['expressions']);
    }

    public function testTemplateStructureInspectionCurrentlyIgnoresStylesXmlTemplateExpressions(): void
    {
        $template = new OdtTemplate($this->createTemplate(
            '<text:p>No body placeholder</text:p>',
            '<office:master-styles>'
            . '<style:master-page style:name="Standard">'
            . '<style:header><text:p>Header {{header_name}}</text:p></style:header>'
            . '</style:master-page>'
            . '</office:master-styles>'
        ));

        $inspection = $template->inspectTemplateStructure();

        self::assertSame([], $inspection->expressionsByVariable('header_name'));
        self::assertSame([], $inspection->expressions());
    }

    public function testExpressionInspectionGrammarIsNarrowerThanRuntimeConditionEvaluator(): void
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        self::assertTrue($dom->loadXML(
            '<root xmlns:text="urn:oasis:names:tc:opendocument:xmlns:text:1.0">'
            . '<text:p>{{#if:gender=="female"}}</text:p>'
            . '</root>'
        ));

        $inspection = (new TemplateStructureInspector())->inspect($dom);

        self::assertCount(1, $inspection->expressions());
        self::assertSame('UNSUPPORTED', $inspection->expressions()[0]->kind());
        self::assertSame('UNSAFE', $inspection->expressions()[0]->classification());
        self::assertSame(
            ['unsupported_template_expression'],
            array_map(
                static fn ($diagnostic): string => $diagnostic->code(),
                $inspection->diagnostics()
            )
        );
    }

    public function testDocumentInspectionHasDifferentDocumentPartCoveragePerNativeObjectType(): void
    {
        $content = $this->dom(
            '<office:document-content'
            . $this->namespaces()
            . '><office:body><office:text>'
            . '<text:section text:name="ContentSection"><text:p>Body</text:p></text:section>'
            . '<text:bookmark text:name="ContentBookmark"/>'
            . '<table:table table:name="ContentTable"/>'
            . '<draw:frame draw:name="ContentFrame"/>'
            . '</office:text></office:body>'
            . '</office:document-content>'
        );

        $styles = $this->dom(
            '<office:document-styles'
            . $this->namespaces()
            . '><office:styles/>'
            . '<office:master-styles>'
            . '<style:master-page style:name="Standard"><style:header>'
            . '<text:section text:name="HeaderSection"><text:p>Header</text:p></text:section>'
            . '<text:bookmark text:name="HeaderBookmark"/>'
            . '<table:table table:name="HeaderTable"/>'
            . '<draw:frame draw:name="HeaderFrame"/>'
            . '</style:header></style:master-page>'
            . '</office:master-styles>'
            . '</office:document-styles>'
        );

        $inspection = (new DocumentInspector())->inspect($content, $styles);

        self::assertNotNull($inspection->section('ContentSection'));
        self::assertNull($inspection->section('HeaderSection'));

        self::assertNotNull($inspection->bookmark('ContentBookmark'));
        self::assertNull($inspection->bookmark('HeaderBookmark'));

        self::assertNotNull($inspection->table('ContentTable'));
        self::assertNotNull($inspection->table('HeaderTable'));

        self::assertNotNull($inspection->frame('ContentFrame'));
        self::assertNotNull($inspection->frame('HeaderFrame'));
    }

    private function createTemplate(string $body, string $masterStyles): string
    {
        $path = tempnam(sys_get_temp_dir(), 'odt-template-authoring-b0-');
        self::assertIsString($path);
        unlink($path);
        $path .= '.odt';
        $this->paths[] = $path;

        $zip = new ZipArchive();
        self::assertTrue(
            $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true
        );

        $zip->addFromString('mimetype', 'application/vnd.oasis.opendocument.text');

        $zip->addFromString(
            'content.xml',
            '<?xml version="1.0" encoding="UTF-8"?>'
            . '<office:document-content'
            . $this->namespaces()
            . '><office:automatic-styles/>'
            . '<office:body><office:text>'
            . $body
            . '</office:text></office:body>'
            . '</office:document-content>'
        );

        $zip->addFromString(
            'styles.xml',
            '<?xml version="1.0" encoding="UTF-8"?>'
            . '<office:document-styles'
            . $this->namespaces()
            . '><office:styles/>'
            . '<office:automatic-styles/>'
            . $masterStyles
            . '</office:document-styles>'
        );

        $zip->addFromString(
            'meta.xml',
            '<?xml version="1.0" encoding="UTF-8"?>'
            . '<office:document-meta'
            . ' xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0">'
            . '<office:meta/>'
            . '</office:document-meta>'
        );

        $zip->addFromString(
            'META-INF/manifest.xml',
            '<?xml version="1.0" encoding="UTF-8"?>'
            . '<manifest:manifest'
            . ' xmlns:manifest="urn:oasis:names:tc:opendocument:xmlns:manifest:1.0">'
            . '<manifest:file-entry manifest:full-path="/"'
            . ' manifest:media-type="application/vnd.oasis.opendocument.text"/>'
            . '<manifest:file-entry manifest:full-path="content.xml"'
            . ' manifest:media-type="text/xml"/>'
            . '<manifest:file-entry manifest:full-path="styles.xml"'
            . ' manifest:media-type="text/xml"/>'
            . '<manifest:file-entry manifest:full-path="meta.xml"'
            . ' manifest:media-type="text/xml"/>'
            . '</manifest:manifest>'
        );

        $zip->close();

        return $path;
    }

    private function dom(string $xml): DOMDocument
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        self::assertTrue($dom->loadXML($xml));

        return $dom;
    }

    private function namespaces(): string
    {
        return ' xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0"'
            . ' xmlns:text="urn:oasis:names:tc:opendocument:xmlns:text:1.0"'
            . ' xmlns:table="urn:oasis:names:tc:opendocument:xmlns:table:1.0"'
            . ' xmlns:draw="urn:oasis:names:tc:opendocument:xmlns:drawing:1.0"'
            . ' xmlns:style="urn:oasis:names:tc:opendocument:xmlns:style:1.0"'
            . ' xmlns:svg="urn:oasis:names:tc:opendocument:xmlns:svg-compatible:1.0"';
    }
}
