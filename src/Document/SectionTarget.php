<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Document;

/**
 * Read-only handle for one named native section in the current document.
 */
final class SectionTarget extends AbstractAddressableTarget
{
    public function type(): string
    {
        return 'section';
    }

    public function descriptor(): SectionDescriptor
    {
        return (new TypedTargetResolver())->resolveSectionDescriptor($this->context, $this->targetName);
    }

    /**
     * Return a deterministic plain-text view of the current section content.
     */
    public function text(): string
    {
        return (new SectionReader())->text($this->context, $this->targetName);
    }

    /** @return list<NamedObjectReference> */
    public function nestedNamedObjects(): array
    {
        return $this->descriptor()->nestedNamedObjects();
    }
}
