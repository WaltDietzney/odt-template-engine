<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Document;

use OdtTemplateEngine\Elements\OdtElement;

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
    public function collectSemantic(OdtElement $root): iterable
    {
        yield from $this->collectSemanticElement($root);
    }

    /** @return iterable<int, StyleRequirement> */
    private function collectSemanticElement(OdtElement $element): iterable
    {
        yield from $element->getOwnStyleRequirements();

        foreach ($element->ownedElements() as $child) {
            yield from $this->collectSemanticElement($child);
        }
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
            'paragraph' => $element->getOwnRequiredParagraphStyles(),
            'text' => $element->getOwnRequiredStyles(),
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
