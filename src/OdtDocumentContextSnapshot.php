<?php

declare(strict_types=1);

namespace OdtTemplateEngine;

use DOMDocument;
use OdtTemplateEngine\Document\FillImageRequirement;
use OdtTemplateEngine\Document\FontFaceRequirement;
use OdtTemplateEngine\Style\StyleContext;

/** @internal Immutable snapshot of the mutable collaborators owned by one document context. */
final readonly class OdtDocumentContextSnapshot
{
    /**
     * @param list<FontFaceRequirement> $fontFaceRequirements
     * @param list<FillImageRequirement> $fillImageRequirements
     */
    public function __construct(
        private DOMDocument $contentDom,
        private DOMDocument $stylesDom,
        private DOMDocument $metaDom,
        private StyleContext $styleContext,
        private array $fontFaceRequirements,
        private array $fillImageRequirements
    ) {
    }

    public function contentDom(): DOMDocument { return $this->contentDom; }
    public function stylesDom(): DOMDocument { return $this->stylesDom; }
    public function metaDom(): DOMDocument { return $this->metaDom; }
    public function styleContext(): StyleContext { return $this->styleContext; }

    /** @return list<FontFaceRequirement> */
    public function fontFaceRequirements(): array { return $this->fontFaceRequirements; }

    /** @return list<FillImageRequirement> */
    public function fillImageRequirements(): array { return $this->fillImageRequirements; }
}
