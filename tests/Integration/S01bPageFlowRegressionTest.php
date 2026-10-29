<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Integration;

use DOMDocument;
use DOMXPath;
use OdtTemplateEngine\OdtTemplate;
use PHPUnit\Framework\TestCase;
use ZipArchive;

final class S01bPageFlowRegressionTest extends TestCase
{
    private const STYLE = 'urn:oasis:names:tc:opendocument:xmlns:style:1.0';
    private const FO = 'urn:oasis:names:tc:opendocument:xmlns:xsl-fo-compatible:1.0';
    private const TEXT = 'urn:oasis:names:tc:opendocument:xmlns:text:1.0';

    public function testS01bFlowStylesChainCompleteExperienceAndQualificationBlocks(): void
    {
        $template = new OdtTemplate($this->templatePath());
        $template->styles()->updateParagraph('CVDate', [
            'keep-with-next' => 'always', 'keep-together' => 'always',
        ]);
        $template->styles()->updateParagraph('P7', [
            'keep-with-next' => 'always', 'keep-together' => 'always',
        ]);
        $template->styles()->updateParagraph('P4', [
            'keep-with-next' => 'always', 'keep-together' => 'always',
        ]);
        $template->styles()->updateParagraph('P13', [
            'keep-with-next' => 'always', 'keep-together' => 'always',
        ]);
        $template->styles()->updateParagraph('CVQualification', [
            'keep-with-next' => 'always', 'keep-together' => 'always',
        ]);

        $jobs = [];
        for ($index = 1; $index <= 8; ++$index) {
            $jobs[] = [
                'JobDuration' => '200' . $index . ' – 200' . ($index + 1),
                'JobName' => 'Role ' . $index,
                'JobCompany' => 'Company ' . $index,
            ];
        }
        $entries = $template->section('Experience')->section('JobSection')->instantiateMany($jobs);
        foreach ($entries as $entry) {
            $entry->section('ActivitySection')->instantiateMany([
                ['Activity' => 'Activity one'],
                ['Activity' => 'Activity two'],
                ['Activity' => 'Activity three'],
            ]);
        }

        $qualifications = $template->section('AdditionalQualifications')->section('QualificationSection')->instantiateMany([
            ['QualificationName' => 'Knowledge sharing', 'QualificationDescription' => 'Authored internal guidance.'],
            ['QualificationName' => 'Continuous improvement', 'QualificationDescription' => 'Process design workshops.'],
        ]);
        self::assertCount(8, $entries);
        self::assertCount(2, $qualifications);

        $output = sys_get_temp_dir() . '/s01b-page-flow-' . uniqid('', true) . '.odt';
        try {
            $template->save($output);
            [$styles, $content] = $this->readXml($output);
            $stylesXPath = $this->xpath($styles);
            $contentXPath = $this->xpath($content);

            foreach (['CVDate', 'P7', 'P4', 'P13', 'CVQualification'] as $styleName) {
                $properties = $stylesXPath->query(
                    '//style:style[@style:name="' . $styleName . '"]/style:paragraph-properties'
                )->item(0) ?? $contentXPath->query(
                    '//style:style[@style:name="' . $styleName . '"]/style:paragraph-properties'
                )->item(0);
                self::assertNotNull($properties, $styleName . ' must retain paragraph properties.');
                self::assertSame('always', $properties->getAttributeNS(self::FO, 'keep-with-next'), $styleName);
                self::assertSame('always', $properties->getAttributeNS(self::FO, 'keep-together'), $styleName);
            }

            self::assertSame(0, $contentXPath->query('//text:section[@text:name="JobSection"]')->length);
            self::assertSame(0, $contentXPath->query('//text:section[@text:name="QualificationSection"]')->length);
            self::assertSame(8, $contentXPath->query('//text:section[starts-with(@text:name, "JobSection_")]')->length);
            self::assertSame(2, $contentXPath->query('//text:section[starts-with(@text:name, "QualificationSection_")]')->length);

            $reopened = new OdtTemplate($output);
            self::assertStringContainsString('Role 8', $reopened->section('JobSection_8')->text());
            self::assertStringContainsString('Knowledge sharing', $reopened->section('QualificationSection_1')->text());
        } finally {
            @unlink($output);
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
        $xpath->registerNamespace('style', self::STYLE);
        $xpath->registerNamespace('fo', self::FO);
        $xpath->registerNamespace('text', self::TEXT);
        return $xpath;
    }

    private function templatePath(): string
    {
        return dirname(__DIR__, 2) . '/samples/templates/template_S01b_cv_structured.odt';
    }
}
