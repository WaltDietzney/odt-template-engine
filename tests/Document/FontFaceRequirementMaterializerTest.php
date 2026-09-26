<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Document;

use DOMDocument;
use DOMXPath;
use OdtTemplateEngine\Document\FontFaceRequirement;
use OdtTemplateEngine\Document\FontFaceRequirementMaterializer;
use OdtTemplateEngine\Document\FontFaceResolutionConflictException;
use OdtTemplateEngine\Document\FontFaceRequirementResolver;
use OdtTemplateEngine\OdtDocumentContext;
use PHPUnit\Framework\TestCase;

final class FontFaceRequirementMaterializerTest extends TestCase
{
    public function testMissingStylesRequirementIsMaterializedWithIndependentIdentityAndFamily(): void
    {
        $context = $this->context($this->document('styles', '<office:styles/>'));
        $requirement = $this->requirement(FontFaceRequirement::PART_STYLES, 'Liberation Sans1', 'Liberation Sans');

        $this->materializer()->materialize($context, $requirement);

        $fontFace = $this->fontFace($context->stylesDom(), 'Liberation Sans1');
        self::assertNotNull($fontFace);
        self::assertSame('Liberation Sans', $fontFace->getAttributeNS($this->svgNamespace(), 'font-family'));
        self::assertSame(1, $this->fontFaceCount($context->stylesDom(), 'Liberation Sans1'));
    }

    public function testMissingContentRequirementIsMaterializedOnlyInContent(): void
    {
        $context = new OdtDocumentContext(
            $this->dom($this->document('content', '<office:body/>')),
            $this->dom($this->document('styles', '<office:styles/>')),
            $this->dom($this->document('meta', ''))
        );
        $requirement = $this->requirement(FontFaceRequirement::PART_CONTENT, 'ContentFace', 'Content Family');

        $this->materializer()->materialize($context, $requirement);

        self::assertSame(1, $this->fontFaceCount($context->contentDom(), 'ContentFace'));
        self::assertSame(0, $this->fontFaceCount($context->stylesDom(), 'ContentFace'));
    }

    public function testExistingEquivalentDeclarationIsUntouchedAndMaterializationIsIdempotent(): void
    {
        $context = $this->context($this->document('styles', '<office:font-face-decls><style:font-face style:name="Face" svg:font-family="\'Family\'" style:font-pitch="variable"/></office:font-face-decls><office:styles/>'));
        $before = $context->stylesDom()->saveXML();
        $requirement = $this->requirement(FontFaceRequirement::PART_STYLES, 'Face', 'Family');

        $this->materializer()->materialize($context, $requirement);
        $this->materializer()->materialize($context, $requirement);

        self::assertSame($before, $context->stylesDom()->saveXML());
        self::assertSame(1, $this->fontFaceCount($context->stylesDom(), 'Face'));
    }

    public function testConflictingExistingDeclarationIsNotChanged(): void
    {
        $context = $this->context($this->document('styles', '<office:font-face-decls><style:font-face style:name="Face" svg:font-family="Other Family"/></office:font-face-decls>'));
        $before = $context->stylesDom()->saveXML();
        $this->expectException(FontFaceResolutionConflictException::class);

        try {
            $this->materializer()->materialize($context, $this->requirement(FontFaceRequirement::PART_STYLES, 'Face', 'Family'));
        } finally {
            self::assertSame($before, $context->stylesDom()->saveXML());
        }
    }

    public function testBothDocumentPartsMayReceiveIndependentDeclarations(): void
    {
        $context = $this->context($this->document('styles', '<office:styles/>'));
        $materializer = $this->materializer();
        $materializer->materialize($context, $this->requirement(FontFaceRequirement::PART_STYLES, 'Shared', 'Styles Family'));
        $materializer->materialize($context, $this->requirement(FontFaceRequirement::PART_CONTENT, 'Shared', 'Content Family'));

        self::assertSame(1, $this->fontFaceCount($context->stylesDom(), 'Shared'));
        self::assertSame(1, $this->fontFaceCount($context->contentDom(), 'Shared'));
    }

    public function testResolverIsSatisfiedAfterMaterialization(): void
    {
        $context = $this->context($this->document('styles', '<office:styles/>'));
        $requirement = $this->requirement(FontFaceRequirement::PART_STYLES, 'Face', 'Family');
        $materializer = $this->materializer();
        $materializer->materialize($context, $requirement);

        self::assertSame(FontFaceRequirementResolver::STATUS_SATISFIED, (new FontFaceRequirementResolver())->resolve($context, $requirement));
    }

    private function materializer(): FontFaceRequirementMaterializer
    {
        return new FontFaceRequirementMaterializer();
    }

    private function requirement(string $part, string $identity, string $family): FontFaceRequirement
    {
        return new FontFaceRequirement($part, $identity, $family);
    }

    private function context(string $stylesXml): OdtDocumentContext
    {
        return new OdtDocumentContext($this->dom($this->document('content', '<office:body/>')), $this->dom($stylesXml), $this->dom($this->document('meta', '')));
    }

    private function document(string $part, string $body): string
    {
        $root = match ($part) {
            'content' => 'document-content',
            'styles' => 'document-styles',
            default => 'document-meta',
        };
        return '<office:' . $root . ' xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0" xmlns:style="urn:oasis:names:tc:opendocument:xmlns:style:1.0" xmlns:svg="urn:oasis:names:tc:opendocument:xmlns:svg-compatible:1.0">' . $body . '</office:' . $root . '>';
    }

    private function dom(string $xml): DOMDocument
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        self::assertTrue($dom->loadXML($xml));
        return $dom;
    }

    private function fontFace(DOMDocument $dom, string $identity): ?\DOMElement
    {
        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('office', 'urn:oasis:names:tc:opendocument:xmlns:office:1.0');
        $xpath->registerNamespace('style', 'urn:oasis:names:tc:opendocument:xmlns:style:1.0');
        return $xpath->query('//office:font-face-decls/style:font-face[@style:name="' . $identity . '"]')->item(0);
    }

    private function fontFaceCount(DOMDocument $dom, string $identity): int
    {
        $fontFace = $this->fontFace($dom, $identity);
        if ($fontFace === null || !$fontFace->parentNode) {
            return 0;
        }
        $count = 0;
        foreach ($fontFace->parentNode->getElementsByTagNameNS($this->styleNamespace(), 'font-face') as $candidate) {
            if ($candidate->getAttributeNS($this->styleNamespace(), 'name') === $identity) {
                $count++;
            }
        }

        return $count;
    }

    private function styleNamespace(): string
    {
        return 'urn:oasis:names:tc:opendocument:xmlns:style:1.0';
    }

    private function svgNamespace(): string
    {
        return 'urn:oasis:names:tc:opendocument:xmlns:svg-compatible:1.0';
    }
}
