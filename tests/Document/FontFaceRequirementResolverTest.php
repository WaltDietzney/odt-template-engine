<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Document;

use DOMDocument;
use OdtTemplateEngine\Document\FontFaceRequirement;
use OdtTemplateEngine\Document\FontFaceResolutionConflictException;
use OdtTemplateEngine\Document\FontFaceRequirementResolver;
use OdtTemplateEngine\OdtDocumentContext;
use PHPUnit\Framework\TestCase;

final class FontFaceRequirementResolverTest extends TestCase
{
    public function testMatchingStylesDeclarationIsSatisfiedAndPreserved(): void
    {
        $context = $this->context($this->dom($this->document('styles', '<office:font-face-decls><style:font-face style:name="Face1" svg:font-family="\'Liberation Sans\'" style:font-family-generic="swiss"/></office:font-face-decls>')));
        $before = $context->stylesDom()->saveXML();

        self::assertSame(FontFaceRequirementResolver::STATUS_SATISFIED, $this->resolver()->resolve($context, $this->requirement(FontFaceRequirement::PART_STYLES, 'Face1', 'Liberation Sans')));
        self::assertSame($before, $context->stylesDom()->saveXML());
        self::assertSame(1, $context->stylesDom()->getElementsByTagNameNS('urn:oasis:names:tc:opendocument:xmlns:style:1.0', 'font-face')->length);
    }

    public function testContentDeclarationIsSatisfiedOnlyForContentPart(): void
    {
        $context = $this->context(
            $this->dom($this->document('styles', '')),
            $this->dom($this->document('content', '<office:font-face-decls><style:font-face style:name="Face1" svg:font-family="Liberation Sans"/></office:font-face-decls>'))
        );

        self::assertSame(FontFaceRequirementResolver::STATUS_SATISFIED, $this->resolver()->resolve($context, $this->requirement(FontFaceRequirement::PART_CONTENT, 'Face1', 'Liberation Sans')));
        self::assertSame(FontFaceRequirementResolver::STATUS_MISSING, $this->resolver()->resolve($context, $this->requirement(FontFaceRequirement::PART_STYLES, 'Face1', 'Liberation Sans')));
    }

    public function testMissingIdentityOrDifferentIdentityIsMissing(): void
    {
        $context = $this->context($this->dom($this->document('styles', '<office:font-face-decls><style:font-face style:name="Other" svg:font-family="Liberation Sans"/></office:font-face-decls>')));
        $resolver = $this->resolver();

        self::assertSame(FontFaceRequirementResolver::STATUS_MISSING, $resolver->resolve($context, $this->requirement(FontFaceRequirement::PART_STYLES, 'Face1', 'Liberation Sans')));
        self::assertSame(FontFaceRequirementResolver::STATUS_MISSING, $resolver->resolve($context, $this->requirement(FontFaceRequirement::PART_CONTENT, 'Other', 'Liberation Sans')));
    }

    public function testExistingIdentityWithDifferentFamilyConflicts(): void
    {
        $context = $this->context($this->dom($this->document('styles', '<office:font-face-decls><style:font-face style:name="Face1" svg:font-family="DejaVu Serif"/></office:font-face-decls>')));
        $this->expectException(FontFaceResolutionConflictException::class);
        $this->resolver()->resolve($context, $this->requirement(FontFaceRequirement::PART_STYLES, 'Face1', 'Liberation Sans'));
    }

    public function testExistingIdentityWithMissingFamilyConflicts(): void
    {
        $context = $this->context($this->dom($this->document('styles', '<office:font-face-decls><style:font-face style:name="Face1"/></office:font-face-decls>')));
        $this->expectException(FontFaceResolutionConflictException::class);
        $this->resolver()->resolve($context, $this->requirement(FontFaceRequirement::PART_STYLES, 'Face1', 'Liberation Sans'));
    }

    public function testMultipleEquivalentDeclarationsAreSatisfiedButDifferentFamiliesConflict(): void
    {
        $resolver = $this->resolver();
        $equivalent = $this->context($this->dom($this->document('styles', '<office:font-face-decls><style:font-face style:name="Face1" svg:font-family="Liberation Sans"/><style:font-face style:name="Face1" svg:font-family="\'Liberation Sans\'"/></office:font-face-decls>')));
        self::assertSame(FontFaceRequirementResolver::STATUS_SATISFIED, $resolver->resolve($equivalent, $this->requirement(FontFaceRequirement::PART_STYLES, 'Face1', 'Liberation Sans')));

        $conflicting = $this->context($this->dom($this->document('styles', '<office:font-face-decls><style:font-face style:name="Face1" svg:font-family="Liberation Sans"/><style:font-face style:name="Face1" svg:font-family="DejaVu Serif"/></office:font-face-decls>')));
        $this->expectException(FontFaceResolutionConflictException::class);
        $resolver->resolve($conflicting, $this->requirement(FontFaceRequirement::PART_STYLES, 'Face1', 'Liberation Sans'));
    }

    public function testMissingReturnsOnlyMissingRequirements(): void
    {
        $context = $this->context($this->dom($this->document('styles', '<office:font-face-decls><style:font-face style:name="Present" svg:font-family="Family"/></office:font-face-decls>')));
        $missing = $this->resolver()->missing($context, [
            $this->requirement(FontFaceRequirement::PART_STYLES, 'Present', 'Family'),
            $this->requirement(FontFaceRequirement::PART_STYLES, 'Absent', 'Family'),
        ]);

        self::assertCount(1, $missing);
        self::assertSame('Absent', $missing[0]->fontFaceName());
    }

    public function testReplacementUsesOnlyReplacementDom(): void
    {
        $context = $this->context($this->dom($this->document('styles', '<office:font-face-decls><style:font-face style:name="Old" svg:font-family="Family"/></office:font-face-decls>')));
        $requirement = $this->requirement(FontFaceRequirement::PART_STYLES, 'Old', 'Family');
        self::assertSame(FontFaceRequirementResolver::STATUS_SATISFIED, $this->resolver()->resolve($context, $requirement));

        $context->replaceCoreDocuments(
            $this->dom($this->document('content', '')),
            $this->dom($this->document('styles', '')),
            $this->dom($this->document('meta', ''))
        );

        self::assertSame(FontFaceRequirementResolver::STATUS_MISSING, $this->resolver()->resolve($context, $requirement));
    }

    private function resolver(): FontFaceRequirementResolver
    {
        return new FontFaceRequirementResolver();
    }

    private function requirement(string $part, string $identity, string $family): FontFaceRequirement
    {
        return new FontFaceRequirement($part, $identity, $family);
    }

    private function context(DOMDocument $styles, ?DOMDocument $content = null): OdtDocumentContext
    {
        return new OdtDocumentContext($content ?? $this->dom($this->document('content', '')), $styles, $this->dom($this->document('meta', '')));
    }

    private function document(string $type, string $body): string
    {
        $root = match ($type) {
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
}
