<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Integration;

use DOMDocument;
use DOMElement;
use DOMXPath;
use OdtTemplateEngine\Elements\ImageElement;
use OdtTemplateEngine\Elements\Paragraph;
use OdtTemplateEngine\OdtTemplate;
use PHPUnit\Framework\TestCase;
use ZipArchive;

/**
 * Characterizes PAGE-FLOW-01D processing of master-page-owned header/footer content.
 *
 * The fixture mirrors the relevant structure of the Writer-authored
 * page-flow-01d-page-owned-content.odt research document. The tests assert
 * document-part processing and preservation only; Writer remains responsible
 * for actual page selection and rendering.
 */
final class PageFlow01DPageOwnedContentCharacterizationTest extends TestCase
{
    private const OFFICE = 'urn:oasis:names:tc:opendocument:xmlns:office:1.0';
    private const STYLE = 'urn:oasis:names:tc:opendocument:xmlns:style:1.0';
    private const TEXT = 'urn:oasis:names:tc:opendocument:xmlns:text:1.0';
    private const TABLE = 'urn:oasis:names:tc:opendocument:xmlns:table:1.0';
    private const DRAW = 'urn:oasis:names:tc:opendocument:xmlns:drawing:1.0';
    private const XLINK = 'http://www.w3.org/1999/xlink';
    private const MANIFEST = 'urn:oasis:names:tc:opendocument:xmlns:manifest:1.0';

    public function testScalarRenderProcessesMasterPageContentAndPreservesNativePageNumberField(): void
    {
        $fixture = $this->createPageOwnedFixture();
        $output = tempnam(sys_get_temp_dir(), 'odt-page-flow-01d-scalar-') . '.odt';

        try {
            $template = new OdtTemplate($fixture);
            $template->setValues([
                'person_name' => 'Walter Beispiel',
                'role' => 'Projektleiter',
                'document_title' => 'Lebenslauf',
            ]);
            $template->render();
            $template->save($output);

            [, $stylesDom] = $this->readCoreXml($output);
            $xpath = $this->xpath($stylesDom);

            $firstHeader = $xpath->query(
                '//style:master-page[@style:name="First_20_Page"]/style:header'
            )->item(0);
            $firstFooter = $xpath->query(
                '//style:master-page[@style:name="First_20_Page"]/style:footer'
            )->item(0);
            $standardHeader = $xpath->query(
                '//style:master-page[@style:name="Standard"]/style:header'
            )->item(0);
            $standardFooter = $xpath->query(
                '//style:master-page[@style:name="Standard"]/style:footer'
            )->item(0);

            self::assertInstanceOf(DOMElement::class, $firstHeader);
            self::assertInstanceOf(DOMElement::class, $firstFooter);
            self::assertInstanceOf(DOMElement::class, $standardHeader);
            self::assertInstanceOf(DOMElement::class, $standardFooter);

            self::assertStringContainsString('FIRST HEADER — Walter Beispiel', $firstHeader->textContent);
            self::assertStringContainsString('FIRST FOOTER — Lebenslauf', $firstFooter->textContent);
            self::assertStringContainsString('STANDARD HEADER — Walter Beispiel', $standardHeader->textContent);
            self::assertStringContainsString('Name: Walter Beispiel', $standardHeader->textContent);
            self::assertStringContainsString('Role: Projektleiter', $standardHeader->textContent);
            self::assertStringContainsString('Lebenslauf', $standardFooter->textContent);

            self::assertSame(
                1,
                $xpath->query(
                    '//style:master-page[@style:name="Standard"]/style:footer//text:page-number'
                )->length,
                'Scalar replacement must preserve the native page-number field.'
            );

            foreach (['person_name', 'role', 'document_title'] as $scalarPlaceholder) {
                self::assertStringNotContainsString(
                    '{{' . $scalarPlaceholder . '}}',
                    $stylesDom->textContent,
                    sprintf(
                        'The assigned scalar placeholder %s must be resolved in page-owned content.',
                        $scalarPlaceholder
                    )
                );
            }

            self::assertStringContainsString(
                '{{header_block}}',
                $standardHeader->textContent,
                'An unassigned structured placeholder must remain untouched by the isolated scalar render path.'
            );
            self::assertStringContainsString(
                '{{header_logo}}',
                $standardHeader->textContent,
                'An unassigned resource-bearing placeholder must remain untouched by the isolated scalar render path.'
            );

            $standardMaster = $xpath->query(
                '//style:master-page[@style:name="Standard"]'
            )->item(0);
            $firstMaster = $xpath->query(
                '//style:master-page[@style:name="First_20_Page"]'
            )->item(0);
            self::assertInstanceOf(DOMElement::class, $standardMaster);
            self::assertInstanceOf(DOMElement::class, $firstMaster);
            self::assertSame('Mpm1', $standardMaster->getAttribute('style:page-layout-name'));
            self::assertSame('Mpm1', $firstMaster->getAttribute('style:page-layout-name'));
            self::assertSame('Standard', $firstMaster->getAttribute('style:next-style-name'));
        } finally {
            @unlink($fixture);
            @unlink($output);
        }
    }

    public function testStructuredInsertionAndImageResourceWorkInMasterPageOwnedHeaderContent(): void
    {
        $fixture = $this->createPageOwnedFixture();
        $output = tempnam(sys_get_temp_dir(), 'odt-page-flow-01d-structured-') . '.odt';

        try {
            $template = new OdtTemplate($fixture);
            $template->setElement(
                'header_block',
                (new Paragraph())->addText('STRUCTURED HEADER BLOCK')
            );
            $template->setElement(
                'header_logo',
                new ImageElement(__DIR__ . '/../../assets/Logo.png', [
                    'width' => '1cm',
                    'anchor' => 'as-char',
                ])
            );
            $template->setValues([
                'person_name' => 'Walter Beispiel',
                'role' => 'Projektleiter',
                'document_title' => 'Lebenslauf',
            ]);
            $template->render();
            $template->save($output);

            [, $stylesDom] = $this->readCoreXml($output);
            $xpath = $this->xpath($stylesDom);

            $header = $xpath->query(
                '//style:master-page[@style:name="Standard"]/style:header'
            )->item(0);
            self::assertInstanceOf(DOMElement::class, $header);
            self::assertStringContainsString('STRUCTURED HEADER BLOCK', $header->textContent);
            self::assertStringNotContainsString('{{header_block}}', $header->textContent);
            self::assertStringNotContainsString('{{header_logo}}', $header->textContent);

            $images = $xpath->query(
                '//style:master-page[@style:name="Standard"]/style:header//draw:image'
            );
            self::assertSame(1, $images->length);
            $image = $images->item(0);
            self::assertInstanceOf(DOMElement::class, $image);
            self::assertSame('Pictures/Logo.png', $image->getAttributeNS(self::XLINK, 'href'));

            $zip = new ZipArchive();
            self::assertTrue($zip->open($output));
            self::assertNotFalse($zip->getFromName('Pictures/Logo.png'));
            $manifest = $zip->getFromName('META-INF/manifest.xml');
            $zip->close();
            self::assertIsString($manifest);

            $manifestDom = new DOMDocument();
            self::assertTrue($manifestDom->loadXML($manifest));
            $manifestXPath = new DOMXPath($manifestDom);
            $manifestXPath->registerNamespace('manifest', self::MANIFEST);
            self::assertSame(
                1,
                $manifestXPath->query(
                    '//manifest:file-entry[@manifest:full-path="Pictures/Logo.png"]'
                )->length,
                'The page-owned image resource must remain a normal ODT package resource.'
            );

            $reopened = new OdtTemplate($output);
            $roundTrip = tempnam(sys_get_temp_dir(), 'odt-page-flow-01d-roundtrip-') . '.odt';
            try {
                $reopened->save($roundTrip);
                [, $roundTripStyles] = $this->readCoreXml($roundTrip);
                $roundTripXPath = $this->xpath($roundTripStyles);
                self::assertSame(
                    1,
                    $roundTripXPath->query(
                        '//style:master-page[@style:name="Standard"]/style:header//draw:image'
                    )->length
                );
                self::assertStringContainsString(
                    'STRUCTURED HEADER BLOCK',
                    $roundTripXPath->query(
                        '//style:master-page[@style:name="Standard"]/style:header'
                    )->item(0)?->textContent ?? ''
                );
            } finally {
                @unlink($roundTrip);
            }
        } finally {
            @unlink($fixture);
            @unlink($output);
        }
    }

    private function createPageOwnedFixture(): string
    {
        $fixture = tempnam(sys_get_temp_dir(), 'odt-page-flow-01d-fixture-') . '.odt';
        self::assertTrue(copy('samples/templates/template_01_simple_variables.odt', $fixture));

        $zip = new ZipArchive();
        self::assertTrue($zip->open($fixture));
        self::assertTrue($zip->addFromString('content.xml', $this->contentXml()));
        self::assertTrue($zip->addFromString('styles.xml', $this->stylesXml()));
        self::assertTrue($zip->close());

        return $fixture;
    }

    /** @return array{DOMDocument, DOMDocument} */
    private function readCoreXml(string $path): array
    {
        $zip = new ZipArchive();
        self::assertTrue($zip->open($path));
        $content = $zip->getFromName('content.xml');
        $styles = $zip->getFromName('styles.xml');
        $zip->close();

        self::assertIsString($content);
        self::assertIsString($styles);

        $contentDom = new DOMDocument();
        $stylesDom = new DOMDocument();
        self::assertTrue($contentDom->loadXML($content));
        self::assertTrue($stylesDom->loadXML($styles));

        return [$contentDom, $stylesDom];
    }

    private function xpath(DOMDocument $dom): DOMXPath
    {
        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('office', self::OFFICE);
        $xpath->registerNamespace('style', self::STYLE);
        $xpath->registerNamespace('text', self::TEXT);
        $xpath->registerNamespace('table', self::TABLE);
        $xpath->registerNamespace('draw', self::DRAW);

        return $xpath;
    }

    private function contentXml(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<office:document-content
    xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0"
    xmlns:style="urn:oasis:names:tc:opendocument:xmlns:style:1.0"
    xmlns:text="urn:oasis:names:tc:opendocument:xmlns:text:1.0"
    xmlns:table="urn:oasis:names:tc:opendocument:xmlns:table:1.0"
    xmlns:fo="urn:oasis:names:tc:opendocument:xmlns:xsl-fo-compatible:1.0"
    xmlns:loext="urn:org:documentfoundation:names:experimental:office:xmlns:loext:1.0"
    office:version="1.2">
    <office:automatic-styles>
        <style:style style:name="P1" style:family="paragraph" style:parent-style-name="Standard" style:master-page-name="First_20_Page">
            <style:paragraph-properties style:page-number="auto"/>
        </style:style>
    </office:automatic-styles>
    <office:body>
        <office:text>
            <text:p text:style-name="P1">PAGE-FLOW-01D BODY</text:p>
            <text:p text:style-name="Standard">Body content used only to retain the page-style request.</text:p>
        </office:text>
    </office:body>
</office:document-content>
XML;
    }

    private function stylesXml(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<office:document-styles
    xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0"
    xmlns:style="urn:oasis:names:tc:opendocument:xmlns:style:1.0"
    xmlns:text="urn:oasis:names:tc:opendocument:xmlns:text:1.0"
    xmlns:table="urn:oasis:names:tc:opendocument:xmlns:table:1.0"
    xmlns:draw="urn:oasis:names:tc:opendocument:xmlns:drawing:1.0"
    xmlns:xlink="http://www.w3.org/1999/xlink"
    xmlns:svg="urn:oasis:names:tc:opendocument:xmlns:svg-compatible:1.0"
    xmlns:fo="urn:oasis:names:tc:opendocument:xmlns:xsl-fo-compatible:1.0"
    office:version="1.2">
    <office:styles>
        <style:style style:name="Standard" style:family="paragraph"/>
        <style:style style:name="Header" style:family="paragraph" style:parent-style-name="Standard"/>
        <style:style style:name="Footer" style:family="paragraph" style:parent-style-name="Standard"/>
        <style:style style:name="Table_20_Contents" style:display-name="Table Contents" style:family="paragraph" style:parent-style-name="Standard"/>
    </office:styles>
    <office:automatic-styles>
        <style:page-layout style:name="Mpm1">
            <style:page-layout-properties fo:page-width="21cm" fo:page-height="29.7cm" fo:margin="2cm"/>
            <style:header-style/>
            <style:footer-style/>
        </style:page-layout>
        <style:style style:name="Table1" style:family="table"/>
        <style:style style:name="Table1.A" style:family="table-column"/>
        <style:style style:name="Table1.B" style:family="table-column"/>
    </office:automatic-styles>
    <office:master-styles>
        <style:master-page style:name="Standard" style:page-layout-name="Mpm1">
            <style:header>
                <text:p text:style-name="Header">STANDARD HEADER — {{person_name}}</text:p>
                <table:table table:name="HeaderTable" table:style-name="Table1">
                    <table:table-column table:style-name="Table1.A"/>
                    <table:table-column table:style-name="Table1.B"/>
                    <table:table-row>
                        <table:table-cell>
                            <text:p text:style-name="Table_20_Contents">Name: {{person_name}}</text:p>
                        </table:table-cell>
                        <table:table-cell>
                            <text:p text:style-name="Table_20_Contents">Role: {{role}}</text:p>
                        </table:table-cell>
                    </table:table-row>
                </table:table>
                <text:p text:style-name="Header">{{header_block}}</text:p>
                <text:p text:style-name="Header">{{header_logo}}</text:p>
            </style:header>
            <style:footer>
                <text:p text:style-name="Footer">Page <text:page-number text:select-page="current">2</text:page-number> — {{document_title}}</text:p>
            </style:footer>
        </style:master-page>
        <style:master-page style:name="First_20_Page" style:display-name="First Page" style:page-layout-name="Mpm1" style:next-style-name="Standard">
            <style:header>
                <text:p text:style-name="Header">FIRST HEADER — {{person_name}}</text:p>
            </style:header>
            <style:footer>
                <text:p text:style-name="Footer">FIRST FOOTER — {{document_title}}</text:p>
            </style:footer>
        </style:master-page>
    </office:master-styles>
</office:document-styles>
XML;
    }
}
