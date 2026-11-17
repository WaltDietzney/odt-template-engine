<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Elements;

use DOMDocument;
use DOMNode;
use InvalidArgumentException;
use OdtTemplateEngine\Document\StyleRequirement;

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

    public function setSectionStyle(string $name): self
    {
        $name = trim($name);
        if ($name === '') {
            throw new InvalidArgumentException('Section style name must not be empty.');
        }
        $this->sectionStyle = $name;
        return $this;
    }

    public function getSectionStyle(): ?string
    {
        return $this->sectionStyle;
    }

    private ?string $sectionStyle = null;

    public function getOwnStyleRequirements(): iterable
    {
        if ($this->sectionStyle !== null) {
            yield new StyleRequirement(
                StyleRequirement::KIND_REFERENCE,
                StyleRequirement::SCOPE_AUTOMATIC,
                'section',
                StyleRequirement::PART_CONTENT,
                $this->sectionStyle
            );
        }
    }

    public function addSection(self $section): self
    {
        return $this->addElement($section);
    }

    public function toDomNode(DOMDocument $dom): DOMNode
    {
        $section = $dom->createElement('text:section');
        $section->setAttribute('text:name', $this->name);
        if ($this->sectionStyle !== null) {
            $section->setAttribute('text:style-name', $this->sectionStyle);
        }
        foreach ($this->ownedElements() as $element) {
            $section->appendChild($element->toDomNode($dom));
        }

        return $section;
    }
}
