<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Integration;

use OdtTemplateEngine\OdtTemplate;
use PHPUnit\Framework\TestCase;
use ZipArchive;

final class DocumentFinalizationArch03CTest extends TestCase
{
    /** @var list<string> */
    private array $outputFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->outputFiles as $outputFile) {
            if (is_file($outputFile)) {
                unlink($outputFile);
            }
        }
    }

    public function testSavePreservesFinalizationOrderAroundStyleWriting(): void
    {
        $template = new FinalizationProbeTemplate($this->templatePath('template_01_simple_variables.odt'));
        $outputFile = $this->newOutputPath('order');

        try {
            $template->save($outputFile);

            self::assertSame(['inject-image-styles', 'adjust-bullets'], $template->events);
            self::assertTrue($this->isOdtPackage($outputFile));
        } finally {
            $template->cleanup();
        }
    }

    public function testSaveWithoutRenderFinalizesDocumentStyles(): void
    {
        $template = new OdtTemplate($this->templatePath('template_18_ListStyles.odt'));
        $outputFile = $this->newOutputPath('without-render');

        try {
            $template->save($outputFile);

            $zip = new ZipArchive();
            self::assertTrue($zip->open($outputFile) === true);

            try {
                $contentXml = $zip->getFromName('content.xml');
                $stylesXml = $zip->getFromName('styles.xml');

                self::assertIsString($contentXml);
                self::assertIsString($stylesXml);
                self::assertStringContainsString('style:list-level-label-alignment', $stylesXml);
            } finally {
                $zip->close();
            }
        } finally {
            $template->cleanup();
        }
    }

    public function testRepeatedSaveProducesValidPackagesWithoutDuplicateFontFaceContainers(): void
    {
        $template = new OdtTemplate($this->templatePath('template_01_simple_variables.odt'));
        $firstOutput = $this->newOutputPath('repeat-first');
        $secondOutput = $this->newOutputPath('repeat-second');

        try {
            $template->save($firstOutput);
            $template->save($secondOutput);

            $firstStyles = $this->readZipEntry($firstOutput, 'styles.xml');
            $secondStyles = $this->readZipEntry($secondOutput, 'styles.xml');

            self::assertSame(
                1,
                substr_count($firstStyles, '<office:font-face-decls')
            );
            self::assertSame(
                1,
                substr_count($secondStyles, '<office:font-face-decls')
            );
        } finally {
            $template->cleanup();
        }
    }

    private function templatePath(string $fileName): string
    {
        $path = dirname(__DIR__, 2) . '/samples/templates/' . $fileName;
        self::assertFileExists($path);

        return $path;
    }

    private function newOutputPath(string $suffix): string
    {
        $path = sys_get_temp_dir() . '/odt-finalization-' . $suffix . '-' . uniqid('', true) . '.odt';
        $this->outputFiles[] = $path;

        return $path;
    }

    private function readZipEntry(string $path, string $entry): string
    {
        $zip = new ZipArchive();
        self::assertTrue($zip->open($path) === true);

        try {
            $contents = $zip->getFromName($entry);
            self::assertIsString($contents);

            return $contents;
        } finally {
            $zip->close();
        }
    }

    private function isOdtPackage(string $path): bool
    {
        $zip = new ZipArchive();
        self::assertTrue($zip->open($path) === true);

        try {
            return $zip->locateName('content.xml') !== false
                && $zip->locateName('styles.xml') !== false;
        } finally {
            $zip->close();
        }
    }
}

final class FinalizationProbeTemplate extends OdtTemplate
{
    /** @var list<string> */
    public array $events = [];

    protected function injectImageStyles(): void
    {
        $this->events[] = 'inject-image-styles';
        parent::injectImageStyles();
    }

    protected function adjustBulletIndentation(): void
    {
        $this->events[] = 'adjust-bullets';
        parent::adjustBulletIndentation();
    }
}
