<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Document;

use DOMElement;
use DOMXPath;
use OdtTemplateEngine\OdtDocumentContext;
use OdtTemplateEngine\Template\NativeObjectDescriptor;
use OdtTemplateEngine\Template\SourceProvenance;

/**
 * Resolves TemplateContract Section evidence against the current working DOM.
 *
 * @internal
 */
final class SectionWorkingTargetResolver
{
    private const OFFICE_NAMESPACE = 'urn:oasis:names:tc:opendocument:xmlns:office:1.0';
    private const STYLE_NAMESPACE = 'urn:oasis:names:tc:opendocument:xmlns:style:1.0';
    private const TEXT_NAMESPACE = 'urn:oasis:names:tc:opendocument:xmlns:text:1.0';

    public function resolve(
        OdtDocumentContext $context,
        NativeObjectDescriptor $descriptor
    ): SectionWorkingTarget {
        if ($descriptor->kind() !== 'section' || $descriptor->name() === null) {
            throw new SectionResolutionException('native object is not a named Section');
        }

        $provenance = $descriptor->provenance();
        [$document, $regionRoot] = $this->regionRoot($context, $provenance);
        $matches = [];
        foreach ($regionRoot->getElementsByTagNameNS(self::TEXT_NAMESPACE, 'section') as $node) {
            if ($node instanceof DOMElement && $node->getAttribute('text:name') === $descriptor->name()) {
                $matches[] = $node;
            }
        }

        if ($matches === []) {
            throw new TargetNotFoundException('section', $descriptor->name());
        }
        if (count($matches) > 1) {
            throw new AmbiguousAddressableTargetException('section', $descriptor->name());
        }

        return new SectionWorkingTarget($document, $regionRoot, $matches[0], $provenance);
    }

    /** @return array{0:\DOMDocument,1:DOMElement} */
    private function regionRoot(OdtDocumentContext $context, SourceProvenance $provenance): array
    {
        if ($provenance->sourcePart() === 'content.xml' && $provenance->regionKind() === 'BODY') {
            if ($provenance->regionOwner() !== null || $provenance->carrierKind() !== 'office:text') {
                throw new SectionResolutionException('contradictory BODY Section provenance');
            }

            $document = $context->contentDom();
            $xpath = $this->xpath($document);
            $body = $xpath->query('/office:document-content/office:body/office:text')->item(0);
            if (!$body instanceof DOMElement) {
                throw new SectionResolutionException('BODY working region is unavailable');
            }

            return [$document, $body];
        }

        if ($provenance->sourcePart() !== 'styles.xml'
            || $provenance->regionKind() !== 'MASTER_PAGE_CONTENT'
            || $provenance->regionOwner() === null
            || !in_array($provenance->carrierKind(), ['style:header', 'style:footer'], true)
        ) {
            throw new SectionResolutionException('unsupported or contradictory Section provenance');
        }

        $document = $context->stylesDom();
        foreach ($document->getElementsByTagNameNS(self::STYLE_NAMESPACE, 'master-page') as $masterPage) {
            if (!$masterPage instanceof DOMElement
                || $masterPage->getAttribute('style:name') !== $provenance->regionOwner()
            ) {
                continue;
            }

            foreach ($masterPage->childNodes as $child) {
                if ($child instanceof DOMElement && $child->nodeName === $provenance->carrierKind()) {
                    return [$document, $child];
                }
            }
        }

        throw new TargetNotFoundException('master-page region', $provenance->regionOwner());
    }

    private function xpath(\DOMDocument $document): DOMXPath
    {
        $xpath = new DOMXPath($document);
        $xpath->registerNamespace('office', self::OFFICE_NAMESPACE);
        return $xpath;
    }
}
