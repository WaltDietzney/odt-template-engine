<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Integration;

use DOMDocument;
use OdtTemplateEngine\OdtPackage;
use PHPUnit\Framework\TestCase;
use ZipArchive;

final class OdtPackageTest extends TestCase
{
    /** @var list<string> */
    private array $outputFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->outputFiles as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }

    public function testPackageOwnsIsolatedWorkspaceAndCoreDocuments(): void
    {
        $templatePath = $this->templatePath('template_01_simple_variables.odt');

        $first = new OdtPackage($templatePath);
        $second = new OdtPackage($templatePath);

        try {
            self::assertNotSame($first->workspacePath(), $second->workspacePath());
            self::assertDirectoryExists($first->workspacePath());
            self::assertDirectoryExists($second->workspacePath());

            self::assertInstanceOf(DOMDocument::class, $first->contentDom());
            self::assertInstanceOf(DOMDocument::class, $first->stylesDom());
            self::assertInstanceOf(DOMDocument::class, $first->metaDom());

            self::assertFileExists($first->path('content.xml'));
            self::assertFileExists($first->path('styles.xml'));
            self::assertFileExists($first->path('meta.xml'));
            self::assertFileExists($first->path('META-INF/manifest.xml'));
            self::assertFileExists($first->path('mimetype'));
        } finally {
            $first->cleanup();
            $second->cleanup();
        }
    }

    public function testPackageCanPersistDomChangesAndReopenThem(): void
    {
        $package = new OdtPackage($this->templatePath('template_01_simple_variables.odt'));
        $output = $this->newOutputPath('roundtrip');

        try {
            $content = $package->contentDom();
            $paragraph = $content->createElement('text:p');
            $paragraph->appendChild($content->createTextNode('ARCH-02 package roundtrip'));

            $officeText = $content->getElementsByTagName('office:text')->item(0);
            self::assertNotNull($officeText);
            $officeText->appendChild($paragraph);

            $package->saveAs($output);
        } finally {
            $package->cleanup();
        }

        $reopened = new OdtPackage($output);

        try {
            self::assertStringContainsString(
                'ARCH-02 package roundtrip',
                $reopened->contentDom()->saveXML() ?: ''
            );
        } finally {
            $reopened->cleanup();
        }
    }

    public function testSaveCreatesExpectedOdtPackageAndStoredMimetype(): void
    {
        $package = new OdtPackage($this->templatePath('template_01_simple_variables.odt'));
        $output = $this->newOutputPath('package');

        try {
            $package->saveAs($output);
        } finally {
            $package->cleanup();
        }

        self::assertFileExists($output);

        $zip = new ZipArchive();
        self::assertTrue($zip->open($output) === true);

        try {
            foreach (['mimetype', 'content.xml', 'styles.xml', 'meta.xml', 'META-INF/manifest.xml'] as $entry) {
                self::assertNotFalse($zip->locateName($entry), sprintf('Missing ODT package entry: %s', $entry));
            }

            self::assertSame(
                'application/vnd.oasis.opendocument.text',
                $zip->getFromName('mimetype')
            );

            $mimetypeStat = $zip->statName('mimetype');
            self::assertIsArray($mimetypeStat);
            self::assertSame(ZipArchive::CM_STORE, $mimetypeStat['comp_method'] ?? null);
        } finally {
            $zip->close();
        }
    }

    public function testImageManifestSynchronizationIsPackageScopedAndIdempotent(): void
    {
        $package = new OdtPackage($this->templatePath('template_06_imageSettings.odt'));
        $output = $this->newOutputPath('manifest');
        $sourceImage = dirname(__DIR__, 2) . '/assets/banner.png';

        self::assertFileExists($sourceImage);

        try {
            $picturesPath = $package->path('Pictures');
            if (!is_dir($picturesPath)) {
                self::assertTrue(mkdir($picturesPath));
            }

            self::assertTrue(copy($sourceImage, $picturesPath . '/arch02-banner.png'));

            $package->synchronizeImageManifest();
            $package->synchronizeImageManifest();
            $package->saveAs($output);

            $manifest = file_get_contents($package->path('META-INF/manifest.xml'));
            self::assertIsString($manifest);
            self::assertSame(1, substr_count($manifest, 'Pictures/arch02-banner.png'));
            self::assertStringContainsString('image/png', $manifest);
        } finally {
            $package->cleanup();
        }

        $zip = new ZipArchive();
        self::assertTrue($zip->open($output) === true);

        try {
            self::assertNotFalse($zip->locateName('Pictures/arch02-banner.png'));
            $manifest = $zip->getFromName('META-INF/manifest.xml');
            self::assertIsString($manifest);
            self::assertSame(1, substr_count($manifest, 'Pictures/arch02-banner.png'));
        } finally {
            $zip->close();
        }
    }

    public function testCleanupIsIdempotentAndDoesNotDeleteOtherPackageWorkspace(): void
    {
        $templatePath = $this->templatePath('template_01_simple_variables.odt');
        $first = new OdtPackage($templatePath);
        $second = new OdtPackage($templatePath);

        $firstWorkspace = $first->workspacePath();
        $secondWorkspace = $second->workspacePath();

        $first->cleanup();
        $first->cleanup();

        self::assertDirectoryDoesNotExist($firstWorkspace);
        self::assertDirectoryExists($secondWorkspace);
        self::assertFileExists($second->path('content.xml'));

        $second->cleanup();
        self::assertDirectoryDoesNotExist($secondWorkspace);
    }

    private function templatePath(string $fileName): string
    {
        $path = dirname(__DIR__, 2) . '/samples/templates/' . $fileName;
        self::assertFileExists($path);

        return $path;
    }

    private function newOutputPath(string $suffix): string
    {
        $path = sys_get_temp_dir() . '/odt-package-' . $suffix . '-' . uniqid('', true) . '.odt';
        $this->outputFiles[] = $path;

        return $path;
    }
}
