<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Elements;

use DOMDocument;
use DOMElement;
use DOMNode;
use InvalidArgumentException;
use OdtTemplateEngine\Document\StructuredInsertionMode;
use OdtTemplateEngine\Document\StyleRequirement;
use OdtTemplateEngine\Utils\StyleMapper;

/** Neutral native drawing composition; child coordinates stay local and order defines paint order. */
final class DrawingGroup extends OdtElement
{
    private DrawingLayout $layout;

    /** @param array<array-key, OdtElement> $children Drawing primitives/images, not Writer text frames. */
    public function __construct(
        private readonly string $name,
        private readonly array $children,
        ?DrawingLayout $layout = null
    ) {
        if (trim($name) === '' || $children === []) {
            throw new InvalidArgumentException('Drawing group requires a nonblank name and at least one child.');
        }
        foreach ($children as $child) {
            if (!$child instanceof ShapeElement && !$child instanceof RoundedRectElement
                && !$child instanceof ImageElement && !$child instanceof self) {
                throw new InvalidArgumentException('Drawing groups accept shapes, images and nested groups; keep Writer frames outside.');
            }
        }
        $this->layout = $layout ?? DrawingLayout::fromArray(['anchor' => 'as-char']);
        if (!in_array($this->layout->anchor(), ['as-char', 'paragraph'], true)
            || $this->layout->width() !== null || $this->layout->height() !== null
            || ($this->layout->horizontalOffset() !== null && (float) $this->layout->horizontalOffset() != 0.0)) {
            throw new InvalidArgumentException('Group bounds derive from children; group x/size are not frame coordinates.');
        }
    }

    public function ownedElements(): iterable
    {
        return $this->children;
    }

    public function structuredInsertionMode(): StructuredInsertionMode
    {
        return $this->layout->anchor() === 'as-char'
            ? StructuredInsertionMode::INLINE_TEXT_FLOW : StructuredInsertionMode::PRESERVE_TEXT_CONTAINER;
    }

    public function toDomNode(DOMDocument $dom): DOMNode
    {
        $group = $dom->createElement('draw:g');
        $group->setAttribute('draw:name', $this->name);
        $group->setAttribute('draw:style-name', $this->styleName());
        $group->setAttribute('text:anchor-type', $this->layout->anchor());
        if ($this->layout->verticalOffset() !== null) {
            $group->setAttribute('svg:y', $this->layout->verticalOffset());
        }
        if ($this->layout->zIndex() !== null) {
            $group->setAttribute('draw:z-index', (string) $this->layout->zIndex());
        }
        foreach (array_values($this->children) as $index => $child) {
            $node = $child->toDomNode($dom);
            if ($node instanceof DOMElement && $node->nodeName === 'text:p'
                && $node->childNodes->length === 1) {
                $node = $node->firstChild;
            }
            if (!$node instanceof DOMElement
                || !in_array($node->nodeName, ['draw:custom-shape', 'draw:rect', 'draw:frame', 'draw:g'], true)) {
                throw new InvalidArgumentException('Unsupported drawing-group child serialization.');
            }
            if ($node->getElementsByTagName('text:user-field-get')->length > 0
                || $node->getElementsByTagNameNS('urn:oasis:names:tc:opendocument:xmlns:text:1.0', 'user-field-get')->length > 0) {
                throw new InvalidArgumentException('Writer User Fields must remain outside drawing groups.');
            }
            $node->removeAttribute('text:anchor-type');
            $node->setAttribute('draw:z-index', (string) $index);
            $group->appendChild($node);
        }
        return $group;
    }

    public function getOwnStyleRequirements(): iterable
    {
        yield new StyleRequirement(
            StyleRequirement::KIND_DEFINITION,
            StyleRequirement::SCOPE_AUTOMATIC,
            'graphic',
            StyleRequirement::PART_CONTENT,
            $this->styleName(),
            null,
            ['style:graphic-properties' => $this->properties()]
        );
    }

    private function properties(): array
    {
        $properties = array_merge(
            ['style:wrap' => 'run-through', 'style:run-through' => 'foreground'],
            $this->layout->anchor() === 'as-char'
                ? ['style:vertical-pos' => 'middle', 'style:vertical-rel' => 'char'] : [],
            (new DrawingLayoutProjector())->graphicLayoutProperties($this->layout)
        );
        if ($properties['style:wrap'] !== 'run-through') {
            unset($properties['style:run-through']);
        }
        return $properties;
    }

    private function styleName(): string
    {
        return 'group_' . StyleMapper::generateStyleName($this->properties());
    }
}
