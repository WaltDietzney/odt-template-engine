<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Template;

use DOMDocument;

/** Read-only template-language structure inspection. */
final class TemplateStructureInspector
{
    public function __construct(private readonly TemplateExpressionProjector $projector = new TemplateExpressionProjector()) {}

    public function inspect(DOMDocument $content): TemplateStructureInspection
    {
        $diagnostics = [];
        $expressions = $this->projector->project($content, $diagnostics);
        return new TemplateStructureInspection($expressions, $diagnostics);
    }
}
