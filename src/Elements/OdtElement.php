<?php

namespace OdtTemplateEngine\Elements;

use DOMDocument;
use DOMNode;
use OdtTemplateEngine\Document\FillImageRequirement;
use OdtTemplateEngine\Document\StyleRequirement;
use OdtTemplateEngine\Document\StructuredInsertionMode;
use OdtTemplateEngine\Elements\DOMElement;


abstract class OdtElement
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
     * Returns the OdtElements logically owned by this element.
     *
     * Concrete composites may map their natural internal storage to this
     * semantic view without changing their rendering-oriented storage model.
     *
     * @return iterable<int, OdtElement>
     */
    public function ownedElements(): iterable
    {
        return $this->getEmbeddedElements();
    }

    /**
     * Abstract method that should be implemented by subclasses to generate the ODT-compatible DOM node (e.g., text:p, table:table, etc.)
     *
     * @param DOMDocument $dom The target DOM document.
     * @return DOMNode The generated DOM node to be inserted into the document.
     */
    abstract public function toDomNode(DOMDocument $dom): DOMNode;

    /**
     * Returns how this element participates in structured placeholder insertion.
     */
    public function structuredInsertionMode(): StructuredInsertionMode
    {
        return StructuredInsertionMode::BLOCK;
    }

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
     * Returns semantic style requirements owned directly by this element.
     *
     * Traversal of owned elements belongs to StyleRequirementCollector; leaf
     * and composite elements only describe their own requirements here.
     *
     * @return iterable<int, StyleRequirement>
     */
    public function getOwnStyleRequirements(): iterable
    {
        return [];
    }

    /**
     * Returns semantic fill-image dependencies owned directly by this element.
     *
     * This typed hook is deliberately separate from the historical
     * getOwnFillImageRequirements() array API. Transitive traversal belongs to
     * FillImageRequirementCollector through ownedElements().
     *
     * @return iterable<int, FillImageRequirement>
     */
    public function getOwnFillImageDependencies(): iterable
    {
        return [];
    }

    /** @return array<string, array<string, mixed>> */
    public function getOwnFrameStyleRequirements(): array
    {
        return [];
    }

    /** @return array<string, array<string, mixed>> */
    public function getOwnImageStyleRequirements(): array
    {
        return [];
    }

    /** @return array<string, array<string, mixed>> */
    public function getOwnFillImageRequirements(): array
    {
        return [];
    }

    /** @return array<string, array<string, mixed>> */
    public function getFrameStyleRequirements(): array
    {
        return $this->collectGraphicRequirements('getFrameStyleRequirements');
    }

    /** @return array<string, array<string, mixed>> */
    public function getImageStyleRequirements(): array
    {
        return $this->collectGraphicRequirements('getImageStyleRequirements');
    }

    /** @return array<string, array<string, mixed>> */
    public function getFillImageRequirements(): array
    {
        return $this->collectGraphicRequirements('getFillImageRequirements');
    }

    /** @param non-empty-string $method */
    private function collectGraphicRequirements(string $method): array
    {
        $requirements = [];
        foreach ($this->ownedElements() as $element) {
            if (method_exists($element, $method)) {
                $requirements = array_merge($requirements, $element->{$method}());
            }
        }

        return $requirements;
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
     * Returns physical image resources produced directly by this element.
     *
     * Composite traversal is intentionally supplied by the resource collector
     * through ownedElements(). Existing getImageAssets() compatibility
     * semantics remain unchanged.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getOwnImageAssets(): array
    {
        return [];
    }

    /** @return array<int, array<string, mixed>> */
    public function getImageAssets(): array
    {
        $assets = [];
        foreach ($this->ownedElements() as $element) {
            if (method_exists($element, 'getImageAssets')) {
                $assets = array_merge($assets, $element->getImageAssets());
            }
        }

        return $assets;
    }

}
