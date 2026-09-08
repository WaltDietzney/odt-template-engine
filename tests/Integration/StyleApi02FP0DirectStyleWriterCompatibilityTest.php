<?php

declare(strict_types=1);

namespace OdtTemplateEngineTests\Integration;

use DOMDocument;
use DOMElement;
use DOMXPath;
use OdtTemplateEngine\Utils\StyleMapper;
use OdtTemplateEngine\Utils\StyleWriter;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;

final class StyleApi02FP0DirectStyleWriterCompatibilityTest extends TestCase
{
    private const STYLE_NS = 'urn:oasis:names:tc:opendocument:xmlns:style:1.0';

    #[RunInSeparateProcess]
    public function testDirectParagraphRegistrationIsWrittenAsParagraphStyle(): void
    {
        $name = '02FP0_Paragraph_' . bin2hex(random_bytes(4));
        StyleMapper::registerParagraphStyle($name, ['margin-top' => '0.2cm']);

        $dom = $this->stylesDom();
        StyleWriter::writeAllStyles($dom);

        $style = $this->style($dom, $name, 'paragraph');
        self::assertNotNull($style);
        self::assertSame('paragraph', $this->attribute($style, 'style:family'));
        self::assertSame('0.2cm', $this->attribute($this->property($style, 'style:paragraph-properties'), 'fo:margin-top'));
    }

    #[RunInSeparateProcess]
    public function testDirectTextRegistrationIsWrittenAsTextStyle(): void
    {
        $name = StyleMapper::registerTextStyle(['fo:font-weight' => 'bold']);

        $dom = $this->stylesDom();
        StyleWriter::writeAllStyles($dom);

        $style = $this->style($dom, $name, 'text');
        self::assertNotNull($style);
        self::assertSame('text', $this->attribute($style, 'style:family'));
        self::assertSame('bold', $this->attribute($this->property($style, 'style:text-properties'), 'fo:font-weight'));
    }

    #[RunInSeparateProcess]
    public function testDirectTableRegistrationIsWrittenAsTableStyle(): void
    {
        $name = '02FP0_Table_' . bin2hex(random_bytes(4));
        StyleMapper::registerTableStyle($name, ['table:align' => 'left']);

        $dom = $this->stylesDom();
        StyleWriter::writeAllStyles($dom);

        $style = $this->style($dom, $name, 'table');
        self::assertNotNull($style);
        self::assertSame('table', $this->attribute($style, 'style:family'));
        self::assertSame('left', $this->attribute($this->property($style, 'style:table-properties'), 'table:align'));
    }

    #[RunInSeparateProcess]
    public function testDirectFrameRegistrationIsWrittenAsGraphicStyle(): void
    {
        $name = '02FP0_Frame_' . bin2hex(random_bytes(4));
        StyleMapper::addFrameStyle($name, ['draw:fill' => 'solid', 'draw:fill-color' => '#123456']);

        $dom = $this->stylesDom();
        StyleWriter::writeAllStyles($dom);

        $style = $this->style($dom, $name, 'graphic');
        self::assertNotNull($style);
        self::assertSame('graphic', $this->attribute($style, 'style:family'));
        self::assertSame('solid', $this->attribute($this->property($style, 'style:graphic-properties'), 'draw:fill'));
        self::assertSame('#123456', $this->attribute($this->property($style, 'style:graphic-properties'), 'draw:fill-color'));
    }

    private function stylesDom(): DOMDocument
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        self::assertTrue($dom->loadXML(
            '<office:document-styles'
            . ' xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0"'
            . ' xmlns:style="urn:oasis:names:tc:opendocument:xmlns:style:1.0"'
            . ' xmlns:fo="urn:oasis:names:tc:opendocument:xmlns:xsl-fo-compatible:1.0"'
            . ' xmlns:draw="urn:oasis:names:tc:opendocument:xmlns:drawing:1.0"'
            . ' xmlns:table="urn:oasis:names:tc:opendocument:xmlns:table:1.0"'
            . ' xmlns:svg="urn:oasis:names:tc:opendocument:xmlns:svg-compatible:1.0"/>'
        ));

        return $dom;
    }

    private function style(DOMDocument $dom, string $name, string $family): ?DOMElement
    {
        $officeStyles = null;
        foreach ($dom->documentElement?->childNodes ?? [] as $child) {
            if ($child instanceof DOMElement && $child->nodeName === 'office:styles') {
                $officeStyles = $child;
                break;
            }
        }
        self::assertInstanceOf(DOMElement::class, $officeStyles);
        foreach ($officeStyles->childNodes as $node) {
            if (!$node instanceof DOMElement) {
                continue;
            }
            if ($this->attribute($node, 'style:name') === $name
                && $this->attribute($node, 'style:family') === $family
            ) {
                return $node;
            }
        }

        return null;
    }

    private function property(DOMElement $style, string $name): DOMElement
    {
        foreach ($style->childNodes as $child) {
            if ($child instanceof DOMElement && $child->nodeName === $name) {
                return $child;
            }
        }

        self::fail('Expected style property ' . $name . '.');
    }

    private function attribute(DOMElement $element, string $name): string
    {
        return $element->attributes->getNamedItem($name)?->nodeValue ?? '';
    }
}
