<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Integration;

use OdtTemplateEngine\Elements\ImageElement;
use OdtTemplateEngine\Elements\RichText;
use OdtTemplateEngine\OdtTemplate;
use PHPUnit\Framework\TestCase;
use ZipArchive;

final class StructuredImageResourceArch05HTest extends TestCase
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

    public function testStructuredImageResourcePreparationDoesNotUseNamedImageReplacement(): void
    {
        $template = new StructuredImageResourceInspectableTemplate(
            $this->templatePath('template_18_ListStyles.odt')
        );
        $template->setElement(
            'my_list',
            (new RichText())->addImage(new ImageElement($this->imagePath()))
        );

        $output = sys_get_temp_dir() . '/odt-arch05h-' . uniqid('', true) . '.odt';
        $this->outputFiles[] = $output;
        $template->save($output);

        $zip = new ZipArchive();
        self::assertTrue($zip->open($output) === true);

        try {
            self::assertNotFalse($zip->locateName('Pictures/WaltDietzney.png'));
            $manifest = $zip->getFromName('META-INF/manifest.xml');
            self::assertIsString($manifest);
            self::assertStringContainsString('Pictures/WaltDietzney.png', $manifest);
        } finally {
            $zip->close();
        }
    }

    private function templatePath(string $fileName): string
    {
        $path = dirname(__DIR__, 2) . '/samples/templates/' . $fileName;
        self::assertFileExists($path);

        return $path;
    }

    private function imagePath(): string
    {
        $path = dirname(__DIR__, 2) . '/assets/WaltDietzney.png';
        self::assertFileExists($path);

        return $path;
    }
}

final class StructuredImageResourceInspectableTemplate extends OdtTemplate
{
    public function replaceImageByName(string $name, string $imagePath, array $options = []): void
    {
        throw new \LogicException('Structured image insertion must not use named image replacement.');
    }
}
