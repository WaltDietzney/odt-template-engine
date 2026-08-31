<?php

declare(strict_types=1);

namespace OdtTemplateEngineTests\Integration;

use OdtTemplateEngine\Elements\CircularImageElement;
use OdtTemplateEngine\Elements\DrawTextBox;
use OdtTemplateEngine\Elements\ImageElement;
use OdtTemplateEngine\OdtTemplate;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;
use ZipArchive;

/**
 * Characterizes the legacy assign/render structured-element path after D4.
 *
 * These tests intentionally cover the observable style compatibility that the
 * pre-D4 global finalization path provided. Image assets are not asserted here:
 * the legacy setValuesInDom() path did not prepare element assets before D4.
 */
final class StyleContextGraphicImageLegacyCompatibilityTest extends TestCase
{
    /** @var list<string> */
    private array $outputs = [];

    /** @var list<OdtTemplate> */
    private array $templates = [];

    protected function tearDown(): void
    {
        foreach ($this->templates as $template) {
            $template->cleanup();
        }
        foreach ($this->outputs as $output) {
            if (is_file($output)) {
                unlink($output);
            }
        }
    }

    #[RunInSeparateProcess]
    public function testLegacyImageElementKeepsItsGraphicStyleInSavedDocument(): void
    {
        $image = new ImageElement($this->imagePath(), [
            'width' => '4cm',
            'anchor' => 'as-char',
        ]);
        $template = $this->legacyTemplate($image);
        $styleName = (string) $image->getImageOptions()['style-name'];

        $output = $this->save($template);
        $content = $this->entry($output, 'content.xml');
        $styles = $this->entry($output, 'styles.xml');

        self::assertStringContainsString('draw:style-name="' . $styleName . '"', $content);
        self::assertStringContainsString('style:name="' . $styleName . '"', $styles);
        self::assertTrue($this->isWellFormedXml($content));
        self::assertTrue($this->isWellFormedXml($styles));
    }

    #[RunInSeparateProcess]
    public function testLegacyCircularImageKeepsGraphicAndFillStylesInSavedDocument(): void
    {
        $image = new CircularImageElement($this->imagePath(), [
            'width' => '3cm',
            'height' => '3cm',
        ]);
        $template = $this->legacyTemplate($image);
        $fillName = 'cv_photo_' . pathinfo($this->imagePath(), PATHINFO_FILENAME);

        $output = $this->save($template);
        $content = $this->entry($output, 'content.xml');
        $styles = $this->entry($output, 'styles.xml');

        $contentDom = new \DOMDocument();
        self::assertTrue($contentDom->loadXML($content));
        $xpath = new \DOMXPath($contentDom);
        $xpath->registerNamespace('draw', 'urn:oasis:names:tc:opendocument:xmlns:drawing:1.0');
        $styleName = $xpath->evaluate('string((//draw:custom-shape/@draw:style-name)[1])');

        self::assertStringContainsString('draw:custom-shape', $content);
        self::assertNotSame('', $styleName);
        self::assertStringContainsString('style:name="' . $styleName . '"', $styles);
        self::assertStringContainsString('draw:name="' . $fillName . '"', $styles);
        self::assertTrue($this->isWellFormedXml($styles));
    }

    #[RunInSeparateProcess]
    public function testLegacyDrawTextBoxKeepsItsGraphicStyleInSavedDocument(): void
    {
        $box = new DrawTextBox('LegacyCompatibilityBox', [
            'background-color' => '#d4a100',
        ]);
        $template = $this->legacyTemplate($box);
        $styleName = array_key_first($box->getStyleDefinitions());
        self::assertIsString($styleName);

        $output = $this->save($template);
        $content = $this->entry($output, 'content.xml');
        $styles = $this->entry($output, 'styles.xml');

        self::assertStringContainsString('draw:style-name="' . $styleName . '"', $content);
        self::assertSame(1, substr_count($styles, 'style:name="' . $styleName . '"'));
        self::assertTrue($this->isWellFormedXml($styles));
    }

    private function legacyTemplate(object $element): OdtTemplate
    {
        $template = new OdtTemplate($this->templatePath('sample_textfeld.odt'));
        $this->templates[] = $template;
        $template->assign(['test1' => $element]);
        $template->render();

        return $template;
    }

    private function save(OdtTemplate $template): string
    {
        $output = sys_get_temp_dir() . '/odt-style-legacy-d4a-' . bin2hex(random_bytes(6)) . '.odt';
        $this->outputs[] = $output;
        $template->save($output);

        return $output;
    }

    private function entry(string $path, string $name): string
    {
        $zip = new ZipArchive();
        self::assertTrue($zip->open($path) === true);
        try {
            $value = $zip->getFromName($name);
            self::assertIsString($value);

            return $value;
        } finally {
            $zip->close();
        }
    }

    private function isWellFormedXml(string $xml): bool
    {
        $dom = new \DOMDocument();

        return $dom->loadXML($xml);
    }

    private function templatePath(string $name): string
    {
        return dirname(__DIR__, 2) . '/samples/templates/' . $name;
    }

    private function imagePath(): string
    {
        return dirname(__DIR__, 2) . '/assets/WaltDietzney.png';
    }
}
