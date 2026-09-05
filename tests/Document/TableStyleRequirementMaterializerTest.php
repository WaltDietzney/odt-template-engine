<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Document;

use DOMDocument;
use DOMElement;
use InvalidArgumentException;
use OdtTemplateEngine\Document\StyleRequirement;
use OdtTemplateEngine\Document\StyleRequirementMaterializer;
use OdtTemplateEngine\OdtDocumentContext;
use PHPUnit\Framework\TestCase;

final class TableStyleRequirementMaterializerTest extends TestCase
{
    public function testCommonTableDefinitionUsesTablePropertiesInStylesXml(): void
    {
        $context = $this->context();

        (new StyleRequirementMaterializer())->materialize($context, $this->requirement(
            StyleRequirement::SCOPE_COMMON,
            StyleRequirement::PART_STYLES,
            'table',
            'SemanticTable',
            ['style:table-properties' => ['table:align' => 'center']]
        ));

        $style = $this->style($context->stylesDom(), 'SemanticTable', 'table');
        self::assertNotNull($style);
        $properties = $this->property($style, 'table-properties');
        self::assertSame('center', $properties->getAttributeNS(self::TABLE_NS, 'align'));
        self::assertSame(1, $this->styleCount($context->stylesDom(), 'SemanticTable', 'table'));
        self::assertSame(0, $this->styleCount($context->contentDom(), 'SemanticTable', 'table'));
    }

    public function testAutomaticTableColumnDefinitionUsesContentAutomaticStyles(): void
    {
        $context = $this->context();

        (new StyleRequirementMaterializer())->materialize($context, $this->requirement(
            StyleRequirement::SCOPE_AUTOMATIC,
            StyleRequirement::PART_CONTENT,
            'table-column',
            'coSemantic',
            ['style:table-column-properties' => ['style:column-width' => '2cm']]
        ));

        $style = $this->style($context->contentDom(), 'coSemantic', 'table-column');
        self::assertNotNull($style);
        self::assertSame('2cm', $this->property($style, 'table-column-properties')
            ->getAttributeNS(self::STYLE_NS, 'column-width'));
        self::assertSame(0, $this->styleCount($context->stylesDom(), 'coSemantic', 'table-column'));
    }

    public function testTableRowDefinitionUsesRowProperties(): void
    {
        $context = $this->context();

        (new StyleRequirementMaterializer())->materialize($context, $this->requirement(
            StyleRequirement::SCOPE_AUTOMATIC,
            StyleRequirement::PART_CONTENT,
            'table-row',
            'SemanticRow',
            ['style:table-row-properties' => ['style:min-row-height' => '0.5cm']]
        ));

        $style = $this->style($context->contentDom(), 'SemanticRow', 'table-row');
        self::assertNotNull($style);
        self::assertSame('0.5cm', $this->property($style, 'table-row-properties')
            ->getAttributeNS(self::STYLE_NS, 'min-row-height'));
    }

    public function testTableCellDefinitionUsesCellProperties(): void
    {
        $context = $this->context();

        (new StyleRequirementMaterializer())->materialize($context, $this->requirement(
            StyleRequirement::SCOPE_AUTOMATIC,
            StyleRequirement::PART_CONTENT,
            'table-cell',
            'SemanticCell',
            ['style:table-cell-properties' => ['fo:background-color' => '#ddeeff']]
        ));

        $style = $this->style($context->contentDom(), 'SemanticCell', 'table-cell');
        self::assertNotNull($style);
        self::assertSame('#ddeeff', $this->property($style, 'table-cell-properties')
            ->getAttributeNS(self::FO_NS, 'background-color'));
    }

    public function testExistingTableDefinitionRemainsAuthoritative(): void
    {
        $context = $this->context();
        $styles = $context->stylesDom()->getElementsByTagNameNS(self::OFFICE_NS, 'styles')->item(0);
        self::assertInstanceOf(DOMElement::class, $styles);
        $existing = $context->stylesDom()->createElementNS(self::STYLE_NS, 'style:style');
        $existing->setAttributeNS(self::STYLE_NS, 'style:name', 'AuthoredTable');
        $existing->setAttributeNS(self::STYLE_NS, 'style:family', 'table');
        $properties = $context->stylesDom()->createElementNS(self::STYLE_NS, 'style:table-properties');
        $properties->setAttributeNS(self::TABLE_NS, 'table:align', 'left');
        $existing->appendChild($properties);
        $styles->appendChild($existing);

        (new StyleRequirementMaterializer())->materialize($context, $this->requirement(
            StyleRequirement::SCOPE_COMMON,
            StyleRequirement::PART_STYLES,
            'table',
            'AuthoredTable',
            ['style:table-properties' => ['table:align' => 'right']]
        ));

        self::assertSame(1, $this->styleCount($context->stylesDom(), 'AuthoredTable', 'table'));
        self::assertSame('left', $properties->getAttributeNS(self::TABLE_NS, 'align'));
    }

    public function testAutomaticTableDefinitionIsIdempotent(): void
    {
        $context = $this->context();
        $requirement = $this->requirement(
            StyleRequirement::SCOPE_AUTOMATIC,
            StyleRequirement::PART_CONTENT,
            'table-cell',
            'OnlyOnceCell',
            ['style:table-cell-properties' => ['fo:padding' => '0.1cm']]
        );
        $materializer = new StyleRequirementMaterializer();
        $materializer->materialize($context, $requirement);
        $materializer->materialize($context, $requirement);

        self::assertSame(1, $this->styleCount($context->contentDom(), 'OnlyOnceCell', 'table-cell'));
    }

    public function testCommonTableDefinitionCannotTargetContentXml(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Common table styles require styles.xml.');

        (new StyleRequirementMaterializer())->materialize($this->context(), $this->requirement(
            StyleRequirement::SCOPE_COMMON,
            StyleRequirement::PART_CONTENT,
            'table',
            'InvalidTable',
            ['style:table-properties' => ['table:align' => 'center']]
        ));
    }

    private const OFFICE_NS = 'urn:oasis:names:tc:opendocument:xmlns:office:1.0';
    private const STYLE_NS = 'urn:oasis:names:tc:opendocument:xmlns:style:1.0';
    private const TABLE_NS = 'urn:oasis:names:tc:opendocument:xmlns:table:1.0';
    private const FO_NS = 'urn:oasis:names:tc:opendocument:xmlns:xsl-fo-compatible:1.0';

    /** @param array<string, array<string, mixed>> $groups */
    private function requirement(string $scope, string $part, string $family, string $name, array $groups): StyleRequirement
    {
        return new StyleRequirement(
            StyleRequirement::KIND_DEFINITION,
            $scope,
            $family,
            $part,
            $name,
            'Standard',
            $groups
        );
    }

    private function context(): OdtDocumentContext
    {
        return new OdtDocumentContext(
            $this->dom('<office:document-content xmlns:office="' . self::OFFICE_NS . '" xmlns:style="' . self::STYLE_NS . '"><office:automatic-styles/></office:document-content>'),
            $this->dom('<office:document-styles xmlns:office="' . self::OFFICE_NS . '" xmlns:style="' . self::STYLE_NS . '"><office:styles/></office:document-styles>'),
            $this->dom('<office:document-meta xmlns:office="' . self::OFFICE_NS . '"/>')
        );
    }

    private function dom(string $xml): DOMDocument
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        self::assertTrue($dom->loadXML($xml));
        return $dom;
    }

    private function style(DOMDocument $dom, string $name, string $family): ?DOMElement
    {
        foreach ($dom->getElementsByTagNameNS(self::STYLE_NS, 'style') as $style) {
            if ($style->getAttributeNS(self::STYLE_NS, 'name') === $name
                && $style->getAttributeNS(self::STYLE_NS, 'family') === $family) {
                return $style;
            }
        }
        return null;
    }

    private function styleCount(DOMDocument $dom, string $name, string $family): int
    {
        $count = 0;
        foreach ($dom->getElementsByTagNameNS(self::STYLE_NS, 'style') as $style) {
            if ($style->getAttributeNS(self::STYLE_NS, 'name') === $name
                && $style->getAttributeNS(self::STYLE_NS, 'family') === $family) {
                $count++;
            }
        }
        return $count;
    }

    private function property(DOMElement $style, string $localName): DOMElement
    {
        foreach ($style->childNodes as $child) {
            if ($child instanceof DOMElement && $child->localName === $localName) {
                return $child;
            }
        }
        self::fail('Missing style property group: ' . $localName);
    }
}
