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

    /**
     * Populate the bounded scalar data region of this Writer-authored table.
     *
     * @param list<list<scalar|null>> $rows
     * @param array{keepRows?: list<int>} $options
     */
    public function populate(array $rows, array $options = []): self
    {
        $this->descriptor();
        (new NativeTablePopulationService())->populate($this->context, $this->targetName, $rows, $options);

        return $this;
    }
}
