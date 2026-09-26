<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Document;

use DOMElement;

/** Bounded internal removal primitive used by collection finalization. */
final class SectionRemovalService
{
    public function remove(DOMElement $section): void
    {
        if ($section->parentNode === null) {
            throw new SectionInstantiationException($section->getAttribute('text:name'), 'prototype has no parent removal context');
        }

        try {
            $section->parentNode->removeChild($section);
        } catch (\Throwable $exception) {
            throw new SectionInstantiationException($section->getAttribute('text:name'), 'prototype could not be removed');
        }
    }
}
