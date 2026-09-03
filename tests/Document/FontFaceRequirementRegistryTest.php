<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Document;

use DOMDocument;
use OdtTemplateEngine\Document\FontFaceRequirement;
use OdtTemplateEngine\Document\FontFaceRequirementConflictException;
use OdtTemplateEngine\OdtDocumentContext;
use PHPUnit\Framework\TestCase;

final class FontFaceRequirementRegistryTest extends TestCase
{
    public function testRequirementPreservesIdentityAndFamilyIndependently(): void
    {
        $requirement = new FontFaceRequirement(
            FontFaceRequirement::PART_STYLES,
            'Liberation Sans1',
            'Liberation Sans'
        );

        self::assertSame(FontFaceRequirement::PART_STYLES, $requirement->documentPart());
        self::assertSame('Liberation Sans1', $requirement->fontFaceName());
        self::assertSame('Liberation Sans', $requirement->fontFamily());
        self::assertNotSame($requirement->fontFaceName(), $requirement->fontFamily());
    }

    public function testEquivalentDuplicateRegistrationIsIdempotent(): void
    {
        $context = $this->context();
        $requirement = $this->requirement(FontFaceRequirement::PART_STYLES, 'Shared', 'Liberation Sans');

        $context->registerFontFaceRequirement($requirement);
        $context->registerFontFaceRequirement(new FontFaceRequirement(
            FontFaceRequirement::PART_STYLES,
            'Shared',
            'Liberation Sans'
        ));

        self::assertSame([$requirement], $context->fontFaceRequirements()->requirements());
    }

    public function testSameIdentityAndDifferentFamilyInOnePartConflicts(): void
    {
        $context = $this->context();
        $context->registerFontFaceRequirement($this->requirement(
            FontFaceRequirement::PART_STYLES,
            'Shared',
            'Liberation Sans'
        ));

        $this->expectException(FontFaceRequirementConflictException::class);
        $this->expectExceptionMessage('Font-face identity "Shared" in styles.xml is already registered for a different family.');
        $context->registerFontFaceRequirement($this->requirement(
            FontFaceRequirement::PART_STYLES,
            'Shared',
            'DejaVu Serif'
        ));
    }

    public function testSameIdentityInDifferentDocumentPartsIsIndependent(): void
    {
        $context = $this->context();
        $context->registerFontFaceRequirement($this->requirement(
            FontFaceRequirement::PART_STYLES,
            'Shared',
            'Liberation Sans'
        ));
        $context->registerFontFaceRequirement($this->requirement(
            FontFaceRequirement::PART_CONTENT,
            'Shared',
            'DejaVu Serif'
        ));

        self::assertCount(2, $context->fontFaceRequirements()->requirements());
        self::assertSame(
            [FontFaceRequirement::PART_STYLES, FontFaceRequirement::PART_CONTENT],
            array_map(
                static fn (FontFaceRequirement $requirement): string => $requirement->documentPart(),
                $context->fontFaceRequirements()->requirements()
            )
        );
    }

    public function testDifferentIdentitiesWithSameFamilyAreAllowed(): void
    {
        $context = $this->context();
        $context->registerFontFaceRequirement($this->requirement(
            FontFaceRequirement::PART_STYLES,
            'Liberation Sans1',
            'Liberation Sans'
        ));
        $context->registerFontFaceRequirement($this->requirement(
            FontFaceRequirement::PART_STYLES,
            'Liberation Sans2',
            'Liberation Sans'
        ));

        self::assertCount(2, $context->fontFaceRequirements()->requirements());
    }

    public function testReplacingCoreDocumentsClearsPendingFontFaceRequirements(): void
    {
        $context = $this->context();
        $context->registerFontFaceRequirement($this->requirement(
            FontFaceRequirement::PART_STYLES,
            'Temporary',
            'Liberation Sans'
        ));

        $context->replaceCoreDocuments(
            $this->dom('<office:document-content xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0"/>'),
            $this->dom('<office:document-styles xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0"/>'),
            $this->dom('<office:document-meta xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0"/>')
        );

        self::assertSame([], $context->fontFaceRequirements()->requirements());
    }

    public function testSeparateDocumentContextsDoNotSharePendingFontFaceRequirements(): void
    {
        $contextA = $this->context();
        $contextB = $this->context();
        $contextA->registerFontFaceRequirement($this->requirement(
            FontFaceRequirement::PART_STYLES,
            'OnlyA',
            'Liberation Sans'
        ));

        self::assertCount(1, $contextA->fontFaceRequirements()->requirements());
        self::assertSame([], $contextB->fontFaceRequirements()->requirements());
        self::assertNotSame($contextA->fontFaceRequirements(), $contextB->fontFaceRequirements());
    }

    private function requirement(string $part, string $identity, string $family): FontFaceRequirement
    {
        return new FontFaceRequirement($part, $identity, $family);
    }

    private function context(): OdtDocumentContext
    {
        return new OdtDocumentContext(
            $this->dom('<office:document-content xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0"/>'),
            $this->dom('<office:document-styles xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0"/>'),
            $this->dom('<office:document-meta xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0"/>')
        );
    }

    private function dom(string $xml): DOMDocument
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        self::assertTrue($dom->loadXML($xml));

        return $dom;
    }
}
