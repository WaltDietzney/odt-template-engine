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
    public function testGradientDefinitionsAreDocumentLocalAndIdempotent(): void
    {
        $context = $this->context();
        $styles = new DocumentStyles(static fn (): OdtDocumentContext => $context);
        $options = ['type' => 'linear', 'start-color' => '#112233', 'end-color' => '#abcdef', 'angle' => 45];
        $styles->defineGradient('CVGradient', $options);
        $styles->defineGradient('CVGradient', $options);
        $nodes = $context->stylesDom()->getElementsByTagNameNS(
            'urn:oasis:names:tc:opendocument:xmlns:drawing:1.0', 'gradient'
        );
        self::assertCount(1, $nodes);
        self::assertSame('45deg', $nodes->item(0)->getAttribute('draw:angle'));
        self::assertSame('#abcdef', $nodes->item(0)->getAttribute('draw:end-color'));
        $before = $context->stylesDom()->saveXML();
        try {
            $styles->defineGradient('CVGradient', array_replace($options, ['angle' => 90]));
            self::fail('Conflicting definition must fail.');
        } catch (LogicException $exception) {
            self::assertStringContainsString('Conflicting', $exception->getMessage());
        }
        self::assertSame($before, $context->stylesDom()->saveXML());
        self::assertCount(0, $this->context()->stylesDom()->getElementsByTagNameNS(
            'urn:oasis:names:tc:opendocument:xmlns:drawing:1.0', 'gradient'
        ));
    }

    public function testGradientNativeTypesAndNames(): void
    {
        $context = $this->context();
        $styles = new DocumentStyles(static fn (): OdtDocumentContext => $context);
        $options = ['start-color' => '#112233', 'end-color' => '#abcdef'];
        foreach (['linear', 'axial', 'radial', 'elliptical', 'square', 'rectangular'] as $type) {
            $styles->defineGradient('Gradient_' . $type, $options + ['type' => $type]);
        }
        $nodes = $context->stylesDom()->getElementsByTagNameNS(
            'urn:oasis:names:tc:opendocument:xmlns:drawing:1.0', 'gradient'
        );
        self::assertSame(['linear', 'axial', 'radial', 'ellipsoid', 'square', 'rectangular'],
            array_map(static fn ($node) => $node->getAttribute('draw:style'), iterator_to_array($nodes)));
        foreach (['', 'bad name', '1bad', 'bad:name'] as $name) {
            try {
                $styles->defineGradient($name, $options + ['type' => 'linear']);
                self::fail('Invalid NCName accepted.');
            } catch (\InvalidArgumentException $exception) {
                self::assertStringContainsString('NCName', $exception->getMessage());
            }
        }
        $styles->defineGradient('Überblendung', $options + ['type' => 'linear']);
        self::assertCount(7, $nodes);
    }

    public function testFillImageWithoutPackageProviderFails(): void
    {
        $context = $this->context();
        $styles = new DocumentStyles(static fn (): OdtDocumentContext => $context);
        $this->expectException(LogicException::class);
        $styles->defineFillImage('Bitmap', 'unused.png');
    }

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

    public function testUpdateParagraphMergesSpacingAndFlowWithoutChangingIdentityOrInheritance(): void
    {
        $context = $this->context(<<<'XML'
<office:document-styles
    xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0"
    xmlns:style="urn:oasis:names:tc:opendocument:xmlns:style:1.0"
    xmlns:fo="urn:oasis:names:tc:opendocument:xmlns:xsl-fo-compatible:1.0">
    <office:font-face-decls/>
    <office:styles>
        <style:style style:name="Entry" style:family="paragraph" style:parent-style-name="Standard">
            <style:paragraph-properties fo:margin-left="1cm" fo:margin-bottom="0.1cm"/>
            <style:text-properties fo:color="#123456"/>
        </style:style>
    </office:styles>
    <office:automatic-styles/>
    <office:master-styles/>
</office:document-styles>
XML);
        $styles = new DocumentStyles(static fn (): OdtDocumentContext => $context);

        $styles->updateParagraph('Entry', [
            'margin-top' => '0.2cm',
            'margin-bottom' => '0.35cm',
            'line-height' => '110%',
            'keep-with-next' => 'always',
        ]);

        $style = $this->paragraphStyle($context->stylesDom(), 'Entry');
        self::assertInstanceOf(DOMElement::class, $style);
        self::assertSame('Standard', $style->getAttribute('style:parent-style-name'));

        $paragraph = $style->getElementsByTagNameNS(
            'urn:oasis:names:tc:opendocument:xmlns:style:1.0',
            'paragraph-properties'
        )->item(0);
        self::assertInstanceOf(DOMElement::class, $paragraph);
        self::assertSame('1cm', $paragraph->getAttribute('fo:margin-left'));
        self::assertSame('0.2cm', $paragraph->getAttribute('fo:margin-top'));
        self::assertSame('0.35cm', $paragraph->getAttribute('fo:margin-bottom'));
        self::assertSame('110%', $paragraph->getAttribute('fo:line-height'));
        self::assertSame('always', $paragraph->getAttribute('fo:keep-with-next'));

        $text = $style->getElementsByTagNameNS(
            'urn:oasis:names:tc:opendocument:xmlns:style:1.0',
            'text-properties'
        )->item(0);
        self::assertInstanceOf(DOMElement::class, $text);
        self::assertSame('#123456', $text->getAttribute('fo:color'));
    }

    public function testUpdateParagraphMutatesContentAutomaticStyle(): void
    {
        $context = $this->context();
        $automaticStyles = $context->contentDom()->getElementsByTagNameNS(
            'urn:oasis:names:tc:opendocument:xmlns:office:1.0',
            'automatic-styles'
        )->item(0);
        self::assertInstanceOf(DOMElement::class, $automaticStyles);

        $style = $context->contentDom()->createElementNS(
            'urn:oasis:names:tc:opendocument:xmlns:style:1.0',
            'style:style'
        );
        $style->setAttribute('style:name', 'ContentEntry');
        $style->setAttribute('style:family', 'paragraph');
        $style->setAttribute('style:parent-style-name', 'Standard');
        $properties = $context->contentDom()->createElementNS(
            'urn:oasis:names:tc:opendocument:xmlns:style:1.0',
            'style:paragraph-properties'
        );
        $properties->setAttribute('fo:margin-bottom', '0.1cm');
        $style->appendChild($properties);
        $automaticStyles->appendChild($style);

        $styles = new DocumentStyles(static fn (): OdtDocumentContext => $context);
        $styles->updateParagraph('ContentEntry', ['margin-bottom' => '0.4cm']);

        self::assertSame('0.4cm', $properties->getAttribute('fo:margin-bottom'));
        self::assertSame('Standard', $style->getAttribute('style:parent-style-name'));
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


    public function testDocumentDefaultsModifyStandardAndPreserveUnspecifiedAuthoredProperties(): void
    {
        $context = $this->context(<<<'XML'
<office:document-styles
    xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0"
    xmlns:style="urn:oasis:names:tc:opendocument:xmlns:style:1.0"
    xmlns:fo="urn:oasis:names:tc:opendocument:xmlns:xsl-fo-compatible:1.0">
    <office:font-face-decls/>
    <office:styles>
        <style:style style:name="Standard" style:family="paragraph">
            <style:text-properties fo:color="#123456" fo:font-size="11pt"/>
            <style:paragraph-properties fo:margin-bottom="0.2cm"/>
        </style:style>
        <style:style style:name="CVText" style:family="paragraph" style:parent-style-name="Standard"/>
    </office:styles>
    <office:automatic-styles/>
    <office:master-styles/>
</office:document-styles>
XML);
        $styles = new DocumentStyles(static fn (): OdtDocumentContext => $context);

        $styles->setDocumentDefaults([
            'text' => [
                'font-family' => 'Arial',
                'font-size' => '10pt',
            ],
            'paragraph' => [
                'line-height' => '115%',
            ],
        ]);

        $standard = $this->paragraphStyle($context->stylesDom(), 'Standard');
        self::assertInstanceOf(DOMElement::class, $standard);

        $text = $standard->getElementsByTagNameNS(
            'urn:oasis:names:tc:opendocument:xmlns:style:1.0',
            'text-properties'
        )->item(0);
        self::assertInstanceOf(DOMElement::class, $text);
        self::assertSame('Arial', $text->getAttribute('style:font-name'));
        self::assertSame('Arial', $text->getAttribute('fo:font-family'));
        self::assertSame('10pt', $text->getAttribute('fo:font-size'));
        self::assertSame('#123456', $text->getAttribute('fo:color'));

        $paragraph = $standard->getElementsByTagNameNS(
            'urn:oasis:names:tc:opendocument:xmlns:style:1.0',
            'paragraph-properties'
        )->item(0);
        self::assertInstanceOf(DOMElement::class, $paragraph);
        self::assertSame('115%', $paragraph->getAttribute('fo:line-height'));
        self::assertSame('0.2cm', $paragraph->getAttribute('fo:margin-bottom'));

        $child = $this->paragraphStyle($context->stylesDom(), 'CVText');
        self::assertInstanceOf(DOMElement::class, $child);
        self::assertSame('Standard', $child->getAttribute('style:parent-style-name'));
    }

    public function testDocumentDefaultsCreateMissingStandardAndMaterializeFontFace(): void
    {
        $context = $this->context();
        $styles = new DocumentStyles(static fn (): OdtDocumentContext => $context);

        $styles->setDocumentDefaults([
            'text' => ['font-family' => 'Arial'],
        ]);

        $standard = $this->paragraphStyle($context->stylesDom(), 'Standard');
        self::assertInstanceOf(DOMElement::class, $standard);

        $xpath = new DOMXPath($context->stylesDom());
        $xpath->registerNamespace('style', 'urn:oasis:names:tc:opendocument:xmlns:style:1.0');
        $fontFace = $xpath->query('//style:font-face[@style:name="Arial"]')->item(0);
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
