<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Document;

use DOMDocument;
use DOMElement;
use DOMNode;
use OdtTemplateEngine\Elements\OdtElement;
use OdtTemplateEngine\Elements\SectionElement;
use OdtTemplateEngine\OdtDocumentContext;
use OdtTemplateEngine\OdtPackage;

/**
 * Replaces the children of one native section with bounded structured content.
 */
final class SectionMutationService
{
    private const OFFICE_NAMESPACE = 'urn:oasis:names:tc:opendocument:xmlns:office:1.0';
    private const DRAWING_NAMESPACE = 'urn:oasis:names:tc:opendocument:xmlns:drawing:1.0';
    private const TABLE_NAMESPACE = 'urn:oasis:names:tc:opendocument:xmlns:table:1.0';
    private const TEXT_NAMESPACE = 'urn:oasis:names:tc:opendocument:xmlns:text:1.0';

    public function replaceContent(
        OdtDocumentContext $context,
        string $name,
        OdtElement $content,
        ?OdtPackage $package = null
    ): void
    {
        $section = $this->findSection($context->contentDom(), $name);
        $this->replaceLocatedContent($context->contentDom(), $section, $name, $content, $package);
    }

    /** @internal Reuse SECTION-03 mutation semantics for a provenance-bounded region target. */
    public function replaceContentInWorkingTarget(
        SectionWorkingTarget $target,
        OdtElement $content,
        ?OdtPackage $package = null
    ): void {
        $this->replaceLocatedContent($target->document(), $target->section(), $target->name(), $content, $package);
    }

    public function appendBody(
        OdtDocumentContext $context,
        OdtElement $content,
        ?OdtPackage $package = null
    ): void {
        $body = $this->findBodyText($context->contentDom());
        $nodes = $this->buildValidatedNodes($context, $content, 'appendBody', '');
        $this->appendNodes($context->contentDom(), $body, $nodes, $content, $package, 'appendBody', '');
    }

    public function appendSection(
        OdtDocumentContext $context,
        SectionElement $section,
        ?OdtPackage $package = null
    ): string {
        $body = $this->findBodyText($context->contentDom());
        $nodes = $this->buildValidatedNodes($context, $section, 'appendSection', $section->name());
        $this->appendNodes($context->contentDom(), $body, $nodes, $section, $package, 'appendSection', $section->name());

        return $section->name();
    }

    /**
     * Construction must reject ownership cycles before any recursive collector
     * or renderer is allowed to walk the user-owned graph. Repeated references
     * are valid; only an object encountered on the current recursion path is a
     * cycle.
     */
    public function validateOwnershipTree(OdtElement $root, string $operation, string $sectionName = ''): void
    {
        $active = [];
        $walk = function (OdtElement $element) use (&$walk, &$active, $operation, $sectionName): void {
            $id = spl_object_id($element);
            if (isset($active[$id])) {
                throw new SectionMutationException($sectionName, $operation, 'content ownership contains a cycle');
            }
            $active[$id] = true;
            foreach ($element->ownedElements() as $child) {
                if (!$child instanceof OdtElement) {
                    throw new SectionMutationException($sectionName, $operation, 'content ownership contains a non-element value');
                }
                $walk($child);
            }
            unset($active[$id]);
        };

        $walk($root);
    }

    /** @return list<DOMElement> */
    private function buildValidatedNodes(
        OdtDocumentContext $context,
        OdtElement $content,
        string $operation,
        string $sectionName
    ): array {
        $document = $context->contentDom();
        $replacement = $content->toDomNode($document->cloneNode(true));
        if ($replacement instanceof DOMElement) {
            $nodes = [$replacement];
        } elseif ($replacement instanceof \DOMDocumentFragment) {
            $nodes = iterator_to_array($replacement->childNodes);
        } else {
            $this->constructionFail($operation, $sectionName, 'body content returned an unsupported top-level node');
        }
        $result = [];
        foreach ($nodes as $node) {
            if (!$node instanceof DOMElement) {
                $this->constructionFail($operation, $sectionName, 'body content contains non-element top-level content');
            }
            if (!in_array($node->nodeName, ['text:p', 'text:h', 'text:list', 'table:table', 'draw:frame', 'text:section'], true)) {
                $this->constructionFail($operation, $sectionName, 'body content contains a node that is not legal block content');
            }
            $this->normalizeSectionFrames($node);
            $this->validateConstructedSubtree($node, $operation, $sectionName);
            $this->validateSectionStyleReferences($context, $node, $operation, $sectionName);
            $result[] = $this->hostFrameInTextFlow($node);
        }

        $existing = $this->namedIdentities($document);
        $introduced = [];
        $this->validateBookmarkPairs($result, $operation, $sectionName);
        foreach ($result as $node) {
            foreach ($this->namedIdentitiesInSubtree($node) as [$type, $name]) {
                if ($name === '') {
                    if ($type === 'frame') {
                        continue;
                    }
                    $this->constructionFail($operation, $sectionName, 'body content contains a named native object without an identity');
                }
                $key = $type . ':' . $name;
                if (isset($introduced[$key]) || isset($existing[$key])) {
                    throw new SectionMutationException($sectionName, $operation, 'body content introduces a same-type native identity collision', $type, $name);
                }
                $introduced[$key] = true;
            }
        }

        return $result;
    }

    private function validateSectionStyleReferences(
        OdtDocumentContext $context,
        DOMElement $node,
        string $operation,
        string $sectionName
    ): void {
        if ($node->nodeName === 'text:section') {
            $styleName = $node->getAttribute('text:style-name');
            if ($styleName !== '' && !$this->hasAutomaticSectionStyle($context->contentDom(), $styleName)) {
                $this->constructionFail(
                    $operation,
                    $sectionName,
                    sprintf('Section style "%s" is missing or does not belong to the section family', $styleName),
                    'section',
                    $styleName
                );
            }
        }
        foreach ($node->childNodes as $child) {
            if ($child instanceof DOMElement) {
                $this->validateSectionStyleReferences($context, $child, $operation, $sectionName);
            }
        }
    }

    private function hasAutomaticSectionStyle(DOMDocument $dom, string $name): bool
    {
        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('office', self::OFFICE_NAMESPACE);
        $xpath->registerNamespace('style', 'urn:oasis:names:tc:opendocument:xmlns:style:1.0');
        return $xpath->query(sprintf(
            '//office:automatic-styles/style:style[@style:name=%s and @style:family="section"]',
            $this->xpathLiteral($name)
        ))?->length === 1;
    }

    private function xpathLiteral(string $value): string
    {
        if (!str_contains($value, "'")) {
            return "'" . $value . "'";
        }
        if (!str_contains($value, '"')) {
            return '"' . $value . '"';
        }
        $parts = explode("'", $value);
        return 'concat(' . implode(", \"'\", ", array_map(static fn (string $part): string => "'" . $part . "'", $parts)) . ')';
    }

    private function appendNodes(
        DOMDocument $document,
        DOMElement $body,
        array $nodes,
        OdtElement $content,
        ?OdtPackage $package,
        string $operation,
        string $sectionName
    ): void {
        $resourceReferences = $this->imageReferences($nodes, $operation, $sectionName);
        if ($resourceReferences !== [] && $package === null) {
            $this->constructionFail($operation, $sectionName, 'resource-bearing content requires package ownership');
        }
        $createdResources = [];
        $appended = [];
        try {
            if ($package !== null) {
                // This is the sole construction resource discovery/copy path.
                // It follows the ownership contract, including nested composites.
                $assets = iterator_to_array((new StructuredResourceCollector())->collect($content), false);
                $createdResources = $package->copyImageResourcesAtomically($assets);
                foreach ($resourceReferences as $reference) {
                    if (!str_starts_with($reference, 'Pictures/') || str_contains($reference, '..')) {
                        $this->constructionFail($operation, $sectionName, 'emitted package image reference is unsafe');
                    }
                    if (!is_file($package->path($reference))) {
                        $this->constructionFail($operation, $sectionName, 'emitted package image reference has no prepared or existing resource');
                    }
                }
            }
            foreach ($nodes as $node) {
                $copy = $this->copyNode($document, $node);
                $body->appendChild($copy);
                $appended[] = $copy;
            }
        } catch (\Throwable $exception) {
            foreach ($appended as $node) {
                if ($node->parentNode === $body) {
                    $body->removeChild($node);
                }
            }
            $package?->removePreparedPackageFiles($createdResources);
            throw $exception;
        }
    }

    private function findBodyText(DOMDocument $dom): DOMElement
    {
        $office = $dom->documentElement;
        $body = null;
        if ($office instanceof DOMElement) {
            foreach ($office->childNodes as $child) {
                if ($child instanceof DOMElement && $child->namespaceURI === self::OFFICE_NAMESPACE && $child->localName === 'body') {
                    foreach ($child->childNodes as $bodyChild) {
                        if ($bodyChild instanceof DOMElement && $bodyChild->namespaceURI === self::OFFICE_NAMESPACE && $bodyChild->localName === 'text') {
                            $body = $bodyChild;
                            break 2;
                        }
                    }
                }
            }
        }
        if (!$body instanceof DOMElement) {
            $this->constructionFail('appendBody', '', 'document does not contain office:body/office:text');
        }
        return $body;
    }

    private function validateConstructedSubtree(DOMElement $node, string $operation, string $sectionName): void
    {
        $kind = $this->validatedElementKind($node, $operation, $sectionName);
        if ($kind === 'text:section' && $node->getAttribute('text:name') === '') {
            $this->constructionFail($operation, $sectionName, 'content contains a Section without an identity');
        }
        foreach ($node->childNodes as $child) {
            if ($child->nodeType === XML_TEXT_NODE && in_array($kind, ['text:p', 'text:h', 'text:span', 'text:a'], true)) {
                continue;
            }
            if (!$child instanceof DOMElement) {
                $this->constructionFail($operation, $sectionName, 'content contains an unsupported non-element node');
            }
            $childKind = $this->validatedElementKind($child, $operation, $sectionName);
            if ($kind === 'text:section'
                && !in_array($childKind, ['text:p', 'text:h', 'text:list', 'table:table', 'draw:frame', 'text:section'], true)) {
                $this->constructionFail($operation, $sectionName, 'Section contains a node that is not legal block content');
            }
            if ($childKind === 'text:section' && $kind !== 'text:section') {
                $this->constructionFail($operation, $sectionName, 'content contains a Section outside legal block flow');
            }
            $this->validateConstructedSubtree($child, $operation, $sectionName);
        }
    }

    /**
     * Existing elements intentionally emit prefixed, unnamespaced DOM nodes.
     * Accept that legacy form, but never let a prefixed node claim the wrong
     * namespace. Namespace-aware native nodes must identify the same ODF kind.
     */
    private function validatedElementKind(DOMElement $node, string $operation, string $sectionName): string
    {
        $expected = [
            'office' => self::OFFICE_NAMESPACE,
            'text' => self::TEXT_NAMESPACE,
            'table' => self::TABLE_NAMESPACE,
            'draw' => self::DRAWING_NAMESPACE,
        ];
        $qualified = $node->nodeName;
        $prefix = str_contains($qualified, ':') ? explode(':', $qualified, 2)[0] : '';
        $expectedNamespace = $expected[$prefix] ?? null;
        if ($expectedNamespace === null) {
            $this->constructionFail($operation, $sectionName, 'content contains an unsupported element identity');
        }
        if ($node->namespaceURI !== null && $node->namespaceURI !== $expectedNamespace) {
            $this->constructionFail($operation, $sectionName, 'content contains an element with a wrong namespace');
        }
        foreach ($node->attributes as $attribute) {
            if (!str_contains($attribute->nodeName, ':') || $attribute->prefix === 'xmlns') {
                continue;
            }
            $attributePrefix = $attribute->prefix;
            $attributeNamespace = match ($attributePrefix) {
                'office' => self::OFFICE_NAMESPACE,
                'text' => self::TEXT_NAMESPACE,
                'table' => self::TABLE_NAMESPACE,
                'draw' => self::DRAWING_NAMESPACE,
                'xlink' => 'http://www.w3.org/1999/xlink',
                'style' => 'urn:oasis:names:tc:opendocument:xmlns:style:1.0',
                'svg' => 'urn:oasis:names:tc:opendocument:xmlns:svg-compatible:1.0',
                'fo' => 'urn:oasis:names:tc:opendocument:xmlns:xsl-fo-compatible:1.0',
                default => null,
            };
            if ($attributeNamespace !== null && $attribute->namespaceURI !== null && $attribute->namespaceURI !== $attributeNamespace) {
                $this->constructionFail($operation, $sectionName, 'content contains an attribute with a wrong namespace');
            }
        }

        return $qualified;
    }

    private function normalizeSectionFrames(DOMElement $node): void
    {
        if ($node->nodeName === 'text:section') {
            foreach (iterator_to_array($node->childNodes) as $child) {
                if ($child instanceof DOMElement && $child->nodeName === 'draw:frame') {
                    $this->hostFrameInTextFlow($child);
                    continue;
                }
                if ($child instanceof DOMElement) {
                    $this->normalizeSectionFrames($child);
                }
            }
        }
    }

    private function constructionFail(string $operation, string $sectionName, string $reason): never
    {
        throw new SectionMutationException($sectionName, $operation, $reason);
    }

    private function replaceLocatedContent(
        DOMDocument $document,
        DOMElement $section,
        string $name,
        OdtElement $content,
        ?OdtPackage $package
    ): void {
        $staging = $document->cloneNode(true);
        if (!$staging instanceof DOMDocument) {
            $this->fail($name, 'unable to create a detached materialization document');
        }

        $stagedSection = $this->correspondingElement($document, $section, $staging);
        $replacement = $content->toDomNode($staging);
        $nodes = $this->replacementNodes($replacement, $name);
        $this->validateNames($document, $section, $nodes, $name);

        $assets = $content->getImageAssets();
        $containsImage = false;
        foreach ($nodes as $node) {
            if ($this->containsImageAsset($node)) {
                $containsImage = true;
                break;
            }
        }
        if ($containsImage && $assets === []) {
            $this->fail($name, 'resource-bearing content does not expose package assets');
        }
        if ($assets !== [] && $package === null) {
            $this->fail($name, 'resource-bearing content requires package ownership');
        }
        $createdResources = $package?->copyImageResourcesAtomically($assets) ?? [];
        $originalChildren = [];
        foreach ($section->childNodes as $child) {
            $originalChildren[] = $child->cloneNode(true);
        }

        try {
            while ($stagedSection->firstChild !== null) {
                $stagedSection->removeChild($stagedSection->firstChild);
            }
            foreach ($nodes as $node) {
                $stagedSection->appendChild($node);
            }

            while ($section->firstChild !== null) {
                $section->removeChild($section->firstChild);
            }
            foreach ($nodes as $node) {
                $section->appendChild($this->copyNode($document, $node));
            }
        } catch (\Throwable $exception) {
            while ($section->firstChild !== null) {
                $section->removeChild($section->firstChild);
            }
            foreach ($originalChildren as $child) {
                $section->appendChild($this->copyNode($document, $child));
            }
            $package?->removePreparedPackageFiles($createdResources);
            throw $exception;
        }
    }

    private function correspondingElement(DOMDocument $source, DOMElement $element, DOMDocument $clone): DOMElement
    {
        $indexes = [];
        for ($node = $element; $node !== $source; $node = $node->parentNode) {
            if ($node->parentNode === null) {
                $this->fail($element->getAttribute('text:name'), 'Section target is detached from its owning document');
            }
            $index = 0;
            for ($sibling = $node->previousSibling; $sibling !== null; $sibling = $sibling->previousSibling) {
                ++$index;
            }
            $indexes[] = $index;
        }
        $current = $clone;
        foreach (array_reverse($indexes) as $index) {
            $current = $current->childNodes->item($index);
            if (!$current instanceof DOMElement) {
                $this->fail($element->getAttribute('text:name'), 'Unable to correlate the Section in the staging document');
            }
        }
        return $current;
    }

    private function findSection(DOMDocument $dom, string $name): DOMElement
    {
        $matches = [];
        foreach ($dom->getElementsByTagNameNS(self::TEXT_NAMESPACE, 'section') as $node) {
            if ($node instanceof DOMElement && $node->getAttribute('text:name') === $name) {
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

    /** @return list<DOMElement> */
    private function replacementNodes(DOMNode $replacement, string $name): array
    {
        $nodes = [];
        if ($replacement instanceof DOMElement) {
            $candidates = [$replacement];
        } else {
            $candidates = iterator_to_array($replacement->childNodes);
        }
        foreach ($candidates as $node) {
            if (!$node instanceof DOMElement) {
                $this->fail($name, 'replacement contains non-element top-level content');
            }
            if (!in_array($node->nodeName, ['text:p', 'text:h', 'text:list', 'table:table', 'draw:frame'], true)) {
                $this->fail($name, 'replacement contains a node that is not legal section block content');
            }
            $nodes[] = $this->hostFrameInTextFlow($node);
        }

        return $nodes;
    }

    private function hostFrameInTextFlow(DOMElement $node): DOMElement
    {
        if ($node->nodeName !== 'draw:frame') {
            return $node;
        }

        $document = $node->ownerDocument;
        $paragraph = $document->createElementNS(self::TEXT_NAMESPACE, 'text:p');
        $node->parentNode?->replaceChild($paragraph, $node);
        $paragraph->appendChild($node);

        return $paragraph;
    }

    /** @param list<DOMElement> $nodes */
    private function validateNames(DOMDocument $original, DOMElement $section, array $nodes, string $sectionName): void
    {
        $existing = $this->namedIdentities($original, $section);
        $this->validateLegacyBookmarkPairs($nodes, $sectionName);
        $introduced = [];
        foreach ($nodes as $node) {
            foreach ($this->namedIdentitiesInSubtree($node) as [$type, $name]) {
                if ($name === '') {
                    if ($type === 'frame') {
                        continue;
                    }
                    $this->fail($sectionName, 'replacement contains a named native object without an identity');
                }
                $key = $type . ':' . $name;
                if (isset($introduced[$key]) || isset($existing[$key])) {
                    throw new SectionMutationException(
                        $sectionName,
                        'replaceContent',
                        'replacement introduces a same-type native identity collision',
                        $type,
                        $name
                    );
                }
                $introduced[$key] = true;
            }
        }
    }

    /** @return array<string, true> */
    private function namedIdentities(DOMDocument $dom, ?DOMElement $excludedSection = null): array
    {
        $identities = [];
        foreach ($dom->getElementsByTagNameNS(self::TEXT_NAMESPACE, 'section') as $node) {
            if ($node instanceof DOMElement && ($excludedSection === null || (!$this->inside($node, $excludedSection) && $node !== $excludedSection))) {
                $this->addIdentity($identities, 'section', $node->getAttribute('text:name'));
            }
        }
        foreach ($dom->getElementsByTagNameNS(self::TEXT_NAMESPACE, 'bookmark-start') as $node) {
            if ($node instanceof DOMElement && ($excludedSection === null || !$this->inside($node, $excludedSection))) {
                $this->addIdentity($identities, 'bookmark', $node->getAttribute('text:name'));
            }
        }
        foreach ($dom->getElementsByTagNameNS(self::TEXT_NAMESPACE, 'bookmark') as $node) {
            if ($node instanceof DOMElement && ($excludedSection === null || !$this->inside($node, $excludedSection))) {
                $this->addIdentity($identities, 'bookmark', $node->getAttribute('text:name'));
            }
        }
        foreach ($dom->getElementsByTagNameNS(self::TABLE_NAMESPACE, 'table') as $node) {
            if ($node instanceof DOMElement && ($excludedSection === null || !$this->inside($node, $excludedSection))) {
                $this->addIdentity($identities, 'table', $node->getAttribute('table:name'));
            }
        }
        foreach ($dom->getElementsByTagNameNS(self::DRAWING_NAMESPACE, 'frame') as $node) {
            if ($node instanceof DOMElement && ($excludedSection === null || !$this->inside($node, $excludedSection))) {
                $this->addIdentity($identities, 'frame', $node->getAttribute('draw:name'));
            }
        }

        return $identities;
    }

    /** @return list<array{string, string}> */
    private function namedIdentitiesInSubtree(DOMElement $node): array
    {
        $identities = [];
        $walk = function (DOMNode $current) use (&$walk, &$identities): void {
            if ($current instanceof DOMElement) {
                $identity = match ($current->nodeName) {
                    'text:section' => ['section', $current->getAttribute('text:name')],
                    'text:bookmark-start', 'text:bookmark' => ['bookmark', $current->getAttribute('text:name')],
                    'table:table' => ['table', $current->getAttribute('table:name')],
                    'draw:frame' => ['frame', $current->getAttribute('draw:name')],
                    default => null,
                };
                if ($identity !== null) {
                    $identities[] = $identity;
                }
            }
            foreach ($current->childNodes as $child) {
                $walk($child);
            }
        };
        $walk($node);

        return $identities;
    }

    /** @param array<string, true> $identities */
    private function addIdentity(array &$identities, string $type, string $name): void
    {
        if ($name !== '') {
            $identities[$type . ':' . $name] = true;
        }
    }

    private function inside(DOMNode $node, DOMElement $ancestor): bool
    {
        for ($current = $node->parentNode; $current !== null; $current = $current->parentNode) {
            if ($current === $ancestor) {
                return true;
            }
        }

        return false;
    }

    /** @param list<DOMElement> $nodes @return list<string> */
    private function imageReferences(array $nodes, string $operation, string $sectionName): array
    {
        $references = [];
        $walk = function (DOMNode $node) use (&$walk, &$references, $operation, $sectionName): void {
            if ($node instanceof DOMElement && $node->nodeName === 'draw:image') {
                $reference = $node->getAttribute('xlink:href');
                if ($reference === '') {
                    throw new SectionMutationException($sectionName, $operation, 'emitted image has no package reference');
                }
                $references[$reference] = true;
            }
            foreach ($node->childNodes as $child) {
                $walk($child);
            }
        };
        foreach ($nodes as $node) {
            $walk($node);
        }

        return array_keys($references);
    }

    private function containsImageAsset(DOMNode $node): bool
    {
        if ($node instanceof DOMElement && $node->nodeName === 'draw:image') {
            return true;
        }
        foreach ($node->childNodes as $child) {
            if ($this->containsImageAsset($child)) {
                return true;
            }
        }

        return false;
    }

    /** @param list<DOMElement> $nodes */
    private function validateLegacyBookmarkPairs(array $nodes, string $sectionName): void
    {
        $markers = [];
        $walk = function (DOMNode $node) use (&$walk, &$markers): void {
            if ($node instanceof DOMElement) {
                $type = match ($node->nodeName) {
                    'text:bookmark' => 'collapsed',
                    'text:bookmark-start' => 'start',
                    'text:bookmark-end' => 'end',
                    default => null,
                };
                if ($type !== null) {
                    $name = $node->getAttribute('text:name');
                    $markers[$name][$type] = ($markers[$name][$type] ?? 0) + 1;
                }
            }
            foreach ($node->childNodes as $child) {
                $walk($child);
            }
        };
        foreach ($nodes as $node) {
            $walk($node);
        }
        foreach ($markers as $name => $counts) {
            $collapsed = $counts['collapsed'] ?? 0;
            $starts = $counts['start'] ?? 0;
            $ends = $counts['end'] ?? 0;
            if ($name === '' || ($collapsed !== 0 && ($collapsed !== 1 || $starts !== 0 || $ends !== 0))
                || ($collapsed === 0 && ($starts !== 1 || $ends !== 1))) {
                $this->fail($sectionName, 'replacement contains a malformed bookmark identity');
            }
        }
    }

    private function validateBookmarkPairs(array $nodes, string $operation, string $sectionName): void
    {
        $open = [];
        $seen = [];
        $walk = function (DOMNode $node) use (&$walk, &$open, &$seen, $operation, $sectionName): void {
            if ($node instanceof DOMElement) {
                $type = match ($node->nodeName) {
                    'text:bookmark' => 'collapsed',
                    'text:bookmark-start' => 'start',
                    'text:bookmark-end' => 'end',
                    default => null,
                };
                if ($type !== null) {
                    $name = $node->getAttribute('text:name');
                    if ($name === '') {
                        $this->bookmarkFail($operation, $sectionName);
                    }
                    if ($type === 'collapsed') {
                        if (isset($open[$name])) {
                            $this->bookmarkFail($operation, $sectionName);
                        }
                        $seen[$name] = true;
                    } elseif ($type === 'start') {
                        if (isset($open[$name])) {
                            $this->bookmarkFail($operation, $sectionName);
                        }
                        $open[$name] = true;
                    } elseif (!isset($open[$name])) {
                        $this->bookmarkFail($operation, $sectionName);
                    } else {
                        unset($open[$name]);
                        $seen[$name] = true;
                    }
                }
            }
            foreach ($node->childNodes as $child) {
                $walk($child);
            }
        };
        foreach ($nodes as $node) {
            $walk($node);
        }
        if ($open !== []) {
            $this->bookmarkFail($operation, $sectionName);
        }
    }

    private function bookmarkFail(string $operation, string $sectionName): never
    {
        if ($operation === 'replaceContent') {
            $this->fail($sectionName, 'replacement contains a malformed bookmark identity');
        }
        $this->failOperation($sectionName, $operation, 'content contains a malformed bookmark identity');
    }

    private function fail(string $sectionName, string $reason): never
    {
        throw new SectionMutationException($sectionName, 'replaceContent', $reason);
    }

    private function failOperation(string $sectionName, string $operation, string $reason): never
    {
        throw new SectionMutationException($sectionName, $operation, $reason);
    }

    private function copyNode(DOMDocument $document, DOMNode $node): DOMNode
    {
        if (!$node instanceof DOMElement) {
            return $document->importNode($node, true);
        }

        $namespaces = [
            'draw' => self::DRAWING_NAMESPACE,
            'table' => self::TABLE_NAMESPACE,
            'text' => self::TEXT_NAMESPACE,
            'office' => 'urn:oasis:names:tc:opendocument:xmlns:office:1.0',
            'style' => 'urn:oasis:names:tc:opendocument:xmlns:style:1.0',
            'svg' => 'urn:oasis:names:tc:opendocument:xmlns:svg-compatible:1.0',
            'xlink' => 'http://www.w3.org/1999/xlink',
            'fo' => 'urn:oasis:names:tc:opendocument:xmlns:xsl-fo-compatible:1.0',
            'dr3d' => 'urn:oasis:names:tc:opendocument:xmlns:dr3d:1.0',
        ];
        $prefix = str_contains($node->nodeName, ':') ? explode(':', $node->nodeName, 2)[0] : null;
        $namespace = $node->namespaceURI ?? ($prefix !== null ? ($namespaces[$prefix] ?? null) : null);
        $copy = $namespace !== null
            ? $document->createElementNS($namespace, $node->nodeName)
            : $document->createElement($node->nodeName);
        foreach ($node->attributes as $attribute) {
            if ($attribute->namespaceURI !== null) {
                $copy->setAttributeNS($attribute->namespaceURI, $attribute->nodeName, $attribute->nodeValue);
            } elseif (str_contains($attribute->nodeName, ':')) {
                [$attributePrefix] = explode(':', $attribute->nodeName, 2);
                if (isset($namespaces[$attributePrefix])) {
                    $copy->setAttributeNS($namespaces[$attributePrefix], $attribute->nodeName, $attribute->nodeValue);
                } else {
                    $copy->setAttribute($attribute->nodeName, $attribute->nodeValue);
                }
            } else {
                $copy->setAttribute($attribute->nodeName, $attribute->nodeValue);
            }
        }
        foreach ($node->childNodes as $child) {
            $copy->appendChild($this->copyNode($document, $child));
        }

        return $copy;
    }
}
