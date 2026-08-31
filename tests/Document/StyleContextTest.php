<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Document;

use DOMDocument;
use LogicException;
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

    public function testTwoDocumentsOwnIndependentStyleContexts(): void
    {
        $documentA = $this->documentContext();
        $documentB = $this->documentContext();

        self::assertNotSame($documentA->styleContext(), $documentB->styleContext());

        $documentA->styleContext()->registerParagraphStyle('OnlyA', ['font-weight' => 'bold']);

        self::assertArrayHasKey('OnlyA', $documentA->styleContext()->paragraphStyles());
        self::assertArrayNotHasKey('OnlyA', $documentB->styleContext()->paragraphStyles());
    }

    public function testReplacingCoreDocumentsResetsPendingStyleRequirements(): void
    {
        $document = $this->documentContext();
        $styleContext = $document->styleContext();
        $styleContext->registerParagraphStyle('Temporary', ['font-style' => 'italic']);
        $styleContext->registerTextStyle('TemporaryInline', ['fo:color' => '#123456']);

        $document->replaceCoreDocuments(
            $this->dom('<office:document-content xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0"/>'),
            $this->dom('<office:document-styles xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0"/>'),
            $this->dom('<office:document-meta xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0"/>')
        );

        self::assertSame($styleContext, $document->styleContext());
        self::assertSame([], $document->styleContext()->paragraphStyles());
        self::assertSame([], $document->styleContext()->textStyles());
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
