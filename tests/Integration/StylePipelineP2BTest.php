<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Integration;

use OdtTemplateEngine\Elements\RichText;
use OdtTemplateEngine\OdtTemplate;
use PHPUnit\Framework\TestCase;
use ZipArchive;

final class StylePipelineP2BTest extends TestCase
{
    private string $temporaryDirectory;

    protected function setUp(): void
    {
        $this->temporaryDirectory = sys_get_temp_dir() . '/odt-style-p2b-' . bin2hex(random_bytes(6));
        mkdir($this->temporaryDirectory);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->temporaryDirectory . '/*') ?: [] as $path) {
            @unlink($path);
        }
        @rmdir($this->temporaryDirectory);
    }

    public function testStylesFromOneDocumentDoNotAppearInTheNextDocument(): void
    {
        $documentA = new RichText();
        $documentA->addParagraph('Document A', 'P2B_Document_A_Paragraph', [
            'margin-left' => '7cm',
        ]);
        $documentA->addText('Document A text', [
            'font-family' => 'P2B Document A Font',
            'color' => '#a10000',
        ]);

        $outputA = $this->saveRichText($documentA, 'A');
        $stylesA = $this->readStyles($outputA);

        self::assertStringContainsString('P2B_Document_A_Paragraph', $stylesA);
        self::assertStringContainsString('P2B Document A Font', $stylesA);

        $documentB = new RichText();
        $documentB->addParagraph('Document B', 'P2B_Document_B_Paragraph', [
            'margin-left' => '8cm',
        ]);
        $documentB->addText('Document B text', [
            'font-family' => 'P2B Document B Font',
            'color' => '#00a100',
        ]);

        $outputB = $this->saveRichText($documentB, 'B');
        $stylesB = $this->readStyles($outputB);

        self::assertStringContainsString('P2B_Document_B_Paragraph', $stylesB);
        self::assertStringContainsString('P2B Document B Font', $stylesB);
        self::assertStringNotContainsString('P2B_Document_A_Paragraph', $stylesB);
        self::assertStringNotContainsString('P2B Document A Font', $stylesB);
    }

    public function testCustomFontFaceAndTextStyleAreWrittenOnce(): void
    {
        $richText = new RichText();
        $richText->addParagraph('Custom font', null, [
            'font-family' => 'Arial',
        ]);

        $styles = $this->readStyles($this->saveRichText($richText, 'font'));

        self::assertSame(1, substr_count($styles, '<style:font-face style:name="Arial"'));
        self::assertStringContainsString('style:font-name="Arial"', $styles);
        self::assertStringContainsString('fo:font-family="Arial"', $styles);
        self::assertTrue(simplexml_load_string($styles) !== false);
    }

    public function testBulletPostProcessingDoesNotRemoveParagraphMargin(): void
    {
        $paragraph = new \OdtTemplateEngine\Elements\Paragraph();
        $paragraph->setBulleted();
        $paragraph->setParagraphStyleOptions(['margin-left' => '2cm']);
        $paragraph->addText('Bullet item');

        $richText = new RichText();
        $richText->addParagraph($paragraph);
        $styles = $this->readStyles($this->saveRichText($richText, 'bullet'));

        self::assertStringContainsString('fo:margin-left="2cm"', $styles);
        self::assertStringContainsString('<style:list-level-label-alignment', $styles);
        self::assertStringContainsString('fo:text-indent="-0.25cm"', $styles);
    }

    private function saveRichText(RichText $richText, string $suffix): string
    {
        $output = $this->temporaryDirectory . '/document-' . $suffix . '.odt';
        $template = new OdtTemplate(
            dirname(__DIR__, 2) . '/samples/templates/template_18_ListStyles.odt'
        );
        $template->setElement('my_list', $richText);
        $template->save($output);

        return $output;
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
