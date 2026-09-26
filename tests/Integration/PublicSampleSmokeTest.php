<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Integration;

use PHPUnit\Framework\TestCase;
use ZipArchive;

final class PublicSampleSmokeTest extends TestCase
{
    private string $temporaryDirectory;

    protected function setUp(): void
    {
        $this->temporaryDirectory = sys_get_temp_dir() . '/odt-public-samples-' . bin2hex(random_bytes(6));
        mkdir($this->temporaryDirectory, 0755, true);

        $repositoryRoot = dirname(__DIR__, 2);
        $this->copyDirectory($repositoryRoot . '/assets', $this->temporaryDirectory . '/assets');
        $this->copyDirectory($repositoryRoot . '/src', $this->temporaryDirectory . '/src');
        $this->copyDirectory($repositoryRoot . '/vendor', $this->temporaryDirectory . '/vendor');
        $this->copyDirectory($repositoryRoot . '/samples', $this->temporaryDirectory . '/samples', [
            'output',
        ]);
        mkdir($this->temporaryDirectory . '/samples/output', 0755, true);
        mkdir($this->temporaryDirectory . '/caller', 0755, true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->temporaryDirectory);
    }

    public function testAllPublicSamplesRunInIsolationFromAnExternalWorkingDirectory(): void
    {
        $repositoryRoot = dirname(__DIR__, 2);
        $registry = require $this->temporaryDirectory . '/samples/sample-registry.php';
        $samples = array_values(array_filter(
            $registry['samples'],
            static fn (array $sample): bool => $sample['distribution'] === 'composer'
                && $sample['execution_mode'] === 'odt'
        ));
        $beforeOutput = $this->directorySnapshot($repositoryRoot . '/samples/output');

        self::assertNotEmpty($samples, 'The registry must define runnable packaged ODT samples.');

        foreach ($samples as $sample) {
            $sampleName = pathinfo($sample['entry_point'], PATHINFO_FILENAME);
            $sampleFile = $this->temporaryDirectory . '/' . $sample['entry_point'];
            $expectedOutput = $this->temporaryDirectory . '/' . $sample['output_path'];

            [$exitCode, $stdout, $stderr] = $this->runSample($sampleFile);

            self::assertSame('', trim($stderr), $sampleName . ' emitted stderr: ' . $stderr);
            self::assertSame(0, $exitCode, $sampleName . ' failed. Output: ' . $stdout);
            self::assertFileExists($expectedOutput, $sampleName . ' did not create its canonical output.');

            $archive = new ZipArchive();
            self::assertSame(
                true,
                $archive->open($expectedOutput) === true,
                $sampleName . ' did not create a valid ZIP/ODT archive.'
            );
            self::assertNotFalse($archive->locateName('content.xml'), $sampleName . ' is missing content.xml.');
            self::assertNotFalse($archive->locateName('styles.xml'), $sampleName . ' is missing styles.xml.');
            if (in_array($sample['id'], ['L01', 'L02', 'L03'], true)) {
                $content = $archive->getFromName('content.xml');
                self::assertIsString($content);
                self::assertStringNotContainsString('{{', $content, $sample['id'] . ' left template expressions unresolved.');

                if ($sample['id'] === 'L01') {
                    foreach ([
                        'Anna Beispiel',
                        'ANNA BEISPIEL',
                        'anna@example.com',
                        '15.08.1995',
                        '1.345,50',
                        '1.345,50 €',
                        'Thank you for your order.',
                        'Your receipt is attached.',
                    ] as $expectedText) {
                        self::assertStringContainsString($expectedText, $content, 'L01 omitted ' . $expectedText);
                    }
                    self::assertStringContainsString('<text:line-break', $content);
                } elseif ($sample['id'] === 'L02') {
                    self::assertStringContainsString('Priority member · benefits are active.', $content);
                    self::assertStringNotContainsString('Standard membership.', $content);
                    self::assertStringContainsString('Paid in full. Thank you for your payment.', $content);
                    self::assertStringContainsString('No additional review is required.', $content);
                    self::assertStringNotContainsString('Payment is due.', $content);
                    self::assertStringNotContainsString('credit balance.', $content);
                } else {
                    foreach (['Notebook', 'Fountain pen', 'Ink bottle'] as $item) {
                        self::assertSame(1, substr_count($content, $item), 'L03 did not render exactly one ' . $item . ' item.');
                    }
                    foreach (['PAP-01', 'WR-14', 'INK-03'] as $sku) {
                        self::assertStringContainsString($sku, $content);
                    }
                    self::assertStringNotContainsString('{{#foreach:items}}', $content);
                    self::assertStringNotContainsString('{{#endforeach}}', $content);
                }
            }
            if ($sample['id'] === 'L04') {
                $content = $archive->getFromName('content.xml');
                self::assertIsString($content);
                self::assertStringNotContainsString('{{', $content, 'L04 left its insertion marker unresolved.');
                $xpath = $this->contentXPath($content);
                self::assertCount(4, $xpath->query('//text:p[contains(., "Project:") or contains(., "Status:") or contains(., "Summary:") or contains(., "More information:")]'));
                self::assertCount(1, $xpath->query('//text:a[@xlink:href="https://example.com/aurora"]'));
                self::assertCount(1, $xpath->query('//text:line-break'));
                self::assertGreaterThanOrEqual(1, $xpath->query('//text:span[@text:style-name]')->length);
            }
            if ($sample['id'] === 'L05') {
                $content = $archive->getFromName('content.xml');
                self::assertIsString($content);
                self::assertStringNotContainsString('{{', $content, 'L05 left its insertion marker unresolved.');
                $xpath = $this->contentXPath($content);
                self::assertGreaterThanOrEqual(5, $xpath->query('//text:list')->length);
                self::assertGreaterThan(3, $xpath->query('//text:list-item')->length);
                self::assertGreaterThan(0, $xpath->query('//text:list[text:list-item/text:list]')->length);
                self::assertGreaterThan(0, $xpath->query('//text:list[@text:style-name="Numbering_20_Symbol"]')->length);
                self::assertGreaterThan(0, $xpath->query('//text:list[@text:style-name="Bullet_20_Symbol"][parent::text:list-item]')->length);
                self::assertStringContainsString('Prepare the project', $content);
                self::assertStringContainsString('Create the template', $content);
                self::assertStringNotContainsString('•', $content, 'L05 must use native lists, not typed bullet characters.');
                self::assertStringNotContainsString('1. Prepare the project', $content, 'L05 must use native numbering, not typed numbers.');
            }
            if ($sample['id'] === 'L06') {
                $content = $archive->getFromName('content.xml');
                $manifest = $archive->getFromName('META-INF/manifest.xml');
                self::assertIsString($content);
                self::assertIsString($manifest);
                self::assertStringNotContainsString('{{', $content, 'L06 left its insertion marker unresolved.');
                $xpath = $this->contentXPath($content);
                self::assertCount(1, $xpath->query('//draw:frame[@draw:name="LearnTemplatePosition"]/draw:image[@xlink:href="Pictures/Logo.png"]'));
                self::assertCount(1, $xpath->query('//draw:frame[draw:image[@xlink:href="Pictures/banner.png"]]'));
                self::assertCount(1, $xpath->query('//draw:frame[@draw:name="LearnTemplatePosition"][@svg:width="4.5cm"][@svg:height="2.4cm"]'));
                self::assertStringContainsString('Pictures/Logo.png', $manifest);
                self::assertStringContainsString('Pictures/banner.png', $manifest);
                self::assertNotFalse($archive->locateName('Pictures/Logo.png'));
                self::assertNotFalse($archive->locateName('Pictures/banner.png'));
            }
            if ($sample['id'] === 'L07') {
                $content = $archive->getFromName('content.xml');
                self::assertIsString($content);
                self::assertStringNotContainsString('{{', $content, 'L07 left its insertion marker unresolved.');
                $xpath = $this->contentXPath($content);
                self::assertCount(1, $xpath->query('//table:table'));
                self::assertCount(4, $xpath->query('//table:table/table:table-header-rows/table:table-row | //table:table/table:table-row'));
                self::assertCount(3, $xpath->query('//table:table-header-rows/table:table-row/table:table-cell'));
                self::assertStringContainsString('Requirements', $content);
                self::assertStringContainsString('Scope and stakeholders', $content);
                self::assertStringContainsString('In progress', $content);
                self::assertGreaterThanOrEqual(1, $xpath->query('//table:table-cell[@table:style-name]')->length);
            }
            if ($sample['id'] === 'L08') {
                $content = $archive->getFromName('content.xml');
                $manifest = $archive->getFromName('META-INF/manifest.xml');
                self::assertIsString($content);
                self::assertIsString($manifest);
                self::assertStringNotContainsString('{{', $content, 'L08 left its insertion marker unresolved.');
                $xpath = $this->contentXPath($content);
                self::assertGreaterThanOrEqual(2, $xpath->query('//text:p')->length);
                self::assertGreaterThanOrEqual(2, $xpath->query('//text:p[@text:style-name="Heading 1" or @text:style-name="Heading 2"]')->length);
                self::assertGreaterThan(0, $xpath->query('//text:span[@text:style-name]')->length);
                self::assertCount(1, $xpath->query('//text:a[@xlink:href="https://example.com/aurora"]'));
                self::assertGreaterThan(0, $xpath->query('//text:line-break')->length);
                self::assertGreaterThanOrEqual(2, $xpath->query('//text:list')->length);
                self::assertGreaterThan(0, $xpath->query('//text:list[@text:style-name="Numbering_20_Symbol"]')->length);
                self::assertGreaterThan(0, $xpath->query('//text:list[@text:style-name="Bullet_20_Symbol"]')->length);
                self::assertCount(1, $xpath->query('//table:table'));
                self::assertGreaterThanOrEqual(3, $xpath->query('//table:table-row')->length);
                self::assertGreaterThanOrEqual(9, $xpath->query('//table:table-cell')->length);
                self::assertStringContainsString('Contributor', $content);
                self::assertStringContainsString('Implementation notes:', $content);
                self::assertCount(2, $xpath->query('//draw:frame/draw:image'));

                foreach ($xpath->query('//draw:frame/draw:image/@xlink:href') as $href) {
                    $resource = (string) $href->nodeValue;
                    self::assertNotFalse($archive->locateName($resource), 'L08 image resource is missing: ' . $resource);
                    self::assertStringContainsString(basename($resource), $manifest);
                }
            }
            if ($sample['id'] === 'L09') {
                $content = $archive->getFromName('content.xml');
                self::assertIsString($content);
                self::assertStringNotContainsString('{{', $content, 'L09 left a template insertion point unresolved.');
                $xpath = $this->contentXPath($content);
                self::assertCount(1, $xpath->query('//text:bookmark-start[@text:name="ClientName"]'));
                self::assertCount(1, $xpath->query('//text:bookmark-end[@text:name="ClientName"]'));
                self::assertCount(1, $xpath->query('//text:section[@text:name="ProjectSummary"]'));
                self::assertCount(1, $xpath->query('//table:table[@table:name="ProjectMilestones"]'));
                self::assertCount(1, $xpath->query('//draw:frame[@draw:name="ProjectNote"]'));
                self::assertStringContainsString('Aurora Studio', $content);
                self::assertStringContainsString('Project summary supplied by PHP.', $content);
            }
            if ($sample['id'] === 'L10') {
                $content = $archive->getFromName('content.xml');
                $styles = $archive->getFromName('styles.xml');
                self::assertIsString($content);
                self::assertIsString($styles);
                self::assertStringContainsString('office:string-value="Aurora Studio"', $content);
                self::assertStringContainsString('office:string-value="Aurora Studio"', $styles);
                self::assertStringContainsString('text:user-field-get', $content);
                self::assertStringContainsString('text:user-field-get', $styles);
            }
            if ($sample['id'] === 'L12') {
                $content = $archive->getFromName('content.xml');
                self::assertIsString($content);
                self::assertStringNotContainsString('{{', $content, 'L12 left a template expression unresolved.');
                $xpath = $this->contentXPath($content);
                self::assertCount(1, $xpath->query('//table:table[@table:name="L12_ResultTable"]'));
                self::assertCount(1, $xpath->query('//table:table[@table:name="L12_ResultTable"]/table:table-header-rows/table:table-row'));
                self::assertCount(6, $xpath->query('//table:table[@table:name="L12_ResultTable"]//table:table-row'));
                self::assertStringContainsString('KEEP · Writer source row 0', $content);
                self::assertStringContainsString('Participant outcomes', $content);
                self::assertStringContainsString('Further training', $content);
                self::assertGreaterThanOrEqual(1, $xpath->query('//table:table[@table:name="L12_ResultTable"]//table:table-cell[@table:style-name]')->length);
                self::assertGreaterThanOrEqual(1, $xpath->query('//table:table[@table:name="L12_ResultTable"]//text:span[@text:style-name]')->length);
            }
            if ($sample['id'] === 'C01') {
                $content = $archive->getFromName('content.xml');
                $styles = $archive->getFromName('styles.xml');
                self::assertIsString($content);
                self::assertIsString($styles);
                self::assertStringNotContainsString('{{', $content, 'C01 left a template expression unresolved.');
                $xpath = $this->contentXPath($content);
                $flowStyles = $content . $styles;
                foreach (['keep-with-next', 'keep-together', 'widows', 'orphans', 'break-before', 'break-after'] as $property) {
                    self::assertStringContainsString('fo:' . $property . '=', $flowStyles, 'C01 did not materialize ' . $property . '.');
                }
                self::assertStringContainsString('style:name="First Page"', $styles);
                self::assertStringContainsString('style:next-style-name="Standard"', $styles);
                self::assertStringContainsString('style:master-page-name="First Page"', $content);
                self::assertStringContainsString('text:page-number', $styles);
                self::assertGreaterThanOrEqual(2, $xpath->query('//text:p')->length);
            }
            if ($sample['id'] === 'C02') {
                $content = $archive->getFromName('content.xml');
                $styles = $archive->getFromName('styles.xml');
                self::assertIsString($content);
                self::assertIsString($styles);
                self::assertStringNotContainsString('{{', $content, 'C02 left an insertion expression unresolved.');
                $xpath = $this->contentXPath($content);
                self::assertCount(2, $xpath->query('//table:table'));
                $tableStyles = $content . $styles;
                self::assertStringContainsString('style:width="16cm"', $tableStyles);
                self::assertStringContainsString('style:rel-width="82%"', $tableStyles);
                self::assertStringContainsString('style:row-height="0.95cm"', $tableStyles);
                self::assertStringContainsString('style:min-row-height="1.0cm"', $tableStyles);
                self::assertStringContainsString('style:vertical-align="middle"', $tableStyles);
                self::assertStringContainsString('€ 24,500', $content);
            }
            if ($sample['id'] === 'C03') {
                $content = $archive->getFromName('content.xml');
                $manifest = $archive->getFromName('META-INF/manifest.xml');
                self::assertIsString($content);
                self::assertIsString($manifest);
                self::assertStringNotContainsString('{{', $content, 'C03 left an insertion expression unresolved.');
                $xpath = $this->contentXPath($content);
                self::assertGreaterThanOrEqual(4, $xpath->query('//draw:frame')->length);
                self::assertGreaterThanOrEqual(2, $xpath->query('//draw:image')->length);
                self::assertStringContainsString('draw:name="DeliveryCallout"', $content);
                self::assertStringContainsString('Pictures/Logo.png', $manifest);
                self::assertNotFalse($archive->locateName('Pictures/Logo.png'));
            }
            if ($sample['id'] === 'B02') {
                $content = $archive->getFromName('content.xml');
                $styles = $archive->getFromName('styles.xml');
                $manifest = $archive->getFromName('META-INF/manifest.xml');
                self::assertIsString($content);
                self::assertIsString($styles);
                self::assertIsString($manifest);
                self::assertStringNotContainsString('{{', $content, 'B02 left a template expression unresolved.');
                self::assertStringContainsString('Annual Performance Report 2026', $content);
                self::assertStringContainsString('3 program locations', $content);
                self::assertStringContainsString('1,146', $content);
                self::assertStringContainsString('Employer engagement proved most effective', $content);
                self::assertStringContainsString('fictional', $content);
                self::assertGreaterThanOrEqual(5, $this->contentXPath($content)->query('//table:table')->length);
                self::assertGreaterThanOrEqual(2, $this->contentXPath($content)->query('//draw:frame/draw:image')->length);
                self::assertGreaterThanOrEqual(2, $this->contentXPath($content)->query('//draw:frame/draw:text-box')->length);
                self::assertStringContainsString('Pictures/asteria-b02-cover.png', $manifest);
                self::assertStringContainsString('Pictures/asteria-b02-outcomes.png', $manifest);
                self::assertNotFalse($archive->locateName('Pictures/asteria-b02-cover.png'));
                self::assertNotFalse($archive->locateName('Pictures/asteria-b02-outcomes.png'));
                self::assertStringContainsString('text:page-number', $styles);
            }
            if ($sample['id'] === 'C04') {
                $content = $archive->getFromName('content.xml');
                $styles = $archive->getFromName('styles.xml');
                self::assertIsString($content);
                self::assertIsString($styles);
                self::assertStringNotContainsString('text:name="#foreach:projects"', $content);
                self::assertStringNotContainsString('text:name="#foreach:milestones"', $content);
                self::assertStringContainsString('text:name="#foreach:projects_1"', $content);
                self::assertStringContainsString('text:name="#foreach:projects_2"', $content);
                self::assertStringContainsString('text:name="#foreach:milestones_1_1"', $content);
                self::assertStringContainsString('text:name="#foreach:milestones_1_2"', $content);
                self::assertStringContainsString('text:name="#foreach:milestones_2_1"', $content);
                self::assertStringContainsString('Aurora', $content);
                self::assertStringContainsString('Beacon', $content);
                self::assertStringContainsString('Status: ON TRACK', $content);
                self::assertStringContainsString('Status: PLANNED', $content);
                self::assertStringContainsString('FEATURED PROJECT', $content);
                self::assertStringContainsString('Current roadmap', $content);
                self::assertStringNotContainsString('FEATURED PROJECT', substr($content, strpos($content, 'Beacon')));
                self::assertStringNotContainsString('Current roadmap', substr($content, strpos($content, 'Beacon')));
                self::assertLessThan(strpos($content, 'Beacon'), strpos($content, 'Aurora'));
                self::assertLessThan(strpos($content, 'Template review'), strpos($content, 'Discovery complete'));
                self::assertStringContainsString('style-name="Heading_20_2"', $content);
                self::assertStringContainsString('Body_20_Text.foot', $content);
                self::assertStringContainsString('style:name="Heading_20_2"', $styles);
                self::assertStringNotContainsString('{{', $content);
            }
            if ($sample['id'] === 'C05') {
                $content = $archive->getFromName('content.xml');
                $metadata = $archive->getFromName('meta.xml');
                $manifest = $archive->getFromName('META-INF/manifest.xml');
                self::assertIsString($content);
                self::assertIsString($metadata);
                self::assertIsString($manifest);
                self::assertStringContainsString('Northstar Studio', $content);
                self::assertStringContainsString('Template modernization', $content);
                self::assertStringContainsString('Anna Example', $content);
                self::assertStringContainsString('Ben Example', $content);
                self::assertStringContainsString('authored report structure in Writer', $content);
                self::assertStringNotContainsString('#foreach:members"', $content);
                self::assertStringContainsString('dc:creator', $metadata);
                self::assertStringContainsString('Northstar Delivery Team', $metadata);
                self::assertStringContainsString('Pictures/Logo.png', $manifest);
                self::assertNotFalse($archive->locateName('Pictures/Logo.png'));
            }
            if ($sample['id'] === 'S01b') {
                $content = $archive->getFromName('content.xml');
                $styles = $archive->getFromName('styles.xml');
                $manifest = $archive->getFromName('META-INF/manifest.xml');
                self::assertIsString($content);
                self::assertIsString($styles);
                self::assertIsString($manifest);
                self::assertStringNotContainsString('{{', $content, 'S01b left a template expression unresolved.');
                self::assertSame(4, substr_count($content, 'text:name="JobSection_'));
                self::assertSame(9, substr_count($content, 'text:name="ActivitySection_'));
                self::assertSame(2, substr_count($content, 'text:name="EducationSection_'));
                self::assertSame(3, substr_count($content, 'text:name="QualificationSection_'));
                foreach (['Andrew', 'Thompson', 'Senior Project Manager', 'Harbour Digital', 'University of Sydney', 'Volunteer work'] as $expected) {
                    self::assertStringContainsString($expected, $content, 'S01b omitted ' . $expected . '.');
                }
                self::assertStringContainsString('PROFILE', $content);
                self::assertStringContainsString('Senior Project Manager with 10+ years of delivery leadership', $content);
                self::assertStringContainsString('Experienced project manager specialising in agile transformation', $content);
                self::assertStringNotContainsString('Erfahrener Projektmanager mit über 10 Jahren', $content);
                self::assertStringContainsString('S01bSidebarHeading', $styles);
                self::assertStringContainsString('S01bSidebarLine', $styles);
                self::assertStringContainsString('S01bSidebarSkill', $styles);
                self::assertSame(3, substr_count($styles, 'text:tab'));
                foreach (['JobSection"', 'ActivitySection"', 'EducationSection"', 'QualificationSection"'] as $prototype) {
                    self::assertStringNotContainsString('text:name="' . $prototype, $content, 'S01b retained prototype ' . $prototype . '.');
                }
                self::assertStringContainsString('draw:name="CVImage"', $content);
                self::assertStringContainsString('svg:width="4.001cm"', $content);
                self::assertStringContainsString('svg:height="3.799cm"', $content);
                self::assertStringContainsString('Pictures/BFoto.png', $content);
                self::assertStringContainsString('Pictures/BFoto.png', $manifest);
                self::assertStringContainsString('ANDREW THOMPSON', $styles);
                self::assertStringContainsString('First Page', $styles);
                self::assertStringContainsString('CV Continuation', $styles);
                self::assertStringContainsString('style:next-style-name="CV_20_Continuation"', $styles);
                self::assertStringContainsString('style:display-name="CV Continuation"', $styles);
            }
            $archive->close();
        }

        $inspectionSample = null;
        foreach ($registry['samples'] as $sample) {
            if ($sample['id'] === 'L11') {
                $inspectionSample = $sample;
                break;
            }
        }
        self::assertIsArray($inspectionSample, 'L11 must be present as an inspection-only public sample.');
        [$inspectionExit, $inspectionOutput, $inspectionError] = $this->runSample(
            $this->temporaryDirectory . '/' . $inspectionSample['entry_point']
        );
        self::assertSame('', trim($inspectionError), 'L11 emitted stderr: ' . $inspectionError);
        self::assertSame(0, $inspectionExit, 'L11 inspection sample failed: ' . $inspectionOutput);
        foreach (["\"bindings\"", "\"controls\"", "\"native_objects\"", "\"dependencies\"", "\"capabilities\"", "\"diagnostics\""] as $key) {
            self::assertStringContainsString($key, $inspectionOutput);
        }
        self::assertFileDoesNotExist($this->temporaryDirectory . '/samples/output/output_L11_template_inspection.odt');

        self::assertSame($beforeOutput, $this->directorySnapshot($repositoryRoot . '/samples/output'));
    }

    public function testPackagedSampleRunsFromComposerConsumerLayout(): void
    {
        $repositoryRoot = dirname(__DIR__, 2);
        $packageRoot = $this->temporaryDirectory
            . '/vendor/waltdietzney/odt-template-engine';

        $this->copyDirectory($repositoryRoot . '/samples', $packageRoot . '/samples', [
            'output',
        ]);
        mkdir($packageRoot . '/samples/output', 0755, true);

        [$exitCode, $stdout, $stderr] = $this->runSample(
            $packageRoot . '/samples/sample_L01_variables_filters.php'
        );

        self::assertSame('', trim($stderr), 'Packaged L01 emitted stderr: ' . $stderr);
        self::assertSame(0, $exitCode, 'Packaged L01 failed. Output: ' . $stdout);
        self::assertFileExists(
            $packageRoot . '/samples/output/output_L01_variables_filters.odt'
        );
    }

    /**
     * @return array{0: int, 1: string, 2: string}
     */
    private function runSample(string $sampleFile): array
    {
        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open(
            [PHP_BINARY, $sampleFile],
            $descriptors,
            $pipes,
            $this->temporaryDirectory . '/caller'
        );

        self::assertIsResource($process);
        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);

        return [$exitCode, $stdout, $stderr];
    }

    /**
     * @param list<string> $excludedDirectories
     */
    private function copyDirectory(string $source, string $destination, array $excludedDirectories = []): void
    {
        mkdir($destination, 0755, true);

        $directory = new \RecursiveDirectoryIterator($source, \FilesystemIterator::SKIP_DOTS);
        $iterator = new \RecursiveIteratorIterator($directory, \RecursiveIteratorIterator::SELF_FIRST);

        foreach ($iterator as $item) {
            $relativePath = substr($item->getPathname(), strlen($source) + 1);
            $firstSegment = explode(DIRECTORY_SEPARATOR, $relativePath, 2)[0];
            if (in_array($firstSegment, $excludedDirectories, true)) {
                continue;
            }

            $target = $destination . '/' . $relativePath;
            if ($item->isDir()) {
                if (!is_dir($target)) {
                    mkdir($target, 0755, true);
                }
                continue;
            }

            if (!is_dir(dirname($target))) {
                mkdir(dirname($target), 0755, true);
            }
            copy($item->getPathname(), $target);
        }
    }

    /**
     * @return array<string, string>
     */
    private function directorySnapshot(string $directory): array
    {
        $snapshot = [];
        foreach (glob($directory . '/*') ?: [] as $path) {
            if (is_file($path)) {
                $snapshot[basename($path)] = hash_file('sha256', $path);
            }
        }
        ksort($snapshot);

        return $snapshot;
    }

    private function contentXPath(string $content): \DOMXPath
    {
        $dom = new \DOMDocument();
        self::assertTrue($dom->loadXML($content));
        $xpath = new \DOMXPath($dom);
        $namespaces = [
            'office' => 'urn:oasis:names:tc:opendocument:xmlns:office:1.0',
            'style' => 'urn:oasis:names:tc:opendocument:xmlns:style:1.0',
            'fo' => 'urn:oasis:names:tc:opendocument:xmlns:xsl-fo-compatible:1.0',
            'text' => 'urn:oasis:names:tc:opendocument:xmlns:text:1.0',
            'draw' => 'urn:oasis:names:tc:opendocument:xmlns:drawing:1.0',
            'xlink' => 'http://www.w3.org/1999/xlink',
            'svg' => 'urn:oasis:names:tc:opendocument:xmlns:svg-compatible:1.0',
            'table' => 'urn:oasis:names:tc:opendocument:xmlns:table:1.0',
        ];
        foreach ($namespaces as $prefix => $namespace) {
            $xpath->registerNamespace($prefix, $namespace);
        }

        return $xpath;
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
                continue;
            }

            unlink($item->getPathname());
        }
        rmdir($directory);
    }
}
