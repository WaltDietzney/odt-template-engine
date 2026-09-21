<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Integration;

use PHPUnit\Framework\TestCase;

final class SampleRegistryTest extends TestCase
{
    public function testRegistryEntriesHaveUniqueValidIdentityAndOwnershipMetadata(): void
    {
        $samples = $this->registry()['samples'];
        $ids = [];
        $entryPoints = [];
        $outputPaths = [];

        foreach ($samples as $sample) {
            self::assertMatchesRegularExpression('/^[a-z][a-z0-9.-]+$/', $sample['id']);
            self::assertNotContains($sample['id'], $ids, 'Duplicate sample ID: ' . $sample['id']);
            $ids[] = $sample['id'];

            self::assertContains($sample['role'], ['learn', 'capability', 'showcase']);
            self::assertContains($sample['ownership'], [
                'simple-template',
                'programmatic-elements',
                'addressable-native-odt',
                'mixed',
            ]);
            self::assertContains($sample['status'], ['canonical', 'migration']);
            self::assertContains($sample['distribution'], ['composer', 'repository-only']);
            self::assertContains($sample['execution_mode'], ['odt', 'inspection']);
            self::assertNotSame('', trim($sample['title']));
            self::assertNotSame('', trim($sample['purpose']));
            self::assertIsArray($sample['migration_targets']);

            self::assertStringStartsWith('samples/', $sample['entry_point']);
            self::assertFileExists($this->path($sample['entry_point']));
            self::assertNotContains($sample['entry_point'], $entryPoints, 'Duplicate sample entry point.');
            $entryPoints[] = $sample['entry_point'];

            if ($sample['template_path'] !== null) {
                self::assertFileExists($this->path($sample['template_path']));
            }

            if ($sample['execution_mode'] === 'inspection') {
                self::assertNull($sample['output_path']);
            } else {
                self::assertMatchesRegularExpression(
                    '#^samples/output/output_[A-Za-z0-9_-]+\.odt$#',
                    $sample['output_path']
                );
                self::assertNotContains($sample['output_path'], $outputPaths, 'Duplicate canonical output path.');
                $outputPaths[] = $sample['output_path'];
            }
        }
    }

    public function testMigrationTargetsAreReferencesRatherThanCanonicalSampleIdentities(): void
    {
        foreach ($this->registry()['samples'] as $sample) {
            if ($sample['status'] === 'migration') {
                self::assertStringStartsWith('legacy.', $sample['id']);
            } else {
                self::assertMatchesRegularExpression('/^(?:L|C|S)\d{2}[a-z]?$/', $sample['id']);
            }

            foreach ($sample['migration_targets'] as $target) {
                self::assertMatchesRegularExpression('/^(?:L|C|S)\d{2}[a-z]?$/', $target);
            }
        }
    }

    public function testSamplesDependingOnTestFixturesAreExplicitlyRepositoryOnly(): void
    {
        $samples = [];
        foreach ($this->registry()['samples'] as $sample) {
            $samples[$sample['entry_point']] = $sample;
        }

        foreach ([
            'samples/sample_28_inspectTemplateContract.php',
            'samples/sample_29_userFieldBinding.php',
        ] as $entryPoint) {
            self::assertSame('repository-only', $samples[$entryPoint]['distribution']);
            self::assertStringStartsWith('tests/fixtures/', $samples[$entryPoint]['template_path']);
        }

        self::assertSame('inspection', $samples['samples/sample_28_inspectTemplateContract.php']['execution_mode']);
        self::assertSame(
            'samples/output/output_29_userFieldBinding.odt',
            $samples['samples/sample_29_userFieldBinding.php']['output_path']
        );
    }

    public function testBookmarkAndSectionSamplesUseTheirActualTrackedPaths(): void
    {
        $samples = [];
        foreach ($this->registry()['samples'] as $sample) {
            $samples[$sample['entry_point']] = $sample;
        }

        self::assertSame(
            'samples/templates/sample_22_bookmarkTextReplacement.odt',
            $samples['samples/sample_22_bookmarkTextReplacement.php']['template_path']
        );
        self::assertSame(
            'samples/templates/sample_23_sectionContentReplacement.odt',
            $samples['samples/sample_23_sectionContentReplacement.php']['template_path']
        );
        self::assertSame(
            'samples/templates/sample_24_sectionImageReplacement.odt',
            $samples['samples/sample_24_sectionImageReplacement.php']['template_path']
        );
    }

    public function testSampleExplorerGeneratorRejectsUnregisteredAndRepositoryOnlyEntries(): void
    {
        $unregistered = $this->runGenerator('sample_999_arbitrary');
        self::assertStringContainsString('Sample is not registered for public discovery.', $unregistered);

        $repositoryOnly = $this->runGenerator('legacy.sample-29.user-field-binding');
        self::assertStringContainsString('not a self-contained packaged ODT example', $repositoryOnly);
    }

    /** @return array{version: int, samples: list<array<string, mixed>>} */
    private function registry(): array
    {
        $registry = require dirname(__DIR__, 2) . '/samples/sample-registry.php';
        self::assertSame(1, $registry['version']);
        self::assertNotEmpty($registry['samples']);

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
}
