<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Integration;

use PHPUnit\Framework\TestCase;

final class SampleRegistryTest extends TestCase
{
    public function testRegistryContainsOnlyTheCanonicalPublicTaxonomy(): void
    {
        $registry = $this->registry();
        $samples = $registry['samples'];
        $ids = array_column($samples, 'id');

        self::assertSame(
            ['L01', 'L02', 'L03', 'L04', 'L05', 'L06', 'L07', 'L08', 'L09', 'L10', 'L11', 'L12',
                'C01', 'C02', 'C03', 'C04', 'C05', 'B01', 'B02', 'S01b', 'S03'],
            $ids
        );

        foreach ($samples as $sample) {
            self::assertMatchesRegularExpression('/^(?:L|C|B|S)\d{2}[a-z]?$/', $sample['id']);
            self::assertSame('canonical', $sample['status']);
            self::assertArrayNotHasKey('migration_targets', $sample);
            self::assertSame('composer', $sample['distribution']);
            self::assertFileExists($this->path($sample['entry_point']));
            if ($sample['template_path'] !== null) {
                self::assertFileExists($this->path($sample['template_path']));
            }
        }
    }

    public function testCanonicalSamplesHaveUniqueRunnablePaths(): void
    {
        $samples = $this->registry()['samples'];
        $entryPoints = array_column($samples, 'entry_point');
        $outputPaths = array_filter(array_column($samples, 'output_path'));

        self::assertCount(count(array_unique($entryPoints)), $entryPoints);
        self::assertCount(count(array_unique($outputPaths)), $outputPaths);

        foreach ($samples as $sample) {
            self::assertMatchesRegularExpression('#^samples/#', $sample['entry_point']);
            self::assertContains($sample['role'], ['learn', 'capability', 'builder', 'showcase']);
            self::assertContains($sample['ownership'], [
                'simple-template', 'programmatic-elements', 'addressable-native-odt', 'mixed',
            ]);
            self::assertNotSame('', trim($sample['purpose']));
            if ($sample['execution_mode'] === 'inspection') {
                self::assertNull($sample['output_path']);
            } else {
                self::assertMatchesRegularExpression('#^samples/output/output_[A-Za-z0-9_-]+\.odt$#', $sample['output_path']);
            }
        }
    }

    public function testCanonicalSamplesUseExpectedRepresentativePaths(): void
    {
        $samples = [];
        foreach ($this->registry()['samples'] as $sample) {
            $samples[$sample['id']] = $sample;
        }

        self::assertSame('samples/templates/invoice-richtext-prototype.odt', $samples['B01']['template_path']);
        self::assertSame('samples/templates/template_S01b_cv_structured.odt', $samples['S01b']['template_path']);
        self::assertSame('samples/templates/template_S03_structured_professional_report.odt', $samples['S03']['template_path']);
        self::assertSame('inspection', $samples['L11']['execution_mode']);
        self::assertNull($samples['L11']['output_path']);
    }

    public function testC04TemplateContainsNativeNestedSectionControls(): void
    {
        $archive = new \ZipArchive();
        self::assertTrue($archive->open($this->path('samples/templates/template_C04_declarative_structured_collections.odt')) === true);
        $content = $archive->getFromName('content.xml');
        $styles = $archive->getFromName('styles.xml');
        $archive->close();
        self::assertIsString($content);
        self::assertIsString($styles);

        $dom = new \DOMDocument();
        self::assertTrue($dom->loadXML($content));
        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('text', 'urn:oasis:names:tc:opendocument:xmlns:text:1.0');
        self::assertSame(1, $xpath->query('//text:section[@text:name="#foreach:projects"]')->length);
        self::assertSame(1, $xpath->query('//text:section[@text:name="#if:featured"]')->length);
        self::assertSame(1, $xpath->query('//text:section[@text:name="#ifnot:archived"]')->length);
        self::assertSame(1, $xpath->query('//text:section[@text:name="#foreach:milestones"]')->length);
        self::assertStringContainsString('Heading_20_2', $styles);
    }

    public function testSampleExplorerRejectsUnregisteredSampleAndListsCanonicalSamples(): void
    {
        $unregistered = $this->runGenerator('sample_999_arbitrary');
        self::assertStringContainsString('Sample is not registered for public discovery.', $unregistered);

        $html = $this->runExplorer();
        foreach (['L01', 'L12', 'C01', 'C05', 'B01', 'B02', 'S01b', 'S03'] as $id) {
            self::assertStringContainsString('data-sample-id="' . $id . '"', $html);
        }
        foreach (['legacy.sample-', 'sample_01_', 'sample_21_', 'sample_S02_', 'sample_S03_structured_professional_report_b'] as $legacyMarker) {
            self::assertStringNotContainsString($legacyMarker, $html);
        }
    }

    /** @return array{version: int, samples: list<array<string, mixed>>} */
    private function registry(): array
    {
        $registry = require dirname(__DIR__, 2) . '/samples/sample-registry.php';
        self::assertSame(1, $registry['version']);
        return $registry;
    }

    private function path(string $repositoryRelativePath): string
    {
        self::assertFalse(str_starts_with($repositoryRelativePath, '/'));
        self::assertDoesNotMatchRegularExpression('#(?:^|/)\.\.(?:/|$)#', $repositoryRelativePath);
        return dirname(__DIR__, 2) . '/' . $repositoryRelativePath;
    }

    private function runGenerator(string $sampleId): string
    {
        $repositoryRoot = dirname(__DIR__, 2);
        $phpCode = '$_GET["sample"] = ' . var_export($sampleId, true)
            . '; require "demo/sample-explorer/generate.php";';
        $process = proc_open(
            [PHP_BINARY, '-r', $phpCode],
            [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes,
            $repositoryRoot
        );
        self::assertIsResource($process);
        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        self::assertSame(0, proc_close($process));
        self::assertSame('', $stderr);
        return $stdout;
    }

    private function runExplorer(): string
    {
        $repositoryRoot = dirname(__DIR__, 2);
        $process = proc_open(
            [PHP_BINARY, 'demo/sample-explorer/index.php'],
            [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes,
            $repositoryRoot
        );
        self::assertIsResource($process);
        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        self::assertSame(0, proc_close($process));
        self::assertSame('', $stderr);
        return $stdout;
    }
}
