<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Document;

/**
 * Read-only handle for one named native table.
 */
final class TableTarget extends AbstractAddressableTarget
{
    public function type(): string
    {
        return 'table';
    }

    public function descriptor(): TableDescriptor
    {
        return (new TypedTargetResolver())->resolveTableDescriptor($this->context, $this->targetName);
    }
}
