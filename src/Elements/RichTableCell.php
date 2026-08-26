<?php

namespace OdtTemplateEngine\Elements;

use DOMDocument;
use DOMNode;
use DOMElement;
use OdtTemplateEngine\Contracts\HasStyles;
use OdtTemplateEngine\Utils\StyleMapper;
use OdtTemplateEngine\Utils\StyleOptionSplitter;

/**
 * Represents a single cell within a table in an ODT document.
 *
 * Supports plain text, Paragraphs, or RichText content and allows styling for
 * both the cell and the internal paragraph (e.g., text alignment).
 *
 * Example:
 * ```php
 * $cell = (new RichTableCell('Total'))
 *     ->setBackground('#f0f0f0')
 *     ->alignRight()
 *     ->setColspan(2);
 * ```
 */
class RichTableCell extends OdtElement implements HasStyles
{
    /**
     * The content of the cell.
     *
     * Can be a string, Paragraph, or RichText.
     *
     * @var mixed
     */
    protected mixed $content;

    /**
     * Style definition for the table cell (ODF-compatible attributes).
     *
     * @var array<string, string>
     */
    protected array $style = [];

    /**
     * Auto-generated or assigned style name.
     *
     * @var string
     */
    protected string $styleName = '';

    /**
     * Whether the content should be wrapped in a paragraph explicitly.
     *
     * @var bool
     */
    protected bool $forceParagraph = false;

    /**
     * Number of columns this cell spans.
     *
     * @var int
     */
    protected int $colspan = 1;

    /**
     * Number of rows this cell spans.
     *
     * @var int
     */
    protected int $rowspan = 1;

    /** Whether the current paragraph was created from plain string content. */
    protected bool $contentCreatedFromString = false;

    /** Original plain text used for compatibility style normalization. */
    protected string $plainTextContent = '';

    /**
     * Constructor.
     *
     * @param string|Paragraph|RichText $content The cell content.
     * @param array<string, string> $style Optional style array.
     */
    public function __construct(string|Paragraph|RichText $content, array $style = [])
    {
        if (is_string($content)) {
            $this->contentCreatedFromString = true;
            $this->plainTextContent = $content;
            $this->content = (new Paragraph())->addText($content);
        } else {
            $this->content = $content;
        }

        $this->setStyle($style);
        $this->registerStylesAndRefresh();
    }

    /**
     * Sets the cell content.
     *
     * @param mixed $content
     * @return self
     */
    public function setContent(mixed $content): self
    {
        $this->content = $content;
        $this->contentCreatedFromString = false;
        $this->plainTextContent = '';
        return $this;
    }

    /**
     * Returns the cell content.
     *
     * @return mixed
     */
    public function getContent(): mixed
    {
        return $this->content;
    }

    /**
     * Applies a style array to the cell.
     *
     * Supports both cell and paragraph styling (text alignment).
     *
     * @param array<string, string> $style
     * @return self
     */
    public function setStyle(array $style): self
    {
        $split = StyleOptionSplitter::split($style, 'table-cell');
        $this->style = StyleMapper::mapTableCellStyleOptions($split['cell']);
        $this->styleName = StyleMapper::generateStyleName($this->style);
        StyleMapper::registerTableCellStyle($this->styleName, $this->style);

        if (
            $this->contentCreatedFromString
            && (!empty($split['text']) || !empty($split['paragraph']))
        ) {
            $paragraph = new Paragraph();
            if (!empty($split['paragraph'])) {
                $paragraph->setParagraphStyleOptions($split['paragraph']);
            }
            $paragraph->addText($this->plainTextContent, $split['text']);
            $this->content = $paragraph;
        } elseif ($this->content instanceof Paragraph) {
            if (!empty($split['paragraph'])) {
                $this->content->setParagraphStyleOptions($split['paragraph']);
            }
            if (!empty($split['text'])) {
                $this->content->applyTextStyle($split['text']);
            }
        } elseif ($this->content instanceof RichText) {
            if (!empty($split['paragraph'])) {
                $this->content->applyParagraphStyleOptions($split['paragraph']);
            }
            if (!empty($split['text'])) {
                $this->content->applyTextStyle($split['text']);
            }
        }

        return $this;
    }

    /**
     * Sets the number of columns the cell spans.
     *
     * @param int $colspan
     * @return self
     */
    public function setColspan(int $colspan): self
    {
        $this->colspan = max(1, $colspan);
        return $this;
    }

    /**
     * Sets the number of rows this cell spans.
     *
     * @param int $rowspan
     * @return self
     */
    public function setRowspan(int $rowspan): self
    {
        $this->rowspan = max(1, $rowspan);
        return $this;
    }

    /**
     * Gets the colspan value.
     *
     * @return int
     */
    public function getColspan(): int
    {
        return $this->colspan;
    }

    /**
     * Gets the rowspan value.
     *
     * @return int
     */
    public function getRowspan(): int
    {
        return $this->rowspan;
    }

    /**
     * Returns the internal style array.
     *
     * @return array<string, string>
     */
    public function getStyle(): array
    {
        return $this->style;
    }

    /**
     * Gets the auto-generated style name.
     *
     * @return string
     */
    public function getStyleName(): string
    {
        return $this->styleName;
    }

    /**
     * Registers the current style with the global style map.
     *
     * @return void
     */
    public function registerStyles(): void
    {
        if (!empty($this->style)) {
            $this->setStyle($this->style);
        }
    }

    /**
     * Returns the style definitions required by this cell.
     *
     * @return array<string, array<string, string>>
     */
    public function getStyleDefinitions(): array
    {
        if (empty($this->style)) {
            return [];
        }

        return [$this->styleName => $this->style];
    }

    /**
     * Converts the cell to an ODT-compatible DOMNode.
     *
     * @param DOMDocument $dom
     * @return DOMNode
     */
    public function toDomNode(DOMDocument $dom): DOMNode
    {
        $cell = $dom->createElement('table:table-cell');

        if ($this->colspan > 1) {
            $cell->setAttribute('table:number-columns-spanned', (string) $this->colspan);
        }

        if ($this->rowspan > 1) {
            $cell->setAttribute('table:number-rows-spanned', (string) $this->rowspan);
        }

        if (!empty($this->styleName)) {
            $cell->setAttribute('table:style-name', $this->styleName);
        }

        if ($this->content instanceof Paragraph || $this->content instanceof RichText) {
            $child = $this->content->toDomNode($dom);
            if ($child instanceof \DOMDocumentFragment) {
                foreach ($child->childNodes as $node) {
                    $cell->appendChild($node->cloneNode(true));
                }
            } else {
                $cell->appendChild($child);
            }
        }

        return $cell;
    }

    /**
     * Converts the style to a DOM style node.
     *
     * @param DOMDocument $dom
     * @return DOMElement|null
     */
    public function toStyleDomNode(DOMDocument $dom): ?DOMElement
    {
        if (empty($this->style) || empty($this->styleName)) {
            return null;
        }

        $styleNode = $dom->createElement('style:style');
        $styleNode->setAttribute('style:name', $this->styleName);
        $styleNode->setAttribute('style:family', 'table-cell');
        $styleNode->setAttribute('style:parent-style-name', 'Default');

        $propsNode = $dom->createElement('style:table-cell-properties');

        foreach ($this->style as $key => $value) {
            $propsNode->setAttribute($key, $value);
        }

        $styleNode->appendChild($propsNode);

        return $styleNode;
    }

    /**
     * Forces paragraph-wrapping of the content if not already done.
     *
     * @param bool $force
     * @return self
     */
    public function forceParagraphAlignment(bool $force = true): self
    {
        $this->forceParagraph = $force;
        return $this;
    }

    /**
     * Sets text alignment to center.
     *
     * @return self
     */
    public function alignCenter(): self
    {
        if ($this->content instanceof Paragraph) {
            $this->content->setParagraphStyle('CenterPara');
        }
        return $this;
    }

    /**
     * Sets text alignment to left.
     *
     * @return self
     */
    public function alignLeft(): self
    {
        if ($this->content instanceof Paragraph) {
            $this->content->setParagraphStyle('LeftPara');
        }

        return $this;
    }

    /**
     * Sets text alignment to right.
     *
     * @return self
     */
    public function alignRight(): self
    {
        if ($this->content instanceof Paragraph) {
            $this->content->setParagraphStyle('RightPara');
        }
        return $this;
    }

    /**
     * Sets the background color of the cell.
     *
     * @param string $color Hex color string.
     * @return self
     */
    public function setBackground(string $color): self
    {
        $this->style['fo:background-color'] = $color;
        return $this->registerStylesAndRefresh();
    }

    /**
     * Sets a uniform border for the cell.
     *
     * @param string $border
     * @return self
     */
    public function setBorder(string $border): self
    {
        $this->style['fo:border'] = $border;
        return $this->registerStylesAndRefresh();
    }

    /**
     * Sets the top border of the cell.
     *
     * @param string $border CSS-like border definition (e.g. "0.1pt solid #ccc").
     * @return self
     */
    public function setBorderTop(string $border): self
    {
        $this->style['fo:border-top'] = $border;
        return $this->registerStylesAndRefresh();
    }

    /**
     * Sets the bottom border of the cell.
     *
     * @param string $border CSS-like border definition.
     * @return self
     */
    public function setBorderBottom(string $border): self
    {
        $this->style['fo:border-bottom'] = $border;
        return $this->registerStylesAndRefresh();
    }

    /**
     * Sets the left border of the cell.
     *
     * @param string $border CSS-like border definition.
     * @return self
     */
    public function setBorderLeft(string $border): self
    {
        $this->style['fo:border-left'] = $border;
        return $this->registerStylesAndRefresh();
    }

    /**
     * Sets the right border of the cell.
     *
     * @param string $border CSS-like border definition.
     * @return self
     */
    public function setBorderRight(string $border): self
    {
        $this->style['fo:border-right'] = $border;
        return $this->registerStylesAndRefresh();
    }

    /**
     * Sets the padding inside the cell.
     *
     * @param string $padding Padding value (e.g. "0.2cm").
     * @return self
     */
    public function setPadding(string $padding): self
    {
        $this->style['fo:padding'] = $padding;
        return $this->registerStylesAndRefresh();
    }

    /**
     * Sets the top padding inside the cell.
     *
     * @param string $padding Padding value (e.g. "0.2cm").
     * @return self
     */
    public function setPaddingTop(string $padding): self
    {
        $this->style['fo:padding-top'] = $padding;
        return $this->registerStylesAndRefresh();
    }

    /**
     * Sets the bottom padding inside the cell.
     *
     * @param string $padding Padding value.
     * @return self
     */
    public function setPaddingBottom(string $padding): self
    {
        $this->style['fo:padding-bottom'] = $padding;
        return $this->registerStylesAndRefresh();
    }

    /**
     * Sets the left padding inside the cell.
     *
     * @param string $padding Padding value.
     * @return self
     */
    public function setPaddingLeft(string $padding): self
    {
        $this->style['fo:padding-left'] = $padding;
        return $this->registerStylesAndRefresh();
    }

    /**
     * Sets the right padding inside the cell.
     *
     * @param string $padding Padding value.
     * @return self
     */
    public function setPaddingRight(string $padding): self
    {
        $this->style['fo:padding-right'] = $padding;
        return $this->registerStylesAndRefresh();
    }

    /**
     * Sets a logical column width for the cell.
     *
     * This does not directly affect the output, but may be used in layout calculations
     * such as column width ratio handling.
     *
     * @param string $width Desired column width (e.g. "5cm").
     * @return self
     */
    public function setWidth(string $width): self
    {
        $this->style['__column-width'] = $width;
        return $this->registerStylesAndRefresh();
    }

    /**
     * @internal Ensures content is wrapped into a Paragraph instance.
     */
    protected function ensureParagraph(): void
    {
        if (!($this->content instanceof Paragraph)) {
            $paragraph = new Paragraph();
            $paragraph->addText((string) $this->content);
            $this->content = $paragraph;
        }
    }

    /**
     * Internally registers the style again after modification.
     *
     * @return self
     */
    public function registerStylesAndRefresh(): self
    {
        $this->style = StyleMapper::mapTableCellStyleOptions($this->style);
        $this->styleName = StyleMapper::generateStyleName($this->style);
        StyleMapper::registerTableCellStyle($this->styleName, $this->style);
        return $this;
    }

    /**
     * Creates a new RichTableCell using a fluent interface.
     *
     * @param string|Paragraph|RichText $content
     * @param array<string, string> $style
     * @return self
     */
    public static function create(string|Paragraph|RichText $content, array $style = []): self
    {
        return new self($content, $style);
    }

    /**
     * Applies a named style or an inline CSS string.
     *
     * @param string $styleNameOrDefinition
     * @return self
     */
    public function style(string $styleNameOrDefinition): self
    {
        if (str_contains($styleNameOrDefinition, ':') || str_contains($styleNameOrDefinition, ';')) {
            $this->setStyle(StyleMapper::parseInlineStyle($styleNameOrDefinition));
        } else {
            $this->styleName = $styleNameOrDefinition;
        }
        return $this;
    }

    /**
     * Fluent alias for setColspan().
     *
     * @param int $count
     * @return self
     */
    public function colspan(int $count): self
    {
        return $this->setColspan($count);
    }

    /**
     * Fluent alias for setRowspan().
     *
     * @param int $count
     * @return self
     */
    public function rowspan(int $count): self
    {
        return $this->setRowspan($count);
    }
}
