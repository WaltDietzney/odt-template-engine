<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Mapping;

final readonly class ProjectedDocumentCapability
{
    public function __construct(
        private string $group,
        private string $target,
        private string $payloadKind = 'SCALAR'
    )
    {
    }

    public function group(): string
    {
        return $this->group;
    }

    public function target(): string
    {
        return $this->target;
    }

    public function supported(): bool
    {
        return true;
    }

    public function payloadKind(): string
    {
        return $this->payloadKind;
    }

    public function mutationOwner(): string
    {
        return 'METADATA';
    }
}
