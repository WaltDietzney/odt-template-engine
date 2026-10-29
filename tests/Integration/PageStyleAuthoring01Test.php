<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Integration;

use DOMDocument;
use DOMXPath;
use OdtTemplateEngine\Elements\Paragraph;
use OdtTemplateEngine\PageLayoutOdtTemplate;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use ZipArchive;

final class PageStyleAuthoring01Test extends TestCase
{
    private const OFFICE = 'urn:oasis:names:tc:opendocument:xmlns:office:1.0';
    private const STYLE = 'urn:oasis:names:tc:opendocument:xmlns:style:1.0';
    private const FO = 'urn:oasis:names:tc:opendocument:xmlns:xsl-fo-compatible:1.0';
    private const TEXT = 'urn:oasis:names:tc:opendocument:xmlns:text:1.0';

    public function testDefinesPageLayoutsMasterPagesSuccessionAssignmentAndPageOwnedContent(): void
    {
        $template = new PageLayoutOdtTemplate($this->templatePath());
        $template
            ->definePageLayout('PSA01_FirstLayout', [
                'page-width' => '21cm', 'page-height' => '29.7cm',
                'margin-top' => '1cm', 'margin-right' => '1cm',
                'margin-bottom' => '1cm', 'margin-left' => '1cm',
                'orientation' => 'portrait',
            ])
            ->definePageLayout('PSA01_ContinuationLayout', [
                'page-width' => '21cm', 'page-height' => '29.7cm',
                'margin-top' => '2cm', 'margin-right' => '2cm',
                'margin-bottom' => '2cm', 'margin-left' => '2cm',
                'orientation' => 'portrait',
            ])
            ->definePageStyle('PSA01_First', 'PSA01_FirstLayout', [
                'next-style-name' => 'PSA01_Continuation',
                'header' => (new Paragraph())->addText('FIRST {{person}}'),
                'footer' => (new Paragraph())->addText('FIRST FOOTER'),
            ])
            ->definePageStyle('PSA01_Continuation', 'PSA01_ContinuationLayout', [
                'header' => (new Paragraph())->addText('CONTINUATION {{person}}'),
                'footer' => (new Paragraph())->addText('CONTINUATION FOOTER'),
            ])
            ->assignPageStyle('PSA01_FirstParagraph', 'PSA01_First')
            ;
        $template->setValues(['person' => 'Ada Example']);
        $template->render();

        $output = sys_get_temp_dir() . '/odt-page-style-authoring-01-' . uniqid('', true) . '.odt';
        try {
            $template->save($output);
            [$styles, $content] = $this->readXml($output);
            $styleXPath = $this->xpath($styles);
            $contentXPath = $this->xpath($content);

            $first = $styleXPath->query('//style:master-page[@style:name="PSA01_First"]')->item(0);
            self::assertNotNull($first);
            self::assertSame('PSA01_FirstLayout', $first->getAttributeNS(self::STYLE, 'page-layout-name'));
            self::assertSame('PSA01_Continuation', $first->getAttributeNS(self::STYLE, 'next-style-name'));
            self::assertStringContainsString('FIRST Ada Example', $first->textContent);

            $continuation = $styleXPath->query('//style:master-page[@style:name="PSA01_Continuation"]')->item(0);
            self::assertNotNull($continuation);
            self::assertSame('PSA01_ContinuationLayout', $continuation->getAttributeNS(self::STYLE, 'page-layout-name'));
            self::assertStringContainsString('CONTINUATION Ada Example', $continuation->textContent);

            $firstLayout = $styleXPath->query('//style:page-layout[@style:name="PSA01_FirstLayout"]/style:page-layout-properties')->item(0);
            self::assertSame('1cm', $firstLayout?->getAttributeNS(self::FO, 'margin-top'));
            $continuationLayout = $styleXPath->query('//style:page-layout[@style:name="PSA01_ContinuationLayout"]/style:page-layout-properties')->item(0);
            self::assertSame('2cm', $continuationLayout?->getAttributeNS(self::FO, 'margin-top'));

            $assigned = $contentXPath->query(
                '//office:automatic-styles/style:style[@style:name="PSA01_FirstParagraph"]'
            )->item(0);
            self::assertSame('PSA01_First', $assigned?->getAttributeNS(self::STYLE, 'master-page-name'));
            $reopened = new PageLayoutOdtTemplate($output);
            $reopened->setValues(['person' => 'Reopened']);
            $reopened->render();
            $roundTrip = sys_get_temp_dir() . '/odt-page-style-authoring-01-roundtrip-' . uniqid('', true) . '.odt';
            try {
                $reopened->save($roundTrip);
                [$roundTripStyles] = $this->readXml($roundTrip);
                $roundTripXPath = $this->xpath($roundTripStyles);
                self::assertSame('PSA01_Continuation', $roundTripXPath->query('//style:master-page[@style:name="PSA01_First"]')->item(0)?->getAttributeNS(self::STYLE, 'next-style-name'));
            } finally {
                @unlink($roundTrip);
            }
        } finally {
            @unlink($output);
            $template->cleanup();
        }
    }

    public function testExistingAutomaticParagraphStyleIsMutatedInContentScope(): void
    {
        $template = new PageLayoutOdtTemplate(dirname(__DIR__) . '/../samples/templates/template_S01b_cv_structured.odt');
        $template
            ->definePageLayout('PSA01_AutomaticLayout', [
                'page-width' => '21cm', 'page-height' => '29.7cm',
                'margin-top' => '1cm', 'margin-right' => '1cm',
                'margin-bottom' => '1cm', 'margin-left' => '1cm',
                'orientation' => 'portrait',
            ])
            ->definePageStyle('PSA01_AutomaticPage', 'PSA01_AutomaticLayout')
            ->assignPageStyle('P12', 'PSA01_AutomaticPage');

        $output = sys_get_temp_dir() . '/odt-page-style-automatic-' . uniqid('', true) . '.odt';
        try {
            $template->save($output);
            [, $content] = $this->readXml($output);
            $contentXPath = $this->xpath($content);
            $assigned = $contentXPath->query(
                '//office:automatic-styles/style:style[@style:name="P12"]'
            )->item(0);
            self::assertNotNull($assigned);
            self::assertSame('paragraph', $assigned->getAttributeNS(self::STYLE, 'family'));
            self::assertSame('CVTitel', $assigned->getAttributeNS(self::STYLE, 'parent-style-name'));
            self::assertSame('PSA01_AutomaticPage', $assigned->getAttributeNS(self::STYLE, 'master-page-name'));
            self::assertSame('auto', $assigned->getElementsByTagNameNS(
                self::STYLE,
                'paragraph-properties'
            )->item(0)?->getAttributeNS(self::STYLE, 'page-number'));
        } finally {
            @unlink($output);
            $template->cleanup();
        }
    }

    public function testCommonParagraphStyleIsCopiedToContentScopeWithPropertiesPreserved(): void
    {
        $template = new PageLayoutOdtTemplate($this->templatePath());
        $template
            ->definePageLayout('PSA01_CommonLayout', ['page-width' => '21cm'])
            ->definePageStyle('PSA01_CommonPage', 'PSA01_CommonLayout')
            ->assignPageStyle('Heading', 'PSA01_CommonPage');

        $output = sys_get_temp_dir() . '/odt-page-style-common-' . uniqid('', true) . '.odt';
        try {
            $template->save($output);
            [$styles, $content] = $this->readXml($output);
            $styleXPath = $this->xpath($styles);
            $contentXPath = $this->xpath($content);
            $common = $styleXPath->query('//office:styles/style:style[@style:name="Heading"]')->item(0);
            $local = $contentXPath->query(
                '//office:automatic-styles/style:style[@style:name="Heading"]'
            )->item(0);
            self::assertNotNull($common);
            self::assertNotNull($local);
            self::assertSame('Standard', $local->getAttributeNS(self::STYLE, 'parent-style-name'));
            self::assertSame('PSA01_CommonPage', $local->getAttributeNS(self::STYLE, 'master-page-name'));
            self::assertSame(
                '0.423cm',
                $local->getElementsByTagNameNS(self::STYLE, 'paragraph-properties')->item(0)?->getAttributeNS(self::FO, 'margin-top')
            );
            self::assertSame(
                $common->getElementsByTagNameNS(self::STYLE, 'text-properties')->item(0)?->getAttributeNS(self::FO, 'font-size'),
                $local->getElementsByTagNameNS(self::STYLE, 'text-properties')->item(0)?->getAttributeNS(self::FO, 'font-size')
            );
        } finally {
            @unlink($output);
            $template->cleanup();
        }
    }

    public function testRejectsConflictingPageLayoutAndMasterPageNames(): void
    {
        $template = new PageLayoutOdtTemplate($this->templatePath());
        try {
            $template->definePageLayout('PSA01_Conflict', ['page-width' => '21cm']);
            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('Page layout "PSA01_Conflict" already exists.');
            $template->definePageLayout('PSA01_Conflict', ['page-width' => '21cm']);
        } finally {
            $template->cleanup();
        }
    }

    public function testRejectsMissingPageLayoutAndUnknownNextPageStyle(): void
    {
        $template = new PageLayoutOdtTemplate($this->templatePath());
        try {
            try {
                $template->definePageStyle('PSA01_Missing', 'Missing');
                self::fail('A missing page layout must be rejected.');
            } catch (RuntimeException $exception) {
                self::assertSame('Page layout "Missing" was not found.', $exception->getMessage());
            }

            $template->definePageLayout('PSA01_Layout', ['page-width' => '21cm']);
            $template->definePageStyle('PSA01_Page', 'PSA01_Layout');
            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('Master page "MissingNext" was not found.');
            $template->setNextPageStyle('PSA01_Page', 'MissingNext');
        } finally {
            $template->cleanup();
        }
    }

    /** @return array{DOMDocument, DOMDocument} */
    private function readXml(string $path): array
    {
        $zip = new ZipArchive();
        self::assertTrue($zip->open($path));
        $stylesXml = $zip->getFromName('styles.xml');
        $contentXml = $zip->getFromName('content.xml');
        $zip->close();
        self::assertIsString($stylesXml);
        self::assertIsString($contentXml);
        $styles = new DOMDocument();
        $content = new DOMDocument();
        self::assertTrue($styles->loadXML($stylesXml));
        self::assertTrue($content->loadXML($contentXml));
        return [$styles, $content];
    }

    private function xpath(DOMDocument $dom): DOMXPath
    {
        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('office', self::OFFICE);
        $xpath->registerNamespace('style', self::STYLE);
        $xpath->registerNamespace('fo', self::FO);
        $xpath->registerNamespace('text', self::TEXT);
        return $xpath;
    }

    private function templatePath(): string
    {
        return dirname(__DIR__) . '/Fixtures/LegacySamples/templates/template_01_simple_variables.odt';
    }
}
