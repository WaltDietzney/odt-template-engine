<?php

namespace OdtTemplateEngine\Elements;

use DOMDocument;
use DOMNode;
use DOMElement;
use OdtTemplateEngine\AbstractOdtTemplate;
use OdtTemplateEngine\Contracts\HasStyles;
use OdtTemplateEngine\Utils\StyleMapper;

/**
 * Represents a single cell within a rich table structure in an ODT document.
 */
class RichTableCell extends OdtElement implements HasStyles
{
    /**
     * Summary of content
     * @var mixed
     */
    protected mixed $content;

    /**
     * Summary of style
     * @var array
     */
    protected array $style = [];

    /**
     * Summary of styleName
     * @var string
     */
    protected string $styleName = '';

    /**
     * Summary of forceParagraph
     * @var bool
     */
    protected bool $forceParagraph = false;

    /**
     * Summary of colspan
     * @var int
     */
    protected int $colspan = 1;

    /**
     * Summary of rowspan
     * @var int
     */
    protected int $rowspan = 1;

    /**
     * Constructor.
     *
     * @param string|Paragraph|RichText $content Cell content.
     * @param array $style Optional style array for the cell.
     */
    public function __construct(string|Paragraph|RichText $content, array $style = [])
    {
        if (is_string($content)) {
            $paragraph = new Paragraph();
            $paragraph->addText($content);
            $this->content = $paragraph;
        } else {
            $this->content = $content;
        }

        $this->setStyle($style);
    }

    /**
     * Sets the content of the table cell.
     *
     * @param mixed $content
     * @return self
     */
    public function setContent(mixed $content): self
    {
        $this->content = $content;
        return $this;
    }

    /**
     * Gets the content of the table cell.
     *
     * @return mixed
     */
    public function getContent(): mixed
    {
        return $this->content;
    }

    /**
     * Sets the table cell style.
     *
     * @param array $style
     * @return self
     */
    public function setStyle(array $style): self
    {
        $this->style = StyleMapper::mapTableCellStyleOptions($style);
        $this->styleName = StyleMapper::generateStyleName($this->style);
        StyleMapper::registerTableCellStyle($this->styleName, $this->style);
        return $this;
    }

    /**
     * Setzt die Colspan (Spalten-Übergreifung).
     */
    public function setColspan(int $colspan): self
    {
        $this->colspan = max(1, $colspan);
        return $this;
    }

    /**
     * Setzt die Rowspan (Zeilen-Übergreifung).
     */
    public function setRowspan(int $rowspan): self
    {
        $this->rowspan = max(1, $rowspan);
        return $this;
    }

    /**
     * Gibt die Colspan zurück.
     */
    public function getColspan(): int
    {
        return $this->colspan;
    }

    /**
     * Gibt die Rowspan zurück.
     */
    public function getRowspan(): int
    {
        return $this->rowspan;
    }


    /**
     * Returns the cell style as an array.
     *
     * @return array
     */
    public function getStyle(): array
    {
        return $this->style;
    }

    /**
     * Gets the generated style name.
     *
     * @return string
     */
    public function getStyleName(): string
    {
        return $this->styleName;
    }

    /**
     * Registers the current style to the global style registry.
     */
    public function registerStyles(): void
    {
        if (!empty($this->style)) {
            $this->setStyle($this->style);
        }
    }

    /**
     * Returns the required style definitions.
     *
     * @return array
     */
    public function getStyleDefinitions(): array
    {
        if (empty($this->style)) {
            return [];
        }

        return [$this->styleName => $this->style];
    }

    /**
     * Converts the table cell into a DOMNode.
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
     * Converts the style into a DOMElement.
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
     * Forces the cell content to be wrapped into a Paragraph if necessary.
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
     * Aligns the text inside the cell to center.
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
     * Aligns the text inside the cell to the left.
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
     * Aligns the text inside the cell to the right.
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
     * Sets background color of the cell.
     *
     * @param string $color
     * @return self
     */
    public function setBackground(string $color): self
    {
        $this->style['fo:background-color'] = $color;
        return $this->registerStylesAndRefresh();
    }

    /**
     * Sets a border around the cell.
     *
     * @param string $border
     * @return self
     */
    public function setBorder(string $border): self
    {
        $this->style['fo:border'] = $border;
        return $this->registerStylesAndRefresh();
    }

    public function setBorderTop(string $border): self
    {
        $this->style['fo:border-top'] = $border;
        return $this->registerStylesAndRefresh();
    }

    public function setBorderBottom(string $border): self
    {
        $this->style['fo:border-bottom'] = $border;
        return $this->registerStylesAndRefresh();
    }

    public function setBorderLeft(string $border): self
    {
        $this->style['fo:border-left'] = $border;
        return $this->registerStylesAndRefresh();
    }

    public function setBorderRight(string $border): self
    {
        $this->style['fo:border-right'] = $border;
        return $this->registerStylesAndRefresh();
    }


    /**
     * Sets padding inside the cell.
     *
     * @param string $value
     * @return self
     */
    public function setPadding(string $padding): self
    {
        $this->style['fo:padding'] = $padding;
        return $this->registerStylesAndRefresh();
    }

    public function setPaddingTop(string $padding): self
    {
        $this->style['fo:padding-top'] = $padding;
        return $this->registerStylesAndRefresh();
    }

    public function setPaddingBottom(string $padding): self
    {
        $this->style['fo:padding-bottom'] = $padding;
        return $this->registerStylesAndRefresh();
    }

    public function setPaddingLeft(string $padding): self
    {
        $this->style['fo:padding-left'] = $padding;
        return $this->registerStylesAndRefresh();
    }

    public function setPaddingRight(string $padding): self
    {
        $this->style['fo:padding-right'] = $padding;
        return $this->registerStylesAndRefresh();
    }



    /**
     * Internal: Ensures the content is wrapped into a Paragraph.
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
     * Internal: Refreshes style registration.
     *
     * @return self
     */
    protected function registerStylesAndRefresh(): self
    {
        $this->style = StyleMapper::mapTableCellStyleOptions($this->style);
        $this->styleName = StyleMapper::generateStyleName($this->style);
        StyleMapper::registerTableCellStyle($this->styleName, $this->style);
        return $this;
    }

    public static function create(string|Paragraph|RichText $content, array $style = []): self
    {
        return new self($content, $style);
    }


    /** 🎨 Direkt Style setzen */
    public function style(string $styleNameOrDefinition): self
    {
        if (str_contains($styleNameOrDefinition, ':') || str_contains($styleNameOrDefinition, ';')) {
            // Ist wohl ein CSS-Stil → parsen und zuweisen
            $this->setStyle(StyleMapper::parseInlineStyle($styleNameOrDefinition));
        } else {
            // Ist ein direkter Stilname
            $this->styleName = $styleNameOrDefinition;
        }
        return $this;
    }


    /** ↔️ Spalten übergreifen */
    public function colspan(int $count): self
    {
        $this->colspan = $count;
        return $this;
    }

    /** ↕️ Zeilen übergreifen */
    public function rowspan(int $count): self
    {
        $this->rowspan = $count;
        return $this;
    }
}
