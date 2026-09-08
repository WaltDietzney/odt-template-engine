<?php

declare(strict_types=1);

namespace OdtTemplateEngineTests\Integration;

use OdtTemplateEngine\OdtTemplate;
use OdtTemplateEngine\Utils\StyleWriter;
use PHPUnit\Framework\TestCase;
use ZipArchive;

final class OdtTemplateProtectedStyleRegistrationTest extends TestCase
{
    /** @var list<string> */
    private array $outputs = [];

    protected function tearDown(): void
    {
        foreach ($this->outputs as $output) {
            if (is_file($output)) {
                unlink($output);
            }
        }
    }

    public function testProtectedStyleRegistrationRemainsFunctionalWithoutStyleWriterHelpers(): void
    {
        $template = new ProtectedStyleRegistrationTemplate($this->templatePath());
        $definitions = [
            'compat-text' => [
                'family' => 'text',
                'properties' => ['fo:font-weight' => 'bold'],
            ],
            'compat-paragraph' => [
                'family' => 'paragraph',
                'properties' => ['fo:margin-top' => '0.2cm'],
            ],
        ];

        $template->registerStylesForTest($definitions);
        $template->registerStylesForTest($definitions);

        $output = sys_get_temp_dir() . '/odt-protected-style-' . bin2hex(random_bytes(5)) . '.odt';
        $this->outputs[] = $output;
        $template->save($output);

        $styles = $this->entry($output, 'styles.xml');
        self::assertSame(1, substr_count($styles, 'style:name="compat-text"'));
        self::assertSame(1, substr_count($styles, 'style:name="compat-paragraph"'));
        self::assertStringContainsString(
            'style:family="text" style:parent-style-name="Standard"',
            $styles
        );
        self::assertStringContainsString('fo:font-weight="bold"', $styles);
        self::assertStringContainsString(
            'style:family="paragraph" style:parent-style-name="Standard"',
            $styles
        );
        self::assertStringContainsString('fo:margin-top="0.2cm"', $styles);

        self::assertFalse(method_exists(StyleWriter::class, 'styleAlreadyExists'));
        self::assertFalse(method_exists(StyleWriter::class, 'appendStyleToStylesXml'));
    }

    private function templatePath(): string
    {
        return dirname(__DIR__, 2) . '/samples/templates/sample_textfeld.odt';
    }

    private function entry(string $path, string $name): string
    {
        $zip = new ZipArchive();
        self::assertTrue($zip->open($path) === true);
        try {
            $entry = $zip->getFromName($name);
            self::assertIsString($entry);
            return $entry;
        } finally {
            $zip->close();
        }
    }
}

final class ProtectedStyleRegistrationTemplate extends OdtTemplate
{
    public function registerStylesForTest(array $styleDefinitions): void
    {
        $this->registerStyles($styleDefinitions);
    }
}
