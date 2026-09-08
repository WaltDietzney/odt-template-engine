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

final class StyleApi02FP0LegacyStructuredGraphicCompatibilityTest extends TestCase
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
    public function testLegacyAssignedTextBoxUsesGlobalFrameCarrierAndWritesGraphicStyle(): void
    {
        $box = new DrawTextBox('02FP0_LegacyFrame', ['background-color' => '#d4a100']);
        $template = $this->legacyTemplate($box);
        $output = $this->save($template, 'frame');
        $content = $this->entry($output, 'content.xml');
        $styles = $this->entry($output, 'styles.xml');
        $styleName = $this->graphicStyleName($content, '02FP0_LegacyFrame');

        self::assertArrayHasKey($styleName, $template->frameStylesForAudit());
        self::assertStringContainsString('style:name="' . $styleName . '"', $styles);
        self::assertStringContainsString('style:family="graphic"', $styles);
        self::assertStringContainsString('draw:fill-color="#d4a100"', $styles);
    }

    #[RunInSeparateProcess]
    public function testLegacyAssignedImageUsesGlobalImageCarrierAndWritesGraphicStyle(): void
    {
        $image = new ImageElement($this->imagePath(), ['width' => '4cm', 'anchor' => 'as-char']);
        $template = $this->legacyTemplate($image);
        $output = $this->save($template, 'image');
        $content = $this->entry($output, 'content.xml');
        $styles = $this->entry($output, 'styles.xml');
        $styleName = (string) $image->getImageOptions()['style-name'];

        self::assertArrayHasKey($styleName, $template->imageStylesForAudit());
        self::assertStringContainsString('draw:style-name="' . $styleName . '"', $content);
        self::assertStringContainsString('style:name="' . $styleName . '"', $styles);
    }

    #[RunInSeparateProcess]
    public function testLegacyAssignedCircularImageUsesGlobalImageAndFillCarriers(): void
    {
        $image = new CircularImageElement($this->imagePath(), ['width' => '3cm', 'height' => '3cm']);
        $template = $this->legacyTemplate($image);
        $output = $this->save($template, 'circular-image');
        $styles = $this->entry($output, 'styles.xml');
        $imageName = (string) array_key_first($image->getImageStyleRequirements());
        $fillName = (string) array_key_first($image->getFillImageRequirements());

        self::assertArrayHasKey($imageName, $template->imageStylesForAudit());
        self::assertArrayHasKey($fillName, $template->fillImagesForAudit());
        self::assertStringContainsString('style:name="' . $imageName . '"', $styles);
        self::assertStringContainsString('draw:name="' . $fillName . '"', $styles);
    }

    private function legacyTemplate(object $element): OdtTemplate
    {
        $template = new class($this->templatePath('sample_textfeld.odt')) extends OdtTemplate {
            public function frameStylesForAudit(): array
            {
                return $this->documentContext()->styleContext()->frameStyles();
            }

            public function imageStylesForAudit(): array
            {
                return $this->documentContext()->styleContext()->imageStyles();
            }

            public function fillImagesForAudit(): array
            {
                return $this->documentContext()->styleContext()->fillImages();
            }
        };
        $this->templates[] = $template;
        $template->assign(['test1' => $element]);
        $template->render();

        return $template;
    }

    private function save(OdtTemplate $template, string $name): string
    {
        $output = sys_get_temp_dir() . '/odt-style-api-02f-p0-' . $name . '-' . bin2hex(random_bytes(5)) . '.odt';
        $this->outputs[] = $output;
        $template->save($output);

        return $output;
    }

    private function graphicStyleName(string $content, string $frameName): string
    {
        $dom = new \DOMDocument();
        self::assertTrue($dom->loadXML($content));
        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('draw', 'urn:oasis:names:tc:opendocument:xmlns:drawing:1.0');
        $name = $xpath->evaluate(
            'string((//*[@draw:name=' . $this->xpathLiteral($frameName) . ']/@draw:style-name)[1])'
        );
        self::assertNotSame('', $name);

        return $name;
    }

    private function xpathLiteral(string $value): string
    {
        return "'" . str_replace("'", "&apos;", $value) . "'";
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

    private function templatePath(string $name): string
    {
        return dirname(__DIR__, 2) . '/samples/templates/' . $name;
    }

    private function imagePath(): string
    {
        return dirname(__DIR__, 2) . '/assets/WaltDietzney.png';
    }
}
