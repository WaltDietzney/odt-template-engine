<?php

namespace OdtTemplateEngine\Elements;

use DOMDocument;
use DOMNode;
use OdtTemplateEngine\Contracts\HasStyles;
use OdtTemplateEngine\Utils\StyleMapper;

/**
 * Represents a cell within a rich table element in an ODT document.
 * 
 * This class defines a table cell that can contain text, paragraphs, or rich text.
 * It also manages the styles for the cell and ensures the correct mapping and registration of those styles.
 */
class RichTableCell extends OdtElement implements HasStyles
{
    /**
     * @var mixed The content of the table cell, which can be a string, Paragraph, or RichText.
     */
    protected mixed $content;

    /**
     * @var array The styles applied to the table cell.
     */
    protected array $style = [];

    /**
     * @var string The name of the style applied to the table cell.
     */
    protected string $styleName = '';

    /**
     * RichTableCell constructor.
     * 
     * Initializes the table cell with content and optional style.
     * The style is mapped and registered using the StyleMapper utility.
     * 
     * @param string|Paragraph|RichText $content The content to be placed in the cell.
     * @param array $style Optional style to be applied to the cell.
     */
    public function __construct(string|Paragraph|RichText $content, array $style = [])
    {
        $this->content = $content;
        $this->style = $style;

        if (!empty($this->style)) {
            $this->style = StyleMapper::mapTableCellStyleOptions($style);
            $styleName = StyleMapper::generateStyleName($this->style);
            $this->styleName = $styleName;

            StyleMapper::registerTableCellStyle($styleName, $this->style);

            return $this;
        }
    }

    /**
     * Sets the content of the table cell.
     * 
     * @param mixed $content The new content to set for the cell.
     * @return self The current instance of RichTableCell.
     */
    public function setContent(mixed $content): self
    {
        $this->content = $content;
        return $this;
    }

    /**
     * Gets the content of the table cell.
     * 
     * @return mixed The content of the table cell.
     */
    public function getContent(): mixed
    {
        return $this->content;
    }

    public function setStyle(array $style): self
    {
        // 1. Style-Array mappen (falls nötig)
        $this->style = StyleMapper::mapTableCellStyleOptions($style);

        // 2. Generiere einen einzigartigen Style-Namen
        $styleName = StyleMapper::generateStyleName($this->style);
        $this->styleName = $styleName;

        // 3. Registriere den Style im StyleMapper
        StyleMapper::registerTableCellStyle($styleName, $this->style);

        return $this;
    }


    /**
     * Gets the style of the table cell.
     * 
     * @return array The style options for the table cell.
     */
    public function getStyle(): array
    {
        return $this->style;
    }

    /**
     * Gets the name of the style applied to the table cell.
     * 
     * @return string The style name.
     */
    public function getStyleName(): string
    {
        return $this->styleName ?? '';
    }

    /**
     * Registers the style for the table cell, mapping the style options 
     * and ensuring it is registered using the StyleMapper utility.
     * 
     * @return void
     */
    public function registerStyles(): void
    {

        if (!empty($this->style)) {
            error_log("🧩 Style options before mapping:");
            error_log(print_r($this->style, true));

            $mapped = StyleMapper::mapTableCellStyleOptions($this->style);
            error_log("🧩 Mapped style options:");
            error_log(print_r($mapped, true));

            $styleName = StyleMapper::generateStyleName($mapped);
            $this->styleName = $styleName;
            StyleMapper::registerTableCellStyle($styleName, $mapped);
        }
    }

    /**
     * Gets the style definitions for the table cell.
     * 
     * If the cell has a style, it returns the style definitions associated with the cell.
     * Otherwise, it returns an empty array.
     * 
     * @return array An array of style definitions for the table cell.
     */
    public function getStyleDefinitions(): array
    {
        if (empty($this->style)) {
            return [];
        }

        return [
            $this->getStyleName() => $this->style
        ];
    }

    /**
     * Converts the RichTableCell object to a DOM node.
     * 
     * This method creates a table cell element in ODT format, inserts the content (text, paragraph, etc.),
     * and applies the associated style to the cell element.
     * 
     * @param DOMDocument $dom The DOM document to append the table cell to.
     * @return DOMNode The resulting table cell node.
     */
    public function toDomNode(DOMDocument $dom): DOMNode
    {
        $cell = $dom->createElement('table:table-cell');

        // 📦 Insert content into the cell
        $content = $this->getContent();

        if ($content instanceof Paragraph || $content instanceof RichText) {
            $child = $content->toDomNode($dom);
            if ($child instanceof \DOMDocumentFragment) {
                foreach ($child->childNodes as $node) {
                    $cell->appendChild($node->cloneNode(true));
                }
            } else {
                $cell->appendChild($child);
            }
        } else {
            $p = $dom->createElement('text:p');
            $p->appendChild($dom->createTextNode((string) $content));
            $cell->appendChild($p);
        }

        // 🎨 Apply style to the cell and ensure it is registered
        if (!empty($this->style)) {
            $styleName = $this->getStyleName();
            $cell->setAttribute('table:style-name', $styleName);

            // Ensure style registration (if not done in the constructor)
            StyleMapper::registerTableCellStyle($styleName, $this->style);
        }

        return $cell;
    }

    /**
     * Generates a <style:style> DOM node for the table-cell style.
     *
     * @param DOMDocument $dom The DOM document to create the style node in.
     * @return \DOMElement|null The resulting style node or null if no style is defined.
     */
    public function toStyleDomNode(DOMDocument $dom): ?\DOMElement
    {
        if (empty($this->style) || !$this->styleName) {
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


    public function getRequiredTableCellStyleNodes(): array
    {
        if (empty($this->styleName)) {
            return [];
        }

        $styleProps = StyleMapper::getRegisteredTableCellStyles()[$this->styleName] ?? null;

        if (!$styleProps) {
            return [];
        }

        $dom = new DOMDocument();
        $style = $dom->createElement('style:style');
        $style->setAttribute('style:name', $this->styleName);
        $style->setAttribute('style:family', 'table-cell');
        $style->setAttribute('style:parent-style-name', 'Default');

        $props = $dom->createElement('style:table-cell-properties');
        foreach ($styleProps as $key => $value) {
            $props->setAttribute($key, $value);
        }

        $style->appendChild($props);

        return [$style];
    }

}
