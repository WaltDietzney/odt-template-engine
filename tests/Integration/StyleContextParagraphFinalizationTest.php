<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Integration;

use OdtTemplateEngine\Elements\Paragraph;
use OdtTemplateEngine\Elements\RichText;
use OdtTemplateEngine\OdtTemplate;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;
use ZipArchive;

final class StyleContextParagraphFinalizationTest extends TestCase
{
    private string $temporaryDirectory;

    protected function setUp(): void
    {
        $this->temporaryDirectory = sys_get_temp_dir() . '/odt-style-finalization-' . bin2hex(random_bytes(6));
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
    public function testStructuredParagraphStylesRemainIsolatedInEitherSaveOrder(): void
    {
        $styleA = '01FB_SaveA_' . bin2hex(random_bytes(4));
        $styleB = '01FB_SaveB_' . bin2hex(random_bytes(4));
        $templateA = $this->templateWithParagraph($styleA, '1cm');
        $templateB = $this->templateWithParagraph($styleB, '2cm');
        $outputA = $this->temporaryDirectory . '/a.odt';
        $outputB = $this->temporaryDirectory . '/b.odt';

        $templateA->save($outputA);
        $templateB->save($outputB);

        $stylesA = $this->readStyles($outputA);
        $stylesB = $this->readStyles($outputB);
        self::assertStringContainsString($styleA, $stylesA);
        self::assertStringNotContainsString($styleB, $stylesA);
        self::assertStringContainsString($styleB, $stylesB);
        self::assertStringNotContainsString($styleA, $stylesB);

        $templateA = $this->templateWithParagraph($styleA, '1cm');
        $templateB = $this->templateWithParagraph($styleB, '2cm');
        $templateB->save($outputB);
        $templateA->save($outputA);

        $stylesA = $this->readStyles($outputA);
        $stylesB = $this->readStyles($outputB);
        self::assertStringContainsString($styleA, $stylesA);
        self::assertStringNotContainsString($styleB, $stylesA);
        self::assertStringContainsString($styleB, $stylesB);
        self::assertStringNotContainsString($styleA, $stylesB);
    }

    #[RunInSeparateProcess]
    public function testRepeatedSaveDoesNotDuplicateStructuredParagraphStyle(): void
    {
        $style = '01FB_Repeated_' . bin2hex(random_bytes(4));
        $template = $this->templateWithParagraph($style, '3cm');
        $first = $this->temporaryDirectory . '/first.odt';
        $second = $this->temporaryDirectory . '/second.odt';

        $template->save($first);
        $template->save($second);

        self::assertSame(1, substr_count($this->readStyles($first), 'style:name="' . $style . '"'));
        self::assertSame(1, substr_count($this->readStyles($second), 'style:name="' . $style . '"'));
    }

    private function templateWithParagraph(string $style, string $margin): OdtTemplate
    {
        $template = new OdtTemplate(dirname(__DIR__, 2) . '/samples/templates/template_18_ListStyles.odt');
        $paragraph = new Paragraph($style, ['margin-left' => $margin]);
        $paragraph->addText('Document-local paragraph');
        $template->setElement('my_list', (new RichText())->addParagraph($paragraph));

        return $template;
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
