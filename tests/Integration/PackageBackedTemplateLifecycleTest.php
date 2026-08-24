<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Integration;

use OdtTemplateEngine\Internal\PackageBackedOdtTemplate;
use PHPUnit\Framework\TestCase;
use ZipArchive;

final class PackageBackedTemplateLifecycleTest extends TestCase
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

    public function testPackageBackedLifecycleSupportsNormalRenderAndSaveFlow(): void
    {
        $template = new PackageBackedOdtTemplate(
            $this->templatePath('template_01_simple_variables.odt')
        );
        $output = $this->newOutputPath('render');

        $template->assign([
            'name' => 'ARCH-02 Adapter',
            'datum' => '2026-08-24',
        ]);
        $template->assignRepeating('items', [
            ['produkt' => 'Coffee', 'preis' => '4.99'],
            ['produkt' => 'Tea', 'preis' => '3.49'],
        ]);
        $template->render();
        $workspace = $template->package()->workspacePath();
        $template->save($output);
        $template->cleanup();

        self::assertDirectoryDoesNotExist($workspace);
        self::assertFileExists($output);

        $this->withArchive($output, function (ZipArchive $zip): void {
            $content = $zip->getFromName('content.xml');
            self::assertIsString($content);
            self::assertStringContainsString('ARCH-02 Adapter', $content);
            self::assertStringContainsString('Coffee', $content);
            self::assertStringContainsString('Tea', $content);
            self::assertStringNotContainsString('{{name}}', $content);
        });
    }

    public function testPackageBackedLifecyclePersistsMetadata(): void
    {
        $template = new PackageBackedOdtTemplate(
            $this->templatePath('template_04_metadata.odt')
        );
        $output = $this->newOutputPath('metadata');

        $template->setMeta([
            'title' => 'ARCH-02 Metadata',
            'author' => 'Package Context Test',
            'language' => 'en',
        ]);
        $template->save($output);
        $template->cleanup();

        $this->withArchive($output, function (ZipArchive $zip): void {
            $meta = $zip->getFromName('meta.xml');
            self::assertIsString($meta);
            self::assertStringContainsString('ARCH-02 Metadata', $meta);
            self::assertStringContainsString('Package Context Test', $meta);
        });

        $reloaded = new PackageBackedOdtTemplate($output);
        try {
            $metadata = $reloaded->getMeta();
            self::assertSame('ARCH-02 Metadata', $metadata['title'] ?? null);
            self::assertSame('Package Context Test', $metadata['author'] ?? null);
        } finally {
            $reloaded->cleanup();
        }
    }

    public function testPackageBackedLifecycleKeepsImageAndManifestBehavior(): void
    {
        $template = new PackageBackedOdtTemplate(
            $this->templatePath('template_06_imageSettings.odt')
        );
        $output = $this->newOutputPath('image');
        $image = dirname(__DIR__, 2) . '/assets/banner.png';

        self::assertFileExists($image);

        $template->setImage('image', $image, [
            'width' => '6cm',
            'anchor' => 'paragraph',
            'wrap' => 'none',
        ]);
        $template->save($output);
        $template->cleanup();

        $this->withArchive($output, function (ZipArchive $zip): void {
            self::assertNotFalse($zip->locateName('Pictures/banner.png'));

            $content = $zip->getFromName('content.xml');
            $manifest = $zip->getFromName('META-INF/manifest.xml');
            self::assertIsString($content);
            self::assertIsString($manifest);
            self::assertStringContainsString('Pictures/banner.png', $content);
            self::assertStringContainsString('Pictures/banner.png', $manifest);
            self::assertStringContainsString('image/png', $manifest);
        });
    }

    private function templatePath(string $fileName): string
    {
        $path = dirname(__DIR__, 2) . '/samples/templates/' . $fileName;
        self::assertFileExists($path);

        return $path;
    }

    private function newOutputPath(string $suffix): string
    {
        $path = sys_get_temp_dir() . '/odt-package-adapter-' . $suffix . '-' . uniqid('', true) . '.odt';
        $this->outputFiles[] = $path;

        return $path;
    }

    private function withArchive(string $path, callable $callback): void
    {
        self::assertFileExists($path);

        $zip = new ZipArchive();
        self::assertTrue($zip->open($path) === true);

        try {
            $callback($zip);
        } finally {
            $zip->close();
        }
    }
}
