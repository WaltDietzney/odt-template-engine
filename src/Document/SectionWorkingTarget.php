<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Document;

use DOMDocument;
use DOMElement;
use OdtTemplateEngine\Template\SourceProvenance;

/**
 * Immutable internal Section target resolved within one current document part
 * and one TemplateContract-supported carrier region.
 *
 * @internal
 */
final readonly class SectionWorkingTarget
{
    public function __construct(
        private DOMDocument $document,
        private DOMElement $regionRoot,
        private DOMElement $section,
        private SourceProvenance $provenance,
        private string $nativeObjectId
    ) {
    }

    public function document(): DOMDocument
    {
        return $this->document;
    }

    public function regionRoot(): DOMElement
    {
        return $this->regionRoot;
    }

    public function section(): DOMElement
    {
        return $this->section;
    }

    public function name(): string
    {
        return $this->section->getAttribute('text:name');
    }

    public function provenance(): SourceProvenance
    {
        return $this->provenance;
    }

    public function nativeObjectId(): string
    {
        return $this->nativeObjectId;
    }
}
