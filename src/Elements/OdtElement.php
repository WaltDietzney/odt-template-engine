<?php

namespace OdtTemplateEngine\Elements;

use DOMDocument;
use DOMNode;
use OdtTemplateEngine\Contracts\HasStyles;
use OdtTemplateEngine\Elements\DOMElement;


abstract class OdtElement implements HasStyles
{
    /**
     * @var OdtElement[] $embeddedElements List of embedded elements within this element.
     */
    protected array $embeddedElements = [];

    /**
     * Adds an embedded element to this OdtElement.
     *
     * @param OdtElement $element The OdtElement to be added as an embedded element.
     * @return $this The current instance for method chaining.
     */
    public function addElement(OdtElement $element): self
    {
        $this->embeddedElements[] = $element;
        return $this;
    }

    /**
     * Returns the list of embedded elements.
     *
     * @return OdtElement[] List of embedded OdtElements.
     */
    public function getEmbeddedElements(): array
    {
        return $this->embeddedElements;
    }

    /**
     * Abstract method that should be implemented by subclasses to generate the ODT-compatible DOM node (e.g., text:p, table:table, etc.)
     *
     * @param DOMDocument $dom The target DOM document.
     * @return DOMNode The generated DOM node to be inserted into the document.
     */
    abstract public function toDomNode(DOMDocument $dom): DOMNode;

    /**
     * Optional: Returns a style DOM element (e.g., for image frames).
     *
     * @param DOMDocument $dom The target DOM document.
     * @return DOMElement|null A DOM element representing the style, or null if no style is defined.
     */
    public function toStyleDomNode(DOMDocument $dom): ?\DOMElement
    {
        return null;
    }

    /**
     * Optional: Returns the styles that should be defined for this element in the styles.xml.
     *
     * @return array An array of style definitions for this element.
     */
    public function getRequiredStyles(): array
    {
        return [];
    }

    /**
     * Optional: Returns the placeholder name that this element should replace.
     * For example, returns 'textblock' if it replaces {{textblock}}.
     *
     * @return string|null The placeholder name or null if no placeholder is defined.
     */
    public function getPlaceholderName(): ?string
    {
        return null;
    }

    /**
     * Returns the image assets embedded within this element and its sub-elements.
     *
     * @return array An array of image assets.
     */
    public function getImageAssets(): array
    {
        $assets = [];

        foreach ($this->getEmbeddedElements() as $element) {
            if (method_exists($element, 'getImageAssets')) {
                $assets = array_merge($assets, $element->getImageAssets());
            }
        }

        return $assets;
    }

    /**
     * Returns the style definitions for this element and its embedded elements.
     *
     * @return array An array of style definitions.
     */
    public function getStyleDefinitions(): array
    {
        $styles = [];

        foreach ($this->getEmbeddedElements() as $element) {
            if (method_exists($element, 'getStyleDefinitions')) {
                $styles = array_merge($styles, $element->getStyleDefinitions());
            }
        }

        return $styles;
    }
}

