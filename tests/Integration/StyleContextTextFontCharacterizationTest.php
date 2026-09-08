<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Integration;

use OdtTemplateEngine\Elements\Paragraph;
use OdtTemplateEngine\Elements\RichText;
use OdtTemplateEngine\OdtTemplate;
use OdtTemplateEngine\Utils\StyleMapper;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;
use ZipArchive;

final class StyleContextTextFontCharacterizationTest extends TestCase
{
    private string $temporaryDirectory;

    protected function setUp(): void
    {
        $this->temporaryDirectory = sys_get_temp_dir() . '/odt-style-text-font-' . bin2hex(random_bytes(6));
        mkdir($this->temporaryDirectory);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->temporaryDirectory . '/*') ?: [] as $path) {
            @unlink($path);
        }
        @rmdir($this->temporaryDirectory);
    }

    #[RunInSeparateProcess]
    public function testCurrentStructuredTextAndFontPathIsolatedAcrossDocuments(): void
    {
        $fontA = '01FC Font A ' . bin2hex(random_bytes(3));
        $fontB = '01FC Font B ' . bin2hex(random_bytes(3));
        [$templateA, $styleA] = $this->templateWithStyledText($fontA, '#a10000');
        [$templateB, $styleB] = $this->templateWithStyledText($fontB, '#00a100');
        $outputA = $this->temporaryDirectory . '/a.odt';
        $outputB = $this->temporaryDirectory . '/b.odt';

        $templateA->save($outputA);
        $templateB->save($outputB);

        $stylesA = $this->readStyles($outputA);
        $stylesB = $this->readStyles($outputB);
        self::assertStringContainsString($styleA, $stylesA);
        self::assertStringContainsString($fontA, $stylesA);
        self::assertStringNotContainsString($styleB, $stylesA);
        self::assertStringNotContainsString($fontB, $stylesA);
        self::assertStringContainsString($styleB, $stylesB);
        self::assertStringContainsString($fontB, $stylesB);
        self::assertStringNotContainsString($styleA, $stylesB);
        self::assertStringNotContainsString($fontA, $stylesB);
    }

    #[RunInSeparateProcess]
    public function testMappingFontFamilyIsNowFreeOfLegacyFontStateMutation(): void
    {
        $font = '01FC Mapping Font ' . bin2hex(random_bytes(3));

        self::assertSame(
            [
                'style:font-name' => $font,
                'fo:font-family' => $font,
            ],
            StyleMapper::mapTextStyleOptions(['font-family' => $font])
        );
    }

    /** @return array{0: OdtTemplate, 1: string} */
    private function templateWithStyledText(string $font, string $color): array
    {
        $template = new OdtTemplate(dirname(__DIR__, 2) . '/samples/templates/template_18_ListStyles.odt');
        $paragraph = new Paragraph();
        $paragraph->addText('Styled text', [
            'font-family' => $font,
            'color' => $color,
        ]);
        $richText = (new RichText())->addParagraph($paragraph);
        $style = null;
        foreach ($paragraph->getOwnStyleRequirements() as $requirement) {
            if ($requirement->family() === 'text') {
                $style = $requirement->name();
                break;
            }
        }
        self::assertIsString($style);
        $template->setElement('my_list', $richText);

        return [$template, $style];
    }

    private function readStyles(string $path): string
    {
        $zip = new ZipArchive();
        self::assertTrue($zip->open($path) === true);

        try {
            $styles = $zip->getFromName('styles.xml');
            self::assertIsString($styles);

            return $styles;
        } finally {
            $zip->close();
        }
    }
}
