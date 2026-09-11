<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Integration;

use DOMDocument;
use DOMElement;
use DOMXPath;
use OdtTemplateEngine\Elements\DrawTextBox;
use OdtTemplateEngine\OdtTemplate;
use PHPUnit\Framework\TestCase;
use ZipArchive;

final class FrameLayout01CSlice2DrawTextBoxIntegrationTest extends TestCase
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

    public function testFriendlyLayoutMaterializesThroughGraphicStyleAndFrameGeometry(): void
    {
        $template = new OdtTemplate($this->templatePath('sample_textfeld.odt'));
        $this->templates[] = $template;

        $box = (new DrawTextBox('FriendlyBox', [
            'background-color' => '#eeeeee',
        ]))->setFrameLayout([
            'anchor' => 'paragraph',
            'width' => '6cm',
            'height' => '4cm',
            'horizontal' => [
                'alignment' => 'right',
                'relative-to' => 'paragraph',
            ],
            'vertical' => [
                'offset' => '0.4cm',
                'relative-to' => 'paragraph',
            ],
            'wrap' => 'parallel',
        ]);

        $template->setElement('test1', $box);

        $output = sys_get_temp_dir()
            . '/odt-frame-layout-slice2-'
            . bin2hex(random_bytes(6))
            . '.odt';
        $this->outputs[] = $output;
        $template->save($output);

        $content = $this->dom($this->entry($output, 'content.xml'));
        $styles = $this->dom($this->entry($output, 'styles.xml'));

        $contentXPath = $this->xpath($content);
        $frame = $contentXPath->query('//draw:frame[@draw:name="FriendlyBox"]')->item(0);
        self::assertInstanceOf(DOMElement::class, $frame);

        self::assertSame('paragraph', $frame->getAttributeNS($this->textNs(), 'anchor-type'));
        self::assertSame('6cm', $frame->getAttributeNS($this->svgNs(), 'width'));
        self::assertSame('4cm', $frame->getAttributeNS($this->svgNs(), 'height'));
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
        self::assertSame('parallel', $properties->getAttributeNS($this->styleNs(), 'wrap'));
        self::assertSame('#eeeeee', $properties->getAttributeNS($this->foNs(), 'background-color'));
    }

    private function templatePath(string $name): string
    {
        return dirname(__DIR__, 2) . '/samples/templates/' . $name;
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

    private function foNs(): string
    {
        return 'urn:oasis:names:tc:opendocument:xmlns:xsl-fo-compatible:1.0';
    }
}
