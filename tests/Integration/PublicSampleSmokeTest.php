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
        $sampleFiles = $this->publicSampleFiles($this->temporaryDirectory . '/samples');
        $beforeOutput = $this->directorySnapshot($repositoryRoot . '/samples/output');

        self::assertCount(25, $sampleFiles);

        foreach ($sampleFiles as $sampleFile) {
            $sampleName = pathinfo($sampleFile, PATHINFO_FILENAME);
            $expectedOutput = $this->temporaryDirectory . '/samples/output/output_' . substr($sampleName, 7) . '.odt';

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
            $archive->close();
        }

        self::assertSame($beforeOutput, $this->directorySnapshot($repositoryRoot . '/samples/output'));
    }

    /**
     * @return list<string>
     */
    private function publicSampleFiles(string $sampleDirectory): array
    {
        $sampleFiles = [];

        foreach (glob($sampleDirectory . '/sample_*.php') ?: [] as $sampleFile) {
            $sampleName = basename($sampleFile, '.php');
            if (preg_match('/^sample_(\d{2})_/', $sampleName, $matches) !== 1) {
                continue;
            }

            $sampleNumber = (int) $matches[1];
            if ($sampleNumber >= 1 && $sampleNumber <= 25) {
                $sampleFiles[] = $sampleFile;
            }
        }

        sort($sampleFiles);

        return $sampleFiles;
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
