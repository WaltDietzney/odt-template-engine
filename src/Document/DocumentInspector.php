<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Document;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;

/**
 * Creates a read-only snapshot of native named structures in an ODF document.
 *
 * The inspector intentionally reports only the bounded addressability scope.
 * It does not expose DOM nodes and does not resolve mutable target handles.
 */
final class DocumentInspector
{
    private const DRAWING_NAMESPACE = 'urn:oasis:names:tc:opendocument:xmlns:drawing:1.0';
    private const OFFICE_NAMESPACE = 'urn:oasis:names:tc:opendocument:xmlns:office:1.0';
    private const TABLE_NAMESPACE = 'urn:oasis:names:tc:opendocument:xmlns:table:1.0';
    private const TEXT_NAMESPACE = 'urn:oasis:names:tc:opendocument:xmlns:text:1.0';

    public function inspect(DOMDocument $contentDom, DOMDocument $stylesDom): DocumentInspection
    {
        $contentXpath = $this->xpath($contentDom);
        $stylesXpath = $this->xpath($stylesDom);
        $diagnostics = [];

        $sections = $this->inspectSections($contentXpath, $diagnostics);
        $bookmarks = $this->inspectBookmarks($contentDom, $contentXpath, $diagnostics);
        $tables = $this->inspectTables($contentXpath, $stylesXpath, $diagnostics);
        $frames = $this->inspectFrames($contentXpath, $stylesXpath, $diagnostics);

        $this->addDuplicateDiagnostics($sections, 'section', $diagnostics);
        $this->addDuplicateDiagnostics($bookmarks, 'bookmark', $diagnostics);
        $this->addDuplicateDiagnostics($tables, 'table', $diagnostics);
        $this->addDuplicateDiagnostics($frames, 'frame', $diagnostics);

        return new DocumentInspection($sections, $bookmarks, $tables, $frames, $diagnostics);
    }

    /** @param list<InspectionDiagnostic> $diagnostics
     *  @return list<SectionDescriptor>
     */
    private function inspectSections(DOMXPath $xpath, array &$diagnostics): array
    {
        $sections = [];
        foreach ($xpath->query('//text:section') ?: [] as $node) {
            if (!$node instanceof DOMElement) {
                continue;
            }

            $name = $node->getAttribute('text:name');
            if ($name === '') {
                $diagnostics[] = $this->missingName('section');
                continue;
            }

            $nested = [];
            $seenBookmarks = [];
            foreach ($xpath->query(
                './/text:section[@text:name] | .//text:bookmark[@text:name] | .//text:bookmark-start[@text:name] | .//text:bookmark-end[@text:name] | .//table:table[@table:name] | .//draw:frame[@draw:name]',
                $node
            ) ?: [] as $child) {
                if (!$child instanceof DOMElement) {
                    continue;
                }

                if (in_array($child->nodeName, ['text:bookmark', 'text:bookmark-start', 'text:bookmark-end'], true)) {
                    $type = 'bookmark';
                    $attribute = 'text:name';
                    $bookmarkName = $child->getAttribute($attribute);
                    if (isset($seenBookmarks[$bookmarkName])) {
                        continue;
                    }
                    $seenBookmarks[$bookmarkName] = true;
                } elseif ($child->nodeName === 'text:section') {
                    $type = 'section';
                    $attribute = 'text:name';
                } elseif ($child->nodeName === 'table:table') {
                    $type = 'table';
                    $attribute = 'table:name';
                } else {
                    $type = 'frame';
                    $attribute = 'draw:name';
                }
                $nested[] = new NamedObjectReference($type, $child->getAttribute($attribute), 'content.xml');
            }

            $sections[] = new SectionDescriptor(
                $name,
                'content.xml',
                $this->childSummary($xpath, $node),
                $nested
            );
        }

        return $sections;
    }

    /** @param list<InspectionDiagnostic> $diagnostics
     *  @return list<BookmarkDescriptor>
     */
    private function inspectBookmarks(
        DOMDocument $dom,
        DOMXPath $xpath,
        array &$diagnostics
    ): array {
        /** @var array<string, list<DOMElement>> $markers */
        $markers = [];
        foreach ($xpath->query('//text:bookmark | //text:bookmark-start | //text:bookmark-end') ?: [] as $node) {
            if (!$node instanceof DOMElement) {
                continue;
            }

            $name = $node->getAttribute('text:name');
            if ($name === '') {
                $diagnostics[] = $this->missingName('bookmark');
                continue;
            }

            $markers[$name][] = $node;
        }

        $bookmarks = [];
        foreach ($markers as $name => $namedMarkers) {
            $collapsed = [];
            $starts = [];
            $ends = [];
            foreach ($namedMarkers as $marker) {
                if ($marker->nodeName === 'text:bookmark') {
                    $collapsed[] = $marker;
                } elseif ($marker->nodeName === 'text:bookmark-start') {
                    $starts[] = $marker;
                } elseif ($marker->nodeName === 'text:bookmark-end') {
                    $ends[] = $marker;
                }
            }

            $hasStart = $collapsed !== [] || $starts !== [];
            $hasEnd = $collapsed !== [] || $ends !== [];
            $start = $starts[0] ?? null;
            $end = $ends[0] ?? null;
            $bookmarkDiagnostics = [];

            if (count($collapsed) > 1 || count($starts) > 1 || count($ends) > 1) {
                $bookmarkDiagnostics[] = new InspectionDiagnostic(
                    'duplicate_bookmark_markers',
                    InspectionDiagnostic::SEVERITY_WARNING,
                    'Multiple bookmark markers use the same native name.',
                    'bookmark',
                    $name
                );
            }

            if ($collapsed !== []) {
                $topology = BookmarkDescriptor::TOPOLOGY_COLLAPSED;
                $text = '';
            } elseif ($start === null || $end === null) {
                $topology = BookmarkDescriptor::TOPOLOGY_MALFORMED;
                $text = null;
                $bookmarkDiagnostics[] = new InspectionDiagnostic(
                    'unpaired_bookmark_marker',
                    InspectionDiagnostic::SEVERITY_ERROR,
                    'Bookmark range has no matching start or end marker.',
                    'bookmark',
                    $name
                );
            } else {
                $topology = $this->classifyTopology($start, $end, $dom);
                $text = $this->rangeText($start, $end, $dom);
            }

            foreach ($bookmarkDiagnostics as $diagnostic) {
                $diagnostics[] = $diagnostic;
            }

            $bookmarks[] = new BookmarkDescriptor(
                $name,
                'content.xml',
                $hasStart,
                $hasEnd,
                $topology,
                $text,
                $bookmarkDiagnostics
            );
        }

        return $bookmarks;
    }

    /** @param list<InspectionDiagnostic> $diagnostics
     *  @return list<TableDescriptor>
     */
    private function inspectTables(
        DOMXPath $contentXpath,
        DOMXPath $stylesXpath,
        array &$diagnostics
    ): array {
        $tables = [];
        foreach ([[$contentXpath, 'content.xml'], [$stylesXpath, 'styles.xml']] as [$xpath, $part]) {
            foreach ($xpath->query('//table:table') ?: [] as $node) {
                if (!$node instanceof DOMElement) {
                    continue;
                }

                $name = $node->getAttribute('table:name');
                if ($name === '') {
                    $diagnostics[] = $this->missingName('table');
                    continue;
                }

                $rows = $this->tableRows($node);
                $tables[] = new TableDescriptor(
                    $name,
                    $part,
                    count($rows),
                    $this->columnCount($rows),
                    $this->containingSection($node),
                );
            }
        }

        return $tables;
    }

    /** @param list<InspectionDiagnostic> $diagnostics
     *  @return list<FrameDescriptor>
     */
    private function inspectFrames(
        DOMXPath $contentXpath,
        DOMXPath $stylesXpath,
        array &$diagnostics
    ): array {
        $frames = [];
        foreach ([[$contentXpath, 'content.xml'], [$stylesXpath, 'styles.xml']] as [$xpath, $part]) {
            foreach ($xpath->query('//draw:frame') ?: [] as $node) {
                if (!$node instanceof DOMElement) {
                    continue;
                }

                $name = $node->getAttribute('draw:name');
                if ($name === '') {
                    $diagnostics[] = $this->missingName('frame');
                    continue;
                }

                $payloadType = 'other';
                foreach ($node->childNodes as $child) {
                    if ($child->nodeName === 'draw:image') {
                        $payloadType = 'image';
                        break;
                    }
                    if ($child->nodeName === 'draw:text-box') {
                        $payloadType = 'text-box';
                    }
                }

                $frames[] = new FrameDescriptor(
                    $name,
                    $part,
                    $payloadType,
                    $node->getAttribute('svg:width') ?: null,
                    $node->getAttribute('svg:height') ?: null,
                    $this->containingSection($node),
                );
            }
        }

        return $frames;
    }

    /** @return array<string, int> */
    private function childSummary(DOMXPath $xpath, DOMElement $section): array
    {
        $summary = [];
        foreach ([
            'paragraphs' => './/text:p',
            'headings' => './/text:h',
            'lists' => './/text:list',
            'tables' => './/table:table',
            'frames' => './/draw:frame',
        ] as $key => $expression) {
            $summary[$key] = ($xpath->query($expression, $section)?->length) ?? 0;
        }

        return $summary;
    }

    /** @return list<DOMElement> */
    private function tableRows(DOMElement $table): array
    {
        $rows = [];
        foreach ($table->childNodes as $group) {
            if (!in_array($group->nodeName, ['table:table-rows', 'table:table-header-rows'], true)) {
                continue;
            }
            foreach ($group->childNodes as $row) {
                if ($row instanceof DOMElement && $row->nodeName === 'table:table-row') {
                    $rows[] = $row;
                }
            }
        }

        return $rows;
    }

    /** @param list<DOMElement> $rows */
    private function columnCount(array $rows): ?int
    {
        if ($rows === []) {
            return null;
        }

        $count = 0;
        foreach ($rows[0]->childNodes as $cell) {
            if (!$cell instanceof DOMElement || !in_array($cell->nodeName, ['table:table-cell', 'table:covered-table-cell'], true)) {
                continue;
            }
            $count += max(1, (int) ($cell->getAttribute('table:number-columns-repeated') ?: 1));
        }

        return $count;
    }

    private function containingSection(DOMNode $node): ?string
    {
        for ($ancestor = $node->parentNode; $ancestor !== null; $ancestor = $ancestor->parentNode) {
            if ($ancestor instanceof DOMElement && $ancestor->nodeName === 'text:section') {
                return $ancestor->getAttribute('text:name') ?: null;
            }
        }

        return null;
    }

    private function classifyTopology(DOMElement $start, DOMElement $end, DOMDocument $dom): string
    {
        $startParagraph = $this->nearestAncestor($start, ['text:p', 'text:h']);
        $endParagraph = $this->nearestAncestor($end, ['text:p', 'text:h']);
        if ($startParagraph !== null && $startParagraph === $endParagraph) {
            return BookmarkDescriptor::TOPOLOGY_INLINE;
        }

        if ($this->hasAncestor($start, ['text:list', 'text:list-item'])
            || $this->hasAncestor($end, ['text:list', 'text:list-item'])
        ) {
            return BookmarkDescriptor::TOPOLOGY_LIST_SPANNING;
        }

        if ($this->hasAncestor($start, ['table:table', 'table:table-cell'])
            || $this->hasAncestor($end, ['table:table', 'table:table-cell'])
        ) {
            return BookmarkDescriptor::TOPOLOGY_TABLE_SPANNING;
        }

        $nodes = $this->orderedNodes($dom);
        $positions = [];
        foreach ($nodes as $index => $node) {
            $positions[spl_object_id($node)] = $index;
        }
        $startIndex = $positions[spl_object_id($start)] ?? 0;
        $endIndex = $positions[spl_object_id($end)] ?? PHP_INT_MAX;
        $blockTypes = [];
        foreach ($nodes as $index => $node) {
            if ($index <= $startIndex || $index >= $endIndex) {
                continue;
            }
            if ($node instanceof DOMElement && in_array($node->nodeName, ['text:p', 'text:h', 'text:list', 'table:table', 'draw:frame'], true)) {
                $blockTypes[$node->nodeName] = true;
            }
        }

        if (isset($blockTypes['table:table']) || isset($blockTypes['draw:frame'])) {
            return BookmarkDescriptor::TOPOLOGY_MIXED_BLOCK;
        }

        if (count($blockTypes) > 1) {
            return BookmarkDescriptor::TOPOLOGY_MIXED_BLOCK;
        }

        return BookmarkDescriptor::TOPOLOGY_PARAGRAPH_SPANNING;
    }

    private function rangeText(DOMElement $start, DOMElement $end, DOMDocument $dom): ?string
    {
        $nodes = $this->orderedNodes($dom);
        $positions = [];
        foreach ($nodes as $index => $node) {
            $positions[spl_object_id($node)] = $index;
        }
        $startIndex = $positions[spl_object_id($start)] ?? null;
        $endIndex = $positions[spl_object_id($end)] ?? null;
        if ($startIndex === null || $endIndex === null || $startIndex >= $endIndex) {
            return null;
        }

        $text = '';
        foreach ($nodes as $index => $node) {
            if ($index <= $startIndex || $index >= $endIndex) {
                continue;
            }
            if ($node !== null && $node->nodeType === XML_TEXT_NODE) {
                $text .= $node->nodeValue;
            }
        }

        return $text;
    }

    /** @return list<DOMNode> nodes in document order */
    private function orderedNodes(DOMDocument $dom): array
    {
        $nodes = [];
        $walk = function (DOMNode $node) use (&$walk, &$nodes): void {
            $nodes[] = $node;
            foreach ($node->childNodes as $child) {
                $walk($child);
            }
        };
        $walk($dom);

        return $nodes;
    }

    /** @param list<string> $nodeNames */
    private function nearestAncestor(DOMNode $node, array $nodeNames): ?DOMNode
    {
        for ($current = $node; $current !== null; $current = $current->parentNode) {
            if (in_array($current->nodeName, $nodeNames, true)) {
                return $current;
            }
        }

        return null;
    }

    /** @param list<string> $nodeNames */
    private function hasAncestor(DOMNode $node, array $nodeNames): bool
    {
        return $this->nearestAncestor($node, $nodeNames) !== null;
    }

    private function addDuplicateDiagnostics(array $descriptors, string $type, array &$diagnostics): void
    {
        $counts = [];
        foreach ($descriptors as $descriptor) {
            $counts[$descriptor->name()] = ($counts[$descriptor->name()] ?? 0) + 1;
        }
        foreach ($counts as $name => $count) {
            if ($count > 1) {
                $diagnostics[] = new InspectionDiagnostic(
                    'duplicate_native_name',
                    InspectionDiagnostic::SEVERITY_ERROR,
                    sprintf('Multiple %s targets use the native name "%s".', $type, $name),
                    $type,
                    $name
                );
            }
        }
    }

    private function missingName(string $type): InspectionDiagnostic
    {
        return new InspectionDiagnostic(
            'missing_native_name',
            InspectionDiagnostic::SEVERITY_WARNING,
            sprintf('A native %s has no author-facing name.', $type),
            $type
        );
    }

    private function xpath(DOMDocument $dom): DOMXPath
    {
        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('draw', self::DRAWING_NAMESPACE);
        $xpath->registerNamespace('office', self::OFFICE_NAMESPACE);
        $xpath->registerNamespace('table', self::TABLE_NAMESPACE);
        $xpath->registerNamespace('text', self::TEXT_NAMESPACE);

        return $xpath;
    }
}
