<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Document;

use DOMElement;
use OdtTemplateEngine\OdtDocumentContext;

/** Orchestrates ordered collection expansion and terminal prototype removal. */
final class SectionCollectionInstantiationService
{
    public function __construct(
        private readonly SectionInstantiationService $instances = new SectionInstantiationService(),
        private readonly SectionRemovalService $removal = new SectionRemovalService()
    ) {
    }

    /**
     * @param list<array<string, mixed>> $items
     * @return list<DOMElement>
     */
    public function instantiateMany(
        OdtDocumentContext $context,
        string $prototypeName,
        array $items,
        ?string $ownerName = null
    ): array {
        $prototype = $this->findPrototype($context, $prototypeName, $ownerName);
        $created = [];

        try {
            foreach ($items as $item) {
                if (!is_array($item)) {
                    throw new SectionInstantiationException($prototypeName, 'collection item must be an associative array');
                }
                $created[] = $ownerName === null
                    ? $this->instances->instantiate($context, $prototypeName, $item)
                    : $this->instances->instantiateNested($context, $ownerName, $prototypeName, $item);
            }

            $this->removal->remove($prototype);
            return $created;
        } catch (\Throwable $exception) {
            foreach (array_reverse($created) as $instance) {
                if ($instance->parentNode !== null) {
                    $instance->parentNode->removeChild($instance);
                }
            }
            throw $exception;
        }
    }

    private function findPrototype(OdtDocumentContext $context, string $name, ?string $ownerName): DOMElement
    {
        $root = $context->contentDom();
        if ($ownerName === null) {
            $matches = [];
            foreach ($root->getElementsByTagNameNS('urn:oasis:names:tc:opendocument:xmlns:text:1.0', 'section') as $node) {
                if ($node instanceof DOMElement && $node->getAttribute('text:name') === $name) {
                    $matches[] = $node;
                }
            }
        } else {
            $owner = $this->findUnique($root, $ownerName);
            $matches = [];
            foreach ($owner->getElementsByTagNameNS('urn:oasis:names:tc:opendocument:xmlns:text:1.0', 'section') as $node) {
                if ($node instanceof DOMElement && $node->getAttribute('text:name') === $name) {
                    $matches[] = $node;
                }
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

    private function findUnique(\DOMDocument $document, string $name): DOMElement
    {
        $matches = [];
        foreach ($document->getElementsByTagNameNS('urn:oasis:names:tc:opendocument:xmlns:text:1.0', 'section') as $node) {
            if ($node instanceof DOMElement && $node->getAttribute('text:name') === $name) {
                $matches[] = $node;
            }
        }
        if (count($matches) !== 1) {
            throw count($matches) === 0
                ? new TargetNotFoundException('section', $name)
                : new AmbiguousAddressableTargetException('section', $name);
        }
        return $matches[0];
    }
}
