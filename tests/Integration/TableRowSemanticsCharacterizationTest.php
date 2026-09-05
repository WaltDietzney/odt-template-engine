<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Integration;

use DOMDocument;
use OdtTemplateEngine\Elements\RichTable;
use PHPUnit\Framework\TestCase;

/**
 * Characterizes the dormant RichTable row-style argument before SR-07E2.
 */
final class TableRowSemanticsCharacterizationTest extends TestCase
{
    public function testNonEmptyRowStyleRendersStructuralReferenceWithoutDirectDefinition(): void
    {
        $dom = $this->contentDom();
        $table = (new RichTable())
            ->setTableName('TestTable')
            ->addRow(['A'], ['min-row-height' => '1cm']);
        $dom->documentElement->appendChild($table->toDomNode($dom));

        $xml = $dom->saveXML() ?: '';
        self::assertSame(1, substr_count($xml, '<table:table-row'));
        self::assertStringContainsString('table:table-row table:style-name="TestTable_ro0"', $xml);
        self::assertStringNotContainsString('table-row-properties', $xml);
    }

    public function testIgnoredRowStyleDoesNotCreateTableRowStyleDefinition(): void
    {
        $dom = $this->contentDom();
        $table = (new RichTable())->addRow(['A'], [
            'height' => '1cm',
            'keep-together' => 'always',
            'break-before' => 'page',
        ]);
        $dom->documentElement->appendChild($table->toDomNode($dom));

        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('style', self::STYLE_NS);
        self::assertSame(0, $xpath->query('//style:style[@style:family="table-row"]')->length);
        self::assertSame(0, $xpath->query('//*[contains(name(), "table-row-properties")]')->length);
    }

    public function testStyledAndUnstyledRowsHaveEquivalentRowLevelStructure(): void
    {
        $styledDom = $this->contentDom();
        $styled = (new RichTable())->addRow(['A'], ['row-height' => '2cm']);
        $styledDom->documentElement->appendChild($styled->toDomNode($styledDom));

        $plainDom = $this->contentDom();
        $plain = (new RichTable())->addRow(['A']);
        $plainDom->documentElement->appendChild($plain->toDomNode($plainDom));

        $styledXpath = new \DOMXPath($styledDom);
        $plainXpath = new \DOMXPath($plainDom);
        $styledXpath->registerNamespace('table', self::TABLE_NS);
        $plainXpath->registerNamespace('table', self::TABLE_NS);
        $styledRow = $styledXpath->query('//*[contains(name(), "table:table-row")]')->item(0);
        $plainRow = $plainXpath->query('//*[contains(name(), "table:table-row")]')->item(0);
        self::assertNotNull($styledRow);
        self::assertNotNull($plainRow);
        self::assertSame($plainRow->attributes?->length, $styledRow->attributes?->length);
        self::assertSame(
            $plainRow->firstChild?->nodeName,
            $styledRow->firstChild?->nodeName
        );
    }

    private const STYLE_NS = 'urn:oasis:names:tc:opendocument:xmlns:style:1.0';
    private const TABLE_NS = 'urn:oasis:names:tc:opendocument:xmlns:table:1.0';

    private function contentDom(): DOMDocument
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $root = $dom->createElementNS(
            'urn:oasis:names:tc:opendocument:xmlns:office:1.0',
            'office:document-content'
        );
        $dom->appendChild($root);
        $root->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:style', self::STYLE_NS);
        $root->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:table', self::TABLE_NS);
        $root->appendChild($dom->createElementNS(
            'urn:oasis:names:tc:opendocument:xmlns:office:1.0',
            'office:automatic-styles'
        ));
        return $dom;
    }
}
