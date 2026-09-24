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
        $temporaryOutput = $this->generateFreshOutput();
        $output = $this->openAbsoluteArchive($temporaryOutput);

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
            self::assertSame(1, substr_count($outputContent, 'Employer engagement proved most effective when'));
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
            self::assertStringContainsString('text:name="program_name"', $styles);
            self::assertStringContainsString('text:user-field-get text:name="program_name"', $styles);
            self::assertStringNotContainsString('Pathways 360 Demo Program</text:span>', $styles);

            $manifest = $this->part($output, 'META-INF/manifest.xml');
            self::assertStringContainsString('s03-participant-outcomes-2027.png', $manifest);
            self::assertNotFalse($output->locateName('Pictures/s03-participant-outcomes-2027.png'));
        } finally {
            $template->close();
            $output->close();
            $this->removeDirectory(dirname(dirname(dirname($temporaryOutput))));
        }
    }

    private function openArchive(string $relativePath): ZipArchive
    {
        $archive = new ZipArchive();
        self::assertTrue($archive->open(dirname(__DIR__, 2) . '/' . $relativePath) === true);

        return $archive;
    }

    private function openAbsoluteArchive(string $path): ZipArchive
    {
        $archive = new ZipArchive();
        self::assertTrue($archive->open($path) === true);

        return $archive;
    }

    private function generateFreshOutput(): string
    {
        $repositoryRoot = dirname(__DIR__, 2);
        $temporaryRoot = sys_get_temp_dir() . '/odt-s03-a-' . bin2hex(random_bytes(6));
        mkdir($temporaryRoot . '/samples/templates', 0755, true);
        mkdir($temporaryRoot . '/samples/assets', 0755, true);
        mkdir($temporaryRoot . '/samples/output', 0755, true);
        mkdir($temporaryRoot . '/src', 0755, true);
        mkdir($temporaryRoot . '/vendor', 0755, true);
        mkdir($temporaryRoot . '/caller', 0755, true);

        $this->copyDirectory($repositoryRoot . '/src', $temporaryRoot . '/src');
        $this->copyDirectory($repositoryRoot . '/vendor', $temporaryRoot . '/vendor');
        self::assertTrue(copy(
            $repositoryRoot . '/samples/templates/template_S03_structured_professional_report.odt',
            $temporaryRoot . '/samples/templates/template_S03_structured_professional_report.odt'
        ));
        self::assertTrue(copy(
            $repositoryRoot . '/samples/sample_S03_structured_professional_report.php',
            $temporaryRoot . '/samples/sample_S03_structured_professional_report.php'
        ));
        self::assertTrue(copy(
            $repositoryRoot . '/samples/assets/s03-participant-outcomes-2027.png',
            $temporaryRoot . '/samples/assets/s03-participant-outcomes-2027.png'
        ));

        $process = proc_open(
            [PHP_BINARY, $temporaryRoot . '/samples/sample_S03_structured_professional_report.php'],
            [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes,
            $temporaryRoot . '/caller'
        );
        self::assertIsResource($process);
        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        self::assertSame('', trim($stderr), $stderr);
        self::assertSame(0, proc_close($process), $stdout);

        return $temporaryRoot . '/samples/output/output_S03_structured_professional_report.odt';
    }

    private function copyDirectory(string $source, string $destination): void
    {
        $directory = new \RecursiveDirectoryIterator($source, \FilesystemIterator::SKIP_DOTS);
        $iterator = new \RecursiveIteratorIterator($directory, \RecursiveIteratorIterator::SELF_FIRST);
        foreach ($iterator as $item) {
            $relative = substr($item->getPathname(), strlen($source) + 1);
            $target = $destination . '/' . $relative;
            if ($item->isDir()) {
                mkdir($target, 0755, true);
            } else {
                mkdir(dirname($target), 0755, true);
                copy($item->getPathname(), $target);
            }
        }
    }

    private function removeDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($iterator as $item) {
            if ($item->isDir()) {
                rmdir($item->getPathname());
            } else {
                unlink($item->getPathname());
            }
        }
        rmdir($directory);
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
