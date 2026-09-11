<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Integration;

use DOMDocument;
use DOMElement;
use DOMXPath;
use OdtTemplateEngine\Document\StructuredElementMaterializer;
use OdtTemplateEngine\Elements\DrawTextBox;
use OdtTemplateEngine\Elements\ImageElement;
use PHPUnit\Framework\TestCase;

final class FrameLayout01CSlice0InsertionCharacterizationTest extends TestCase
{
    public function testAsCharImageCurrentlyReplacesBodyParagraphWithBareFrame(): void
    {
        $dom = $this->bodyDom('{{logo}}');
        $image = new ImageElement($this->imagePath(), [
            'width' => '1cm',
            'height' => '1cm',
            'anchor' => 'as-char',
        ]);

        (new StructuredElementMaterializer())->replacePlaceholder(
            $dom,
            'logo',
            $image->toDomNode($dom)
        );

        $xpath = $this->xpath($dom);
        self::assertSame(0, $xpath->query('//office:body//text:p')->length);
        $frames = $dom->getElementsByTagName('draw:frame');
        self::assertSame(1, $frames->length);

        $frame = $frames->item(0);
        self::assertInstanceOf(DOMElement::class, $frame);
        self::assertStringContainsString(
            'text:anchor-type="as-char"',
            $dom->saveXML($frame) ?: ''
        );
    }

    public function testAsCharImageCurrentlyReplacesHeaderParagraphWithBareFrame(): void
    {
        $dom = $this->stylesDom('{{logo}}');
        $image = new ImageElement($this->imagePath(), [
            'width' => '1cm',
            'height' => '1cm',
            'anchor' => 'as-char',
        ]);

        (new StructuredElementMaterializer())->replacePlaceholder(
            $dom,
            'logo',
            $image->toDomNode($dom)
        );

        $xpath = $this->xpath($dom);
        self::assertSame(0, $xpath->query('//style:header//text:p')->length);
        $frames = $dom->getElementsByTagName('draw:frame');
        self::assertSame(1, $frames->length);

        $frame = $frames->item(0);
        self::assertInstanceOf(DOMElement::class, $frame);
        self::assertStringContainsString(
            'text:anchor-type="as-char"',
            $dom->saveXML($frame) ?: ''
        );
    }

    public function testAsCharTextBoxCurrentlyReplacesPlaceholderParagraphWithBareFrame(): void
    {
        $dom = $this->bodyDom('{{box}}');
        $box = new DrawTextBox('InlineBox', [
            'width' => '2cm',
            'height' => '1cm',
            'anchor' => 'as-char',
        ]);

        $replacement = $box->toDomNode($dom);
        self::assertSame('draw:frame', $replacement->nodeName);

        (new StructuredElementMaterializer())->replacePlaceholder(
            $dom,
            'box',
            $replacement
        );

        $xpath = $this->xpath($dom);
        self::assertSame(0, $xpath->query('//office:body//text:p')->length);
        self::assertSame(1, $dom->getElementsByTagName('draw:frame')->length);
    }

    public function testFloatingTextBoxCurrentlyReturnsParagraphWrapperBeforeMaterializer(): void
    {
        $dom = $this->bodyDom('{{box}}');
        $box = new DrawTextBox('FloatingBox', [
            'width' => '2cm',
            'height' => '1cm',
            'anchor' => 'paragraph',
        ]);

        $replacement = $box->toDomNode($dom);

        self::assertSame('text:p', $replacement->nodeName);
        self::assertSame('draw:frame', $replacement->firstChild?->nodeName);

        (new StructuredElementMaterializer())->replacePlaceholder(
            $dom,
            'box',
            $replacement
        );

        $paragraphs = $dom->getElementsByTagName('text:p');
        self::assertSame(1, $paragraphs->length);
        $paragraph = $paragraphs->item(0);
        self::assertInstanceOf(DOMElement::class, $paragraph);
        self::assertSame('draw:frame', $paragraph->firstChild?->nodeName);
    }

    private function bodyDom(string $placeholder): DOMDocument
    {
        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<office:document-content
    xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0"
    xmlns:text="urn:oasis:names:tc:opendocument:xmlns:text:1.0"
    xmlns:draw="urn:oasis:names:tc:opendocument:xmlns:drawing:1.0"
    xmlns:style="urn:oasis:names:tc:opendocument:xmlns:style:1.0"
    xmlns:svg="urn:oasis:names:tc:opendocument:xmlns:svg-compatible:1.0"
    xmlns:xlink="http://www.w3.org/1999/xlink">
    <office:body>
        <office:text>
            <text:p>{$placeholder}</text:p>
        </office:text>
    </office:body>
</office:document-content>
XML;

        $dom = new DOMDocument('1.0', 'UTF-8');
        self::assertTrue($dom->loadXML($xml));

        return $dom;
    }

    private function stylesDom(string $placeholder): DOMDocument
    {
        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<office:document-styles
    xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0"
    xmlns:text="urn:oasis:names:tc:opendocument:xmlns:text:1.0"
    xmlns:draw="urn:oasis:names:tc:opendocument:xmlns:drawing:1.0"
    xmlns:style="urn:oasis:names:tc:opendocument:xmlns:style:1.0"
    xmlns:svg="urn:oasis:names:tc:opendocument:xmlns:svg-compatible:1.0"
    xmlns:xlink="http://www.w3.org/1999/xlink">
    <office:master-styles>
        <style:master-page style:name="Standard">
            <style:header>
                <text:p>{$placeholder}</text:p>
            </style:header>
        </style:master-page>
    </office:master-styles>
</office:document-styles>
XML;

        $dom = new DOMDocument('1.0', 'UTF-8');
        self::assertTrue($dom->loadXML($xml));

        return $dom;
    }

    private function xpath(DOMDocument $dom): DOMXPath
    {
        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace(
            'office',
            'urn:oasis:names:tc:opendocument:xmlns:office:1.0'
        );
        $xpath->registerNamespace(
            'text',
            'urn:oasis:names:tc:opendocument:xmlns:text:1.0'
        );
        $xpath->registerNamespace(
            'draw',
            'urn:oasis:names:tc:opendocument:xmlns:drawing:1.0'
        );
        $xpath->registerNamespace(
            'style',
            'urn:oasis:names:tc:opendocument:xmlns:style:1.0'
        );

        return $xpath;
    }

    private function imagePath(): string
    {
        return dirname(__DIR__, 2) . '/assets/WaltDietzney.png';
    }
}
