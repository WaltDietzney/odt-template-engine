<?php

declare(strict_types=1);

namespace OdtTemplateEngine;

use DOMDocument;
use DOMElement;
use OdtTemplateEngine\Document\FillImageRequirement;
use OdtTemplateEngine\Document\FillImageRequirementRegistry;
use OdtTemplateEngine\Document\FontFaceRequirement;
use OdtTemplateEngine\Document\FontFaceRequirementRegistry;
use OdtTemplateEngine\Style\StyleContext;

/**
 * Holds the mutable state of one ODT document.
 *
 * This is deliberately a small document-scoped state object. It creates a
 * stable ownership boundary for the core XML documents and document-local
 * collaborators without mixing those concerns into archive mechanics.
 */
final class OdtDocumentContext
{
    private StyleContext $styleContext;

    private FontFaceRequirementRegistry $fontFaceRequirements;

    private FillImageRequirementRegistry $fillImageRequirements;

    public function __construct(
        private DOMDocument $contentDom,
        private DOMDocument $stylesDom,
        private DOMDocument $metaDom
    ) {
        $this->styleContext = new StyleContext($contentDom, $stylesDom);
        $this->fontFaceRequirements = new FontFaceRequirementRegistry();
        $this->fillImageRequirements = new FillImageRequirementRegistry();
    }

    public function contentDom(): DOMDocument
    {
        return $this->contentDom;
    }

    public function stylesDom(): DOMDocument
    {
        return $this->stylesDom;
    }

    public function metaDom(): DOMDocument
    {
        return $this->metaDom;
    }

    public function styleContext(): StyleContext
    {
        return $this->styleContext;
    }

    public function registerFontFaceRequirement(FontFaceRequirement $requirement): void
    {
        $this->fontFaceRequirements->register($requirement);
    }

    public function fontFaceRequirements(): FontFaceRequirementRegistry
    {
        return $this->fontFaceRequirements;
    }

    public function registerFillImageRequirement(FillImageRequirement $requirement): void
    {
        $this->fillImageRequirements->register($requirement);
    }

    public function fillImageRequirements(): FillImageRequirementRegistry
    {
        return $this->fillImageRequirements;
    }

    /**
     * Replace the core XML documents after a package reload.
     *
     * Replacing the document contents is also a reset boundary for pending
     * document-scoped requirements.
     */
    public function replaceCoreDocuments(
        DOMDocument $contentDom,
        DOMDocument $stylesDom,
        DOMDocument $metaDom
    ): void {
        $this->contentDom = $contentDom;
        $this->stylesDom = $stylesDom;
        $this->metaDom = $metaDom;
        $this->fontFaceRequirements->reset();
        $this->fillImageRequirements->reset();
        $this->styleContext->reset();
        $this->styleContext->replaceDocumentParts($contentDom, $stylesDom);
    }

    /** @internal Capture all mutable document-local state used by Phase-E owners. */
    public function snapshotState(): OdtDocumentContextSnapshot
    {
        $content = $this->cloneDom($this->contentDom);
        $styles = $this->cloneDom($this->stylesDom);
        $meta = $this->cloneDom($this->metaDom);

        return new OdtDocumentContextSnapshot(
            $content,
            $styles,
            $meta,
            $this->styleContext->snapshotState(),
            $this->fontFaceRequirements->requirements(),
            $this->fillImageRequirements->requirements()
        );
    }

    /** @internal Restore state in the existing DOM and service instances. */
    public function restoreState(OdtDocumentContextSnapshot $snapshot): void
    {
        $this->restoreDom($this->contentDom, $snapshot->contentDom());
        $this->restoreDom($this->stylesDom, $snapshot->stylesDom());
        $this->restoreDom($this->metaDom, $snapshot->metaDom());
        $this->styleContext->restoreState($snapshot->styleContext(), $this->contentDom, $this->stylesDom);
        $this->fontFaceRequirements->restore($snapshot->fontFaceRequirements());
        $this->fillImageRequirements->restore($snapshot->fillImageRequirements());
    }

    private function cloneDom(DOMDocument $dom): DOMDocument
    {
        $clone = $dom->cloneNode(true);
        if (!$clone instanceof DOMDocument) {
            throw new \RuntimeException('Unable to snapshot a working ODT XML document.');
        }

        return $clone;
    }

    private function restoreDom(DOMDocument $target, DOMDocument $snapshot): void
    {
        $targetRoot = $target->documentElement;
        $snapshotRoot = $snapshot->documentElement;
        if (!$targetRoot instanceof DOMElement || !$snapshotRoot instanceof DOMElement) {
            throw new \RuntimeException('Unable to restore a working ODT XML document.');
        }

        while ($targetRoot->hasAttributes()) {
            $attribute = $targetRoot->attributes?->item(0);
            if ($attribute === null) {
                break;
            }
            if ($attribute->namespaceURI !== null) {
                $targetRoot->removeAttributeNS($attribute->namespaceURI, $attribute->localName);
            } else {
                $targetRoot->removeAttribute($attribute->name);
            }
        }
        foreach ($snapshotRoot->attributes as $attribute) {
            if ($attribute->namespaceURI !== null) {
                $targetRoot->setAttributeNS($attribute->namespaceURI, $attribute->nodeName, $attribute->nodeValue ?? '');
            } else {
                $targetRoot->setAttribute($attribute->name, $attribute->nodeValue ?? '');
            }
        }
        while ($targetRoot->firstChild !== null) {
            $targetRoot->removeChild($targetRoot->firstChild);
        }
        foreach ($snapshotRoot->childNodes as $child) {
            $targetRoot->appendChild($target->importNode($child, true));
        }
    }
}
