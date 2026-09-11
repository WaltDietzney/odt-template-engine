<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Integration;

use DOMDocument;
use DOMElement;
use DOMXPath;
use OdtTemplateEngine\Document\StructuredElementMaterializer;
use OdtTemplateEngine\Document\StructuredInsertionMode;
use OdtTemplateEngine\Elements\DrawTextBox;
use OdtTemplateEngine\Elements\ImageElement;
use PHPUnit\Framework\TestCase;

final class FrameLayout01CSlice4AnchorSensitiveInsertionTest extends TestCase
{
    public function testAsCharImageReportsInlineTextFlowInsertion(): void
    {
        $image = (new ImageElement($this->imagePath(), [
            'width' => '1cm',
        ]))->setFrameLayout([
            'anchor' => 'as-char',
            'vertical' => [
                'alignment' => 'top',
                'relative-to' => 'baseline',
            ],
        ]);

        self::assertSame(
            StructuredInsertionMode::INLINE_TEXT_FLOW,
            $image->structuredInsertionMode()
        );
    }

    public function testFloatingImageReportsBlockInsertion(): void
    {
        $image = (new ImageElement($this->imagePath(), [
            'width' => '1cm',
        ]))->setFrameLayout([
            'anchor' => 'paragraph',
        ]);

        self::assertSame(
            StructuredInsertionMode::BLOCK,
            $image->structuredInsertionMode()
        );
    }

    public function testAsCharTextBoxReportsInlineTextFlowInsertion(): void
    {
        $box = (new DrawTextBox('InlineBox'))->setFrameLayout([
            'anchor' => 'as-char',
            'width' => '2cm',
            'height' => '1cm',
            'vertical' => [
                'alignment' => 'top',
                'relative-to' => 'baseline',
            ],
        ]);

        self::assertSame(
            StructuredInsertionMode::INLINE_TEXT_FLOW,
            $box->structuredInsertionMode()
        );
    }

    public function testInlineInsertionPreservesBodyParagraphAndSurroundingText(): void
    {
        $dom = $this->bodyDom('Before {{logo}} after.');
        $replacement = $dom->createElement('draw:frame');
        $replacement->setAttribute('text:anchor-type', 'as-char');

        (new StructuredElementMaterializer())->replacePlaceholder(
            $dom,
            'logo',
            $replacement,
            StructuredInsertionMode::INLINE_TEXT_FLOW
        );

        $xpath = $this->xpath($dom);
        $paragraph = $xpath->query('//office:body//text:p')->item(0);

        self::assertInstanceOf(DOMElement::class, $paragraph);
        self::assertSame('Before  after.', $paragraph->textContent);
        self::assertSame(1, $dom->getElementsByTagName('draw:frame')->length);
        self::assertSame('text:p', $dom->getElementsByTagName('draw:frame')->item(0)?->parentNode?->nodeName);
    }

    public function testInlineInsertionPreservesHeaderParagraph(): void
    {
        $dom = $this->stylesDom('{{logo}}');
        $replacement = $dom->createElement('draw:frame');
        $replacement->setAttribute('text:anchor-type', 'as-char');

        (new StructuredElementMaterializer())->replacePlaceholder(
            $dom,
            'logo',
            $replacement,
            StructuredInsertionMode::INLINE_TEXT_FLOW
        );

        $xpath = $this->xpath($dom);
        self::assertSame(1, $xpath->query('//style:header//text:p')->length);
        self::assertSame(1, $dom->getElementsByTagName('draw:frame')->length);

        $frame = $dom->getElementsByTagName('draw:frame')->item(0);
        self::assertSame('text:p', $frame?->parentNode?->nodeName);
    }

    public function testBlockInsertionStillReplacesContainingParagraph(): void
    {
        $dom = $this->bodyDom('{{box}}');
        $replacement = $dom->createElement('draw:frame');
        $replacement->setAttribute('text:anchor-type', 'paragraph');

        (new StructuredElementMaterializer())->replacePlaceholder(
            $dom,
            'box',
            $replacement,
            StructuredInsertionMode::BLOCK
        );

        $xpath = $this->xpath($dom);
        self::assertSame(0, $xpath->query('//office:body//text:p')->length);
        self::assertSame(1, $dom->getElementsByTagName('draw:frame')->length);
    }

    private function bodyDom(string $content): DOMDocument
    {
        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<office:document-content
    xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0"
    xmlns:text="urn:oasis:names:tc:opendocument:xmlns:text:1.0"
    xmlns:draw="urn:oasis:names:tc:opendocument:xmlns:drawing:1.0">
    <office:body>
        <office:text>
            <text:p>{$content}</text:p>
        </office:text>
    </office:body>
</office:document-content>
XML;

        $dom = new DOMDocument('1.0', 'UTF-8');
        self::assertTrue($dom->loadXML($xml));

        return $dom;
    }

    private function stylesDom(string $content): DOMDocument
    {
        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<office:document-styles
    xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0"
    xmlns:text="urn:oasis:names:tc:opendocument:xmlns:text:1.0"
    xmlns:draw="urn:oasis:names:tc:opendocument:xmlns:drawing:1.0"
    xmlns:style="urn:oasis:names:tc:opendocument:xmlns:style:1.0">
    <office:master-styles>
        <style:master-page style:name="Standard">
            <style:header>
                <text:p>{$content}</text:p>
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
