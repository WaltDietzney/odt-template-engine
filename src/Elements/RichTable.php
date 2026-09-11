<?php

namespace OdtTemplateEngine\Elements;

use OdtTemplateEngine\Utils\StyleMapper;
use OdtTemplateEngine\Elements\OdtElement;
use OdtTemplateEngine\Elements\RichTableCell;
use OdtTemplateEngine\Document\StyleRequirement;
use OdtTemplateEngine\Utils\StyleWriter;
use DOMDocument;
use DOMNode;
use DOMElement;

/**
 * Represents a rich, styleable table element in an ODT document.
 *
 * Supports cell and row styling, column width definitions, header row grouping,
 * style presets, and relative column width definitions.
 */
class RichTable extends OdtElement
{
    /**
     * Global counter to auto-generate unique table names.
     *
     * @var int
     */
    protected static int $tableCounter = 1;

    /**
     * Collection of rows. Each row is an array with 'cells' and optional 'style'.
     *
     * @var array<int, array{cells: array, style: array}>
     */
    protected array $rows = [];

    /**
     * Optional style name applied to the table as a whole.
     *
     * @var string|null
     */
    protected ?string $tableStyleName = null;

    /** @var array<string, mixed> Element-owned table style properties. */
    protected array $tableStyleOptions = [];

    /**
     * Number of header rows that will be wrapped in `<table:table-header-rows>`.
     *
     * @var int
     */
    protected int $headerRowCount = 0;

    /**
     * Internal name for the table (used in ODT DOM).
     *
     * @var string
     */
    protected string $tableName;

    /**
     * User-defined named style presets (e.g., "finance", "report").
     *
     * @var array<string, array>
     */
    private array $customStyles = [];

    /**
     * Keywords to match for highlighting summary rows (e.g., "Total", "Summe").
     *
     * @var array<string>
     */
    private array $summaryKeywords = ['summe', 'gesamt', 'total'];

    /**
     * Fixed widths for columns in the table (e.g., "5cm", "3.5cm").
     *
     * @var array<int, string>
     */
    private array $columnWidths = [];

    /**
     * Relative width ratios for columns.
     *
     * @var array<int, int>
     */
    private array $columnWidthRatios = [];


    /**
     * Constructor: initializes a table with an auto-generated name.
     */
    public function __construct()
    {
        $this->tableName = 'Table_' . self::$tableCounter++;
    }
    /**
     * Adds a row to the table.
     *
     * Accepts RichTableCell instances or plain strings, Paragraphs, or RichText.
     * Automatically wraps non-cell content into a RichTableCell.
     *
     * Column ratios define relative column widths for semantic table-column
     * requirements; they do not alter caller-defined cell spans.
     *
     * @param array $cells Array of cell content or RichTableCell instances.
     * @param array $style Optional row-level style; currently supports `min-row-height`.
     * @return self
     */
    public function addRow(array $cells, array $style = []): self
    {
        foreach ($cells as &$cell) {
            if (!$cell instanceof RichTableCell) {
                $cell = new RichTableCell($cell);
            }
        }
        unset($cell);

        $this->rows[] = ['cells' => $cells, 'style' => $style];
        return $this;
    }

    /** @return iterable<int, OdtElement> */
    public function ownedElements(): iterable
    {
        foreach ($this->rows as $row) {
            foreach ($row['cells'] as $cell) {
                if ($cell instanceof RichTableCell) {
                    yield $cell;
                }
            }
        }
    }

    /**
     * Defines how many rows should be treated as table headers.
     *
     * @param int $count
     * @return self
     */
    public function setHeaderRowCount(int $count): self
    {
        $this->headerRowCount = $count;
        return $this;
    }

    /**
     * Assigns a style name to the table element.
     *
     * @param string $styleName
     * @return self
     */
    public function setTableStyleName(string $styleName): self
    {
        $this->tableStyleName = $styleName;
        $this->tableStyleOptions = [];
        return $this;
    }

    /**
     * Assigns element-owned properties for a generated table style.
     *
     * The resulting style is materialized through semantic requirements and
     * is not registered in process-global StyleMapper state.
     *
     * @param array<string, mixed> $style
     * @return self
     */
    public function setStyle(array $style): self
    {
        return $this->replaceElementOwnedTableStyle($style);
    }

    /**
     * Assigns a friendly element-owned table style.
     *
     * This is the semantic master API for table-level style/layout authoring.
     * It replaces the complete current local table style and explicitly
     * switches away from a named-style reference when necessary.
     *
     * @param array<string, mixed> $options
     * @return self
     */
    public function setTableStyle(array $options): self
    {
        if ($options === []) {
            return $this->replaceElementOwnedTableStyle([]);
        }

        if (array_key_exists('width', $options) && array_key_exists('relative-width', $options)) {
            throw new \InvalidArgumentException(
                'Table style cannot define both width and relative-width.'
            );
        }

        $this->validateFriendlyTableStyleOptions($options);

        if (isset($options['width']) && is_string($options['width'])) {
            $options['width'] = trim($options['width']);
        }
        if (isset($options['relative-width']) && is_string($options['relative-width'])) {
            $options['relative-width'] = trim($options['relative-width']);
        }
        if (isset($options['alignment']) && is_string($options['alignment'])) {
            $options['alignment'] = strtolower(trim($options['alignment']));
        }

        return $this->replaceElementOwnedTableStyle(
            StyleMapper::mapTableStyleOptions($options)
        );
    }

    /**
     * Sets the absolute width of the table.
     */
    public function setTableWidth(string $width): self
    {
        $this->assertElementOwnedTableStyleMutationAllowed();

        $width = trim($width);
        $this->assertOdfLength($width, 'Table width');

        $options = $this->tableStyleOptions;
        unset($options['style:rel-width']);
        $options['style:width'] = $width;

        return $this->replaceElementOwnedTableStyle($options);
    }

    /**
     * Sets the relative width of the table.
     */
    public function setTableRelativeWidth(string $width): self
    {
        $this->assertElementOwnedTableStyleMutationAllowed();

        if (!preg_match('/^(?:\d+(?:\.\d+)?|\.\d+)%$/', trim($width))) {
            throw new \InvalidArgumentException(
                'Relative table width must be a percentage string such as "60%".'
            );
        }

        $options = $this->tableStyleOptions;
        unset($options['style:width']);
        $options['style:rel-width'] = trim($width);

        return $this->replaceElementOwnedTableStyle($options);
    }

    /**
     * Sets the horizontal alignment of the table as a whole.
     */
    public function setTableAlignment(string $alignment): self
    {
        $this->assertElementOwnedTableStyleMutationAllowed();

        $alignment = strtolower(trim($alignment));
        if (!in_array($alignment, ['left', 'center', 'right', 'margins'], true)) {
            throw new \InvalidArgumentException(
                'Table alignment must be one of: left, center, right, margins.'
            );
        }

        $options = $this->tableStyleOptions;
        $options['table:align'] = $alignment;

        return $this->replaceElementOwnedTableStyle($options);
    }

    /**
     * Replaces the complete element-owned normalized table property state.
     *
     * @param array<string, mixed> $properties
     */
    private function replaceElementOwnedTableStyle(array $properties): self
    {
        if ($properties === []) {
            $this->tableStyleOptions = [];
            $this->tableStyleName = null;
            return $this;
        }

        $this->tableStyleOptions = $properties;
        $this->tableStyleName = StyleMapper::generateStyleName($properties);

        return $this;
    }

    private function assertElementOwnedTableStyleMutationAllowed(): void
    {
        if ($this->tableStyleName !== null && $this->tableStyleOptions === []) {
            throw new \LogicException(
                'Cannot mutate table style properties while a named table style reference is active.'
            );
        }
    }

    /**
     * @param array<string, mixed> $options
     */
    private function validateFriendlyTableStyleOptions(array $options): void
    {
        if (array_key_exists('width', $options)) {
            if (!is_string($options['width'])) {
                throw new \InvalidArgumentException('Table width must be a length string.');
            }
            $this->assertOdfLength($options['width'], 'Table width');
        }

        if (array_key_exists('relative-width', $options)) {
            if (!is_string($options['relative-width'])
                || !preg_match('/^(?:\d+(?:\.\d+)?|\.\d+)%$/', trim($options['relative-width']))) {
                throw new \InvalidArgumentException(
                    'Relative table width must be a percentage string such as "60%".'
                );
            }
        }

        if (array_key_exists('alignment', $options)) {
            if (!is_string($options['alignment'])
                || !in_array(strtolower(trim($options['alignment'])), ['left', 'center', 'right', 'margins'], true)) {
                throw new \InvalidArgumentException(
                    'Table alignment must be one of: left, center, right, margins.'
                );
            }

            $options['alignment'] = strtolower(trim($options['alignment']));
        }
    }

    private function assertOdfLength(string $value, string $label): void
    {
        $value = trim($value);

        if (!preg_match('/^(?:\d+(?:\.\d+)?|\.\d+)(?:cm|mm|in|pt|pc|px)$/', $value)) {
            throw new \InvalidArgumentException(
                sprintf('%s must be a non-empty ODF-compatible length string.', $label)
            );
        }
    }

    /**
     * Sets the internal name of the table.
     *
     * @param string $name
     * @return self
     */
    public function setTableName(string $name): self
    {
        $this->tableName = $name;
        return $this;
    }

    public function toDomNode(DOMDocument $dom): DOMNode
    {
        $styles = [];

        $columnWidths = $this->getColumnWidths();
        $columnStyleNames = [];
        if (!empty($this->columnWidthRatios)) {
            $columnStyleNames = $this->columnStyleNames($this->columnWidthRatios);
        } elseif (!empty($columnWidths)) {
            $columnStyleNames = $this->columnStyleNames($columnWidths);
            if (!$this->hasAllColumnStyles($dom, $columnStyleNames)) {
                StyleWriter::writeColumnStyles($dom, $columnWidths);
            }
        }


        foreach ($this->rows as $row) {
            foreach ($row['cells'] as $cell) {
                if ($cell instanceof RichTableCell) {
                    $styleDom = $cell->toStyleDomNode($dom);
                    if ($styleDom && $cell->getStyleName()) {
                        $styles[$cell->getStyleName()] = $styleDom;
                    }
                }
            }
        }

        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('office', 'urn:oasis:names:tc:opendocument:xmlns:office:1.0');
        $autoStyles = $xpath->query('//office:automatic-styles')->item(0);

        if ($autoStyles && $styles) {
            foreach ($styles as $styleNode) {
                if (!$this->styleExistsInContainer($autoStyles, $styleNode)) {
                    $autoStyles->appendChild($styleNode);
                }
            }
        }

        $table = $dom->createElement('table:table');
        $table->setAttribute('table:name', $this->tableName);

        if ($this->tableStyleName) {
            $table->setAttribute('table:style-name', $this->tableStyleName);
        }

        if (!empty($columnStyleNames)) {
            foreach ($columnStyleNames as $styleName) {
                $col = $dom->createElement('table:table-column');
                $col->setAttribute('table:style-name', $styleName);
                $table->appendChild($col);
            }
        } else {
            $columnCount = 0;
            foreach ($this->rows as $row) {
                if (isset($row['cells']) && is_array($row['cells'])) {
                    $columnCount = max($columnCount, count($row['cells']));
                }
            }
            $col = $dom->createElement('table:table-column');
            $col->setAttribute('table:number-columns-repeated', $columnCount);
            $table->appendChild($col);
        }




        $tableHeaderRows = null;
        $currentRow = 0;

        foreach ($this->rows as $row) {
            if ($this->headerRowCount > 0 && $currentRow < $this->headerRowCount) {
                if (!$tableHeaderRows) {
                    $tableHeaderRows = $dom->createElement('table:table-header-rows');
                    $table->appendChild($tableHeaderRows);
                }
                $parent = $tableHeaderRows;
            } else {
                $parent = $table;
            }

            $tr = $dom->createElement('table:table-row');
            if ($this->hasSupportedRowStyle($row['style'])) {
                $tr->setAttribute('table:style-name', $this->rowStyleName($currentRow));
            }

            foreach ($row['cells'] as $cell) {
                $tc = $dom->createElement('table:table-cell');

                if ($cell instanceof RichTableCell) {
                    $style = $cell->getStyleName();
                    if ($style) {
                        $tc->setAttribute('table:style-name', $style);
                    }

                    $content = $cell->getContent();
                    if ($content instanceof Paragraph || $content instanceof RichText) {
                        $contentDom = $content->toDomNode($dom);
                        if ($contentDom instanceof \DOMDocumentFragment) {
                            foreach ($contentDom->childNodes as $child) {
                                $tc->appendChild($child->cloneNode(true));
                            }
                        } else {
                            $tc->appendChild($contentDom);
                        }
                    } else {
                        $p = $dom->createElement('text:p');
                        $p->appendChild($dom->createTextNode((string) $content));
                        $tc->appendChild($p);
                    }

                    if ($cell->getColspan() > 1) {
                        $tc->setAttribute('table:number-columns-spanned', $cell->getColspan());
                    }
                    if ($cell->getRowspan() > 1) {
                        $tc->setAttribute('table:number-rows-spanned', $cell->getRowspan());
                    }
                } else {
                    $p = $dom->createElement('text:p');
                    $p->appendChild($dom->createTextNode((string) $cell));
                    $tc->appendChild($p);
                }

                $tr->appendChild($tc);
            }

            $parent->appendChild($tr);
            $currentRow++;
        }

        return $table;
    }

    private function styleExistsInContainer(DOMElement $container, DOMElement $candidate): bool
    {
        $styleNamespace = 'urn:oasis:names:tc:opendocument:xmlns:style:1.0';
        $candidateName = $candidate->getAttributeNS($styleNamespace, 'name')
            ?: $candidate->getAttribute('style:name');
        $candidateFamily = $candidate->getAttributeNS($styleNamespace, 'family')
            ?: $candidate->getAttribute('style:family');

        foreach ($container->getElementsByTagName('*') as $style) {
            if (!$style instanceof DOMElement || $style->localName !== 'style') {
                continue;
            }

            $name = $style->getAttributeNS($styleNamespace, 'name')
                ?: $style->getAttribute('style:name');
            $family = $style->getAttributeNS($styleNamespace, 'family')
                ?: $style->getAttribute('style:family');

            if ($name === $candidateName && $family === $candidateFamily) {
                return true;
            }
        }

        return false;
    }

    /** @param array<int, int|string> $widths */
    private function columnStyleNames(array $widths): array
    {
        $names = [];
        foreach (array_values($widths) as $index => $_width) {
            $names[] = 'co' . $index;
        }
        return $names;
    }

    /** @param list<string> $styleNames */
    private function hasAllColumnStyles(DOMDocument $dom, array $styleNames): bool
    {
        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('office', 'urn:oasis:names:tc:opendocument:xmlns:office:1.0');
        $automaticStyles = $xpath->query('//office:automatic-styles')->item(0);
        if (!$automaticStyles instanceof DOMElement) {
            return false;
        }

        foreach ($styleNames as $styleName) {
            $found = false;
            foreach ($automaticStyles->getElementsByTagName('*') as $style) {
                if (!$style instanceof DOMElement || !in_array($style->localName, ['style', 'style:style'], true)) {
                    continue;
                }
                $name = $style->getAttributeNS(
                    'urn:oasis:names:tc:opendocument:xmlns:style:1.0',
                    'name'
                ) ?: $style->getAttribute('style:name');
                $family = $style->getAttributeNS(
                    'urn:oasis:names:tc:opendocument:xmlns:style:1.0',
                    'family'
                ) ?: $style->getAttribute('style:family');
                if ($name === $styleName && $family === 'table-column') {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                return false;
            }
        }

        return true;
    }

    /** @return iterable<int, StyleRequirement> */
    public function getOwnStyleRequirements(): iterable
    {
        if (!empty($this->columnWidthRatios)) {
            foreach ($this->normalizedRelativeColumnWidths() as $index => $width) {
                yield new StyleRequirement(
                    StyleRequirement::KIND_DEFINITION,
                    StyleRequirement::SCOPE_AUTOMATIC,
                    'table-column',
                    StyleRequirement::PART_CONTENT,
                    'co' . $index,
                    null,
                    ['style:table-column-properties' => ['style:rel-column-width' => $width]]
                );
            }
        } else {
            foreach (array_values($this->columnWidths) as $index => $width) {
                yield new StyleRequirement(
                    StyleRequirement::KIND_DEFINITION,
                    StyleRequirement::SCOPE_AUTOMATIC,
                    'table-column',
                    StyleRequirement::PART_CONTENT,
                    'co' . $index,
                    null,
                    ['style:table-column-properties' => ['style:column-width' => $width]]
                );
            }
        }

        foreach ($this->rows as $index => $row) {
            if (!$this->hasSupportedRowStyle($row['style'])) {
                continue;
            }

            yield new StyleRequirement(
                StyleRequirement::KIND_DEFINITION,
                StyleRequirement::SCOPE_AUTOMATIC,
                'table-row',
                StyleRequirement::PART_CONTENT,
                $this->rowStyleName($index),
                null,
                [
                    'style:table-row-properties' => [
                        'style:min-row-height' => $row['style']['min-row-height'],
                    ],
                ]
            );
        }

        if ($this->tableStyleName === null) {
            return;
        }

        if ($this->tableStyleOptions !== []) {
            yield new StyleRequirement(
                StyleRequirement::KIND_DEFINITION,
                StyleRequirement::SCOPE_COMMON,
                'table',
                StyleRequirement::PART_STYLES,
                $this->tableStyleName,
                null,
                ['style:table-properties' => $this->tableStyleOptions]
            );

            return;
        }

        yield new StyleRequirement(
            StyleRequirement::KIND_REFERENCE,
            null,
            'table',
            null,
            $this->tableStyleName
        );
    }

    private function hasSupportedRowStyle(array $style): bool
    {
        return array_key_exists('min-row-height', $style);
    }

    private function rowStyleName(int $rowIndex): string
    {
        return $this->tableName . '_ro' . $rowIndex;
    }

    /** @return list<string> */
    private function normalizedRelativeColumnWidths(): array
    {
        $ratios = array_values($this->columnWidthRatios);
        $positiveIntegers = $ratios !== [] && array_reduce(
            $ratios,
            static fn (bool $valid, mixed $ratio): bool => $valid && is_int($ratio) && $ratio > 0,
            true
        );

        if (!$positiveIntegers) {
            return array_map(static fn (mixed $ratio): string => (string) $ratio . '*', $ratios);
        }

        $sum = array_sum($ratios);
        $unit = intdiv(65535, $sum);
        $widths = [];
        $materialized = 0;

        foreach ($ratios as $index => $ratio) {
            if ($index === array_key_last($ratios)) {
                $width = 65535 - $materialized;
            } else {
                $width = $unit * $ratio;
                $materialized += $width;
            }

            $widths[] = $width . '*';
        }

        return $widths;
    }

    public function buildTableFromArray(array $tableData, string $styleName = 'default'): self
    {
        $styleSet = $this->customStyles[$styleName] ?? $this->getPredefinedStyles($styleName);

        foreach ($tableData as $rowIndex => $row) {
            $cells = [];

            $isSummaryRow = isset($row[0]) && $this->matchesSummaryKeywords($row[0]);

            foreach ($row as $cellContent) {
                $paragraph = $cellContent instanceof Paragraph ? $cellContent : (new Paragraph())->addText((string) $cellContent);
                $cell = new RichTableCell($paragraph);

                $currentStyle = null;
                if ($rowIndex === 0 && isset($styleSet['header'])) {
                    $currentStyle = $styleSet['header'];
                } elseif ($isSummaryRow && isset($styleSet['summary'])) {
                    $currentStyle = $styleSet['summary'];
                } elseif ($isSummaryRow && isset($styleSet['highlight'])) {
                    $currentStyle = $styleSet['highlight'];
                } elseif ($rowIndex % 2 === 0 && isset($styleSet['row'])) {
                    $currentStyle = $styleSet['row'];
                } elseif (isset($styleSet['row-alt'])) {
                    $currentStyle = $styleSet['row-alt'];
                }

                if ($currentStyle) {
                    $cell->setStyle($currentStyle);

                    if (isset($currentStyle['text-align']) && $cell->getContent() instanceof Paragraph) {
                        $cell->getContent()->setParagraphStyle(
                            $this->getAlignParagraphStyle($currentStyle['text-align'])
                        );
                    }
                }

                $cells[] = $cell;
            }

            $this->addRow($cells);
        }

        return $this;
    }

    /**
     * Check if a cell matches any summary keyword
     */
    private function matchesSummaryKeywords($cellContent): bool
    {
        $text = strtolower(trim((string) $cellContent));
        foreach ($this->summaryKeywords as $keyword) {
            if (strpos($text, strtolower($keyword)) !== false) {
                return true;
            }
        }
        return false;
    }



    // Hilfsfunktion bleibt private
    private function getAlignParagraphStyle(string $align): string
    {
        return match (strtolower($align)) {
            'center' => 'CenterPara',
            'right' => 'RightPara',
            'left' => 'LeftPara',
            default => 'LeftPara'
        };
    }



    private function getPredefinedStyles(string $styleName): array
    {
        $styles = [
            'finance' => [
                'header' => [
                    'background' => '#004080',
                    'color' => '#ffffff',
                    'font-weight' => 'bold',
                    'text-align' => 'center',
                    'padding' => '0.2cm',
                    'border' => '0.1pt solid #003366',
                ],
                'row' => [
                    'background' => '#e6f0ff',
                    'text-align' => 'right',
                    'padding' => '0.2cm',
                    'border' => '0.1pt solid #b3c6ff',
                ],
                'row-alt' => [
                    'background' => '#ffffff',
                    'text-align' => 'right',
                    'padding' => '0.2cm',
                    'border' => '0.1pt solid #b3c6ff',
                ]
            ],
            'default' => [
                'header' => [
                    'background' => '#dddddd',
                    'font-weight' => 'bold',
                    'text-align' => 'left',
                    'padding' => '0.15cm',
                    'border' => '0.05pt solid #999999',
                ],
                'row' => [
                    'background' => '#f9f9f9',
                    'padding' => '0.15cm',
                    'border' => '0.05pt solid #dddddd',
                ],
                'row-alt' => [
                    'background' => '#ffffff',
                    'padding' => '0.15cm',
                    'border' => '0.05pt solid #dddddd',
                ]
            ],
            'finance-light' => [
                'header' => [
                    'background' => '#004d40',
                    'color' => '#ffffff',
                    'font-weight' => 'bold',
                    'text-align' => 'center',
                ],
                'row' => [
                    'background' => '#e0f2f1',
                    'text-align' => 'right',
                ],
                'row-alt' => [
                    'background' => '#b2dfdb',
                    'text-align' => 'right',
                ],
                'summary' => [
                    'background' => '#00796b',
                    'color' => '#ffffff',
                    'font-weight' => 'bold',
                ],
            ],

            'marketing' => [
                'header' => [
                    'background' => '#1e88e5',
                    'color' => '#ffffff',
                    'font-weight' => 'bold',
                    'text-align' => 'center',
                ],
                'row' => [
                    'background' => '#e3f2fd',
                    'text-align' => 'left',
                ],
                'row-alt' => [
                    'background' => '#bbdefb',
                    'text-align' => 'left',
                ],
            ],

            'report' => [
                'header' => [
                    'background' => '#212121',
                    'color' => '#ffffff',
                    'font-weight' => 'bold',
                    'text-align' => 'left',
                ],
                'row' => [
                    'background' => '#f5f5f5',
                    'text-align' => 'justify',
                ],
                'row-alt' => [
                    'background' => '#eeeeee',
                    'text-align' => 'justify',
                ],
            ],

            'highlighted' => [
                'header' => [
                    'background' => '#ff6f00',
                    'color' => '#ffffff',
                    'font-weight' => 'bold',
                    'text-align' => 'center',
                ],
                'row' => [
                    'background' => '#fff3e0',
                    'text-align' => 'center',
                ],
                'row-alt' => [
                    'background' => '#ffe0b2',
                    'text-align' => 'center',
                ],
                'highlight' => [
                    'background' => '#ffcc80',
                    'font-weight' => 'bold',
                ],
            ],
            // weitere Stile können hier ergänzt werden
        ];

        return $styles[$styleName] ?? $styles['default'];
    }

    public function addCustomStyle(string $name, array $styleDefinition): self
    {
        $this->customStyles[$name] = $styleDefinition;
        return $this;
    }

    public function getCustomStyle(string $name): ?array
    {
        return $this->customStyles[$name] ?? null;
    }

    public function setSummaryKeywords(array $keywords): self
    {
        $this->summaryKeywords = array_map('strtolower', $keywords);
        return $this;
    }

    public function setColumnWidths(array $widths): void
    {
        $this->columnWidths = $widths;

        // Sofort auf erste Zeile anwenden, falls vorhanden
        if (!empty($this->rows[0]['cells'])) {
            foreach ($this->rows[0]['cells'] as $i => $cell) {
                if ($cell instanceof RichTableCell && isset($widths[$i])) {
                    $cell->setWidth($widths[$i]); // nutzt deine neue setWidth()-Methode
                }
            }
        }
    }


    /**
     * Gets the defined column widths.
     *
     * @return array<int, string>
     */
    public function getColumnWidths(): array
    {
        return $this->columnWidths;
    }

    public function getTableStyleName(): ?string
    {
        return $this->tableStyleName;
    }


    public function setColumnWidthRatios(array $ratios): void
    {
        $this->columnWidthRatios = $ratios;
    }


}
