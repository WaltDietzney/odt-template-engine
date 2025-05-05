<?php

namespace OdtTemplateEngine\Elements;

use OdtTemplateEngine\Utils\StyleMapper;
use OdtTemplateEngine\Elements\OdtElement;
use OdtTemplateEngine\Elements\RichTableCell;
use OdtTemplateEngine\Contracts\HasStyles;
use DOMDocument;
use DOMNode;
use DOMElement;

/**
 * Represents a rich table element in an ODT document.
 */
class RichTable extends OdtElement implements HasStyles
{
    protected static int $tableCounter = 1; // Counter für automatische Tabellennamen
    protected array $rows = [];
    protected ?string $tableStyleName = null;
    protected int $headerRowCount = 0;
    protected string $tableName;

    private array $customStyles = [];

    // In RichTable (oben bei den Eigenschaften)
    private array $summaryKeywords = ['summe', 'gesamt', 'total'];



    public function __construct()
    {
        $this->tableName = 'Table_' . self::$tableCounter++;
    }

    public function addRow(array $cells, array $style = []): self
    {
        if (!is_array($cells)) {
            throw new \InvalidArgumentException("Cells must be an array");
        }

        // Automatisch Inhalte in RichTableCell wrappen, wenn nötig
        foreach ($cells as &$cell) {
            if (!$cell instanceof RichTableCell && !$cell instanceof Paragraph && !$cell instanceof RichText) {
                $cell = new RichTableCell($cell);
            }
        }

        $this->rows[] = ['cells' => $cells, 'style' => $style];
        return $this;
    }

    public function setHeaderRowCount(int $count): self
    {
        $this->headerRowCount = $count;
        return $this;
    }

    public function setTableStyleName(string $styleName): self
    {
        $this->tableStyleName = $styleName;
        return $this;
    }

    public function setTableName(string $name): self
    {
        $this->tableName = $name;
        return $this;
    }

    public function toDomNode(DOMDocument $dom): DOMNode
    {
        $styles = [];

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
                $autoStyles->appendChild($styleNode);
            }
        }

        $table = $dom->createElement('table:table');
        $table->setAttribute('table:name', $this->tableName);

        if ($this->tableStyleName) {
            $table->setAttribute('table:style-name', $this->tableStyleName);
        }

        $columnCount = 0;
        foreach ($this->rows as $row) {
            if (isset($row['cells']) && is_array($row['cells'])) {
                $columnCount = max($columnCount, count($row['cells']));
            }
        }
        $col = $dom->createElement('table:table-column');
        $col->setAttribute('table:number-columns-repeated', $columnCount);
        $table->appendChild($col);

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

    public function getRequiredStyles(): array
    {
        $styles = [];

        foreach ($this->rows as $row) {
            foreach ($row['cells'] as $cell) {
                if (method_exists($cell, 'getStyleDefinitions')) {
                    $cellStyles = $cell->getStyleDefinitions();
                    if ($cellStyles) {
                        $styles += $cellStyles;
                    }
                }

                if (property_exists($cell, 'content')) {
                    $reflection = new \ReflectionClass($cell);
                    $contentProp = $reflection->getProperty('content');
                    $contentProp->setAccessible(true);
                    $inner = $contentProp->getValue($cell);

                    if ($inner instanceof OdtElement) {
                        $styles += $inner->getRequiredStyles();
                    }
                }
            }
        }

        return $styles;
    }

    public function registerStyles(): void
    {
        foreach ($this->rows as $row) {
            foreach ($row['cells'] as $cell) {
                if ($cell instanceof HasStyles) {
                    $cell->registerStyles();
                }
            }
        }
    }

    public function getRequiredTableCellStyleNodes(): array
    {
        $nodes = [];
        $names = [];

        foreach ($this->rows as $row) {
            foreach ($row['cells'] as $cell) {
                if ($cell instanceof RichTableCell) {
                    $styleName = $cell->getStyleName();
                    if ($styleName && !in_array($styleName, $names, true)) {
                        $styleNode = $cell->toStyleDomNode(new DOMDocument());
                        if ($styleNode instanceof DOMElement) {
                            $nodes[] = $styleNode;
                            $names[] = $styleName;
                        }
                    }
                }
            }
        }

        return $nodes;
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


}
