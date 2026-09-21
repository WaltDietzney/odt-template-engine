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
            self::assertMatchesRegularExpression(
                '/^(?:[a-z][a-z0-9.-]+|(?:L|C|S)\d{2}[a-z]?)$/',
                $sample['id']
            );
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

    public function testL01ThroughL03AreCanonicalSimpleTemplateLearnSamples(): void
    {
        $samples = [];
        foreach ($this->registry()['samples'] as $sample) {
            $samples[$sample['id']] = $sample;
        }

        $expected = [
            'L01' => [
                'samples/sample_L01_variables_filters.php',
                'samples/templates/template_L01_variables_filters.odt',
                'samples/output/output_L01_variables_filters.odt',
            ],
            'L02' => [
                'samples/sample_L02_conditions.php',
                'samples/templates/template_L02_conditions.odt',
                'samples/output/output_L02_conditions.odt',
            ],
            'L03' => [
                'samples/sample_L03_repeating_content.php',
                'samples/templates/template_L03_repeating_content.odt',
                'samples/output/output_L03_repeating_content.odt',
            ],
        ];

        foreach ($expected as $id => [$entryPoint, $templatePath, $outputPath]) {
            $sample = $samples[$id];
            self::assertSame('canonical', $sample['status']);
            self::assertSame('learn', $sample['role']);
            self::assertSame('simple-template', $sample['ownership']);
            self::assertSame('composer', $sample['distribution']);
            self::assertSame('odt', $sample['execution_mode']);
            self::assertSame($entryPoint, $sample['entry_point']);
            self::assertSame($templatePath, $sample['template_path']);
            self::assertSame($outputPath, $sample['output_path']);
            self::assertSame([], $sample['migration_targets']);
        }

        self::assertSame(
            ['L06'],
            $samples['legacy.sample-01.simple-variables']['migration_targets'],
            'Legacy Sample 01 still contains image behavior pending L06.'
        );
        self::assertSame(
            [],
            $samples['legacy.sample-02.filter']['migration_targets'],
            'Legacy Sample 02 is retained, but its filters and conditional logic are represented canonically.'
        );
        self::assertSame([], $samples['legacy.sample-03.logic-elements']['migration_targets']);
    }

    public function testL04ThroughL06AreCanonicalProgrammaticLearnSamples(): void
    {
        $samples = [];
        foreach ($this->registry()['samples'] as $sample) {
            $samples[$sample['id']] = $sample;
        }

        $expected = [
            'L04' => ['programmatic-elements', 'sample_L04_rich_content.php', 'template_L04_rich_content.odt', 'output_L04_rich_content.odt'],
            'L05' => ['programmatic-elements', 'sample_L05_lists.php', 'template_L05_lists.odt', 'output_L05_lists.odt'],
            'L06' => ['mixed', 'sample_L06_images.php', 'template_L06_images.odt', 'output_L06_images.odt'],
        ];

        foreach ($expected as $id => [$ownership, $entry, $template, $output]) {
            $sample = $samples[$id];
            self::assertSame('canonical', $sample['status']);
            self::assertSame('learn', $sample['role']);
            self::assertSame($ownership, $sample['ownership']);
            self::assertSame('composer', $sample['distribution']);
            self::assertSame('odt', $sample['execution_mode']);
            self::assertSame('samples/' . $entry, $sample['entry_point']);
            self::assertSame('samples/templates/' . $template, $sample['template_path']);
            self::assertSame('samples/output/' . $output, $sample['output_path']);
            self::assertSame([], $sample['migration_targets']);
        }

        $legacy = [];
        foreach ($this->registry()['samples'] as $sample) {
            $legacy[$sample['id']] = $sample;
        }
        self::assertSame(['L06'], $legacy['legacy.sample-01.simple-variables']['migration_targets']);
        self::assertSame(['L06'], $legacy['legacy.sample-05.replace-image']['migration_targets']);
        self::assertSame(['L06'], $legacy['legacy.sample-05b.replace-images']['migration_targets']);
        self::assertSame(['L06'], $legacy['legacy.sample-06.image-settings']['migration_targets']);
        self::assertSame(['L04'], $legacy['legacy.sample-07.contact-list']['migration_targets']);
        self::assertSame(['L04', 'L05'], $legacy['legacy.sample-09.richtext-block']['migration_targets']);
        self::assertSame(['L04', 'L06', 'S02'], $legacy['legacy.sample-14.advanced-tabs']['migration_targets']);
        self::assertSame(['L04', 'C03'], $legacy['legacy.sample-16.tabs-basic']['migration_targets']);
        self::assertSame(['L05'], $legacy['legacy.sample-18.list-styles']['migration_targets']);
    }

    public function testL07AndL08AreCanonicalProgrammaticLearnSamples(): void
    {
        $samples = [];
        foreach ($this->registry()['samples'] as $sample) {
            $samples[$sample['id']] = $sample;
        }

        foreach ([
            'L07' => ['Tables', 'sample_L07_tables.php', 'template_L07_tables.odt', 'output_L07_tables.odt'],
            'L08' => ['HTML Import', 'sample_L08_html_import.php', 'template_L08_html_import.odt', 'output_L08_html_import.odt'],
        ] as $id => [$title, $entryPoint, $template, $output]) {
            $sample = $samples[$id];
            self::assertSame($title, $sample['title']);
            self::assertSame('canonical', $sample['status']);
            self::assertSame('learn', $sample['role']);
            self::assertSame('programmatic-elements', $sample['ownership']);
            self::assertSame('composer', $sample['distribution']);
            self::assertSame('odt', $sample['execution_mode']);
            self::assertSame('samples/' . $entryPoint, $sample['entry_point']);
            self::assertSame('samples/templates/' . $template, $sample['template_path']);
            self::assertSame('samples/output/' . $output, $sample['output_path']);
            self::assertSame([], $sample['migration_targets']);
        }

        foreach ([
            'legacy.sample-08.html' => ['L08'],
            'legacy.sample-11.table' => ['C02'],
            'legacy.sample-12.advanced-table' => ['L07'],
            'legacy.sample-13.cell-settings' => ['L07'],
            'legacy.sample-15.styled-table' => [],
            'legacy.sample-19.html-table' => [],
            'legacy.sample-20.table-ratio' => ['C02'],
            'legacy.sample-26.table-layout' => ['C02'],
        ] as $id => $targets) {
            self::assertSame($targets, $samples[$id]['migration_targets'], $id . ' migration state changed unexpectedly.');
        }
    }

    public function testL09ThroughL11AreCanonicalSamplesWithNativeOwnershipAndInspectionMode(): void
    {
        $samples = [];
        foreach ($this->registry()['samples'] as $sample) {
            $samples[$sample['id']] = $sample;
        }

        foreach ([
            'L09' => ['Native Objects', 'addressable-native-odt', 'sample_L09_native_objects.php', 'template_L09_native_objects.odt', 'output_L09_native_objects.odt', 'odt'],
            'L10' => ['Writer User Fields', 'addressable-native-odt', 'sample_L10_writer_user_fields.php', 'template_L10_writer_user_fields.odt', 'output_L10_writer_user_fields.odt', 'odt'],
            'L11' => ['Template Inspection', 'mixed', 'sample_L11_template_inspection.php', 'template_L11_template_inspection.odt', null, 'inspection'],
        ] as $id => [$title, $ownership, $entry, $template, $output, $mode]) {
            $sample = $samples[$id];
            self::assertSame($title, $sample['title']);
            self::assertSame('canonical', $sample['status']);
            self::assertSame('learn', $sample['role']);
            self::assertSame($ownership, $sample['ownership']);
            self::assertSame('composer', $sample['distribution']);
            self::assertSame($mode, $sample['execution_mode']);
            self::assertSame('samples/' . $entry, $sample['entry_point']);
            self::assertSame('samples/templates/' . $template, $sample['template_path']);
            self::assertSame($output === null ? null : 'samples/output/' . $output, $sample['output_path']);
            self::assertSame([], $sample['migration_targets']);
        }

        self::assertSame(['L11'], $samples['legacy.sample-28.template-inspection']['migration_targets']);
        self::assertSame(['L10'], $samples['legacy.sample-29.user-field-binding']['migration_targets']);
        self::assertSame(['L09', 'L06'], $samples['legacy.sample-24.section-image-replacement']['migration_targets']);
        self::assertSame(['C04', 'S01b'], $samples['legacy.sample-25.section-instantiation']['migration_targets']);
    }

    public function testF6CapabilitySamplesAreCanonicalAndRegistryDriven(): void
    {
        $samples = [];
        foreach ($this->registry()['samples'] as $sample) {
            $samples[$sample['id']] = $sample;
        }

        foreach ([
            'C01' => ['Page & Flow Layout', 'mixed', 'page_flow_layout'],
            'C02' => ['Advanced Table Layout', 'programmatic-elements', 'advanced_table_layout'],
            'C03' => ['Frame Layout', 'programmatic-elements', 'frame_layout'],
            'C04' => ['Declarative Structured Collections', 'addressable-native-odt', 'declarative_structured_collections'],
            'C05' => ['Mapping & Automation', 'mixed', 'mapping_automation'],
        ] as $id => [$title, $ownership, $slug]) {
            $sample = $samples[$id];
            self::assertSame($title, $sample['title']);
            self::assertSame('canonical', $sample['status']);
            self::assertSame('capability', $sample['role']);
            self::assertSame($ownership, $sample['ownership']);
            self::assertSame('composer', $sample['distribution']);
            self::assertSame('odt', $sample['execution_mode']);
            self::assertSame('samples/sample_C' . substr($id, 1) . '_' . $slug . '.php', $sample['entry_point']);
            self::assertSame('samples/templates/template_C' . substr($id, 1) . '_' . $slug . '.odt', $sample['template_path']);
            self::assertSame('samples/output/output_C' . substr($id, 1) . '_' . $slug . '.odt', $sample['output_path']);
            self::assertSame([], $sample['migration_targets']);
        }

        self::assertSame(['C02'], $samples['legacy.sample-11.table']['migration_targets']);
        self::assertSame(['C03'], $samples['legacy.sample-17.text-field']['migration_targets']);
        self::assertSame(['C02'], $samples['legacy.sample-20.table-ratio']['migration_targets']);
        self::assertSame(['C02'], $samples['legacy.sample-26.table-layout']['migration_targets']);
        self::assertSame(['C03'], $samples['legacy.sample-27.frame-layout']['migration_targets']);
        self::assertSame(['C04', 'S01b'], $samples['legacy.sample-25.section-instantiation']['migration_targets']);
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
        self::assertSame(
            1,
            $xpath->query('//text:section[@text:name="#foreach:projects"]/text:section[@text:name="#foreach:milestones"]')->length
        );
        self::assertStringContainsString('Heading_20_2', $styles);
        self::assertStringContainsString('Body_20_Text.foot', $content);
    }

    public function testSampleExplorerGeneratorRejectsUnregisteredAndRepositoryOnlyEntries(): void
    {
        $unregistered = $this->runGenerator('sample_999_arbitrary');
        self::assertStringContainsString('Sample is not registered for public discovery.', $unregistered);

        $repositoryOnly = $this->runGenerator('legacy.sample-29.user-field-binding');
        self::assertStringContainsString('not a self-contained packaged ODT example', $repositoryOnly);

        $inspectionOnly = $this->runGenerator('L11');
        self::assertStringContainsString('not a self-contained packaged ODT example', $inspectionOnly);
    }

    public function testSampleExplorerPresentsCanonicalLearnEntriesFromTheRegistry(): void
    {
        $html = $this->runExplorer();

        foreach ([
            'data-sample-id="L01"',
            'data-sample-id="L02"',
            'data-sample-id="L03"',
            'Variables &amp; Filters',
            'Conditions',
            'Repeating Content',
            'data-sample="L01"',
            'data-sample="L02"',
            'data-sample="L03"',
            'data-sample-id="L07"',
            'data-sample-id="L08"',
            'data-sample-id="L09"',
            'data-sample-id="L10"',
            'data-sample-id="L11"',
            'Tables',
            'HTML Import',
            'Native Objects',
            'Writer User Fields',
            'Template Inspection',
            'data-sample-id="C01"',
            'data-sample-id="C02"',
            'data-sample-id="C03"',
            'data-sample-id="C04"',
            'data-sample-id="C05"',
            'Page &amp; Flow Layout',
            'Advanced Table Layout',
            'Frame Layout',
            'Declarative Structured Collections',
            'Mapping &amp; Automation',
            'data-sample="L07"',
            'data-sample="L08"',
            'data-sample="L09"',
            'data-sample="L10"',
            'Inspection only · no generated ODT.',
        ] as $expected) {
            self::assertStringContainsString($expected, $html);
        }
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
