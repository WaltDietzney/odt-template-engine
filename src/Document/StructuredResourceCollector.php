<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Document;

use OdtTemplateEngine\Elements\OdtElement;

/**
 * Walks an element ownership tree and yields physical resource requirements.
 * Resource preparation remains a package concern; this class only discovers
 * resources exposed by the owned element subtree.
 */
final class StructuredResourceCollector
{
    /**
     * @return iterable<int, array<string, mixed>>
     */
    public function collect(OdtElement $root): iterable
    {
        foreach ($root->getOwnImageAssets() as $asset) {
            yield $asset;
        }

        foreach ($root->ownedElements() as $child) {
            foreach ($this->collect($child) as $asset) {
                yield $asset;
            }
        }
    }
}
