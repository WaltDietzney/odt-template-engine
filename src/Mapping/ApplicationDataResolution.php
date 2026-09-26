<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Mapping;

/** Read-only result node for application-path traversal. */
final readonly class ApplicationDataResolution
{
    public const MISSING = 'MISSING';
    public const NULL = 'NULL';
    public const PRESENT = 'PRESENT';
    public const EMPTY_COLLECTION = 'EMPTY_COLLECTION';
    public const WRONG_SHAPE = 'WRONG_SHAPE';

    /**
     * @param list<ApplicationDataResolution> $items
     */
    public function __construct(
        private string $status,
        private mixed $value = null,
        private ?int $itemIndex = null,
        private array $items = []
    ) {
        if (!in_array($status, [
            self::MISSING,
            self::NULL,
            self::PRESENT,
            self::EMPTY_COLLECTION,
            self::WRONG_SHAPE,
        ], true) || !array_is_list($items)) {
            throw new \InvalidArgumentException('Application data resolution is invalid.');
        }
    }

    public function status(): string
    {
        return $this->status;
    }

    /** The resolved value or the value whose shape failed; null for MISSING. */
    public function value(): mixed
    {
        return $this->value;
    }

    /** The zero-based index in the containing collection, when this is an item result. */
    public function itemIndex(): ?int
    {
        return $this->itemIndex;
    }

    /**
     * Collection items or, for nested paths, the item-local result for the remaining path.
     * Each child retains its own zero-based collection index.
     *
     * @return list<ApplicationDataResolution>
     */
    public function items(): array
    {
        return $this->items;
    }
}
