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
