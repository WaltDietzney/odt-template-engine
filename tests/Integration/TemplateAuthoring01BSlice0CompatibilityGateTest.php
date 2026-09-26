<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Integration;

use DOMDocument;
use OdtTemplateEngine\Document\DocumentInspection;
use OdtTemplateEngine\Document\DocumentInspector;
use OdtTemplateEngine\OdtTemplate;
use OdtTemplateEngine\Template\TemplateProcessor;
use OdtTemplateEngine\Template\TemplateStructureInspection;
use OdtTemplateEngine\Template\TemplateStructureInspector;
use PHPUnit\Framework\TestCase;
use ZipArchive;

final class TemplateAuthoring01BSlice0CompatibilityGateTest extends TestCase
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

    public function testExistingInspectionFacadeReturnTypesAndSerializedTopLevelShapeRemainStable(): void
    {
        $template = new OdtTemplate(
            dirname(__DIR__, 2) . '/tests/Fixtures/LegacySamples/templates/template_01_simple_variables.odt'
        );

        $documentInspection = $template->inspect();
        $templateStructureInspection = $template->inspectTemplateStructure();

        self::assertInstanceOf(DocumentInspection::class, $documentInspection);
        self::assertInstanceOf(TemplateStructureInspection::class, $templateStructureInspection);

        self::assertSame(
            ['sections', 'bookmarks', 'tables', 'frames', 'diagnostics'],
            array_keys($documentInspection->toArray())
        );

        self::assertSame(
            ['valid', 'expressions', 'diagnostics'],
            array_keys($templateStructureInspection->toArray())
        );
    }

    public function testFocusedTemplateStructureInspectionRemainsSourceStableAcrossRenderAndSave(): void
    {
        $template = new OdtTemplate(
            dirname(__DIR__, 2) . '/tests/Fixtures/LegacySamples/templates/template_01_simple_variables.odt'
        );

        $before = $template->inspectTemplateStructure()->toArray();

        $template->assign([
            'name' => 'Ada Lovelace',
            'street' => 'Example Street 1',
            'city' => 'London',
        ]);
        $template->render();

        $output = $this->outputPath();
        $template->save($output);

        $after = $template->inspectTemplateStructure()->toArray();

        self::assertSame($before, $after);
    }

    public function testCurrentFocusedInspectorStillIgnoresStylesXmlExpressions(): void
    {
        $template = new OdtTemplate($this->createTemplate(
            '<text:p>Body {{body_name}}</text:p>',
            '<office:master-styles>'
            . '<style:master-page style:name="Standard">'
            . '<style:header><text:p>Header {{header_name}}</text:p></style:header>'
            . '</style:master-page>'
            . '</office:master-styles>'
        ));

        $inspection = $template->inspectTemplateStructure();

        self::assertCount(1, $inspection->expressions());
        self::assertCount(1, $inspection->expressionsByVariable('body_name'));
        self::assertSame([], $inspection->expressionsByVariable('header_name'));
    }

    public function testCurrentNativeInspectionDocumentPartCoverageRemainsAsymmetric(): void
    {
        $content = $this->dom(
            '<office:document-content'
            . $this->namespaces()
            . '><office:body><office:text>'
            . '<text:section text:name="BodySection"><text:p>Body</text:p></text:section>'
            . '<text:bookmark text:name="BodyBookmark"/>'
            . '<table:table table:name="BodyTable"/>'
            . '<draw:frame draw:name="BodyFrame"/>'
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

        self::assertNotNull($inspection->section('BodySection'));
        self::assertNull($inspection->section('HeaderSection'));

        self::assertNotNull($inspection->bookmark('BodyBookmark'));
        self::assertNull($inspection->bookmark('HeaderBookmark'));

        self::assertNotNull($inspection->table('BodyTable'));
        self::assertNotNull($inspection->table('HeaderTable'));

        self::assertNotNull($inspection->frame('BodyFrame'));
        self::assertNotNull($inspection->frame('HeaderFrame'));
    }

    public function testRuntimeAndFocusedInspectionConditionGrammarAreAlignedAfterSlice5Gate(): void
    {
        $processor = new TemplateProcessor();

        self::assertTrue(
            $processor->evaluateCondition(
                'gender=="female"',
                ['gender' => 'female']
            )
        );

        self::assertFalse(
            $processor->evaluateCondition(
                'gender=="female"',
                ['gender' => 'male']
            )
        );

        $dom = $this->dom(
            '<root xmlns:text="urn:oasis:names:tc:opendocument:xmlns:text:1.0">'
            . '<text:p>{{#if:gender=="female"}}</text:p>'
            . '</root>'
        );

        $inspection = (new TemplateStructureInspector())->inspect($dom);

        self::assertCount(1, $inspection->expressions());
        self::assertSame('CONDITION_OPEN', $inspection->expressions()[0]->kind());
        self::assertNotSame('UNSAFE', $inspection->expressions()[0]->classification());
        self::assertSame([], $inspection->diagnostics());
    }

    public function testCurrentDiagnosticSerializationShapesAreFrozen(): void
    {
        $templateDom = $this->dom(
            '<root xmlns:text="urn:oasis:names:tc:opendocument:xmlns:text:1.0">'
            . '<text:p>{{foo bar}}</text:p>'
            . '</root>'
        );

        $templateDiagnostics = (new TemplateStructureInspector())
            ->inspect($templateDom)
            ->diagnostics();
        self::assertNotEmpty($templateDiagnostics);
        $templateDiagnostic = $templateDiagnostics[0]->toArray();

        self::assertSame(
            [
                'code',
                'severity',
                'message',
                'classification',
                'repairable',
                'expression',
                'scope',
            ],
            array_keys($templateDiagnostic)
        );

        $content = $this->dom(
            '<office:document-content'
            . $this->namespaces()
            . '><office:body><office:text>'
            . '<table:table table:name="Duplicate"/>'
            . '<table:table table:name="Duplicate"/>'
            . '</office:text></office:body>'
            . '</office:document-content>'
        );

        $styles = $this->dom(
            '<office:document-styles'
            . $this->namespaces()
            . '><office:styles/><office:master-styles/>'
            . '</office:document-styles>'
        );

        $documentDiagnostics = (new DocumentInspector())->inspect($content, $styles)->diagnostics();
        self::assertNotEmpty($documentDiagnostics);

        self::assertSame(
            ['code', 'severity', 'message', 'target_type', 'target_name'],
            array_keys($documentDiagnostics[0]->toArray())
        );
    }

    private function createTemplate(string $body, string $masterStyles): string
    {
        $path = tempnam(sys_get_temp_dir(), 'odt-template-authoring-b-slice0-');
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
            . '><office:styles/><office:automatic-styles/>'
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

    private function outputPath(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'odt-template-authoring-b-slice0-out-');
        self::assertIsString($path);
        unlink($path);
        $path .= '.odt';
        $this->paths[] = $path;

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
            . ' xmlns:svg="urn:oasis:names:tc:opendocument:xmlns:svg-compatible:1.0"'
            . ' xmlns:fo="urn:oasis:names:tc:opendocument:xmlns:xsl-fo-compatible:1.0"'
            . ' xmlns:loext="urn:org:documentfoundation:names:experimental:office:xmlns:loext:1.0"';
    }
}
