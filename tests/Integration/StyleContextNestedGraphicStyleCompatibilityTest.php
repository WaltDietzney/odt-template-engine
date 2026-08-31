<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Integration;

use OdtTemplateEngine\Elements\DrawTextBox;
use OdtTemplateEngine\Elements\Paragraph;
use OdtTemplateEngine\OdtTemplate;
use PHPUnit\Framework\TestCase;
use ZipArchive;

final class StyleContextNestedGraphicStyleCompatibilityTest extends TestCase
{
    public function testNestedDrawTextBoxStyleIsPersistedWhenParagraphIsInserted(): void
    {
        $template = new OdtTemplate($this->templatePath('template_17_textfield.odt'));
        $output = sys_get_temp_dir() . '/odt-style-nested-graphic-' . bin2hex(random_bytes(6)) . '.odt';

        $box = (new DrawTextBox('Inline', [
            'width' => '4cm',
            'height' => '3cm',
            'anchor' => 'as-char',
            'background-color' => '#ede7f6',
            'border' => '0.03cm solid #5e35b1',
            'padding' => '0.1cm',
        ]))->addElement(
            (new Paragraph())->addText('Inline-Box', ['bold' => true, 'italic' => true])
        );

        $paragraph = (new Paragraph())
            ->addText('Hier kommt eine Inline-Textbox: ')
            ->addElement($box)
            ->addText(' und weiter geht’s mit normalem Text.', ['bold' => true, 'italic' => true]);

        try {
            $template->setElement('INLINE_BOX', $paragraph);
            $template->save($output);

            $zip = new ZipArchive();
            self::assertTrue($zip->open($output) === true);
            try {
                $content = $zip->getFromName('content.xml');
                $styles = $zip->getFromName('styles.xml');
                self::assertIsString($content);
                self::assertIsString($styles);

                $frameStyleName = array_key_first($box->getFrameStyleRequirements());
                self::assertIsString($frameStyleName);
                self::assertStringContainsString('draw:style-name="' . $frameStyleName . '"', $content);
                self::assertSame(1, substr_count($styles, 'style:name="' . $frameStyleName . '"'));
            } finally {
                $zip->close();
            }
        } finally {
            $template->cleanup();
            if (is_file($output)) {
                unlink($output);
            }
        }
    }

    private function templatePath(string $name): string
    {
        return dirname(__DIR__, 2) . '/samples/templates/' . $name;
    }
}
