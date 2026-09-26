<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Document;

/**
 * Describes how a structured element participates in placeholder insertion.
 */
enum StructuredInsertionMode: string
{
    case BLOCK = 'block';
    case INLINE_TEXT_FLOW = 'inline-text-flow';
    case PRESERVE_TEXT_CONTAINER = 'preserve-text-container';
}
