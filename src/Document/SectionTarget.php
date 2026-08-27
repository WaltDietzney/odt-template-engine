<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Document;

use OdtTemplateEngine\Elements\OdtElement;

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

    /**
     * Replace section children while preserving the native section container.
     */
    public function replaceContent(OdtElement $content): self
    {
        $this->descriptor();
        (new SectionMutationService())->replaceContent(
            $this->context,
            $this->targetName,
            $content,
            $this->package
        );

        return $this;
    }

    /**
     * Clone the prototype section into a uniquely addressable native section.
     *
     * This is intentionally distinct from future data-bound instantiation.
     */
    public function clone(): self
    {
        $clone = (new SectionCloneService())->cloneWithRewrittenIdentities(
            $this->context,
            $this->targetName
        );

        return new self($this->context, $clone->getAttribute('text:name'), $this->package);
    }
}
