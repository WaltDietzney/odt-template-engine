<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Integration;

use DOMDocument;
use DOMXPath;
use PHPUnit\Framework\TestCase;
use ZipArchive;

final class S03StructuredProfessionalReportTest extends TestCase
{
    public function testCanonicalTemplateAndOutputContainTheExpectedNativeTargets(): void
    {
        $template = $this->openArchive('samples/templates/template_S03_structured_professional_report.odt');
        $output = $this->openArchive('samples/output/output_S03_structured_professional_report.odt');

        try {
            $templateContent = $this->part($template, 'content.xml');
            $outputContent = $this->part($output, 'content.xml');
            self::assertStringNotContainsString('{{', $templateContent);
            self::assertStringNotContainsString('{{', $outputContent);

            $outputXPath = $this->xpath($outputContent);
            self::assertCount(1, $outputXPath->query('//table:table[@table:name="ActivitiesDeliveredTable"]'));
            self::assertCount(1, $outputXPath->query('//table:table[@table:name="ActivitiesDeliveredTable"]/table:table-header-rows/table:table-row'));
            self::assertCount(6, $outputXPath->query('//table:table[@table:name="ActivitiesDeliveredTable"]/table:table-row'));
            self::assertCount(1, $outputXPath->query('//table:table[@table:name="PerformanceAgainstTargetsTable"]'));
            self::assertCount(1, $outputXPath->query('//table:table[@table:name="PerformanceAgainstTargetsTable"]/table:table-header-rows/table:table-row'));
            self::assertCount(4, $outputXPath->query('//table:table[@table:name="PerformanceAgainstTargetsTable"]/table:table-row'));

            foreach (['ReportHeadline', 'ExecutiveSummary', 'ProgramOverview', 'PerformanceOutcomes', 'FindingsAnalysis', 'RecommendationsOutlook'] as $section) {
                self::assertCount(1, $outputXPath->query('//text:section[@text:name="' . $section . '"]'));
            }

            self::assertStringContainsString('ParticipantOutcomesImage', $outputContent);
            self::assertStringContainsString('svg:width="15.799cm"', $outputContent);
            self::assertStringContainsString('svg:height="10.007cm"', $outputContent);
            self::assertCount(1, $outputXPath->query('//text:bookmark-start[@text:name="ExecutiveSummaryIntro"]'));
            self::assertCount(1, $outputXPath->query('//text:bookmark-end[@text:name="ExecutiveSummaryIntro"]'));
            self::assertStringContainsString('Bridgeway Skills', $outputContent);
            self::assertStringContainsString('Meridian Learning Foundation', $outputContent);

            $styles = $this->part($output, 'styles.xml');
            foreach (['organization_short_name', 'report_title', 'report_year', 'organization_name'] as $field) {
                self::assertStringContainsString('text:name="' . $field . '"', $styles);
            }
            foreach ([
                'Meridian Learning Foundation',
                'MERIDIAN',
                'Community Pathways Performance Report',
                '2027',
                'Bridgeway Skills Programme',
                'January–December 2027',
                'Final Report',
                '31 January 2028',
            ] as $value) {
                self::assertStringContainsString($value, $outputContent);
            }
            foreach (['MERIDIAN', 'Community Pathways Performance Report', '2027', 'Meridian Learning Foundation'] as $value) {
                self::assertStringContainsString($value, $styles);
            }

            $manifest = $this->part($output, 'META-INF/manifest.xml');
            self::assertStringContainsString('s03-participant-outcomes-2027.png', $manifest);
            self::assertNotFalse($output->locateName('Pictures/s03-participant-outcomes-2027.png'));
        } finally {
            $template->close();
            $output->close();
        }
    }

    private function openArchive(string $relativePath): ZipArchive
    {
        $archive = new ZipArchive();
        self::assertTrue($archive->open(dirname(__DIR__, 2) . '/' . $relativePath) === true);

        return $archive;
    }

    private function part(ZipArchive $archive, string $name): string
    {
        $part = $archive->getFromName($name);
        self::assertIsString($part);

        return $part;
    }

    private function xpath(string $xml): DOMXPath
    {
        $document = new DOMDocument();
        self::assertTrue($document->loadXML($xml));
        $xpath = new DOMXPath($document);
        $xpath->registerNamespace('table', 'urn:oasis:names:tc:opendocument:xmlns:table:1.0');
        $xpath->registerNamespace('text', 'urn:oasis:names:tc:opendocument:xmlns:text:1.0');

        return $xpath;
    }
}
