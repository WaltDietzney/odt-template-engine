<?php

declare(strict_types=1);

namespace OdtTemplateEngine;

use DOMDocument;

/**
 * Holds the mutable XML state of one ODT document.
 *
 * This is deliberately a small document-scoped state object. It creates a
 * stable ownership boundary for content.xml, styles.xml, and meta.xml without
 * yet introducing style defaults, page structures, assets, or renderer state.
 * Those concerns may later attach to the same document lifetime when their
 * architecture is defined explicitly.
 */
final class OdtDocumentContext
{
    public function __construct(
        private DOMDocument $contentDom,
        private DOMDocument $stylesDom,
        private DOMDocument $metaDom
    ) {
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

    /**
     * Replace the core XML documents after a package reload.
     */
    public function replaceCoreDocuments(
        DOMDocument $contentDom,
        DOMDocument $stylesDom,
        DOMDocument $metaDom
    ): void {
        $this->contentDom = $contentDom;
        $this->stylesDom = $stylesDom;
        $this->metaDom = $metaDom;
    }
}
