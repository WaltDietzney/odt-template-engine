<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Document;

use OdtTemplateEngine\OdtDocumentContext;
use OdtTemplateEngine\OdtPackage;

/**
 * Strictly resolves typed addressable handles against a current document
 * context. It performs no mutation and stores no DOM nodes.
 */
final class TypedTargetResolver
{
    private DocumentInspector $inspector;

    public function __construct(?DocumentInspector $inspector = null)
    {
        $this->inspector = $inspector ?? new DocumentInspector();
    }

    public function resolveSection(OdtDocumentContext $context, string $name, ?OdtPackage $package = null): SectionTarget
    {
        $this->resolveSectionDescriptor($context, $name);

        return new SectionTarget($context, $name, $package);
    }

    public function resolveBookmark(OdtDocumentContext $context, string $name): BookmarkTarget
    {
        $this->resolveBookmarkDescriptor($context, $name);

        return new BookmarkTarget($context, $name);
    }

    public function resolveTable(OdtDocumentContext $context, string $name): TableTarget
    {
        $this->resolveTableDescriptor($context, $name);

        return new TableTarget($context, $name);
    }

    public function resolveFrame(OdtDocumentContext $context, string $name): FrameTarget
    {
        $this->resolveFrameDescriptor($context, $name);

        return new FrameTarget($context, $name);
    }

    public function resolveSectionDescriptor(OdtDocumentContext $context, string $name): SectionDescriptor
    {
        return $this->unique($this->inspect($context)->sections(), 'section', $name);
    }

    public function resolveSectionElement(OdtDocumentContext $context, string $name): \DOMElement
    {
        $matches = [];
        foreach ($context->contentDom()->getElementsByTagNameNS(
            'urn:oasis:names:tc:opendocument:xmlns:text:1.0',
            'section'
        ) as $node) {
            if ($node instanceof \DOMElement && $node->getAttribute('text:name') === $name) {
                $matches[] = $node;
            }
        }
        if ($matches === []) {
            throw new TargetNotFoundException('section', $name);
        }
        if (count($matches) > 1) {
            throw new AmbiguousAddressableTargetException('section', $name);
        }
        return $matches[0];
    }

    public function resolveBookmarkDescriptor(OdtDocumentContext $context, string $name): BookmarkDescriptor
    {
        $descriptor = $this->unique($this->inspect($context)->bookmarks(), 'bookmark', $name);
        if ($descriptor->topology() === BookmarkDescriptor::TOPOLOGY_MALFORMED) {
            throw new MalformedTargetException('bookmark', $name);
        }

        return $descriptor;
    }

    public function resolveTableDescriptor(OdtDocumentContext $context, string $name): TableDescriptor
    {
        return $this->unique($this->inspect($context)->tables(), 'table', $name);
    }

    public function resolveFrameDescriptor(OdtDocumentContext $context, string $name): FrameDescriptor
    {
        return $this->unique($this->inspect($context)->frames(), 'frame', $name);
    }

    private function inspect(OdtDocumentContext $context): DocumentInspection
    {
        return $this->inspector->inspect($context->contentDom(), $context->stylesDom());
    }

    /**
     * @template T of object
     * @param list<T> $descriptors
     * @return T
     */
    private function unique(array $descriptors, string $type, string $name): object
    {
        $matches = array_values(array_filter(
            $descriptors,
            static fn (object $descriptor): bool => $descriptor->name() === $name
        ));

        if ($matches === []) {
            throw new TargetNotFoundException($type, $name);
        }
        if (count($matches) > 1) {
            throw new AmbiguousAddressableTargetException($type, $name);
        }

        return $matches[0];
    }
}
