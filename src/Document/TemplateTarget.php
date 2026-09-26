<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Document;

use DOMElement;

/**
 * A resolved native ODF template target.
 *
 * The target references the DOM element in the supplied document. It does not
 * copy or mutate the resolved subtree.
 */
final readonly class TemplateTarget
{
    public const TYPE_FRAME = 'frame';
    public const TYPE_TABLE = 'table';

    public function __construct(
        private string $type,
        private string $name,
        private DOMElement $node
    ) {
    }

    public function type(): string
    {
        return $this->type;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function node(): DOMElement
    {
        return $this->node;
    }
}
