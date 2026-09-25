<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Integration;

use DOMDocument;
use DOMElement;
use DOMXPath;
use OdtTemplateEngine\Document\NativeTablePopulationException;
use OdtTemplateEngine\OdtTemplate;
use PHPUnit\Framework\TestCase;

final class TableRow01PopulationTest extends TestCase
{
    public function testRefillPreservesWriterTableAndCellFormatting(): void
    {
        $template = $this->template();
        $template->table('PopulationTable')->populate([
            ['New A', '100'],
            ['New B', '200'],
            ['New C', '300'],
        ]);

        self::assertSame('PopulationTable', $template->table('PopulationTable')->descriptor()->name());
        self::assertSame(4, $template->table('PopulationTable')->descriptor()->rowCount());
        self::assertSame(['Header', 'New A', 'New B', 'New C'], $this->firstColumn($template));
        self::assertSame('ReportTable', $this->table($template)->getAttribute('table:style-name'));
        foreach ($this->bodyRows($template) as $row) {
            self::assertSame('BodyRow', $row->getAttribute('table:style-name'));
            self::assertSame('BodyCell', $row->getElementsByTagNameNS($this->tableNs(), 'table-cell')->item(0)?->getAttribute('table:style-name'));
            self::assertSame('BodyParagraph', $row->getElementsByTagNameNS($this->textNs(), 'p')->item(0)?->getAttribute('text:style-name'));
        }
    }

    public function testRealLibreOfficeDirectRowFixtureCanBePopulated(): void
    {
        $template = new OdtTemplate(
            dirname(__DIR__, 2) . '/tests/fixtures/libreoffice-reference/odt/TABLE-02-formatted-cell.odt'
        );
        $template->table('Tabelle1')->populate([['Updated value']]);

        self::assertSame(1, $template->table('Tabelle1')->descriptor()->rowCount());
        self::assertSame(1, $template->table('Tabelle1')->descriptor()->columnCount());
    }

    public function testHeaderIsPreservedAndConsumesNoData(): void
    {
        $template = $this->template();
        $headerBefore = $this->header($template)->C14N();

        $template->table('PopulationTable')->populate([['Only data', '1']]);

        self::assertSame($headerBefore, $this->header($template)->C14N());
        self::assertSame(['Header', 'Only data'], $this->firstColumn($template));
    }

    public function testKeepRowsUseSourceOrdinaryIndicesAndRetainPositionAndContent(): void
    {
        $template = $this->template();
        $keptBefore = $this->bodyRows($template)[2]->C14N();

        $template->table('PopulationTable')->populate(
            [['Data A', '1'], ['Data B', '2']],
            ['keepRows' => [0, 2]]
        );

        self::assertSame(['Header', 'Row 0', 'Data A', 'Data B', 'Row 2'], $this->firstColumn($template));
        self::assertSame($keptBefore, $this->bodyRows($template)[3]->C14N());
    }

    public function testRepeatedPopulationWithSameKeepRowsUsesOriginalSourceRows(): void
    {
        $template = $this->template();
        $target = $template->table('PopulationTable');

        $target->populate([['First A', '1'], ['First B', '2']], ['keepRows' => [0, 2]]);
        $target->populate([['Second A', '3'], ['Second B', '4']], ['keepRows' => [0, 2]]);

        self::assertSame(['Header', 'Row 0', 'Second A', 'Second B', 'Row 2'], $this->firstColumn($template));
    }

    public function testRepeatedPopulationWithDifferentKeepRowsUsesOriginalSourceRows(): void
    {
        $template = $this->template();
        $target = $template->table('PopulationTable');

        $target->populate([['First A', '1'], ['First B', '2']], ['keepRows' => [0]]);
        $target->populate([['Second A', '3'], ['Second B', '4']], ['keepRows' => [2]]);

        self::assertSame(['Header', 'Second A', 'Second B', 'Row 2'], $this->firstColumn($template));
    }

    public function testSingleStyledSpanPreservesItsFormattingWhileReplacingScalarPayload(): void
    {
        $template = $this->template('styled-span');
        $template->table('PopulationTable')->populate([['Updated', '1']]);

        $paragraph = $this->bodyRows($template)[0]->getElementsByTagNameNS($this->textNs(), 'p')->item(0);
        self::assertInstanceOf(DOMElement::class, $paragraph);
        $span = $paragraph->getElementsByTagNameNS($this->textNs(), 'span')->item(0);
        self::assertInstanceOf(DOMElement::class, $span);
        self::assertSame('Value', $span->getAttribute('text:style-name'));
        self::assertSame('Updated', $span->textContent);
        self::assertSame('BodyParagraph', $paragraph->getAttribute('text:style-name'));
    }

    public function testEmptyParagraphReceivesScalarWithoutLosingParagraphFormatting(): void
    {
        $template = $this->template('empty-paragraph');
        $template->table('PopulationTable')->populate([['Filled', '1']]);

        $paragraph = $this->bodyRows($template)[0]->getElementsByTagNameNS($this->textNs(), 'p')->item(0);
        self::assertInstanceOf(DOMElement::class, $paragraph);
        self::assertSame('BodyParagraph', $paragraph->getAttribute('text:style-name'));
        self::assertSame('Filled', $paragraph->textContent);
    }

    public function testEmptyStyledSpanReceivesScalarWithoutLosingSpanFormatting(): void
    {
        $template = $this->template('empty-span');
        $template->table('PopulationTable')->populate([['Filled', '1']]);

        $span = $this->bodyRows($template)[0]->getElementsByTagNameNS($this->textNs(), 'span')->item(0);
        self::assertInstanceOf(DOMElement::class, $span);
        self::assertSame('Value', $span->getAttribute('text:style-name'));
        self::assertSame('Filled', $span->textContent);
    }

    public function testMultipleFormattedTextRunsAreRejectedAtomically(): void
    {
        $template = $this->template('ambiguous-spans');
        $before = $this->table($template)->C14N();

        $this->expectPopulationFailure(
            static fn (): mixed => $template->table('PopulationTable')->populate([['Rejected', '1']])
        );

        self::assertSame($before, $this->table($template)->C14N());
    }

    public function testShrinkRemovesSurplusMutableRows(): void
    {
        $template = $this->template();
        $template->table('PopulationTable')->populate([['Only', '1']]);

        self::assertSame(['Header', 'Only'], $this->firstColumn($template));
        self::assertSame(2, $template->table('PopulationTable')->descriptor()->rowCount());
    }

    public function testZeroDataRemovesMutableRowsButPreservesHeaderAndKeptRows(): void
    {
        $template = $this->template();
        $template->table('PopulationTable')->populate([], ['keepRows' => [1]]);

        self::assertSame(['Header', 'Row 1'], $this->firstColumn($template));
    }

    public function testGrowthClonesAnExistingMutableWriterRow(): void
    {
        $template = $this->template();
        $template->table('PopulationTable')->populate([
            ['A', '1'], ['B', '2'], ['C', '3'], ['D', '4'], ['E', '5'],
        ]);

        self::assertSame(6, $template->table('PopulationTable')->descriptor()->rowCount());
        self::assertSame(['Header', 'A', 'B', 'C', 'D', 'E'], $this->firstColumn($template));
        foreach ($this->bodyRows($template) as $row) {
            self::assertSame('BodyRow', $row->getAttribute('table:style-name'));
            self::assertSame('BodyCell', $row->getElementsByTagNameNS($this->tableNs(), 'table-cell')->item(0)?->getAttribute('table:style-name'));
        }
    }

    public function testRepeatedPopulationDoesNotAccumulateGeneratedRows(): void
    {
        $template = $this->template();
        $target = $template->table('PopulationTable');
        $target->populate([['A', '1'], ['B', '2'], ['C', '3'], ['D', '4'], ['E', '5']]);
        $target->populate([['F', '6'], ['G', '7']]);
        $target->populate([['H', '8'], ['I', '9'], ['J', '10'], ['K', '11']]);

        self::assertSame(['Header', 'H', 'I', 'J', 'K'], $this->firstColumn($template));
    }

    public function testInvalidKeepRowsFailWithoutChangingTheTable(): void
    {
        foreach ([[-1], [99]] as $keepRows) {
            $template = $this->template();
            $before = $this->table($template)->C14N();

            $this->expectPopulationFailure(
                static fn (): mixed => $template->table('PopulationTable')->populate([['X', '1']], ['keepRows' => $keepRows])
            );

            self::assertSame($before, $this->table($template)->C14N());
        }
    }

    public function testWrongDataWidthFailsAtomically(): void
    {
        $template = $this->template();
        $before = $this->table($template)->C14N();

        $this->expectPopulationFailure(
            static fn (): mixed => $template->table('PopulationTable')->populate([['Too few']])
        );

        self::assertSame($before, $this->table($template)->C14N());
    }

    public function testUnsupportedMutableTopologyFailsBeforeMutation(): void
    {
        $template = $this->template('repeated-cell');
        $before = $this->table($template)->C14N();

        $this->expectPopulationFailure(
            static fn (): mixed => $template->table('PopulationTable')->populate([['X', '1']])
        );

        self::assertSame($before, $this->table($template)->C14N());
    }

    public function testNonEmptyDataRequiresARealMutableSourceRow(): void
    {
        $template = $this->template('header-only');
        $before = $this->table($template)->C14N();

        $this->expectPopulationFailure(
            static fn (): mixed => $template->table('PopulationTable')->populate([['X', '1']])
        );

        self::assertSame($before, $this->table($template)->C14N());
    }

    public function testInspectTemplateRemainsSourceOrientedAndInspectReflectsPopulation(): void
    {
        $template = $this->template();
        $source = $template->inspectTemplate()->toArray();
        $template->table('PopulationTable')->populate([['Current', '1']]);

        self::assertSame($source, $template->inspectTemplate()->toArray());
        self::assertSame(2, $template->inspect()->table('PopulationTable')?->rowCount());
    }

    public function testNamedTableInsideSectionRetainsSectionContainment(): void
    {
        $template = $this->template('section');
        $template->table('PopulationTable')->populate([['Inside', '1']]);

        self::assertSame(
            'ReportSection',
            $template->table('PopulationTable')->descriptor()->containingSection()
        );
    }

    public function testSuccessfulPopulationSurvivesSaveAndReopen(): void
    {
        $template = $this->template();
        $template->table('PopulationTable')->populate([['Saved', '1']]);
        $path = tempnam(sys_get_temp_dir(), 'table-row-01-') . '.odt';
        self::assertNotFalse($path);

        try {
            $template->save($path);
            $reopened = new OdtTemplate($path);
            self::assertSame('PopulationTable', $reopened->table('PopulationTable')->descriptor()->name());
            self::assertSame(2, $reopened->table('PopulationTable')->descriptor()->rowCount());
        } finally {
            if (is_file($path)) {
                unlink($path);
            }
        }
    }

    private function expectPopulationFailure(callable $operation): void
    {
        try {
            $operation();
            self::fail('Expected native table population to fail.');
        } catch (NativeTablePopulationException $exception) {
            self::assertSame('populate', $exception->operation());
            self::assertNotSame('', $exception->reason());
        }
    }

    private function template(string $variant = 'normal'): PopulationTemplate
    {
        $template = new PopulationTemplate(
            dirname(__DIR__, 2) . '/tests/Fixtures/LegacySamples/templates/template_01_simple_variables.odt'
        );
        $template->addPopulationTable($variant);

        return $template;
    }

    /** @return list<string> */
    private function firstColumn(OdtTemplate $template): array
    {
        $values = [];
        foreach ($this->bodyAndHeaderRows($template) as $row) {
            $values[] = trim($row->getElementsByTagNameNS($this->textNs(), 'p')->item(0)?->textContent ?? '');
        }

        return $values;
    }

    /** @return list<DOMElement> */
    private function bodyRows(OdtTemplate $template): array
    {
        $rows = [];
        foreach ($this->xpath($template)->query('//table:table[@table:name="PopulationTable"]/table:table-rows/table:table-row') ?: [] as $row) {
            if ($row instanceof DOMElement) {
                $rows[] = $row;
            }
        }

        return $rows;
    }

    /** @return list<DOMElement> */
    private function bodyAndHeaderRows(OdtTemplate $template): array
    {
        $rows = [];
        foreach ($this->xpath($template)->query('//table:table[@table:name="PopulationTable"]//table:table-row') ?: [] as $row) {
            if ($row instanceof DOMElement) {
                $rows[] = $row;
            }
        }

        return $rows;
    }

    private function header(OdtTemplate $template): DOMElement
    {
        $node = $this->xpath($template)->query('//table:table[@table:name="PopulationTable"]/table:table-header-rows/table:table-row')->item(0);
        self::assertInstanceOf(DOMElement::class, $node);

        return $node;
    }

    private function table(OdtTemplate $template): DOMElement
    {
        $node = $this->xpath($template)->query('//table:table[@table:name="PopulationTable"]')->item(0);
        self::assertInstanceOf(DOMElement::class, $node);

        return $node;
    }

    private function xpath(OdtTemplate $template): DOMXPath
    {
        $xpath = new DOMXPath($template instanceof PopulationTemplate
            ? $template->contentDom()
            : new DOMDocument());
        $xpath->registerNamespace('table', $this->tableNs());
        $xpath->registerNamespace('text', $this->textNs());

        return $xpath;
    }

    private function tableNs(): string
    {
        return 'urn:oasis:names:tc:opendocument:xmlns:table:1.0';
    }

    private function textNs(): string
    {
        return 'urn:oasis:names:tc:opendocument:xmlns:text:1.0';
    }
}

final class PopulationTemplate extends OdtTemplate
{
    public function addPopulationTable(string $variant): void
    {
        $dom = $this->contentDom();
        $text = $dom->getElementsByTagNameNS(
            'urn:oasis:names:tc:opendocument:xmlns:office:1.0',
            'text'
        )->item(0);
        self::assert($text instanceof DOMElement);

        $table = $dom->createElementNS('urn:oasis:names:tc:opendocument:xmlns:table:1.0', 'table:table');
        $table->setAttribute('table:name', 'PopulationTable');
        $table->setAttribute('table:style-name', 'ReportTable');
        $table->appendChild($dom->createElementNS($this->tableNs(), 'table:table-column'));
        $table->appendChild($dom->createElementNS($this->tableNs(), 'table:table-column'));

        $headerGroup = $dom->createElementNS($this->tableNs(), 'table:table-header-rows');
        $headerGroup->appendChild($this->row($dom, 'Header', '0', 'HeaderRow', 'HeaderCell', 'HeaderParagraph'));
        $table->appendChild($headerGroup);

        if ($variant !== 'header-only') {
            $bodyGroup = $dom->createElementNS($this->tableNs(), 'table:table-rows');
            $bodyRows = [];
            $count = 3;
            for ($index = 0; $index < $count; ++$index) {
                $row = $this->row($dom, 'Row ' . $index, (string) $index, 'BodyRow', 'BodyCell', 'BodyParagraph');
                if ($variant === 'repeated-cell' && $index === 0) {
                    $row->getElementsByTagNameNS($this->tableNs(), 'table-cell')->item(0)?->setAttribute('table:number-columns-repeated', '2');
                }
                $bodyGroup->appendChild($row);
                $bodyRows[] = $row;
            }
            $table->appendChild($bodyGroup);
            $this->applyPayloadVariant($dom, $bodyRows, $variant);
        }

        if ($variant === 'section') {
            $section = $dom->createElementNS($this->textNs(), 'text:section');
            $section->setAttribute('text:name', 'ReportSection');
            $section->appendChild($table);
            $text->appendChild($section);
        } else {
            $text->appendChild($table);
        }
    }

    /** @param list<DOMElement> $rows */
    private function applyPayloadVariant(DOMDocument $dom, array $rows, string $variant): void
    {
        if (!in_array($variant, ['styled-span', 'empty-paragraph', 'empty-span', 'ambiguous-spans'], true)) {
            return;
        }
        foreach ($rows as $row) {
            $cell = $row->getElementsByTagNameNS($this->tableNs(), 'table-cell')->item(0);
            $paragraph = $cell?->getElementsByTagNameNS($this->textNs(), 'p')->item(0);
            if (!$cell instanceof DOMElement || !$paragraph instanceof DOMElement) {
                throw new \RuntimeException('Unable to prepare scalar payload fixture.');
            }
            while ($paragraph->firstChild !== null) {
                $paragraph->removeChild($paragraph->firstChild);
            }
            if ($variant === 'empty-paragraph') {
                continue;
            }
            $first = $dom->createElementNS($this->textNs(), 'text:span');
            $first->setAttribute('text:style-name', 'Value');
            if ($variant === 'styled-span') {
                $first->appendChild($dom->createTextNode('Original'));
                $paragraph->appendChild($first);
                continue;
            }
            if ($variant === 'empty-span') {
                $paragraph->appendChild($first);
                continue;
            }
            $first->setAttribute('text:style-name', 'ValueA');
            $first->appendChild($dom->createTextNode('Old'));
            $second = $dom->createElementNS($this->textNs(), 'text:span');
            $second->setAttribute('text:style-name', 'ValueB');
            $second->appendChild($dom->createTextNode(' value'));
            $paragraph->appendChild($first);
            $paragraph->appendChild($second);
        }
    }

    public function contentDom(): DOMDocument
    {
        return $this->documentContext()->contentDom();
    }

    private function row(
        DOMDocument $dom,
        string $first,
        string $second,
        string $rowStyle,
        string $cellStyle,
        string $paragraphStyle
    ): DOMElement {
        $row = $dom->createElementNS($this->tableNs(), 'table:table-row');
        $row->setAttribute('table:style-name', $rowStyle);
        foreach ([$first, $second] as $value) {
            $cell = $dom->createElementNS($this->tableNs(), 'table:table-cell');
            $cell->setAttribute('table:style-name', $cellStyle);
            $paragraph = $dom->createElementNS($this->textNs(), 'text:p');
            $paragraph->setAttribute('text:style-name', $paragraphStyle);
            $paragraph->appendChild($dom->createTextNode($value));
            $cell->appendChild($paragraph);
            $row->appendChild($cell);
        }

        return $row;
    }

    private function tableNs(): string
    {
        return 'urn:oasis:names:tc:opendocument:xmlns:table:1.0';
    }

    private function textNs(): string
    {
        return 'urn:oasis:names:tc:opendocument:xmlns:text:1.0';
    }

    private static function assert(bool $condition): void
    {
        if (!$condition) {
            throw new \RuntimeException('Unable to add the population fixture table.');
        }
    }
}
