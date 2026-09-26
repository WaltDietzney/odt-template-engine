<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Document;

use DOMDocument;
use DOMElement;
use InvalidArgumentException;
use OdtTemplateEngine\Document\StyleRequirement;
use OdtTemplateEngine\Document\StyleRequirementMaterializer;
use OdtTemplateEngine\OdtDocumentContext;
use PHPUnit\Framework\TestCase;

final class GraphicStyleRequirementMaterializerTest extends TestCase
{
    public function testCommonGraphicDefinitionWritesNativeGraphicPropertiesToStylesXml(): void
    {
        $context = $this->context();
        $requirement = $this->graphicRequirement('SemanticGraphic', [
            'fo:background-color' => '#123456',
            'draw:fill' => 'solid',
            'draw:fill-color' => '#123456',
            'fo:border' => '0.02cm solid #000000',
            'fo:padding' => '0.1cm',
        ]);

        (new StyleRequirementMaterializer())->materialize($context, $requirement);

        $style = $this->style($context->stylesDom(), 'SemanticGraphic', 'graphic');
        self::assertNotNull($style);
        self::assertSame('Frame', $style->getAttributeNS(self::STYLE_NS, 'parent-style-name'));
        $properties = $this->property($style, 'graphic-properties');
        self::assertSame('#123456', $properties->getAttributeNS(self::FO_NS, 'background-color'));
        self::assertSame('solid', $properties->getAttributeNS(self::DRAW_NS, 'fill'));
        self::assertSame('#123456', $properties->getAttributeNS(self::DRAW_NS, 'fill-color'));
        self::assertSame('0.02cm solid #000000', $properties->getAttributeNS(self::FO_NS, 'border'));
        self::assertSame('0.1cm', $properties->getAttributeNS(self::FO_NS, 'padding'));
        self::assertNull($this->style($context->contentDom(), 'SemanticGraphic', 'graphic'));
    }

    public function testCircularBitmapGraphicPropertiesAreWrittenVerbatim(): void
    {
        $context = $this->context();
        $requirement = $this->graphicRequirement('CircularGraphic', [
            'draw:fill' => 'bitmap',
            'draw:fill-image-name' => 'cv_photo_avatar',
            'draw:fill-image-width' => '100%',
            'draw:fill-image-height' => '100%',
            'style:repeat' => 'stretch',
            'draw:stroke' => 'none',
        ]);

        (new StyleRequirementMaterializer())->materialize($context, $requirement);

        $style = $this->style($context->stylesDom(), 'CircularGraphic', 'graphic');
        self::assertNotNull($style);
        $properties = $this->property($style, 'graphic-properties');
        self::assertSame('bitmap', $properties->getAttributeNS(self::DRAW_NS, 'fill'));
        self::assertSame('cv_photo_avatar', $properties->getAttributeNS(self::DRAW_NS, 'fill-image-name'));
        self::assertSame('100%', $properties->getAttributeNS(self::DRAW_NS, 'fill-image-width'));
        self::assertSame('100%', $properties->getAttributeNS(self::DRAW_NS, 'fill-image-height'));
        self::assertSame('stretch', $properties->getAttributeNS(self::STYLE_NS, 'repeat'));
        self::assertSame('none', $properties->getAttributeNS(self::DRAW_NS, 'stroke'));
    }

    public function testExistingDocumentGraphicDefinitionRemainsAuthoritativeAndIsNotDuplicated(): void
    {
        $context = $this->context();
        $container = $context->stylesDom()->getElementsByTagNameNS(self::OFFICE_NS, 'styles')->item(0);
        self::assertInstanceOf(DOMElement::class, $container);

        $style = $context->stylesDom()->createElementNS(self::STYLE_NS, 'style:style');
        $style->setAttributeNS(self::STYLE_NS, 'style:name', 'AuthoritativeGraphic');
        $style->setAttributeNS(self::STYLE_NS, 'style:family', 'graphic');
        $properties = $context->stylesDom()->createElementNS(self::STYLE_NS, 'style:graphic-properties');
        $properties->setAttributeNS(self::DRAW_NS, 'draw:fill', 'none');
        $style->appendChild($properties);
        $container->appendChild($style);

        (new StyleRequirementMaterializer())->materialize($context, $this->graphicRequirement(
            'AuthoritativeGraphic',
            ['draw:fill' => 'solid', 'draw:fill-color' => '#ff0000']
        ));

        self::assertSame(1, $this->styleCount($context->stylesDom(), 'AuthoritativeGraphic', 'graphic'));
        self::assertSame('none', $this->property($style, 'graphic-properties')->getAttributeNS(self::DRAW_NS, 'fill'));
        self::assertFalse($this->property($style, 'graphic-properties')->hasAttributeNS(self::DRAW_NS, 'fill-color'));
    }

    public function testRepeatedGraphicMaterializationIsIdempotent(): void
    {
        $context = $this->context();
        $requirement = $this->graphicRequirement('OnceOnly', ['draw:stroke' => 'none']);
        $materializer = new StyleRequirementMaterializer();

        $materializer->materialize($context, $requirement);
        $materializer->materialize($context, $requirement);

        self::assertSame(1, $this->styleCount($context->stylesDom(), 'OnceOnly', 'graphic'));
    }

    public function testAutomaticGraphicDefinitionMayUseContentAutomaticStyles(): void
    {
        $context = $this->context();
        $requirement = new StyleRequirement(
            StyleRequirement::KIND_DEFINITION,
            StyleRequirement::SCOPE_AUTOMATIC,
            'graphic',
            StyleRequirement::PART_CONTENT,
            'AutoGraphic',
            'Frame',
            ['style:graphic-properties' => ['draw:stroke' => 'none']]
        );

        (new StyleRequirementMaterializer())->materialize($context, $requirement);

        self::assertNotNull($this->style($context->contentDom(), 'AutoGraphic', 'graphic'));
        self::assertNull($this->style($context->stylesDom(), 'AutoGraphic', 'graphic'));
    }

    public function testCommonGraphicDefinitionCannotTargetContentXml(): void
    {
        $context = $this->context();
        $requirement = new StyleRequirement(
            StyleRequirement::KIND_DEFINITION,
            StyleRequirement::SCOPE_COMMON,
            'graphic',
            StyleRequirement::PART_CONTENT,
            'InvalidCommonGraphic',
            'Frame',
            ['style:graphic-properties' => ['draw:stroke' => 'none']]
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Common graphic styles require styles.xml.');
        (new StyleRequirementMaterializer())->materialize($context, $requirement);
    }

    private const OFFICE_NS = 'urn:oasis:names:tc:opendocument:xmlns:office:1.0';
    private const STYLE_NS = 'urn:oasis:names:tc:opendocument:xmlns:style:1.0';
    private const FO_NS = 'urn:oasis:names:tc:opendocument:xmlns:xsl-fo-compatible:1.0';
    private const DRAW_NS = 'urn:oasis:names:tc:opendocument:xmlns:drawing:1.0';

    /** @param array<string, mixed> $properties */
    private function graphicRequirement(string $name, array $properties): StyleRequirement
    {
        return new StyleRequirement(
            StyleRequirement::KIND_DEFINITION,
            StyleRequirement::SCOPE_COMMON,
            'graphic',
            StyleRequirement::PART_STYLES,
            $name,
            'Frame',
            ['style:graphic-properties' => $properties]
        );
    }

    private function context(): OdtDocumentContext
    {
        return new OdtDocumentContext(
            $this->dom('<office:document-content xmlns:office="' . self::OFFICE_NS . '" xmlns:style="' . self::STYLE_NS . '"><office:automatic-styles/></office:document-content>'),
            $this->dom('<office:document-styles xmlns:office="' . self::OFFICE_NS . '" xmlns:style="' . self::STYLE_NS . '"><office:styles/></office:document-styles>'),
            $this->dom('<office:document-meta xmlns:office="' . self::OFFICE_NS . '"/>')
        );
    }

    private function dom(string $xml): DOMDocument
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        self::assertTrue($dom->loadXML($xml));
        return $dom;
    }

    private function style(DOMDocument $dom, string $name, string $family): ?DOMElement
    {
        foreach ($dom->getElementsByTagNameNS(self::STYLE_NS, 'style') as $style) {
            if ($style->getAttributeNS(self::STYLE_NS, 'name') === $name
                && $style->getAttributeNS(self::STYLE_NS, 'family') === $family) {
                return $style;
            }
        }
        return null;
    }

    private function styleCount(DOMDocument $dom, string $name, string $family): int
    {
        $count = 0;
        foreach ($dom->getElementsByTagNameNS(self::STYLE_NS, 'style') as $style) {
            if ($style->getAttributeNS(self::STYLE_NS, 'name') === $name
                && $style->getAttributeNS(self::STYLE_NS, 'family') === $family) {
                $count++;
            }
        }
        return $count;
    }

    private function property(DOMElement $style, string $localName): DOMElement
    {
        foreach ($style->childNodes as $child) {
            if ($child instanceof DOMElement && $child->localName === $localName) {
                return $child;
            }
        }
        self::fail('Missing style property group: ' . $localName);
    }
}
