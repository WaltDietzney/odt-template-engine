<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Template;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;
use OdtTemplateEngine\Template\TemplateProcessor;
use PHPUnit\Framework\TestCase;

final class TemplateAuthoring01A1ClassicControlFormatPreservationCharacterizationTest extends TestCase
{
    public function testSelectedConditionalParagraphKeepsParagraphAndSpanStyles(): void
    {
        $dom = $this->dom(
            '<text:p>{{#if:show}}</text:p>'
            . '<text:p text:style-name="BodyA"><text:span text:style-name="Strong">Selected</text:span></text:p>'
            . '<text:p>{{#else}}</text:p>'
            . '<text:p text:style-name="BodyB">Fallback</text:p>'
            . '<text:p>{{#endif}}</text:p>'
        );

        $processor = new TemplateProcessor();
        $processor->applyConditionalsInDom(
            $dom,
            ['show' => true],
            fn (string $expression, array $values): bool => $processor->evaluateCondition($expression, $values)
        );

        $xpath = $this->xpath($dom);

        self::assertSame(1, $xpath->query('//text:p')->length);

        $paragraph = $xpath->query('//text:p')->item(0);
        self::assertInstanceOf(DOMElement::class, $paragraph);
        self::assertSame('BodyA', $paragraph->getAttribute('text:style-name'));

        $span = $xpath->query('//text:p/text:span')->item(0);
        self::assertInstanceOf(DOMElement::class, $span);
        self::assertSame('Strong', $span->getAttribute('text:style-name'));
        self::assertSame('Selected', $span->textContent);
    }

    public function testUnselectedConditionalTableLosesItsParagraphButLeavesTableShell(): void
    {
        $dom = $this->dom(
            '<text:p>{{#if:show}}</text:p>'
            . '<table:table table:name="ConditionalTable" table:style-name="TableStyle">'
            . '  <table:table-row>'
            . '    <table:table-cell>'
            . '      <text:p text:style-name="CellBody">Inside conditional table</text:p>'
            . '    </table:table-cell>'
            . '  </table:table-row>'
            . '</table:table>'
            . '<text:p>{{#else}}</text:p>'
            . '<text:p text:style-name="Fallback">Fallback paragraph</text:p>'
            . '<text:p>{{#endif}}</text:p>'
        );

        $processor = new TemplateProcessor();
        $processor->applyConditionalsInDom(
            $dom,
            ['show' => false],
            fn (string $expression, array $values): bool => $processor->evaluateCondition($expression, $values)
        );

        $xpath = $this->xpath($dom);

        self::assertSame(1, $xpath->query('//table:table[@table:name="ConditionalTable"]')->length);
        self::assertSame(0, $xpath->query('//table:table[@table:name="ConditionalTable"]//text:p')->length);
        self::assertSame(1, $xpath->query('//text:p[@text:style-name="Fallback"]')->length);
    }

    public function testForeachClonesParagraphAndInlineStylesWithoutRebuildingThem(): void
    {
        $dom = $this->dom(
            '<text:p>{{#foreach:items}}</text:p>'
            . '<text:p text:style-name="RepeatBody">'
            . '  <text:span text:style-name="RepeatStrong">{{name}}</text:span>'
            . '</text:p>'
            . '<text:p>{{#endforeach}}</text:p>'
        );

        $processor = new TemplateProcessor();
        $processor->applyRepeatingInDom(
            $dom,
            'items',
            [
                ['name' => 'Alpha'],
                ['name' => 'Beta'],
            ],
            fn (DOMNode $node, array $row): mixed => $processor->replaceScalarTextInSubtree(
                $node,
                $row,
                fn (string $filter, mixed $value, ?string $option): string =>
                    $processor->applyFilter($filter, (string) $value, $option)
            )
        );

        $xpath = $this->xpath($dom);
        $paragraphs = $xpath->query('//text:p[@text:style-name="RepeatBody"]');

        self::assertSame(2, $paragraphs->length);
        self::assertSame('Alpha', trim($paragraphs->item(0)?->textContent ?? ''));
        self::assertSame('Beta', trim($paragraphs->item(1)?->textContent ?? ''));

        foreach ($xpath->query('//text:p[@text:style-name="RepeatBody"]/text:span') ?: [] as $span) {
            self::assertInstanceOf(DOMElement::class, $span);
            self::assertSame('RepeatStrong', $span->getAttribute('text:style-name'));
        }
    }

    public function testForeachClonesNamedTableWithDuplicateNativeIdentity(): void
    {
        $dom = $this->dom(
            '<text:p>{{#foreach:items}}</text:p>'
            . '<table:table table:name="ItemsTable" table:style-name="ItemsTableStyle">'
            . '  <table:table-row>'
            . '    <table:table-cell><text:p>{{name}}</text:p></table:table-cell>'
            . '  </table:table-row>'
            . '</table:table>'
            . '<text:p>{{#endforeach}}</text:p>'
        );

        $processor = new TemplateProcessor();
        $processor->applyRepeatingInDom(
            $dom,
            'items',
            [
                ['name' => 'Alpha'],
                ['name' => 'Beta'],
            ],
            fn (DOMNode $node, array $row): mixed => $processor->replaceScalarTextInSubtree(
                $node,
                $row,
                fn (string $filter, mixed $value, ?string $option): string =>
                    $processor->applyFilter($filter, (string) $value, $option)
            )
        );

        $xpath = $this->xpath($dom);
        $tables = $xpath->query('//table:table[@table:name="ItemsTable"]');

        self::assertSame(2, $tables->length);

        foreach ($tables ?: [] as $table) {
            self::assertInstanceOf(DOMElement::class, $table);
            self::assertSame('ItemsTableStyle', $table->getAttribute('table:style-name'));
        }

        self::assertSame('Alpha', trim($tables->item(0)?->textContent ?? ''));
        self::assertSame('Beta', trim($tables->item(1)?->textContent ?? ''));
    }

    public function testForeachClonesBookmarkIdentityWithoutRewritingIt(): void
    {
        $dom = $this->dom(
            '<text:p>{{#foreach:items}}</text:p>'
            . '<text:p text:style-name="RepeatBody">'
            . '  <text:bookmark-start text:name="RepeatedBookmark"/>'
            . '  {{name}}'
            . '  <text:bookmark-end text:name="RepeatedBookmark"/>'
            . '</text:p>'
            . '<text:p>{{#endforeach}}</text:p>'
        );

        $processor = new TemplateProcessor();
        $processor->applyRepeatingInDom(
            $dom,
            'items',
            [
                ['name' => 'Alpha'],
                ['name' => 'Beta'],
            ],
            fn (DOMNode $node, array $row): mixed => $processor->replaceScalarTextInSubtree(
                $node,
                $row,
                fn (string $filter, mixed $value, ?string $option): string =>
                    $processor->applyFilter($filter, (string) $value, $option)
            )
        );

        $xpath = $this->xpath($dom);

        self::assertSame(
            2,
            $xpath->query('//text:bookmark-start[@text:name="RepeatedBookmark"]')->length
        );
        self::assertSame(
            2,
            $xpath->query('//text:bookmark-end[@text:name="RepeatedBookmark"]')->length
        );
    }

    public function testForeachClonesNamedSectionWithoutIdentityRewriting(): void
    {
        $dom = $this->dom(
            '<text:p>{{#foreach:items}}</text:p>'
            . '<text:section text:name="RepeatedSection">'
            . '  <text:p text:style-name="SectionBody">{{name}}</text:p>'
            . '</text:section>'
            . '<text:p>{{#endforeach}}</text:p>'
        );

        $processor = new TemplateProcessor();
        $processor->applyRepeatingInDom(
            $dom,
            'items',
            [
                ['name' => 'Alpha'],
                ['name' => 'Beta'],
            ],
            fn (DOMNode $node, array $row): mixed => $processor->replaceScalarTextInSubtree(
                $node,
                $row,
                fn (string $filter, mixed $value, ?string $option): string =>
                    $processor->applyFilter($filter, (string) $value, $option)
            )
        );

        $xpath = $this->xpath($dom);
        $sections = $xpath->query('//text:section[@text:name="RepeatedSection"]');

        self::assertSame(2, $sections->length);
        self::assertSame('Alpha', trim($sections->item(0)?->textContent ?? ''));
        self::assertSame('Beta', trim($sections->item(1)?->textContent ?? ''));
    }

    public function testConditionInsideForeachUsesOuterConditionDataRatherThanRowData(): void
    {
        $dom = $this->dom(
            '<text:p>{{#foreach:items}}</text:p>'
            . '<text:p>{{#if:active}}</text:p>'
            . '<text:p text:style-name="Active">active {{name}}</text:p>'
            . '<text:p>{{#else}}</text:p>'
            . '<text:p text:style-name="Inactive">inactive {{name}}</text:p>'
            . '<text:p>{{#endif}}</text:p>'
            . '<text:p>{{#endforeach}}</text:p>'
        );

        $processor = new TemplateProcessor();
        $processor->applyRepeatingInDom(
            $dom,
            'items',
            [
                ['name' => 'Alpha', 'active' => 'true'],
                ['name' => 'Beta', 'active' => 'false'],
            ],
            fn (DOMNode $node, array $row): mixed => $processor->replaceScalarTextInSubtree(
                $node,
                $row,
                fn (string $filter, mixed $value, ?string $option): string =>
                    $processor->applyFilter($filter, (string) $value, $option)
            )
        );

        $processor->applyConditionalsInDom(
            $dom,
            ['active' => false],
            fn (string $expression, array $values): bool => $processor->evaluateCondition($expression, $values)
        );

        $xpath = $this->xpath($dom);

        self::assertSame(0, $xpath->query('//text:p[@text:style-name="Active"]')->length);
        self::assertSame(2, $xpath->query('//text:p[@text:style-name="Inactive"]')->length);

        $inactive = $xpath->query('//text:p[@text:style-name="Inactive"]');
        self::assertSame('inactive Alpha', trim($inactive->item(0)?->textContent ?? ''));
        self::assertSame('inactive Beta', trim($inactive->item(1)?->textContent ?? ''));
    }

    private function dom(string $body): DOMDocument
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = false;
        self::assertTrue($dom->loadXML(
            '<office:document-content'
            . ' xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0"'
            . ' xmlns:text="urn:oasis:names:tc:opendocument:xmlns:text:1.0"'
            . ' xmlns:table="urn:oasis:names:tc:opendocument:xmlns:table:1.0">'
            . '<office:body><office:text>'
            . $body
            . '</office:text></office:body>'
            . '</office:document-content>'
        ));

        return $dom;
    }

    private function xpath(DOMDocument $dom): DOMXPath
    {
        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('office', 'urn:oasis:names:tc:opendocument:xmlns:office:1.0');
        $xpath->registerNamespace('text', 'urn:oasis:names:tc:opendocument:xmlns:text:1.0');
        $xpath->registerNamespace('table', 'urn:oasis:names:tc:opendocument:xmlns:table:1.0');

        return $xpath;
    }
}
