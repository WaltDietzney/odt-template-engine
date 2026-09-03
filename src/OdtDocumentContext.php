<?php

declare(strict_types=1);

namespace OdtTemplateEngine;

use DOMDocument;
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

    public function __construct(
        private DOMDocument $contentDom,
        private DOMDocument $stylesDom,
        private DOMDocument $metaDom
    ) {
        $this->styleContext = new StyleContext($contentDom, $stylesDom);
        $this->fontFaceRequirements = new FontFaceRequirementRegistry();
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

    /**
     * Replace the core XML documents after a package reload.
     *
     * Replacing the document contents is also a reset boundary for pending
     * document-scoped style requirements.
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
        $this->styleContext->reset();
        $this->styleContext->replaceDocumentParts($contentDom, $stylesDom);
    }
}
