<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Document;

use OdtTemplateEngine\OdtDocumentContext;
use OdtTemplateEngine\Elements\OdtElement;

/**
 * Read-only handle for one named native section in the current document.
 */
final class SectionTarget extends AbstractAddressableTarget
{
    public function __construct(
        OdtDocumentContext $context,
        string $targetName,
        ?\OdtTemplateEngine\OdtPackage $package = null,
        private readonly ?string $ownerName = null
    ) {
        parent::__construct($context, $targetName, $package);
    }

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

    /**
     * Clone the prototype and bind scalar template values inside that clone.
     *
     * Caller keys are unsuffixed; clone identity suffixes are internal.
     *
     * @param array<string, scalar|null> $values
     */
    public function instantiate(array $values): self
    {
        $service = new SectionInstantiationService();
        $clone = $this->ownerName === null
            ? $service->instantiate($this->context, $this->targetName, $values)
            : $service->instantiateNested($this->context, $this->ownerName, $this->targetName, $values);

        return new self($this->context, $clone->getAttribute('text:name'), $this->package, $this->ownerName);
    }

    /**
     * Expand this section prototype into an ordered, finalized collection.
     *
     * @param list<array<string, mixed>> $items
     * @return list<self>
     */
    public function instantiateMany(array $items): array
    {
        $nodes = (new SectionCollectionInstantiationService())->instantiateMany(
            $this->context,
            $this->targetName,
            $items,
            $this->ownerName
        );

        return array_map(
            fn (\DOMElement $node): self => new self(
                $this->context,
                $node->getAttribute('text:name'),
                $this->package,
                $this->ownerName
            ),
            $nodes
        );
    }

    /**
     * Resolve a named nested section relative to this section instance.
     * The caller uses the prototype's logical name; generated physical suffixes
     * are deliberately kept internal.
     */
    public function section(string $name): self
    {
        $owner = (new TypedTargetResolver())->resolveSectionElement($this->context, $this->targetName);
        $candidates = [];
        $ownerSuffix = '';
        if (preg_match('/(_\d+)$/', $this->targetName, $match) === 1) {
            $ownerSuffix = $match[1];
        }

        foreach ($owner->getElementsByTagNameNS('urn:oasis:names:tc:opendocument:xmlns:text:1.0', 'section') as $node) {
            if (!$node instanceof \DOMElement) {
                continue;
            }
            $physicalName = $node->getAttribute('text:name');
            if ($physicalName === $name || ($ownerSuffix !== '' && $physicalName === $name . $ownerSuffix)) {
                $candidates[] = $node;
            }
        }

        if ($candidates === []) {
            throw new TargetNotFoundException('nested section', $name);
        }
        if (count($candidates) > 1) {
            throw new AmbiguousAddressableTargetException('nested section', $name);
        }

        return new self($this->context, $candidates[0]->getAttribute('text:name'), $this->package, $this->targetName);
    }
}
