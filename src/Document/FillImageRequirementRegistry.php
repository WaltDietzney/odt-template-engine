<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Document;

/**
 * Owns pending fill-image requirements for one logical document.
 */
final class FillImageRequirementRegistry
{
    /** @var array<string, FillImageRequirement> */
    private array $requirements = [];

    public function register(FillImageRequirement $requirement): void
    {
        $key = $this->key($requirement);
        if (!isset($this->requirements[$key])) {
            $this->requirements[$key] = $requirement;
            return;
        }

        if ($this->requirements[$key]->equals($requirement)) {
            return;
        }

        throw new FillImageRequirementConflictException(sprintf(
            'Fill-image identity "%s" in %s is already registered with a different href.',
            $requirement->name(),
            $requirement->documentPart()
        ));
    }

    /** @return list<FillImageRequirement> */
    public function requirements(): array
    {
        return array_values($this->requirements);
    }

    public function reset(): void
    {
        $this->requirements = [];
    }

    private function key(FillImageRequirement $requirement): string
    {
        return $requirement->documentPart() . "\0" . $requirement->name();
    }
}
