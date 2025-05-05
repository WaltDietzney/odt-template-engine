<?php

namespace OdtTemplateEngine\Elements;

use OdtTemplateEngine\Utils\StyleMapper;
use OdtTemplateEngine\Elements\OdtElement;
use OdtTemplateEngine\Elements\RichTableCell;
use OdtTemplateEngine\Contracts\HasStyles;
use DOMDocument;
use DOMNode;

/**
 * Represents a rich table element in an ODT document.
 * 
 * This class provides methods for adding rows, cells, and handling various content types like text, 
 * paragraphs, and other elements. It also manages the required styles for the table and its contents.
 */
class RichTable extends OdtElement implements HasStyles
{
    /**
     * @var array Contains the rows of the table. Each row is an array of cells.
     */
    protected array $rows = [];


    /**
     * Adds a row to the table.
     * 
     * The cells in the row can be of different types, including string, Paragraph, RichText, or RichTableCell.
     * 
     * @param array $cells An array of cells, which can be of type string, Paragraph, RichText, or RichTableCell.
     * @param array $style Optional style for the row.
     * @return self The current instance of RichTable.
     * @throws \InvalidArgumentException If cells is not an array.
     */
    public function addRow(array $cells, array $style = []): self
    {
        if (!is_array($cells)) {
            throw new \InvalidArgumentException("Cells must be an array");
        }

        $this->rows[] = ['cells' => $cells, 'style' => $style];
        return $this;
    }

    /**
     * Converts the RichTable object to a DOM node.
     * 
     * This method creates a table element in ODT format, including table columns, rows, and cells,
     * and attaches the appropriate styles to each element.
     * 
     * @param DOMDocument $dom The DOM document to append the table to.
     * @return DOMNode The resulting table node.
     */
    public function toDomNode(DOMDocument $dom): DOMNode
    {
        // 🪄 Collect styles for the cells
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

        // 🎨 Find or create automatic styles
        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('office', 'urn:oasis:names:tc:opendocument:xmlns:office:1.0');
        $autoStyles = $xpath->query('//office:automatic-styles')->item(0);

        // 🎨 Append collected styles to automatic-styles (if exists)
        if ($autoStyles && $styles) {
            foreach ($styles as $styleNode) {
                $autoStyles->appendChild($styleNode);
            }
        }

        // 📦 Create the table node
        $table = $dom->createElement('table:table');
        $table->setAttribute('table:name', 'Tabelle1');

        // 🧠 Calculate the number of columns based on the longest row
        $columnCount = 0;
        foreach ($this->rows as $row) {
            if (isset($row['cells']) && is_array($row['cells'])) {
                $columnCount = max($columnCount, count($row['cells']));
            }
        }

        // 🧩 Declare the columns (mandatory in ODF)
        $col = $dom->createElement('table:table-column');
        $col->setAttribute('table:number-columns-repeated', $columnCount);
        $table->appendChild($col);

        // 🔁 Build the table content by iterating over rows and cells
        foreach ($this->rows as $row) {
            $tr = $dom->createElement('table:table-row');

            foreach ($row['cells'] as $cell) {
                $tc = $dom->createElement('table:table-cell');

                // 🔹 Handle RichTableCell first
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

                    // 🔹 Handle Paragraph / RichText
                } elseif ($cell instanceof Paragraph || $cell instanceof RichText) {
                    $cellDom = $cell->toDomNode($dom);
                    if ($cellDom instanceof \DOMDocumentFragment) {
                        foreach ($cellDom->childNodes as $child) {
                            $tc->appendChild($child->cloneNode(true));
                        }
                    } else {
                        $tc->appendChild($cellDom);
                    }

                    // 🔹 Handle plain text
                } else {
                    $p = $dom->createElement('text:p');
                    $p->appendChild($dom->createTextNode((string) $cell));
                    $tc->appendChild($p);
                }

                $tr->appendChild($tc);
            }

            $table->appendChild($tr);
        }

        return $table;
    }

    /**
     * Returns an array of all the required styles for the table's cells and content.
     * 
     * This method collects the styles for all table cells and their content, 
     * including any required styles for paragraphs or rich text elements embedded in the cells.
     * 
     * @return array An array of style definitions required by the table and its cells.
     */
    public function getRequiredStyles(): array
    {
        $styles = [];

        foreach ($this->rows as $row) {
            foreach ($row['cells'] as $cell) {
                $cellStyles = $cell->getStyleDefinitions();
                if ($cellStyles) {
                    $styles += $cellStyles;
                }

                // If the content itself needs styles
                $content = (new \ReflectionClass($cell))->getProperty('content');
                $content->setAccessible(true);
                $inner = $content->getValue($cell);

                if ($inner instanceof OdtElement) {
                    $styles += $inner->getRequiredStyles();
                }
            }
        }

        return $styles;
    }


    /**
     * Registers styles for the table cells and their content.
     * 
     * This method ensures that all styles used in the table's rows and cells are registered 
     * so they can be applied when generating the ODT document.
     * 
     * @return void
     */
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
                        if ($styleNode instanceof \DOMElement) {
                            $nodes[] = $styleNode;
                            $names[] = $styleName;
                        }
                    }
                }
            }
        }

        return $nodes;
    }


}
