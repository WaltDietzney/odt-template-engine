<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Integration;

use DOMDocument;
use DOMElement;
use DOMXPath;
use OdtTemplateEngine\Elements\ImageElement;
use OdtTemplateEngine\OdtTemplate;
use PHPUnit\Framework\TestCase;
use ZipArchive;

final class FrameLayout01CSlice3ImageElementIntegrationTest extends TestCase
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

    public function testFriendlyImageLayoutMaterializesThroughGraphicStyleAndFrameGeometry(): void
    {
        $template = new OdtTemplate($this->templatePath('sample_textfeld.odt'));
        $this->templates[] = $template;

        $image = (new ImageElement($this->imagePath(), [
            'width' => '2cm',
        ]))->setFrameLayout([
            'anchor' => 'paragraph',
            'width' => '3cm',
            'height' => '1.5cm',
            'horizontal' => [
                'alignment' => 'right',
                'relative-to' => 'paragraph',
            ],
            'vertical' => [
                'offset' => '0.4cm',
                'relative-to' => 'paragraph',
            ],
            'wrap' => 'left',
        ]);

        $template->setElement('test1', $image);

        $output = sys_get_temp_dir()
            . '/odt-frame-layout-image-slice3-'
            . bin2hex(random_bytes(6))
            . '.odt';
        $this->outputs[] = $output;
        $template->save($output);

        $content = $this->dom($this->entry($output, 'content.xml'));
        $styles = $this->dom($this->entry($output, 'styles.xml'));

        $contentXPath = $this->xpath($content);
        $frame = $contentXPath->query(
            '//draw:frame[draw:image[contains(@xlink:href, "' . basename($this->imagePath()) . '")]]'
        )->item(0);
        self::assertInstanceOf(DOMElement::class, $frame);

        self::assertSame('paragraph', $frame->getAttributeNS($this->textNs(), 'anchor-type'));
        self::assertSame('3cm', $frame->getAttributeNS($this->svgNs(), 'width'));
        self::assertSame('1.5cm', $frame->getAttributeNS($this->svgNs(), 'height'));
        self::assertSame('0.4cm', $frame->getAttributeNS($this->svgNs(), 'y'));
        self::assertFalse($frame->hasAttributeNS($this->styleNs(), 'horizontal-pos'));
        self::assertFalse($frame->hasAttributeNS($this->styleNs(), 'vertical-pos'));

        $styleName = $frame->getAttributeNS($this->drawNs(), 'style-name');
        self::assertNotSame('', $styleName);

        $stylesXPath = $this->xpath($styles);
        $style = $stylesXPath->query(
            '//style:style[@style:name="' . $styleName . '" and @style:family="graphic"]'
        )->item(0);
        self::assertInstanceOf(DOMElement::class, $style);

        $properties = $stylesXPath->query('style:graphic-properties', $style)->item(0);
        self::assertInstanceOf(DOMElement::class, $properties);
        self::assertSame('right', $properties->getAttributeNS($this->styleNs(), 'horizontal-pos'));
        self::assertSame('paragraph', $properties->getAttributeNS($this->styleNs(), 'horizontal-rel'));
        self::assertSame('from-top', $properties->getAttributeNS($this->styleNs(), 'vertical-pos'));
        self::assertSame('paragraph', $properties->getAttributeNS($this->styleNs(), 'vertical-rel'));
        self::assertSame('left', $properties->getAttributeNS($this->styleNs(), 'wrap'));

        self::assertTrue($this->contains($output, 'Pictures/' . basename($this->imagePath())));
        self::assertStringContainsString(
            'Pictures/' . basename($this->imagePath()),
            $this->entry($output, 'META-INF/manifest.xml')
        );
    }

    private function templatePath(string $name): string
    {
        return dirname(__DIR__, 2) . '/samples/templates/' . $name;
    }

    private function imagePath(): string
    {
        return dirname(__DIR__, 2) . '/assets/WaltDietzney.png';
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

    private function contains(string $path, string $name): bool
    {
        $zip = new ZipArchive();
        self::assertTrue($zip->open($path) === true);
        try {
            return $zip->locateName($name) !== false;
        } finally {
            $zip->close();
        }
    }

    private function dom(string $xml): DOMDocument
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        self::assertTrue($dom->loadXML($xml));

        return $dom;
    }

    private function xpath(DOMDocument $dom): DOMXPath
    {
        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('draw', $this->drawNs());
        $xpath->registerNamespace('style', $this->styleNs());
        $xpath->registerNamespace('xlink', 'http://www.w3.org/1999/xlink');

        return $xpath;
    }

    private function drawNs(): string
    {
        return 'urn:oasis:names:tc:opendocument:xmlns:drawing:1.0';
    }

    private function styleNs(): string
    {
        return 'urn:oasis:names:tc:opendocument:xmlns:style:1.0';
    }

    private function textNs(): string
    {
        return 'urn:oasis:names:tc:opendocument:xmlns:text:1.0';
    }

    private function svgNs(): string
    {
        return 'urn:oasis:names:tc:opendocument:xmlns:svg-compatible:1.0';
    }
}
