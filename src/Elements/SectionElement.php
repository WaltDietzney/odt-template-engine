<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Elements;

use DOMDocument;
use DOMNode;
use InvalidArgumentException;

/** A PHP-owned native Writer Section with ordered block children. */
final class SectionElement extends OdtElement
{
    public function __construct(private readonly string $name)
    {
        if (trim($name) === '') {
            throw new InvalidArgumentException('Section name must not be empty.');
        }
    }

    public function name(): string
    {
        return $this->name;
    }

    public function addSection(self $section): self
    {
        return $this->addElement($section);
    }

    public function toDomNode(DOMDocument $dom): DOMNode
    {
        $section = $dom->createElement('text:section');
        $section->setAttribute('text:name', $this->name);
        foreach ($this->ownedElements() as $element) {
            $section->appendChild($element->toDomNode($dom));
        }

        return $section;
    }
}
