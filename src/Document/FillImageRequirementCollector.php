<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Document;

use OdtTemplateEngine\Elements\OdtElement;

/**
 * Walks one element ownership tree and yields semantic fill-image dependencies.
 *
 * Elements expose only their own dependencies. Transitive traversal is derived
 * exclusively from ownedElements(), matching the established semantic style
 * and structured-resource ownership model.
 */
final class FillImageRequirementCollector
{
    /** @return iterable<int, FillImageRequirement> */
    public function collect(OdtElement $root): iterable
    {
        yield from $this->collectElement($root);
    }

    /** @return iterable<int, FillImageRequirement> */
    private function collectElement(OdtElement $element): iterable
    {
        yield from $element->getOwnFillImageDependencies();

        foreach ($element->ownedElements() as $child) {
            yield from $this->collectElement($child);
        }
    }
}
