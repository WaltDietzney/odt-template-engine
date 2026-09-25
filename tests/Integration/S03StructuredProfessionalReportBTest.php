<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Integration;

use DOMDocument;
use DOMXPath;
use OdtTemplateEngine\OdtTemplate;
use PHPUnit\Framework\TestCase;
use ZipArchive;

final class S03StructuredProfessionalReportBTest extends TestCase
{
    public function testS03BComposesASecondReportFromTheCanonicalWriterTemplate(): void
    {
        $template = $this->openArchive('samples/templates/template_S03_structured_professional_report.odt');
        $output = $this->openArchive('tests/Fixtures/LegacySamples/output/output_S03_structured_professional_report_b.odt');

        try {
            $templateContent = $this->part($template, 'content.xml');
            $outputContent = $this->part($output, 'content.xml');
            $outputStyles = $this->part($output, 'styles.xml');

            self::assertStringNotContainsString('{{', $templateContent);
            self::assertStringNotContainsString('{{', $outputContent);
            self::assertStringNotContainsString('DOMDocument', file_get_contents(dirname(__DIR__, 2) . '/tests/Fixtures/LegacySamples/sample_S03_structured_professional_report_b.php'));
            self::assertStringNotContainsString('DOMXPath', file_get_contents(dirname(__DIR__, 2) . '/tests/Fixtures/LegacySamples/sample_S03_structured_professional_report_b.php'));
            self::assertStringContainsString('Northbridge Community Trust', $outputContent);
            self::assertStringContainsString('ForwardWorks Initiative', $outputContent);
            self::assertStringContainsString('Strategic Review', $outputContent);

            $xpath = $this->xpath($outputContent);
            self::assertCount(1, $xpath->query('//text:section[@text:name="FindingDigitalConfidence"]'));
            self::assertCount(1, $xpath->query('//text:section[@text:name="FindingDigitalConfidence_1"]'));
            self::assertCount(1, $xpath->query('//text:section[@text:name="ProgramObjectivesContent"]'));
            self::assertCount(1, $xpath->query('//text:section[@text:name="ChallengesContent"]'));
            self::assertCount(1, $xpath->query('//text:section[@text:name="RecommendationsContent"]'));
            self::assertStringContainsString('Local delivery capacity needs reinforcement', $outputContent);
            self::assertStringContainsString('Strengthen local delivery partnerships before expanding the footprint.', $outputContent);
            self::assertStringContainsString('Specialist workshop capacity is uneven across locations.', $outputContent);

            self::assertCount(1, $xpath->query('//table:table[@table:name="ActivitiesDeliveredTable"]'));
            self::assertCount(1, $xpath->query('//table:table[@table:name="ActivitiesDeliveredTable"]/table:table-header-rows/table:table-row'));
            self::assertCount(5, $xpath->query('//table:table[@table:name="ActivitiesDeliveredTable"]/table:table-row'));
            self::assertCount(1, $xpath->query('//table:table[@table:name="PerformanceAgainstTargetsTable"]'));
            self::assertCount(1, $xpath->query('//table:table[@table:name="PerformanceAgainstTargetsTable"]/table:table-header-rows/table:table-row'));
            self::assertCount(4, $xpath->query('//table:table[@table:name="PerformanceAgainstTargetsTable"]/table:table-row'));

            self::assertCount(1, $xpath->query('//draw:frame[@draw:name="ReportTitleImage"][@svg:width="15cm"][@svg:height="8.452cm"]'));
            self::assertCount(1, $xpath->query('//draw:frame[@draw:name="ParticipantOutcomesImage"][@svg:width="15.799cm"][@svg:height="10.007cm"]'));
            self::assertStringContainsString('s03-strategic-review-cover-2028.png', $this->part($output, 'META-INF/manifest.xml'));
            self::assertStringContainsString('s03-strategic-outcomes-2028.png', $this->part($output, 'META-INF/manifest.xml'));
            self::assertNotFalse($output->locateName('Pictures/s03-strategic-review-cover-2028.png'));
            self::assertNotFalse($output->locateName('Pictures/s03-strategic-outcomes-2028.png'));
            self::assertStringContainsString('ForwardWorks Initiative', $outputStyles);
        } finally {
            $template->close();
            $output->close();
        }

        $reopened = new OdtTemplate(dirname(__DIR__, 2) . '/tests/Fixtures/LegacySamples/output/output_S03_structured_professional_report_b.odt');
        self::assertSame('FindingDigitalConfidence_1', $reopened->section('FindingDigitalConfidence_1')->descriptor()->name());
        self::assertSame('ProgramObjectivesContent', $reopened->section('ProgramObjectivesContent')->descriptor()->name());
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
        $xpath->registerNamespace('draw', 'urn:oasis:names:tc:opendocument:xmlns:drawing:1.0');
        $xpath->registerNamespace('svg', 'urn:oasis:names:tc:opendocument:xmlns:svg-compatible:1.0');
        $xpath->registerNamespace('table', 'urn:oasis:names:tc:opendocument:xmlns:table:1.0');
        $xpath->registerNamespace('text', 'urn:oasis:names:tc:opendocument:xmlns:text:1.0');

        return $xpath;
    }
}
