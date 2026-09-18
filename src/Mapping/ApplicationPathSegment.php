<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Mapping;

final readonly class ApplicationPathSegment
{
    public const VALUE = 'VALUE';
    public const COLLECTION = 'COLLECTION';

    public function __construct(
        private string $name,
        private string $kind
    ) {
        if (preg_match('/^[A-Za-z_][A-Za-z0-9_-]*$/D', $name) !== 1
            || !in_array($kind, [self::VALUE, self::COLLECTION], true)
        ) {
            throw new \InvalidArgumentException('Application path segment is invalid.');
        }
    }

    public function name(): string
    {
        return $this->name;
    }

    public function kind(): string
    {
        return $this->kind;
    }

    public function isCollection(): bool
    {
        return $this->kind === self::COLLECTION;
    }

    public function toString(): string
    {
        return $this->name . ($this->isCollection() ? '[]' : '');
    }
}
