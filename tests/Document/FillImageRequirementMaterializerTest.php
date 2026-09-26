<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Document;

use DOMDocument;
use DOMElement;
use DOMXPath;
use InvalidArgumentException;
use OdtTemplateEngine\Document\FillImageRequirement;
use OdtTemplateEngine\Document\FillImageRequirementMaterializer;
use OdtTemplateEngine\OdtDocumentContext;
use PHPUnit\Framework\TestCase;

final class FillImageRequirementMaterializerTest extends TestCase
{
    private const DRAW_NAMESPACE = 'urn:oasis:names:tc:opendocument:xmlns:drawing:1.0';
    private const XLINK_NAMESPACE = 'http://www.w3.org/1999/xlink';

    public function testMaterializesNativeFillImageDeclarationIntoOfficeStyles(): void
    {
        $context = $this->context();
        $requirement = new FillImageRequirement(
            FillImageRequirement::PART_STYLES,
            'PhotoFill',
            'Pictures/photo.png'
        );

        (new FillImageRequirementMaterializer())->materialize($context, $requirement);

        $node = $this->declaration($context->stylesDom(), 'PhotoFill');
        self::assertInstanceOf(DOMElement::class, $node);
        self::assertSame('PhotoFill', $node->getAttributeNS(self::DRAW_NAMESPACE, 'name'));
        self::assertSame('Pictures/photo.png', $node->getAttributeNS(self::XLINK_NAMESPACE, 'href'));
        self::assertSame('simple', $node->getAttributeNS(self::XLINK_NAMESPACE, 'type'));
        self::assertSame('embed', $node->getAttributeNS(self::XLINK_NAMESPACE, 'show'));
        self::assertSame('onLoad', $node->getAttributeNS(self::XLINK_NAMESPACE, 'actuate'));
    }

    public function testRepeatedMaterializationIsIdempotent(): void
    {
        $context = $this->context();
        $requirement = new FillImageRequirement(
            FillImageRequirement::PART_STYLES,
            'PhotoFill',
            'Pictures/photo.png'
        );
        $materializer = new FillImageRequirementMaterializer();

        $materializer->materialize($context, $requirement);
        $materializer->materialize($context, $requirement);

        self::assertSame(1, $this->declarationCount($context->stylesDom(), 'PhotoFill'));
    }

    public function testExistingTargetDeclarationIsAuthoritativeEvenWithDifferentHref(): void
    {
        $context = $this->context();
        $styles = $context->stylesDom();
        $xpath = $this->xpath($styles);
        $officeStyles = $xpath->query('/office:document-styles/office:styles')->item(0);
        self::assertInstanceOf(DOMElement::class, $officeStyles);

        $existing = $styles->createElementNS(self::DRAW_NAMESPACE, 'draw:fill-image');
        $existing->setAttributeNS(self::DRAW_NAMESPACE, 'draw:name', 'PhotoFill');
        $existing->setAttributeNS(self::XLINK_NAMESPACE, 'xlink:href', 'Pictures/authored.png');
        $officeStyles->appendChild($existing);

        (new FillImageRequirementMaterializer())->materialize(
            $context,
            new FillImageRequirement(
                FillImageRequirement::PART_STYLES,
                'PhotoFill',
                'Pictures/generated.png'
            )
        );

        self::assertSame(1, $this->declarationCount($styles, 'PhotoFill'));
        $node = $this->declaration($styles, 'PhotoFill');
        self::assertInstanceOf(DOMElement::class, $node);
        self::assertSame('Pictures/authored.png', $node->getAttributeNS(self::XLINK_NAMESPACE, 'href'));
    }

    public function testRequiresOfficeStylesContainer(): void
    {
        $styles = new DOMDocument('1.0', 'UTF-8');
        $styles->loadXML(
            '<office:document-styles '
            . 'xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0" '
            . 'xmlns:draw="urn:oasis:names:tc:opendocument:xmlns:drawing:1.0" '
            . 'xmlns:xlink="http://www.w3.org/1999/xlink"/>'
        );
        $context = new OdtDocumentContext($this->contentDom(), $styles, $this->metaDom());

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('requires office:styles');

        (new FillImageRequirementMaterializer())->materialize(
            $context,
            new FillImageRequirement(
                FillImageRequirement::PART_STYLES,
                'PhotoFill',
                'Pictures/photo.png'
            )
        );
    }

    private function context(): OdtDocumentContext
    {
        $styles = new DOMDocument('1.0', 'UTF-8');
        $styles->loadXML(
            '<office:document-styles '
            . 'xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0" '
            . 'xmlns:draw="urn:oasis:names:tc:opendocument:xmlns:drawing:1.0" '
            . 'xmlns:xlink="http://www.w3.org/1999/xlink">'
            . '<office:styles/>'
            . '<office:automatic-styles/>'
            . '</office:document-styles>'
        );

        return new OdtDocumentContext($this->contentDom(), $styles, $this->metaDom());
    }

    private function contentDom(): DOMDocument
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->loadXML(
            '<office:document-content '
            . 'xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0">'
            . '<office:body><office:text/></office:body>'
            . '</office:document-content>'
        );

        return $dom;
    }

    private function metaDom(): DOMDocument
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->loadXML(
            '<office:document-meta '
            . 'xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0">'
            . '<office:meta/>'
            . '</office:document-meta>'
        );

        return $dom;
    }

    private function xpath(DOMDocument $dom): DOMXPath
    {
        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('office', 'urn:oasis:names:tc:opendocument:xmlns:office:1.0');
        $xpath->registerNamespace('draw', self::DRAW_NAMESPACE);

        return $xpath;
    }

    private function declaration(DOMDocument $dom, string $name): ?DOMElement
    {
        $node = $this->xpath($dom)->query('//draw:fill-image[@draw:name="' . $name . '"]')->item(0);

        return $node instanceof DOMElement ? $node : null;
    }

    private function declarationCount(DOMDocument $dom, string $name): int
    {
        return $this->xpath($dom)->query('//draw:fill-image[@draw:name="' . $name . '"]')->length;
    }
}
