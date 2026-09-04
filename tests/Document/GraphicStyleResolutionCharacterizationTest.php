<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Document;

use DOMDocument;
use LogicException;
use OdtTemplateEngine\Document\StyleRequirement;
use OdtTemplateEngine\OdtDocumentContext;
use OdtTemplateEngine\Style\StyleContext;
use PHPUnit\Framework\TestCase;

final class GraphicStyleResolutionCharacterizationTest extends TestCase
{
    public function testEquivalentGraphicDefinitionsAreIdempotent(): void
    {
        $context = new StyleContext();
        $first = $this->graphicDefinition('GraphicA', '#123456');
        $second = $this->graphicDefinition('GraphicA', '#123456');

        $context->registerRequirement($first);
        $context->registerRequirement($second);

        self::assertCount(1, $context->semanticDefinitions());
        self::assertSame($first, $context->semanticDefinitions()[$this->semanticKey($first)]);
    }

    public function testConflictingGraphicDefinitionsWithSameSemanticIdentityAreRejected(): void
    {
        $context = new StyleContext();
        $context->registerRequirement($this->graphicDefinition('GraphicA', '#123456'));

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Graphic style "GraphicA" is already registered with a different definition');

        $context->registerRequirement($this->graphicDefinition('GraphicA', '#654321'));
    }

    public function testGraphicReferenceResolvesToDocumentLocalDefinitionRegardlessOfRegistrationOrder(): void
    {
        $definition = $this->graphicDefinition('LocalGraphic', '#123456');
        $reference = $this->graphicReference('LocalGraphic');

        $referenceFirst = new StyleContext();
        $referenceFirst->registerRequirement($reference);
        self::assertSame([$reference], $referenceFirst->unresolvedReferences());
        $referenceFirst->registerRequirement($definition);

        $definitionFirst = new StyleContext();
        $definitionFirst->registerRequirement($definition);
        $definitionFirst->registerRequirement($reference);

        self::assertSame('document-local', $referenceFirst->referenceResolution($reference));
        self::assertSame('document-local', $definitionFirst->referenceResolution($reference));
        self::assertSame($definition, $referenceFirst->referenceCandidate($reference));
        self::assertSame($definition, $definitionFirst->referenceCandidate($reference));
        self::assertSame([], $referenceFirst->unresolvedReferences());
        self::assertSame([], $definitionFirst->unresolvedReferences());
    }

    public function testExistingDocumentGraphicDefinitionWinsOverDocumentLocalDefinition(): void
    {
        $styles = $this->dom(
            '<office:document-styles '
            . 'xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0" '
            . 'xmlns:style="urn:oasis:names:tc:opendocument:xmlns:style:1.0">'
            . '<office:styles><style:style style:name="AuthoritativeGraphic" style:family="graphic"/></office:styles>'
            . '</office:document-styles>'
        );
        $context = new StyleContext(null, $styles);
        $local = $this->graphicDefinition('AuthoritativeGraphic', '#123456');
        $reference = $this->graphicReference('AuthoritativeGraphic');

        $context->registerRequirement($local);
        $context->registerRequirement($reference);

        self::assertSame('document', $context->referenceResolution($reference));
        $candidate = $context->referenceCandidate($reference);
        self::assertIsArray($candidate);
        self::assertSame('document', $candidate['source']);
        self::assertSame('graphic', $candidate['family']);
        self::assertSame(StyleRequirement::SCOPE_COMMON, $candidate['scope']);
        self::assertSame(StyleRequirement::PART_STYLES, $candidate['documentPart']);
    }

    public function testBroadGraphicReferenceIsAmbiguousAcrossScopeAndDocumentPart(): void
    {
        $common = $this->graphicDefinition('SharedGraphic', '#123456');
        $automatic = new StyleRequirement(
            StyleRequirement::KIND_DEFINITION,
            StyleRequirement::SCOPE_AUTOMATIC,
            'graphic',
            StyleRequirement::PART_CONTENT,
            'SharedGraphic',
            'Frame',
            ['style:graphic-properties' => ['draw:fill' => 'solid', 'draw:fill-color' => '#123456']]
        );
        $reference = $this->graphicReference('SharedGraphic');
        $context = new StyleContext();

        $context->registerRequirement($common);
        $context->registerRequirement($automatic);
        $context->registerRequirement($reference);

        self::assertSame([$reference], $context->ambiguousReferences());
        self::assertCount(2, $context->ambiguousReferenceCandidates($reference));
        self::assertNull($context->referenceResolution($reference));
        self::assertSame([], $context->unresolvedReferences());
    }

    public function testNarrowedGraphicReferenceSelectsOneSemanticDefinition(): void
    {
        $common = $this->graphicDefinition('SharedGraphic', '#123456');
        $automatic = new StyleRequirement(
            StyleRequirement::KIND_DEFINITION,
            StyleRequirement::SCOPE_AUTOMATIC,
            'graphic',
            StyleRequirement::PART_CONTENT,
            'SharedGraphic',
            'Frame',
            ['style:graphic-properties' => ['draw:fill' => 'solid', 'draw:fill-color' => '#123456']]
        );
        $reference = new StyleRequirement(
            StyleRequirement::KIND_REFERENCE,
            StyleRequirement::SCOPE_COMMON,
            'graphic',
            StyleRequirement::PART_STYLES,
            'SharedGraphic'
        );
        $context = new StyleContext();

        $context->registerRequirement($common);
        $context->registerRequirement($automatic);
        $context->registerRequirement($reference);

        self::assertSame([], $context->ambiguousReferences());
        self::assertSame('document-local', $context->referenceResolution($reference));
        self::assertSame($common, $context->referenceCandidate($reference));
    }

    public function testMultipleExistingDocumentGraphicCandidatesRemainAmbiguous(): void
    {
        $styles = $this->dom(
            '<office:document-styles '
            . 'xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0" '
            . 'xmlns:style="urn:oasis:names:tc:opendocument:xmlns:style:1.0">'
            . '<office:styles>'
            . '<style:style style:name="DuplicateGraphic" style:family="graphic"/>'
            . '<style:style style:name="DuplicateGraphic" style:family="graphic"/>'
            . '</office:styles>'
            . '</office:document-styles>'
        );
        $context = new StyleContext(null, $styles);
        $reference = $this->graphicReference('DuplicateGraphic');

        $context->registerRequirement($reference);

        self::assertSame([$reference], $context->ambiguousReferences());
        self::assertCount(2, $context->ambiguousReferenceCandidates($reference));
        self::assertNull($context->referenceResolution($reference));
        self::assertSame([], $context->unresolvedReferences());
    }

    public function testLegacyFrameAndImageRegistriesDoNotResolveSemanticGraphicReference(): void
    {
        $context = new StyleContext();
        $context->registerFrameStyle('LegacyGraphic', ['draw:fill' => 'solid']);
        $context->registerImageStyle('LegacyGraphic', ['style:wrap' => 'none']);
        $reference = $this->graphicReference('LegacyGraphic');

        $context->registerRequirement($reference);

        self::assertSame(['LegacyGraphic' => ['draw:fill' => 'solid']], $context->frameStyles());
        self::assertSame(['LegacyGraphic' => ['style:wrap' => 'none']], $context->imageStyles());
        self::assertNull($context->referenceResolution($reference));
        self::assertSame([$reference], $context->unresolvedReferences());
        self::assertSame([], $context->semanticDefinitions());
    }

    public function testGraphicSemanticStateIsDocumentLocalAndResetWithCoreDocuments(): void
    {
        $documentA = $this->documentContext();
        $documentB = $this->documentContext();
        $definition = $this->graphicDefinition('OnlyA', '#123456');
        $reference = $this->graphicReference('OnlyA');

        $documentA->styleContext()->registerRequirement($definition);
        $documentA->styleContext()->registerRequirement($reference);

        self::assertArrayHasKey($this->semanticKey($definition), $documentA->styleContext()->semanticDefinitions());
        self::assertSame([], $documentB->styleContext()->semanticDefinitions());
        self::assertSame([], $documentB->styleContext()->semanticReferences());

        $documentA->replaceCoreDocuments(
            $this->contentDom(),
            $this->stylesDom(),
            $this->metaDom()
        );

        self::assertSame([], $documentA->styleContext()->semanticDefinitions());
        self::assertSame([], $documentA->styleContext()->semanticReferences());
        self::assertSame([], $documentA->styleContext()->unresolvedReferences());
    }

    private function graphicDefinition(string $name, string $color): StyleRequirement
    {
        return new StyleRequirement(
            StyleRequirement::KIND_DEFINITION,
            StyleRequirement::SCOPE_COMMON,
            'graphic',
            StyleRequirement::PART_STYLES,
            $name,
            'Frame',
            [
                'style:graphic-properties' => [
                    'draw:fill' => 'solid',
                    'draw:fill-color' => $color,
                ],
            ]
        );
    }

    private function graphicReference(string $name): StyleRequirement
    {
        return new StyleRequirement(
            StyleRequirement::KIND_REFERENCE,
            null,
            'graphic',
            null,
            $name
        );
    }

    private function semanticKey(StyleRequirement $requirement): string
    {
        return implode("\0", [
            $requirement->family(),
            $requirement->name(),
            $requirement->scope(),
            $requirement->documentPart(),
        ]);
    }

    private function documentContext(): OdtDocumentContext
    {
        return new OdtDocumentContext($this->contentDom(), $this->stylesDom(), $this->metaDom());
    }

    private function contentDom(): DOMDocument
    {
        return $this->dom(
            '<office:document-content '
            . 'xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0" '
            . 'xmlns:style="urn:oasis:names:tc:opendocument:xmlns:style:1.0">'
            . '<office:automatic-styles/>'
            . '</office:document-content>'
        );
    }

    private function stylesDom(): DOMDocument
    {
        return $this->dom(
            '<office:document-styles '
            . 'xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0" '
            . 'xmlns:style="urn:oasis:names:tc:opendocument:xmlns:style:1.0">'
            . '<office:styles/>'
            . '</office:document-styles>'
        );
    }

    private function metaDom(): DOMDocument
    {
        return $this->dom(
            '<office:document-meta xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0"/>'
        );
    }

    private function dom(string $xml): DOMDocument
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        self::assertTrue($dom->loadXML($xml));

        return $dom;
    }
}
