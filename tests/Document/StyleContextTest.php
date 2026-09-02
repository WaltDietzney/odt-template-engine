<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Document;

use DOMDocument;
use LogicException;
use OdtTemplateEngine\Document\StyleRequirement;
use OdtTemplateEngine\OdtDocumentContext;
use OdtTemplateEngine\Style\StyleContext;
use PHPUnit\Framework\TestCase;

final class StyleContextTest extends TestCase
{
    public function testEquivalentParagraphRegistrationIsIdempotent(): void
    {
        $context = new StyleContext();
        $definition = ['font-weight' => 'bold', 'margin-bottom' => '0.2cm'];

        $context->registerParagraphStyle('Heading', $definition);
        $context->registerParagraphStyle('Heading', $definition);

        self::assertSame(['Heading' => $definition], $context->paragraphStyles());
    }

    public function testConflictingParagraphRegistrationIsRejected(): void
    {
        $context = new StyleContext();
        $context->registerParagraphStyle('Heading', ['font-weight' => 'bold']);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Paragraph style "Heading" is already registered with a different definition.');

        $context->registerParagraphStyle('Heading', ['font-weight' => 'normal']);
    }

    public function testEquivalentTextRegistrationIsIdempotent(): void
    {
        $context = new StyleContext();
        $definition = ['fo:color' => '#123456'];

        $context->registerTextStyle('Inline', $definition);
        $context->registerTextStyle('Inline', $definition);

        self::assertSame(['Inline' => $definition], $context->textStyles());
    }

    public function testConflictingTextRegistrationIsRejected(): void
    {
        $context = new StyleContext();
        $context->registerTextStyle('Inline', ['fo:color' => '#123456']);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Text style "Inline" is already registered with a different definition.');

        $context->registerTextStyle('Inline', ['fo:color' => '#654321']);
    }

    public function testFrameRegistrationIsIdempotentAndStored(): void
    {
        $context = new StyleContext();
        $definition = ['fo:background-color' => '#123456'];

        $context->registerFrameStyle('Frame', $definition);
        $context->registerFrameStyle('Frame', $definition);

        self::assertSame(['Frame' => $definition], $context->frameStyles());
    }

    public function testConflictingFrameRegistrationIsRejected(): void
    {
        $context = new StyleContext();
        $context->registerFrameStyle('Frame', ['draw:fill' => 'solid']);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Frame style "Frame" is already registered with a different definition.');

        $context->registerFrameStyle('Frame', ['draw:fill' => 'none']);
    }

    public function testImageRegistrationIsIdempotentAndStored(): void
    {
        $context = new StyleContext();
        $definition = ['style:wrap' => 'none', 'svg:width' => '2cm'];

        $context->registerImageStyle('Image', $definition);
        $context->registerImageStyle('Image', $definition);

        self::assertSame(['Image' => $definition], $context->imageStyles());
    }

    public function testConflictingImageRegistrationIsRejected(): void
    {
        $context = new StyleContext();
        $context->registerImageStyle('Image', ['style:wrap' => 'none']);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Image style "Image" is already registered with a different definition.');

        $context->registerImageStyle('Image', ['style:wrap' => 'left']);
    }

    public function testFillImageRegistrationIsIdempotentAndStored(): void
    {
        $context = new StyleContext();
        $definition = ['path' => '/tmp/photo.png', 'filename' => 'photo.png'];

        $context->registerFillImage('Photo', $definition);
        $context->registerFillImage('Photo', $definition);

        self::assertSame(['Photo' => $definition], $context->fillImages());
    }

    public function testConflictingFillImageRegistrationIsRejected(): void
    {
        $context = new StyleContext();
        $context->registerFillImage('Photo', ['filename' => 'one.png']);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Fill-image declaration "Photo" is already registered with a different definition.');

        $context->registerFillImage('Photo', ['filename' => 'two.png']);
    }

    public function testTwoDocumentsOwnIndependentStyleContexts(): void
    {
        $documentA = $this->documentContext();
        $documentB = $this->documentContext();

        self::assertNotSame($documentA->styleContext(), $documentB->styleContext());

        $documentA->styleContext()->registerParagraphStyle('OnlyA', ['font-weight' => 'bold']);

        $documentA->styleContext()->registerFrameStyle('OnlyAFrame', ['draw:fill' => 'solid']);
        $documentA->styleContext()->registerImageStyle('OnlyAImage', ['style:wrap' => 'none']);
        $documentA->styleContext()->registerFillImage('OnlyAFill', ['filename' => 'a.png']);

        self::assertArrayHasKey('OnlyA', $documentA->styleContext()->paragraphStyles());
        self::assertArrayNotHasKey('OnlyA', $documentB->styleContext()->paragraphStyles());
        self::assertArrayHasKey('OnlyAFrame', $documentA->styleContext()->frameStyles());
        self::assertArrayNotHasKey('OnlyAFrame', $documentB->styleContext()->frameStyles());
        self::assertArrayHasKey('OnlyAImage', $documentA->styleContext()->imageStyles());
        self::assertArrayNotHasKey('OnlyAImage', $documentB->styleContext()->imageStyles());
        self::assertArrayHasKey('OnlyAFill', $documentA->styleContext()->fillImages());
        self::assertArrayNotHasKey('OnlyAFill', $documentB->styleContext()->fillImages());
    }

    public function testReplacingCoreDocumentsResetsPendingStyleRequirements(): void
    {
        $document = $this->documentContext();
        $styleContext = $document->styleContext();
        $styleContext->registerParagraphStyle('Temporary', ['font-style' => 'italic']);
        $styleContext->registerTextStyle('TemporaryInline', ['fo:color' => '#123456']);
        $styleContext->registerFrameStyle('TemporaryFrame', ['draw:fill' => 'solid']);
        $styleContext->registerImageStyle('TemporaryImage', ['style:wrap' => 'none']);
        $styleContext->registerFillImage('TemporaryFill', ['filename' => 'temporary.png']);

        $document->replaceCoreDocuments(
            $this->dom('<office:document-content xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0"/>'),
            $this->dom('<office:document-styles xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0"/>'),
            $this->dom('<office:document-meta xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0"/>')
        );

        self::assertSame($styleContext, $document->styleContext());
        self::assertSame([], $document->styleContext()->paragraphStyles());
        self::assertSame([], $document->styleContext()->textStyles());
        self::assertSame([], $document->styleContext()->frameStyles());
        self::assertSame([], $document->styleContext()->imageStyles());
        self::assertSame([], $document->styleContext()->fillImages());
    }

    public function testSemanticDefinitionRegistrationPreservesAllDimensions(): void
    {
        $context = new StyleContext();
        $requirement = $this->paragraphRequirement('SemanticHeading', [
            'style:paragraph-properties' => ['fo:text-align' => 'center'],
        ]);

        $context->registerRequirement($requirement);

        self::assertSame([$this->semanticKey($requirement) => $requirement], $context->semanticDefinitions());
        self::assertSame([], $context->semanticReferences());
    }

    public function testEquivalentSemanticDefinitionsAreIdempotent(): void
    {
        $context = new StyleContext();
        $first = $this->paragraphRequirement('Same', ['style:paragraph-properties' => ['fo:text-align' => 'center']]);
        $second = $this->paragraphRequirement('Same', ['style:paragraph-properties' => ['fo:text-align' => 'center']]);

        $context->registerRequirement($first);
        $context->registerRequirement($second);

        self::assertCount(1, $context->semanticDefinitions());
    }

    public function testConflictingSemanticDefinitionsAreRejectedBySemanticIdentity(): void
    {
        $context = new StyleContext();
        $context->registerRequirement($this->paragraphRequirement('Same', [
            'style:paragraph-properties' => ['fo:text-align' => 'center'],
        ]));

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Paragraph style "Same" is already registered with a different definition');
        $context->registerRequirement($this->paragraphRequirement('Same', [
            'style:paragraph-properties' => ['fo:text-align' => 'right'],
        ]));
    }

    public function testUnresolvedReferenceIsTrackedWithoutInventingDefinition(): void
    {
        $context = new StyleContext();
        $reference = new StyleRequirement(StyleRequirement::KIND_REFERENCE, null, 'paragraph', null, 'Missing');

        $context->registerRequirement($reference);

        self::assertSame([$reference], $context->semanticReferences());
        self::assertSame([], $context->semanticDefinitions());
        self::assertSame([$reference], $context->unresolvedReferences());
    }

    public function testReferenceResolvesToDocumentLocalDefinitionInEitherOrder(): void
    {
        $definition = $this->paragraphRequirement('LocalHeading', [
            'style:paragraph-properties' => ['fo:text-align' => 'center'],
        ]);
        $reference = new StyleRequirement(StyleRequirement::KIND_REFERENCE, null, 'paragraph', null, 'LocalHeading');

        $first = new StyleContext();
        $first->registerRequirement($reference);
        self::assertSame([], $first->resolvedReferences());
        $first->registerRequirement($definition);

        $second = new StyleContext();
        $second->registerRequirement($definition);
        $second->registerRequirement($reference);

        self::assertSame('document-local', $first->referenceResolution($reference));
        self::assertSame('document-local', $second->referenceResolution($reference));
        self::assertSame([], $first->unresolvedReferences());
        self::assertSame([], $second->unresolvedReferences());
    }

    public function testMultipleDocumentLocalCandidatesAreAmbiguousRegardlessOfRegistrationOrder(): void
    {
        $common = $this->paragraphRequirement('Foo', [
            'style:paragraph-properties' => ['fo:text-align' => 'center'],
        ]);
        $automatic = new StyleRequirement(
            StyleRequirement::KIND_DEFINITION,
            StyleRequirement::SCOPE_AUTOMATIC,
            'paragraph',
            StyleRequirement::PART_CONTENT,
            'Foo',
            null,
            ['style:paragraph-properties' => ['fo:text-align' => 'right']]
        );
        $reference = new StyleRequirement(StyleRequirement::KIND_REFERENCE, null, 'paragraph', null, 'Foo');

        $first = new StyleContext();
        $first->registerRequirement($common);
        $first->registerRequirement($automatic);
        $first->registerRequirement($reference);

        $second = new StyleContext();
        $second->registerRequirement($automatic);
        $second->registerRequirement($common);
        $second->registerRequirement($reference);

        self::assertSame([$reference], $first->ambiguousReferences());
        self::assertSame([$reference], $second->ambiguousReferences());
        self::assertCount(2, $first->ambiguousReferenceCandidates($reference));
        self::assertCount(2, $second->ambiguousReferenceCandidates($reference));
        self::assertNull($first->referenceResolution($reference));
        self::assertNull($second->referenceResolution($reference));
        self::assertSame([], $first->unresolvedReferences());
        self::assertSame([], $second->unresolvedReferences());
    }

    public function testNarrowedReferenceSelectsOneOfMultipleSemanticDefinitions(): void
    {
        $common = $this->paragraphRequirement('Foo', [
            'style:paragraph-properties' => ['fo:text-align' => 'center'],
        ]);
        $automatic = new StyleRequirement(
            StyleRequirement::KIND_DEFINITION,
            StyleRequirement::SCOPE_AUTOMATIC,
            'paragraph',
            StyleRequirement::PART_CONTENT,
            'Foo',
            null,
            ['style:paragraph-properties' => ['fo:text-align' => 'right']]
        );
        $reference = new StyleRequirement(
            StyleRequirement::KIND_REFERENCE,
            StyleRequirement::SCOPE_COMMON,
            'paragraph',
            StyleRequirement::PART_STYLES,
            'Foo'
        );
        $context = new StyleContext();
        $context->registerRequirement($common);
        $context->registerRequirement($automatic);
        $context->registerRequirement($reference);

        self::assertSame([], $context->ambiguousReferences());
        self::assertSame('document-local', $context->referenceResolution($reference));
        self::assertSame($common, $context->referenceCandidate($reference));
    }

    public function testReferenceRecognizesExistingStylesAndContentAutomaticStyles(): void
    {
        $styles = $this->dom('<office:document-styles xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0" xmlns:style="urn:oasis:names:tc:opendocument:xmlns:style:1.0"><office:styles><style:style style:name="Existing" style:family="paragraph"/></office:styles></office:document-styles>');
        $content = $this->dom('<office:document-content xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0" xmlns:style="urn:oasis:names:tc:opendocument:xmlns:style:1.0"><office:automatic-styles><style:style style:name="Automatic" style:family="text"/></office:automatic-styles></office:document-content>');
        $context = new StyleContext($content, $styles);
        $existing = new StyleRequirement(StyleRequirement::KIND_REFERENCE, null, 'paragraph', null, 'Existing');
        $automatic = new StyleRequirement(StyleRequirement::KIND_REFERENCE, null, 'text', null, 'Automatic');

        $context->registerRequirement($existing);
        $context->registerRequirement($automatic);

        self::assertSame('document', $context->referenceResolution($existing));
        self::assertSame('document', $context->referenceResolution($automatic));
    }

    public function testMultipleExistingDocumentStylesAreAmbiguous(): void
    {
        $styles = $this->dom('<office:document-styles xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0" xmlns:style="urn:oasis:names:tc:opendocument:xmlns:style:1.0"><office:styles><style:style style:name="Duplicate" style:family="paragraph"/><style:style style:name="Duplicate" style:family="paragraph"/></office:styles></office:document-styles>');
        $context = new StyleContext(null, $styles);
        $reference = new StyleRequirement(StyleRequirement::KIND_REFERENCE, null, 'paragraph', null, 'Duplicate');

        $context->registerRequirement($reference);

        self::assertSame([$reference], $context->ambiguousReferences());
        self::assertCount(2, $context->ambiguousReferenceCandidates($reference));
        self::assertNull($context->referenceResolution($reference));
    }

    public function testExistingDocumentCandidateWinsOverLowerPriorityCandidates(): void
    {
        $name = 'Precedence_' . bin2hex(random_bytes(4));
        \OdtTemplateEngine\Utils\StyleMapper::registerParagraphStyle($name, ['text-align' => 'left']);
        $styles = $this->dom('<office:document-styles xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0" xmlns:style="urn:oasis:names:tc:opendocument:xmlns:style:1.0"><office:styles><style:style style:name="' . $name . '" style:family="paragraph"/></office:styles></office:document-styles>');
        $context = new StyleContext(null, $styles);
        $local = $this->paragraphRequirement($name, []);
        $reference = new StyleRequirement(StyleRequirement::KIND_REFERENCE, null, 'paragraph', null, $name);

        $context->registerRequirement($local);
        $context->registerRequirement($reference);

        self::assertSame('document', $context->referenceResolution($reference));
        self::assertSame('document', $context->referenceCandidate($reference)['source']);
        self::assertSame(StyleRequirement::PART_STYLES, $context->referenceCandidate($reference)['documentPart']);
    }

    public function testReferenceCanUseBoundedLegacyParagraphCompatibilitySource(): void
    {
        $name = 'LegacySemantic_' . bin2hex(random_bytes(4));
        \OdtTemplateEngine\Utils\StyleMapper::registerParagraphStyle($name, ['text-align' => 'center']);
        $context = new StyleContext();
        $reference = new StyleRequirement(StyleRequirement::KIND_REFERENCE, null, 'paragraph', null, $name);

        $context->registerRequirement($reference);

        self::assertSame('legacy', $context->referenceResolution($reference));
    }

    public function testSemanticStateIsResetWhenCoreDocumentsAreReplaced(): void
    {
        $document = $this->documentContext();
        $reference = new StyleRequirement(StyleRequirement::KIND_REFERENCE, null, 'paragraph', null, 'Temporary');
        $document->styleContext()->registerRequirement($reference);

        $document->replaceCoreDocuments(
            $this->dom('<office:document-content xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0"/>'),
            $this->dom('<office:document-styles xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0"/>'),
            $this->dom('<office:document-meta xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0"/>')
        );

        self::assertSame([], $document->styleContext()->semanticDefinitions());
        self::assertSame([], $document->styleContext()->semanticReferences());
        self::assertSame([], $document->styleContext()->unresolvedReferences());
    }

    public function testSemanticRequirementsAreIsolatedBetweenDocumentContexts(): void
    {
        $documentA = $this->documentContext();
        $documentB = $this->documentContext();
        $definition = $this->paragraphRequirement('OnlyA', []);

        $documentA->styleContext()->registerRequirement($definition);

        self::assertArrayHasKey($this->semanticKey($definition), $documentA->styleContext()->semanticDefinitions());
        self::assertArrayNotHasKey($this->semanticKey($definition), $documentB->styleContext()->semanticDefinitions());
    }

    /** @param array<string, array<string, mixed>> $groups */
    private function paragraphRequirement(string $name, array $groups): StyleRequirement
    {
        return new StyleRequirement(
            StyleRequirement::KIND_DEFINITION,
            StyleRequirement::SCOPE_COMMON,
            'paragraph',
            StyleRequirement::PART_STYLES,
            $name,
            'Standard',
            $groups
        );
    }

    private function semanticKey(StyleRequirement $requirement): string
    {
        return implode("\0", [$requirement->family(), $requirement->name(), $requirement->scope(), $requirement->documentPart()]);
    }

    private function documentContext(): OdtDocumentContext
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
