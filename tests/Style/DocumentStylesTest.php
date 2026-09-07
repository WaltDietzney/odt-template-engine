<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Style;

use DOMDocument;
use DOMElement;
use DOMXPath;
use LogicException;
use OdtTemplateEngine\OdtDocumentContext;
use OdtTemplateEngine\Style\DocumentStyles;
use PHPUnit\Framework\TestCase;

final class DocumentStylesTest extends TestCase
{
    public function testDefineParagraphMaterializesDocumentLocalSemanticStyle(): void
    {
        $context = $this->context();
        $styles = new DocumentStyles(static fn (): OdtDocumentContext => $context);

        $styles->defineParagraph('CVEntryTitle', [
            'margin-top' => '0.1cm',
            'margin-bottom' => '0.03cm',
            'bold' => true,
            'color' => '#123456',
        ]);

        $style = $this->paragraphStyle($context->stylesDom(), 'CVEntryTitle');
        self::assertInstanceOf(DOMElement::class, $style);
        self::assertSame('Standard', $style->getAttribute('style:parent-style-name'));

        $paragraphProperties = $style->getElementsByTagNameNS(
            'urn:oasis:names:tc:opendocument:xmlns:style:1.0',
            'paragraph-properties'
        )->item(0);
        self::assertInstanceOf(DOMElement::class, $paragraphProperties);
        self::assertSame('0.1cm', $paragraphProperties->getAttribute('fo:margin-top'));
        self::assertSame('0.03cm', $paragraphProperties->getAttribute('fo:margin-bottom'));

        $textProperties = $style->getElementsByTagNameNS(
            'urn:oasis:names:tc:opendocument:xmlns:style:1.0',
            'text-properties'
        )->item(0);
        self::assertInstanceOf(DOMElement::class, $textProperties);
        self::assertSame('bold', $textProperties->getAttribute('fo:font-weight'));
        self::assertSame('#123456', $textProperties->getAttribute('fo:color'));

        self::assertCount(1, $context->styleContext()->semanticDefinitions());
    }

    public function testEquivalentDefinitionIsIdempotentAndConflictFailsExplicitly(): void
    {
        $context = $this->context();
        $styles = new DocumentStyles(static fn (): OdtDocumentContext => $context);

        $styles->defineParagraph('StableStyle', ['margin-top' => '0.1cm']);
        $styles->defineParagraph('StableStyle', ['margin-top' => '0.1cm']);

        self::assertCount(1, $context->styleContext()->semanticDefinitions());
        self::assertSame(1, $this->paragraphStyleCount($context->stylesDom(), 'StableStyle'));

        $this->expectException(LogicException::class);
        $styles->defineParagraph('StableStyle', ['margin-top' => '0.2cm']);
    }

    public function testAuthoredParagraphStyleIsNotOverwritten(): void
    {
        $context = $this->context(<<<'XML'
<office:document-styles
    xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0"
    xmlns:style="urn:oasis:names:tc:opendocument:xmlns:style:1.0"
    xmlns:fo="urn:oasis:names:tc:opendocument:xmlns:xsl-fo-compatible:1.0">
    <office:font-face-decls/>
    <office:styles>
        <style:style style:name="AuthoredStyle" style:family="paragraph" style:parent-style-name="Standard">
            <style:paragraph-properties fo:margin-top="9cm"/>
        </style:style>
    </office:styles>
    <office:automatic-styles/>
    <office:master-styles/>
</office:document-styles>
XML);
        $styles = new DocumentStyles(static fn (): OdtDocumentContext => $context);

        $styles->defineParagraph('AuthoredStyle', ['margin-top' => '0.1cm']);

        self::assertSame(1, $this->paragraphStyleCount($context->stylesDom(), 'AuthoredStyle'));
        $style = $this->paragraphStyle($context->stylesDom(), 'AuthoredStyle');
        self::assertInstanceOf(DOMElement::class, $style);
        $properties = $style->getElementsByTagNameNS(
            'urn:oasis:names:tc:opendocument:xmlns:style:1.0',
            'paragraph-properties'
        )->item(0);
        self::assertInstanceOf(DOMElement::class, $properties);
        self::assertSame('9cm', $properties->getAttribute('fo:margin-top'));
    }

    public function testRetainedFacadeUsesResetCurrentDocumentState(): void
    {
        $context = $this->context();
        $styles = new DocumentStyles(static fn (): OdtDocumentContext => $context);

        $styles->defineParagraph('LifecycleStyle', ['margin-top' => '0.1cm']);
        self::assertInstanceOf(DOMElement::class, $this->paragraphStyle($context->stylesDom(), 'LifecycleStyle'));

        $context->replaceCoreDocuments(
            $this->contentDom(),
            $this->stylesDom(),
            $this->metaDom()
        );

        self::assertSame([], $context->styleContext()->semanticDefinitions());
        self::assertNull($this->paragraphStyle($context->stylesDom(), 'LifecycleStyle'));

        $styles->defineParagraph('LifecycleStyle', ['margin-top' => '0.2cm']);

        $style = $this->paragraphStyle($context->stylesDom(), 'LifecycleStyle');
        self::assertInstanceOf(DOMElement::class, $style);
        $properties = $style->getElementsByTagNameNS(
            'urn:oasis:names:tc:opendocument:xmlns:style:1.0',
            'paragraph-properties'
        )->item(0);
        self::assertInstanceOf(DOMElement::class, $properties);
        self::assertSame('0.2cm', $properties->getAttribute('fo:margin-top'));
    }

    public function testFontFamilyDependencyIsMaterializedWithParagraphDefinition(): void
    {
        $context = $this->context();
        $styles = new DocumentStyles(static fn (): OdtDocumentContext => $context);

        $styles->defineParagraph('FontStyle', ['font-family' => 'Liberation Sans']);

        $xpath = new DOMXPath($context->stylesDom());
        $xpath->registerNamespace('style', 'urn:oasis:names:tc:opendocument:xmlns:style:1.0');
        $fontFace = $xpath->query('//style:font-face[@style:name="Liberation Sans"]')->item(0);
        self::assertInstanceOf(DOMElement::class, $fontFace);
    }

    private function context(?string $stylesXml = null): OdtDocumentContext
    {
        return new OdtDocumentContext(
            $this->contentDom(),
            $this->stylesDom($stylesXml),
            $this->metaDom()
        );
    }

    private function contentDom(): DOMDocument
    {
        return $this->dom(<<<'XML'
<office:document-content
    xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0"
    xmlns:style="urn:oasis:names:tc:opendocument:xmlns:style:1.0"
    xmlns:text="urn:oasis:names:tc:opendocument:xmlns:text:1.0">
    <office:font-face-decls/>
    <office:automatic-styles/>
    <office:body><office:text/></office:body>
</office:document-content>
XML);
    }

    private function stylesDom(?string $xml = null): DOMDocument
    {
        return $this->dom($xml ?? <<<'XML'
<office:document-styles
    xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0"
    xmlns:style="urn:oasis:names:tc:opendocument:xmlns:style:1.0"
    xmlns:fo="urn:oasis:names:tc:opendocument:xmlns:xsl-fo-compatible:1.0">
    <office:font-face-decls/>
    <office:styles/>
    <office:automatic-styles/>
    <office:master-styles/>
</office:document-styles>
XML);
    }

    private function metaDom(): DOMDocument
    {
        return $this->dom(<<<'XML'
<office:document-meta xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0">
    <office:meta/>
</office:document-meta>
XML);
    }

    private function dom(string $xml): DOMDocument
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        self::assertTrue($dom->loadXML($xml));

        return $dom;
    }

    private function paragraphStyle(DOMDocument $dom, string $name): ?DOMElement
    {
        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('style', 'urn:oasis:names:tc:opendocument:xmlns:style:1.0');
        $node = $xpath->query(sprintf(
            '//style:style[@style:family="paragraph" and @style:name="%s"]',
            $name
        ))->item(0);

        return $node instanceof DOMElement ? $node : null;
    }

    private function paragraphStyleCount(DOMDocument $dom, string $name): int
    {
        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('style', 'urn:oasis:names:tc:opendocument:xmlns:style:1.0');

        return $xpath->query(sprintf(
            '//style:style[@style:family="paragraph" and @style:name="%s"]',
            $name
        ))->length;
    }
}
