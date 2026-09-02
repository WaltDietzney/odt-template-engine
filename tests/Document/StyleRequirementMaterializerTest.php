<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Document;

use DOMDocument;
use OdtTemplateEngine\Document\StyleRequirement;
use OdtTemplateEngine\Document\StyleRequirementMaterializer;
use OdtTemplateEngine\OdtDocumentContext;
use OdtTemplateEngine\Style\StyleContext;
use OdtTemplateEngine\Utils\StyleMapper;
use PHPUnit\Framework\TestCase;

final class StyleRequirementMaterializerTest extends TestCase
{
    public function testCommonParagraphDefinitionWritesBothTypedPropertyGroupsToStylesXml(): void
    {
        $context = $this->context();
        $requirement = $this->requirement(
            StyleRequirement::SCOPE_COMMON,
            StyleRequirement::PART_STYLES,
            'paragraph',
            'MixedParagraph',
            [
                'style:paragraph-properties' => ['fo:text-align' => 'center'],
                'style:text-properties' => ['fo:font-weight' => 'bold', 'fo:color' => '#cc0000'],
            ]
        );

        $materializer = new StyleRequirementMaterializer();
        $materializer->materialize($context, $requirement);

        $style = $this->style($context->stylesDom(), 'MixedParagraph', 'paragraph');
        self::assertNotNull($style);
        self::assertSame('Standard', $style->getAttribute('style:parent-style-name'));
        self::assertSame('center', $this->property($style, 'paragraph-properties')->getAttribute('fo:text-align'));
        self::assertSame('bold', $this->property($style, 'text-properties')->getAttribute('fo:font-weight'));
        self::assertSame('#cc0000', $this->property($style, 'text-properties')->getAttribute('fo:color'));
    }

    public function testCommonTextDefinitionWritesNativePropertiesToStylesXml(): void
    {
        $context = $this->context();
        $requirement = $this->requirement(
            StyleRequirement::SCOPE_COMMON,
            StyleRequirement::PART_STYLES,
            'text',
            'TextStyle',
            ['style:text-properties' => ['fo:color' => '#123456']]
        );

        (new StyleRequirementMaterializer())->materialize($context, $requirement);

        $style = $this->style($context->stylesDom(), 'TextStyle', 'text');
        self::assertNotNull($style);
        self::assertSame('#123456', $this->property($style, 'text-properties')->getAttribute('fo:color'));
    }

    public function testAutomaticParagraphAndTextDefinitionsUseContentAutomaticStyles(): void
    {
        $context = $this->context();
        $materializer = new StyleRequirementMaterializer();
        $materializer->materialize($context, $this->requirement(
            StyleRequirement::SCOPE_AUTOMATIC,
            StyleRequirement::PART_CONTENT,
            'paragraph',
            'AutoParagraph',
            ['style:paragraph-properties' => ['fo:text-align' => 'right']]
        ));
        $materializer->materialize($context, $this->requirement(
            StyleRequirement::SCOPE_AUTOMATIC,
            StyleRequirement::PART_CONTENT,
            'text',
            'AutoText',
            ['style:text-properties' => ['fo:font-weight' => 'bold']]
        ));

        self::assertNotNull($this->style($context->contentDom(), 'AutoParagraph', 'paragraph'));
        self::assertNotNull($this->style($context->contentDom(), 'AutoText', 'text'));
        self::assertSame(0, $context->stylesDom()->getElementsByTagNameNS(
            'urn:oasis:names:tc:opendocument:xmlns:style:1.0',
            'style'
        )->length);
    }

    public function testAutomaticDefinitionMayUseStylesXmlIndependentlyOfScope(): void
    {
        $context = $this->context();
        $requirement = $this->requirement(
            StyleRequirement::SCOPE_AUTOMATIC,
            StyleRequirement::PART_STYLES,
            'text',
            'AutoInStyles',
            ['style:text-properties' => ['fo:color' => '#abcdef']]
        );

        (new StyleRequirementMaterializer())->materialize($context, $requirement);

        self::assertNotNull($this->style($context->stylesDom(), 'AutoInStyles', 'text'));
        self::assertNull($this->style($context->contentDom(), 'AutoInStyles', 'text'));
    }

    public function testExistingDocumentDefinitionRemainsAuthoritativeAndIsNotDuplicated(): void
    {
        $context = $this->context();
        $existing = $context->stylesDom()->getElementsByTagNameNS(
            'urn:oasis:names:tc:opendocument:xmlns:office:1.0',
            'styles'
        )->item(0);
        $style = $context->stylesDom()->createElementNS(
            'urn:oasis:names:tc:opendocument:xmlns:style:1.0',
            'style:style'
        );
        $style->setAttributeNS('urn:oasis:names:tc:opendocument:xmlns:style:1.0', 'style:name', 'Authoritative');
        $style->setAttributeNS('urn:oasis:names:tc:opendocument:xmlns:style:1.0', 'style:family', 'paragraph');
        $props = $context->stylesDom()->createElementNS(
            'urn:oasis:names:tc:opendocument:xmlns:style:1.0',
            'style:paragraph-properties'
        );
        $props->setAttributeNS('urn:oasis:names:tc:opendocument:xmlns:xsl-fo-compatible:1.0', 'fo:text-align', 'left');
        $style->appendChild($props);
        $existing->appendChild($style);

        (new StyleRequirementMaterializer())->materialize($context, $this->requirement(
            StyleRequirement::SCOPE_COMMON,
            StyleRequirement::PART_STYLES,
            'paragraph',
            'Authoritative',
            ['style:paragraph-properties' => ['fo:text-align' => 'right']]
        ));

        self::assertSame(1, $this->styleCount($context->stylesDom(), 'Authoritative', 'paragraph'));
        self::assertSame('left', $this->property($style, 'paragraph-properties')->getAttribute('fo:text-align'));
    }

    public function testDocumentLocalDefinitionIsMaterializedOnceWhenReferenceAlsoExists(): void
    {
        $context = $this->context();
        $definition = $this->requirement(
            StyleRequirement::SCOPE_COMMON,
            StyleRequirement::PART_STYLES,
            'paragraph',
            'LocalStyle',
            ['style:paragraph-properties' => ['fo:text-align' => 'center']]
        );
        $reference = new StyleRequirement(StyleRequirement::KIND_REFERENCE, null, 'paragraph', null, 'LocalStyle');
        $context->styleContext()->registerRequirement($reference);
        $context->styleContext()->registerRequirement($definition);
        $materializer = new StyleRequirementMaterializer();
        foreach ($context->styleContext()->materializationRequirements() as $requirement) {
            $materializer->materialize($context, $requirement);
        }
        $materializer->materialize($context, $definition);

        self::assertSame(1, $this->styleCount($context->stylesDom(), 'LocalStyle', 'paragraph'));
    }

    public function testDemandDrivenLegacyMaterializationExcludesUnreferencedStyles(): void
    {
        $used = 'UsedLegacy_' . bin2hex(random_bytes(4));
        $unused = 'UnusedLegacy_' . bin2hex(random_bytes(4));
        StyleMapper::registerParagraphStyle($used, ['text-align' => 'center']);
        StyleMapper::registerParagraphStyle($unused, ['text-align' => 'right']);
        $context = $this->context();
        $context->styleContext()->registerRequirement(new StyleRequirement(StyleRequirement::KIND_REFERENCE, null, 'paragraph', null, $used));

        $materializer = new StyleRequirementMaterializer();
        foreach ($context->styleContext()->materializationRequirements() as $requirement) {
            $materializer->materialize($context, $requirement);
        }

        self::assertSame(1, $this->styleCount($context->stylesDom(), $used, 'paragraph'));
        self::assertSame(0, $this->styleCount($context->stylesDom(), $unused, 'paragraph'));
    }

    /** @param array<string, array<string, mixed>> $groups */
    private function requirement(string $scope, string $part, string $family, string $name, array $groups): StyleRequirement
    {
        return new StyleRequirement(StyleRequirement::KIND_DEFINITION, $scope, $family, $part, $name, 'Standard', $groups);
    }

    private function context(): OdtDocumentContext
    {
        return new OdtDocumentContext(
            $this->dom('<office:document-content xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0" xmlns:style="urn:oasis:names:tc:opendocument:xmlns:style:1.0"><office:automatic-styles/></office:document-content>'),
            $this->dom('<office:document-styles xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0" xmlns:style="urn:oasis:names:tc:opendocument:xmlns:style:1.0"><office:styles/></office:document-styles>'),
            $this->dom('<office:document-meta xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0"/>')
        );
    }

    private function dom(string $xml): DOMDocument
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        self::assertTrue($dom->loadXML($xml));
        return $dom;
    }

    private function style(DOMDocument $dom, string $name, string $family): ?\DOMElement
    {
        foreach ($dom->getElementsByTagNameNS('urn:oasis:names:tc:opendocument:xmlns:style:1.0', 'style') as $style) {
            if ($style->getAttributeNS('urn:oasis:names:tc:opendocument:xmlns:style:1.0', 'name') === $name
                && $style->getAttributeNS('urn:oasis:names:tc:opendocument:xmlns:style:1.0', 'family') === $family) {
                return $style;
            }
        }
        return null;
    }

    private function styleCount(DOMDocument $dom, string $name, string $family): int
    {
        $count = 0;
        foreach ($dom->getElementsByTagNameNS('urn:oasis:names:tc:opendocument:xmlns:style:1.0', 'style') as $style) {
            if ($style->getAttributeNS('urn:oasis:names:tc:opendocument:xmlns:style:1.0', 'name') === $name
                && $style->getAttributeNS('urn:oasis:names:tc:opendocument:xmlns:style:1.0', 'family') === $family) {
                $count++;
            }
        }
        return $count;
    }

    private function property(\DOMElement $style, string $localName): \DOMElement
    {
        foreach ($style->childNodes as $child) {
            if ($child instanceof \DOMElement && $child->localName === $localName) {
                return $child;
            }
        }
        self::fail('Missing style property group: ' . $localName);
    }
}
