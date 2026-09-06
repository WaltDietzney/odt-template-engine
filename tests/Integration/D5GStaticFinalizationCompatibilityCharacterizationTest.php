<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Integration;

use DOMDocument;
use OdtTemplateEngine\Elements\CircularImageElement;
use OdtTemplateEngine\Elements\DrawTextBox;
use OdtTemplateEngine\Elements\ImageElement;
use OdtTemplateEngine\OdtTemplate;
use OdtTemplateEngine\Utils\StyleMapper;
use OdtTemplateEngine\Utils\StyleWriter;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;
use ZipArchive;

/**
 * Characterizes the remaining static/finalization compatibility boundary.
 *
 * The tests distinguish direct public StyleWriter compatibility from the
 * document-local filtering performed by OdtTemplate.
 */
final class D5GStaticFinalizationCompatibilityCharacterizationTest extends TestCase
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

    #[RunInSeparateProcess]
    public function testDirectStyleWriterDefaultsStillMaterializeStaticCompatibilityFamilies(): void
    {
        $paragraph = 'D5GD_DirectParagraph';
        $table = 'D5GD_DirectTable';
        $cell = 'D5GD_DirectCell';
        $frame = 'D5GD_DirectFrame';

        StyleMapper::registerParagraphStyle($paragraph, ['margin-left' => '1cm']);
        StyleMapper::registerTableStyle($table, ['table:align' => 'left']);
        StyleMapper::registerTableCellStyle($cell, ['background' => '#abcdef']);
        StyleMapper::addFrameStyle($frame, ['draw:fill' => 'solid']);

        $dom = new DOMDocument('1.0', 'UTF-8');
        self::assertTrue($dom->loadXML(
            '<office:document-styles xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0"/>'
        ));
        StyleWriter::writeAllStyles($dom);
        $xml = $dom->saveXML();
        self::assertIsString($xml);

        foreach ([$paragraph, $table, $cell, $frame] as $name) {
            self::assertStringContainsString('style:name="' . $name . '"', $xml);
        }
    }

    #[RunInSeparateProcess]
    public function testMissingLegacyFramePlaceholderDoesNotAdoptStaticFrameStyle(): void
    {
        $name = 'D5GD_MissingFrame';
        $element = new DrawTextBox('Missing frame', ['width' => '4cm']);
        $legacyName = (string) array_key_first($element->getFrameStyleRequirements());

        $template = $this->template();
        StyleMapper::addFrameStyle($name, ['draw:fill' => 'solid']);
        $template->assign(['missing' => $element]);
        $template->render();
        $styles = $this->saveAndReadStyles($template, 'missing-frame');

        self::assertStringNotContainsString('style:name="' . $name . '"', $styles);
        self::assertStringNotContainsString('style:name="' . $legacyName . '"', $styles);
    }

    #[RunInSeparateProcess]
    public function testMissingLegacyImagePlaceholderDoesNotAdoptStaticImageStyle(): void
    {
        $name = 'D5GD_MissingImage';
        StyleMapper::registerImageStyle($name, ['style:wrap' => 'none']);

        $template = $this->template();
        $template->assign(['missing' => new ImageElement($this->imagePath())]);
        $template->render();
        $styles = $this->saveAndReadStyles($template, 'missing-image');

        self::assertStringNotContainsString('style:name="' . $name . '"', $styles);
    }

    #[RunInSeparateProcess]
    public function testMissingLegacyFillPlaceholderDoesNotAdoptStaticDeclaration(): void
    {
        $name = 'D5GD_MissingFill';
        StyleMapper::registerFillImage($name, $this->imagePath('Logo.png'));

        $template = $this->template();
        $template->assign(['missing' => new CircularImageElement($this->imagePath('Logo.png'))]);
        $template->render();
        $styles = $this->saveAndReadStyles($template, 'missing-fill');

        self::assertStringNotContainsString('draw:name="' . $name . '"', $styles);
    }

    private function template(): OdtTemplate
    {
        return new OdtTemplate($this->templatePath('sample_textfeld.odt'));
    }

    private function saveAndReadStyles(OdtTemplate $template, string $label): string
    {
        $output = sys_get_temp_dir() . '/d5gd-' . $label . '-' . bin2hex(random_bytes(5)) . '.odt';
        $this->outputs[] = $output;
        $template->save($output);
        $template->cleanup();

        $zip = new ZipArchive();
        self::assertTrue($zip->open($output) === true);
        try {
            $styles = $zip->getFromName('styles.xml');
            self::assertIsString($styles);
            return $styles;
        } finally {
            $zip->close();
        }
    }

    private function templatePath(string $name): string
    {
        return dirname(__DIR__, 2) . '/samples/templates/' . $name;
    }

    private function imagePath(string $name = 'WaltDietzney.png'): string
    {
        return dirname(__DIR__, 2) . '/assets/' . $name;
    }
}
