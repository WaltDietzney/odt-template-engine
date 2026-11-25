<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Document;

use DOMDocument;
use DOMElement;
use DOMNode;
use InvalidArgumentException;
use OdtTemplateEngine\Elements\DrawingGroup;
use OdtTemplateEngine\Elements\ImageElement;
use OdtTemplateEngine\Elements\OdtElement;
use OdtTemplateEngine\Elements\ShapeElement;

/**
 * Walks an element ownership tree and yields individual style requirements.
 *
 * Providers expose only the requirements produced by the current element;
 * ownership supplies the transitive traversal. Requirements are deliberately
 * yielded one at a time so duplicate names remain visible to StyleContext.
 */
final class StyleRequirementCollector
{
    /**
     * Collect semantic requirements from one ownership subtree.
     *
     * The legacy collect() method remains available for the existing graphic
     * and compatibility pipeline. This projection is intentionally separate
     * until those families receive semantic producer contracts.
     *
     * @return iterable<int, StyleRequirement>
     */
    public function collectSemantic(OdtElement $root, ?string $documentPart = null): iterable
    {
        yield from $this->collectSemanticElement($root, $documentPart);
    }

    /** @return iterable<int, StyleRequirement> */
    private function collectSemanticElement(
        OdtElement $element,
        ?string $documentPart = null,
        bool $drawingContext = false,
        array $active = []
    ): iterable {
        $id = spl_object_id($element);
        if (isset($active[$id])) {
            throw new InvalidArgumentException('Style ownership contains a cycle.');
        }
        $active[$id] = true;
        $requirements = [];
        foreach ($element->getOwnStyleRequirements() as $requirement) {
            $requirements[] = $requirement;
        }
        $drawingChildren = $drawingContext || $element instanceof DrawingGroup;
        if ($element instanceof ShapeElement) {
            $drawingChildren = $drawingChildren || $element->structuredInsertionMode() === StructuredInsertionMode::INLINE_TEXT_FLOW;
            foreach ($requirements as $requirement) {
                $props = $requirement->propertyGroups()['style:graphic-properties'] ?? [];
                $drawingChildren = $drawingChildren || array_key_exists('draw:auto-grow-width', $props)
                    || array_key_exists('draw:auto-grow-height', $props);
            }
        }
        foreach ($requirements as $requirement) {
            $props = $requirement->propertyGroups()['style:graphic-properties'] ?? [];
            $drawingImage = $element instanceof ImageElement
                && ($drawingContext || array_key_exists('style:run-through', $props));
            $project = $drawingContext || $drawingImage || $element instanceof DrawingGroup
                || ($element instanceof ShapeElement && $drawingChildren);
            $part = $documentPart ?? StyleRequirement::PART_CONTENT;
            if ($requirement->kind() === StyleRequirement::KIND_DEFINITION
                && $project && in_array($requirement->family(), ['paragraph', 'text', 'graphic'], true)) {
                yield new StyleRequirement(
                    $requirement->kind(),
                    StyleRequirement::SCOPE_AUTOMATIC,
                    $requirement->family(),
                    $part,
                    self::drawingStyleName($requirement->family(), $requirement->name(), $part),
                    $drawingImage ? 'Graphics' : ($project && $requirement->family() === 'text' ? null : $requirement->parentStyleName()),
                    $requirement->propertyGroups(),
                    false,
                    true
                );
            } else {
                yield $requirement;
            }
        }
        foreach ($element->ownedElements() as $child) {
            yield from $this->collectSemanticElement($child, $documentPart, $drawingChildren, $active);
        }
    }

    private static function drawingStyleName(string $family, string $name, string $part): string
    {
        return 'drawing_' . ($part === StyleRequirement::PART_STYLES ? 's_' : 'c_')
            . substr(hash('sha256', $family . "\0" . $name), 0, 20);
    }

    /** @internal Rebind only newly constructed drawing subtrees to their isolated styles. */
    public function projectDrawingStyles(DOMNode $root, DOMDocument $document, string $part): void
    {
        $available = [];
        $office = 'urn:oasis:names:tc:opendocument:xmlns:office:1.0';
        $style = 'urn:oasis:names:tc:opendocument:xmlns:style:1.0';
        foreach ($document->getElementsByTagNameNS($office, 'automatic-styles') as $container) {
            foreach ($container->getElementsByTagNameNS($style, 'style') as $definition) {
                $available[$definition->getAttributeNS($style, 'name')] = $definition;
            }
        }
        $walk = function (DOMNode $node, bool $drawing = false) use (&$walk, $available, $part): void {
            if (!$node instanceof DOMElement) {
                foreach ($node->childNodes as $child) {
                    $walk($child, $drawing);
                }
                return;
            }
            $graphic = $node->getAttribute('draw:style-name');
            $projected = self::drawingStyleName('graphic', $graphic, $part);
            $definition = $available[$projected] ?? null;
            $props = $definition?->getElementsByTagNameNS('urn:oasis:names:tc:opendocument:xmlns:style:1.0', 'graphic-properties')->item(0);
            $newShape = $node->nodeName === 'draw:custom-shape' && ($drawing
                || $node->getAttribute('text:anchor-type') === 'as-char'
                || $props?->hasAttribute('draw:auto-grow-width') || $props?->hasAttribute('draw:auto-grow-height'));
            $newImage = $node->nodeName === 'draw:frame'
                && ($drawing || $props?->hasAttribute('style:run-through'));
            $drawing = $drawing || $node->nodeName === 'draw:g' || $newShape;
            if (($drawing || $newImage) && $graphic !== '' && $definition !== null) {
                $node->setAttribute('draw:style-name', $projected);
            }
            foreach ([
                'text:style-name' => in_array($node->nodeName, ['text:span', 'text:a'], true) ? 'text' : 'paragraph',
                'draw:text-style-name' => 'paragraph',
            ] as $attribute => $family) {
                $name = $node->getAttribute($attribute);
                $target = self::drawingStyleName($family, $name, $part);
                if ($drawing && $name !== '' && isset($available[$target])) {
                    $node->setAttribute($attribute, $target);
                }
            }
            foreach ($node->childNodes as $child) {
                $walk($child, $drawing);
            }
        };
        $walk($root);
    }

    /**
     * @return iterable<int, array{family: string, name: string, definition: array<string, mixed>}>
     */
    public function collect(OdtElement $root): iterable
    {
        yield from $this->collectElement($root);
    }

    /**
     * @return iterable<int, array{family: string, name: string, definition: array<string, mixed>}>
     */
    private function collectElement(OdtElement $element): iterable
    {
        foreach ([
            'frame' => $element->getOwnFrameStyleRequirements(),
            'image' => $element->getOwnImageStyleRequirements(),
            'fill-image' => $element->getOwnFillImageRequirements(),
        ] as $family => $requirements) {
            foreach ($requirements as $name => $definition) {
                yield [
                    'family' => $family,
                    'name' => (string) $name,
                    'definition' => $definition,
                ];
            }
        }

        foreach ($element->ownedElements() as $child) {
            foreach ($this->collectElement($child) as $requirement) {
                yield $requirement;
            }
        }
    }
}
