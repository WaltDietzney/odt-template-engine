<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Mapping;

final readonly class DependencyAutomationCapability
{
    public function __construct(private string $id)
    {
    }

    public function id(): string
    {
        return $this->id;
    }
}
