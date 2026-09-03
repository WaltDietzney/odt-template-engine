<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Document;

use DOMDocument;
use DOMElement;
use InvalidArgumentException;
use OdtTemplateEngine\OdtDocumentContext;

/**
 * Materializes missing document-local semantic font-face requirements.
 *
 * This service is deliberately separate from style materialization and the
 * legacy StyleWriter. It writes only the identity and family represented by
 * FontFaceRequirement.
 */
final class FontFaceRequirementMaterializer
{
    private const OFFICE_NAMESPACE = 'urn:oasis:names:tc:opendocument:xmlns:office:1.0';
    private const STYLE_NAMESPACE = 'urn:oasis:names:tc:opendocument:xmlns:style:1.0';
    private const SVG_NAMESPACE = 'urn:oasis:names:tc:opendocument:xmlns:svg-compatible:1.0';

    public function __construct(private ?FontFaceRequirementResolver $resolver = null)
    {
        $this->resolver ??= new FontFaceRequirementResolver();
    }

    public function materialize(OdtDocumentContext $context, FontFaceRequirement $requirement): void
    {
        if ($this->resolver->resolve($context, $requirement) !== FontFaceRequirementResolver::STATUS_MISSING) {
            return;
        }

        $dom = $requirement->documentPart() === FontFaceRequirement::PART_CONTENT
            ? $context->contentDom()
            : $context->stylesDom();
        $container = $this->container($dom);
        $fontFace = $dom->createElementNS(self::STYLE_NAMESPACE, 'style:font-face');
        $fontFace->setAttributeNS(self::STYLE_NAMESPACE, 'style:name', $requirement->fontFaceName());
        $fontFace->setAttributeNS(self::SVG_NAMESPACE, 'svg:font-family', $requirement->fontFamily());
        $container->appendChild($fontFace);
    }

    /**
     * @param iterable<FontFaceRequirement> $requirements
     */
    public function materializeAll(OdtDocumentContext $context, iterable $requirements): void
    {
        foreach ($requirements as $requirement) {
            $this->materialize($context, $requirement);
        }
    }

    private function container(DOMDocument $dom): DOMElement
    {
        $root = $dom->documentElement;
        if (!$root instanceof DOMElement) {
            throw new InvalidArgumentException('ODF document has no document element.');
        }

        foreach ($root->childNodes as $child) {
            if ($child instanceof DOMElement
                && $child->namespaceURI === self::OFFICE_NAMESPACE
                && $child->localName === 'font-face-decls') {
                return $child;
            }
        }

        $container = $dom->createElementNS(self::OFFICE_NAMESPACE, 'office:font-face-decls');
        foreach ($root->childNodes as $child) {
            if (!$child instanceof DOMElement || $child->namespaceURI !== self::OFFICE_NAMESPACE) {
                continue;
            }
            if (in_array($child->localName, ['styles', 'automatic-styles', 'master-styles', 'body'], true)) {
                $root->insertBefore($container, $child);
                return $container;
            }
        }

        $root->appendChild($container);

        return $container;
    }
}
