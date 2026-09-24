<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Document;

use DOMDocument;
use DOMElement;
use DOMNode;
use OdtTemplateEngine\OdtDocumentContext;

/**
 * Performs bounded scalar population while retaining the native table node
 * and Writer-authored structural formatting.
 */
final class NativeTablePopulationService
{
    private const OFFICE_NS = 'urn:oasis:names:tc:opendocument:xmlns:office:1.0';
    private const TABLE_NS = 'urn:oasis:names:tc:opendocument:xmlns:table:1.0';

    /**
     * @param list<list<scalar|null>> $rows
     * @param array{keepRows?: list<int>} $options
     */
    public function populate(
        OdtDocumentContext $context,
        string $name,
        array $rows,
        array $options = []
    ): void {
        $table = $this->tableElement($context, $name);
        $structure = (new LogicalTableStructureReader())->read($table);
        $state = $context->nativeTablePopulationState($name);
        if ($state === null) {
            $state = $this->captureState($structure, $name);
        }

        $keepRows = $this->keepRows($options, count($state->ordinaryRows()), $name);
        $mutableIndexes = array_values(array_filter(
            array_keys($state->ordinaryRows()),
            static fn (int $index): bool => !isset($keepRows[$index])
        ));
        $this->validateRows($rows, $name);
        $this->validateSourceRows($state->ordinaryRows(), $mutableIndexes, $name);

        $mutableCount = count($mutableIndexes);
        if ($rows !== [] && $mutableCount === 0) {
            $this->fail($name, 'non-empty data requires at least one mutable source row');
        }
        if ($mutableCount === 0) {
            $context->setNativeTablePopulationState($name, $state);
            return;
        }

        $cellCount = $mutableCount === 0
            ? null
            : count($this->writableCells($state->ordinaryRows()[$mutableIndexes[0]], $name));
        foreach ($rows as $row) {
            if ($cellCount !== count($row)) {
                $this->fail($name, sprintf(
                    'data row has %d values but mutable rows require %d',
                    count($row),
                    $cellCount ?? 0
                ));
            }
        }

        $stagedTable = $table->cloneNode(true);
        if (!$stagedTable instanceof DOMElement) {
            $this->fail($name, 'unable to create a detached table for validation');
        }
        $stagedStructure = (new LogicalTableStructureReader())->read($stagedTable);
        $container = $this->container($stagedTable, $state, $name);
        $this->removeOrdinaryRows($stagedStructure);
        $outputRows = $this->buildRows(
            $context->contentDom(),
            $state,
            $keepRows,
            $mutableIndexes,
            $rows,
            $name
        );
        $this->insertRows($container, $outputRows);

        $originalChildren = [];
        foreach ($table->childNodes as $child) {
            $originalChildren[] = $child->cloneNode(true);
        }
        try {
            while ($table->firstChild !== null) {
                $table->removeChild($table->firstChild);
            }
            foreach (iterator_to_array($stagedTable->childNodes) as $child) {
                $table->appendChild($context->contentDom()->importNode($child, true));
            }
        } catch (\Throwable $exception) {
            while ($table->firstChild !== null) {
                $table->removeChild($table->firstChild);
            }
            foreach ($originalChildren as $child) {
                $table->appendChild($context->contentDom()->importNode($child, true));
            }
            throw $exception;
        }

        $context->setNativeTablePopulationState($name, $state);
    }

    private function tableElement(OdtDocumentContext $context, string $name): DOMElement
    {
        $descriptor = (new TypedTargetResolver())->resolveTableDescriptor($context, $name);
        if ($descriptor->documentPart() !== 'content.xml') {
            $this->fail($name, 'only content.xml tables can be populated');
        }

        $matches = [];
        foreach ($context->contentDom()->getElementsByTagNameNS(self::TABLE_NS, 'table') as $node) {
            if ($node instanceof DOMElement && $node->getAttribute('table:name') === $name) {
                $matches[] = $node;
            }
        }
        if (count($matches) !== 1) {
            $this->fail($name, 'table identity is not uniquely addressable in content.xml');
        }

        return $matches[0];
    }

    private function captureState(LogicalTableStructure $structure, string $name): NativeTablePopulationState
    {
        $ordinary = $structure->ordinaryRows();
        if ($ordinary === []) {
            return new NativeTablePopulationState([], 'table:table-rows', 0);
        }

        $parent = $ordinary[0]->element()->parentNode;
        if (!$parent instanceof DOMElement
            || !in_array($parent->nodeName, ['table:table', 'table:table-rows'], true)
        ) {
            $this->fail($name, 'ordinary rows are not in a supported Writer row container');
        }
        foreach ($ordinary as $row) {
            if ($row->element()->parentNode !== $parent) {
                $this->fail($name, 'ordinary rows use incompatible row containers');
            }
        }

        $ordinal = 0;
        if ($parent->nodeName === 'table:table-rows') {
            for ($child = $parent->previousSibling; $child !== null; $child = $child->previousSibling) {
                if ($child instanceof DOMElement && $child->nodeName === 'table:table-rows') {
                    ++$ordinal;
                }
            }
        }

        $clones = [];
        foreach ($ordinary as $row) {
            $clone = $row->element()->cloneNode(true);
            if (!$clone instanceof DOMElement) {
                $this->fail($name, 'unable to capture an authored ordinary row');
            }
            $clones[] = $clone;
        }

        return new NativeTablePopulationState($clones, $parent->nodeName, $ordinal);
    }

    /** @return array<int, true> */
    private function keepRows(array $options, int $sourceCount, string $name): array
    {
        foreach (array_keys($options) as $key) {
            if ($key !== 'keepRows') {
                $this->fail($name, sprintf('unsupported option "%s"', (string) $key));
            }
        }
        $values = $options['keepRows'] ?? [];
        if (!is_array($values) || !array_is_list($values)) {
            $this->fail($name, 'keepRows must be a list of integer source indices');
        }
        $normalized = [];
        foreach ($values as $value) {
            if (!is_int($value) || $value < 0 || $value >= $sourceCount) {
                $this->fail($name, 'keepRows contains an invalid source row index');
            }
            $normalized[$value] = true;
        }
        ksort($normalized);

        return $normalized;
    }

    private function validateRows(array $rows, string $name): void
    {
        if (!array_is_list($rows)) {
            $this->fail($name, 'data rows must be a list');
        }
        foreach ($rows as $row) {
            if (!is_array($row) || !array_is_list($row)) {
                $this->fail($name, 'each data row must be a list of scalar values');
            }
            foreach ($row as $value) {
                if ($value !== null && !is_scalar($value)) {
                    $this->fail($name, 'data cells must be scalar or null');
                }
                if (is_string($value) && preg_match('/[\r\n\t]/', $value) === 1) {
                    $this->fail($name, 'data cells cannot contain newline or tab characters');
                }
            }
        }
    }

    /** @param list<DOMElement> $sourceRows @param list<int> $mutableIndexes */
    private function validateSourceRows(array $sourceRows, array $mutableIndexes, string $name): void
    {
        if ($mutableIndexes === []) {
            return;
        }
        $basis = null;
        foreach ($mutableIndexes as $index) {
            $row = $sourceRows[$index];
            $cells = $this->writableCells($row, $name);
            $signature = $this->structureSignature($row);
            if ($basis === null) {
                $basis = [$signature, count($cells)];
            } elseif ($basis !== [$signature, count($cells)]) {
                $this->fail($name, 'mutable source rows have incompatible authored structures');
            }
        }
    }

    /** @return list<DOMElement> */
    private function writableCells(DOMElement $row, string $name): array
    {
        if ($row->hasAttribute('table:number-rows-repeated')) {
            $this->fail($name, 'repeated mutable rows are unsupported');
        }
        $cells = [];
        foreach ($row->childNodes as $cell) {
            if (!$cell instanceof DOMElement || $cell->nodeName !== 'table:table-cell') {
                $this->fail($name, 'covered or non-cell mutable row topology is unsupported');
            }
            foreach (['table:number-columns-repeated', 'table:number-columns-spanned', 'table:number-rows-spanned'] as $attribute) {
                if ($cell->hasAttribute($attribute)) {
                    $this->fail($name, 'repeated or spanned mutable cells are unsupported');
                }
            }
            if ($cell->hasAttribute('table:protected')) {
                $this->fail($name, 'protected mutable cells are unsupported');
            }
            $valueType = $cell->getAttribute('office:value-type');
            if ($valueType !== '' && $valueType !== 'string' || $cell->hasAttribute('office:value')) {
                $this->fail($name, 'typed or formula mutable cells are unsupported');
            }
            $paragraphs = [];
            foreach ($cell->childNodes as $child) {
                if ($child instanceof DOMElement && $child->nodeName === 'text:p') {
                    $paragraphs[] = $child;
                } elseif ($child->nodeType === XML_TEXT_NODE && trim($child->nodeValue ?? '') !== '') {
                    $this->fail($name, 'unstructured mutable cell content is unsupported');
                } elseif ($child instanceof DOMElement) {
                    $this->fail($name, 'complex mutable cell content is unsupported');
                }
            }
            if (count($paragraphs) !== 1 || !$this->supportsParagraph($paragraphs[0])) {
                $this->fail($name, 'mutable cells require one simple Writer paragraph');
            }
            $cells[] = $cell;
        }
        if ($cells === []) {
            $this->fail($name, 'mutable rows must contain writable cells');
        }

        return $cells;
    }

    private function supportsParagraph(DOMElement $paragraph): bool
    {
        foreach ($paragraph->childNodes as $child) {
            if ($child->nodeType === XML_TEXT_NODE) {
                continue;
            }
            if ($child instanceof DOMElement && $child->nodeName === 'text:span' && $this->supportsSpan($child)) {
                continue;
            }
            return false;
        }

        return true;
    }

    private function supportsSpan(DOMElement $span): bool
    {
        foreach ($span->childNodes as $child) {
            if ($child->nodeType !== XML_TEXT_NODE) {
                return false;
            }
        }

        return true;
    }

    private function structureSignature(DOMElement $row): string
    {
        $copy = $row->cloneNode(true);
        if (!$copy instanceof DOMElement) {
            return '';
        }
        $this->removeText($copy);

        return $copy->C14N() ?: '';
    }

    private function removeText(DOMNode $node): void
    {
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child->nodeType === XML_TEXT_NODE) {
                $node->removeChild($child);
                continue;
            }
            $this->removeText($child);
        }
    }

    private function container(DOMElement $table, NativeTablePopulationState $state, string $name): DOMElement
    {
        if ($state->containerNodeName() === 'table:table') {
            return $table;
        }
        $seen = 0;
        foreach ($table->childNodes as $child) {
            if ($child instanceof DOMElement && $child->nodeName === $state->containerNodeName()) {
                if ($seen === $state->containerOrdinal()) {
                    return $child;
                }
                ++$seen;
            }
        }
        $this->fail($name, 'the authored ordinary-row container is no longer present');
    }

    private function removeOrdinaryRows(LogicalTableStructure $structure): void
    {
        foreach ($structure->ordinaryRows() as $row) {
            $row->element()->parentNode?->removeChild($row->element());
        }
    }

    /**
     * @param array<int, true> $keepRows
     * @param list<int> $mutableIndexes
     * @param list<list<scalar|null>> $data
     * @return list<DOMElement>
     */
    private function buildRows(
        DOMDocument $document,
        NativeTablePopulationState $state,
        array $keepRows,
        array $mutableIndexes,
        array $data,
        string $name
    ): array {
        $sourceRows = $state->ordinaryRows();
        $dataIndex = 0;
        $output = [];
        $lastMutable = $mutableIndexes[count($mutableIndexes) - 1] ?? null;
        $insertExtrasBefore = null;
        foreach ($sourceRows as $index => $sourceRow) {
            if (isset($keepRows[$index])) {
                if ($lastMutable !== null && $index > $lastMutable && $insertExtrasBefore === null) {
                    $insertExtrasBefore = count($output);
                }
                $clone = $this->cloneInto($document, $sourceRow, $name);
                $output[] = $clone;
                continue;
            }
            if (!array_key_exists($dataIndex, $data)) {
                continue;
            }
            $clone = $this->cloneInto($document, $sourceRow, $name);
            $this->replaceRowPayload($clone, $data[$dataIndex], $name);
            $output[] = $clone;
            ++$dataIndex;
        }

        $extras = [];
        $basis = $mutableIndexes[0] ?? null;
        while (array_key_exists($dataIndex, $data)) {
            if ($basis === null) {
                $this->fail($name, 'no mutable source row is available for growth');
            }
            $clone = $this->cloneInto($document, $sourceRows[$basis], $name);
            $this->replaceRowPayload($clone, $data[$dataIndex], $name);
            $extras[] = $clone;
            ++$dataIndex;
        }
        if ($insertExtrasBefore === null) {
            $insertExtrasBefore = count($output);
        }
        array_splice($output, $insertExtrasBefore, 0, $extras);

        return $output;
    }

    private function cloneInto(DOMDocument $document, DOMElement $source, string $name): DOMElement
    {
        $clone = $source->ownerDocument === $document
            ? $source->cloneNode(true)
            : $document->importNode($source, true);
        if (!$clone instanceof DOMElement) {
            $this->fail($name, 'unable to clone an authored mutable row');
        }

        return $clone;
    }

    /** @param list<scalar|null> $values */
    private function replaceRowPayload(DOMElement $row, array $values, string $name): void
    {
        $cells = $this->writableCells($row, $name);
        foreach ($cells as $index => $cell) {
            $paragraph = null;
            foreach ($cell->childNodes as $child) {
                if ($child instanceof DOMElement && $child->nodeName === 'text:p') {
                    $paragraph = $child;
                    break;
                }
            }
            if (!$paragraph instanceof DOMElement) {
                $this->fail($name, 'mutable cell paragraph disappeared during staging');
            }
            $textNodes = $this->textNodes($paragraph);
            $value = $values[$index] === null ? '' : (string) $values[$index];
            if ($textNodes === []) {
                $paragraph->appendChild($paragraph->ownerDocument->createTextNode($value));
                continue;
            }
            $textNodes[0]->nodeValue = $value;
            foreach (array_slice($textNodes, 1) as $textNode) {
                $textNode->parentNode?->removeChild($textNode);
            }
        }
    }

    /** @return list<DOMNode> */
    private function textNodes(DOMNode $node): array
    {
        $nodes = [];
        foreach ($node->childNodes as $child) {
            if ($child->nodeType === XML_TEXT_NODE) {
                $nodes[] = $child;
            } elseif ($child instanceof DOMElement) {
                $nodes = [...$nodes, ...$this->textNodes($child)];
            }
        }

        return $nodes;
    }

    /** @param list<DOMElement> $rows */
    private function insertRows(DOMElement $container, array $rows): void
    {
        foreach ($rows as $row) {
            $container->appendChild($row);
        }
    }

    private function fail(string $name, string $reason): never
    {
        throw new NativeTablePopulationException($name, 'populate', $reason);
    }
}
