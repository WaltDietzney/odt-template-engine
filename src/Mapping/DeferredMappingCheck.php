<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Mapping;

final readonly class DeferredMappingCheck
{
    /** @param array<string, scalar|null> $context */
    public function __construct(
        private string $code,
        private string $message,
        private array $context = []
    ) {
    }

    public function code(): string
    {
        return $this->code;
    }

    public function message(): string
    {
        return $this->message;
    }

    /** @return array<string, scalar|null> */
    public function context(): array
    {
        return $this->context;
    }
}
