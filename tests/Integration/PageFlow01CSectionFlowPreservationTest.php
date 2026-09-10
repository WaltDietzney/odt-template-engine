<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Integration;

use DOMDocument;
use DOMElement;
use DOMXPath;
use OdtTemplateEngine\OdtTemplate;
use PHPUnit\Framework\TestCase;
use ZipArchive;

/**
 * Characterizes PAGE-FLOW-01C preservation across SECTION-03 collection instantiation.
 *
 * The fixture mirrors the relevant Writer-authored C5 structure from
 * page-flow-01c-section-flow.odt: a native text:section prototype whose
 * paragraphs reference named paragraph styles, including inherited flow
 * semantics. The test intentionally asserts preservation, not pagination;
 * Writer remains responsible for actual page layout.
 */
final class PageFlow01CSectionFlowPreservationTest extends TestCase
{
    private const OFFICE = 'urn:oasis:names:tc:opendocument:xmlns:office:1.0';
    private const STYLE = 'urn:oasis:names:tc:opendocument:xmlns:style:1.0';
    private const TEXT = 'urn:oasis:names:tc:opendocument:xmlns:text:1.0';
    private const FO = 'urn:oasis:names:tc:opendocument:xmlns:xsl-fo-compatible:1.0';

    public function testInstantiateManyPreservesAuthoredParagraphFlowStyleReferencesAcrossSaveAndReopen(): void
    {
        $fixture = $this->createWriterStyleFixture();
        $output = tempnam(sys_get_temp_dir(), 'odt-page-flow-01c-output-') . '.odt';

        try {
            $template = new OdtTemplate($fixture);
            $instances = $template->section('PF01C_Entry')->instantiateMany([
                [
                    'period' => '2024–2026',
                    'position' => 'Projektleiter',
                    'company' => 'Muster GmbH',
                    'description' => 'Leitung eines bereichsübergreifenden Projekts.',
                ],
                [
                    'period' => '2021–2024',
                    'position' => 'Standortleiter',
                    'company' => 'Beispiel AG',
                    'description' => 'Verantwortung für Organisation und operative Abläufe.',
                ],
                [
                    'period' => '2018–2021',
                    'position' => 'Standortkoordinator',
                    'company' => 'Demo GmbH',
                    'description' => 'Koordination von Teams und standortbezogenen Prozessen.',
                ],
            ]);

            self::assertCount(3, $instances);
            self::assertSame('PF01C_Entry_1', $instances[0]->name());
            self::assertSame('PF01C_Entry_2', $instances[1]->name());
            self::assertSame('PF01C_Entry_3', $instances[2]->name());

            $template->save($output);

            [$contentDom, $stylesDom] = $this->readCoreXml($output);
            $contentXPath = $this->xpath($contentDom);
            $stylesXPath = $this->xpath($stylesDom);

            self::assertSame(0, $contentXPath->query(
                '//text:section[@text:name="PF01C_Entry"]'
            )->length, 'The collection prototype should be removed after instantiateMany().');

            foreach ([1, 2, 3] as $index) {
                $sectionName = 'PF01C_Entry_' . $index;
                $section = $contentXPath->query(
                    sprintf('//text:section[@text:name="%s"]', $sectionName)
                )->item(0);

                self::assertInstanceOf(DOMElement::class, $section);
                self::assertSame('Sect1', $section->getAttribute('text:style-name'));

                $paragraphs = [];
                foreach ($section->childNodes as $child) {
                    if ($child instanceof DOMElement && $child->namespaceURI === self::TEXT && $child->localName === 'p') {
                        $paragraphs[] = $child;
                    }
                }

                self::assertCount(4, $paragraphs);
                self::assertSame(
                    ['PF01CMeta', 'PF01CHeading', 'PF01CCompany', 'Standard'],
                    array_map(static fn (DOMElement $paragraph): string => $paragraph->getAttribute('text:style-name'), $paragraphs),
                    sprintf('Instance %s must retain the Writer-authored paragraph style references.', $sectionName)
                );

                foreach ($paragraphs as $paragraph) {
                    self::assertStringNotContainsString('{{', $paragraph->textContent);
                    self::assertStringNotContainsString('}}', $paragraph->textContent);
                }
            }

            $positionStyle = $stylesXPath->query(
                '//style:style[@style:name="PF01CPosition" and @style:family="paragraph"]'
            )->item(0);
            self::assertInstanceOf(DOMElement::class, $positionStyle);
            self::assertSame('Standard', $positionStyle->getAttribute('style:parent-style-name'));

            $positionProperties = $stylesXPath->query('./style:paragraph-properties', $positionStyle)->item(0);
            self::assertInstanceOf(DOMElement::class, $positionProperties);
            self::assertSame('always', $positionProperties->getAttributeNS(self::FO, 'keep-with-next'));
            self::assertSame('always', $positionProperties->getAttributeNS(self::FO, 'keep-together'));

            $companyStyle = $stylesXPath->query(
                '//style:style[@style:name="PF01CCompany" and @style:family="paragraph"]'
            )->item(0);
            self::assertInstanceOf(DOMElement::class, $companyStyle);
            self::assertSame(
                'PF01CPosition',
                $companyStyle->getAttribute('style:parent-style-name'),
                'The Writer-authored style inheritance carrying flow semantics must remain intact.'
            );

            $reopened = new OdtTemplate($output);
            foreach ([1, 2, 3] as $index) {
                $section = $reopened->section('PF01C_Entry_' . $index);
                self::assertStringContainsString(
                    ['Projektleiter', 'Standortleiter', 'Standortkoordinator'][$index - 1],
                    $section->text()
                );
            }
        } finally {
            @unlink($fixture);
            @unlink($output);
        }
    }

    private function createWriterStyleFixture(): string
    {
        $fixture = tempnam(sys_get_temp_dir(), 'odt-page-flow-01c-fixture-') . '.odt';
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
        $xpath->registerNamespace('fo', self::FO);

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
    office:version="1.2">
    <office:automatic-styles/>
    <office:body>
        <office:text>
            <text:section text:style-name="Sect1" text:name="PF01C_Entry">
                <text:p text:style-name="PF01CMeta">{{period}}</text:p>
                <text:p text:style-name="PF01CHeading">{{position}}</text:p>
                <text:p text:style-name="PF01CCompany">{{company}}</text:p>
                <text:p text:style-name="Standard">{{description}}</text:p>
            </text:section>
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
    xmlns:fo="urn:oasis:names:tc:opendocument:xmlns:xsl-fo-compatible:1.0"
    office:version="1.2">
    <office:styles>
        <style:style style:name="Standard" style:family="paragraph"/>
        <style:style style:name="Sect1" style:family="section">
            <style:section-properties/>
        </style:style>
        <style:style style:name="PF01CHeading" style:family="paragraph" style:parent-style-name="Standard">
            <style:paragraph-properties fo:keep-together="always"/>
        </style:style>
        <style:style style:name="PF01CPosition" style:family="paragraph" style:parent-style-name="Standard">
            <style:paragraph-properties fo:keep-with-next="always" fo:keep-together="always"/>
        </style:style>
        <style:style style:name="PF01CCompany" style:family="paragraph" style:parent-style-name="PF01CPosition"/>
        <style:style style:name="PF01CMeta" style:family="paragraph" style:parent-style-name="PF01CPosition"/>
    </office:styles>
    <office:automatic-styles/>
    <office:master-styles/>
</office:document-styles>
XML;
    }
}
