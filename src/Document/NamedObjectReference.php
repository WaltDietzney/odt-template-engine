<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Document;

/**
 * A compact reference to a nested named native object.
 */
final readonly class NamedObjectReference
{
    public function __construct(
        private string $type,
        private string $name,
        private string $documentPart
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

    public function documentPart(): string
    {
        return $this->documentPart;
    }

    /** @return array<string, string> */
    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'name' => $this->name,
            'document_part' => $this->documentPart,
        ];
    }
}
