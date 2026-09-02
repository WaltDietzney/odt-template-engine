<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Document;

use InvalidArgumentException;

/**
 * Immutable semantic description of one ODF style requirement.
 *
 * This value object describes style semantics only. It does not resolve
 * dependencies or materialize XML.
 */
final readonly class StyleRequirement
{
    public const KIND_DEFINITION = 'definition';

    public const KIND_REFERENCE = 'reference';

    public const SCOPE_COMMON = 'common';

    public const SCOPE_AUTOMATIC = 'automatic';

    public const PART_CONTENT = 'content.xml';

    public const PART_STYLES = 'styles.xml';

    /**
     * @param array<string, array<string, mixed>> $propertyGroups
     */
    public function __construct(
        private string $kind,
        private string $scope,
        private string $family,
        private string $documentPart,
        private string $name,
        private ?string $parentStyleName = null,
        private array $propertyGroups = []
    ) {
        $this->validate();
    }

    public function kind(): string
    {
        return $this->kind;
    }

    public function scope(): string
    {
        return $this->scope;
    }

    public function family(): string
    {
        return $this->family;
    }

    public function documentPart(): string
    {
        return $this->documentPart;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function parentStyleName(): ?string
    {
        return $this->parentStyleName;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function propertyGroups(): array
    {
        return $this->propertyGroups;
    }

    private function validate(): void
    {
        if (!in_array($this->kind, [self::KIND_DEFINITION, self::KIND_REFERENCE], true)) {
            throw new InvalidArgumentException(sprintf(
                'Unsupported style requirement kind "%s".',
                $this->kind
            ));
        }

        if (!in_array($this->scope, [self::SCOPE_COMMON, self::SCOPE_AUTOMATIC], true)) {
            throw new InvalidArgumentException(sprintf(
                'Unsupported style requirement scope "%s".',
                $this->scope
            ));
        }

        if (trim($this->family) === '') {
            throw new InvalidArgumentException('Style requirement family must not be empty.');
        }

        if (!in_array($this->documentPart, [self::PART_CONTENT, self::PART_STYLES], true)) {
            throw new InvalidArgumentException(sprintf(
                'Unsupported style requirement document part "%s".',
                $this->documentPart
            ));
        }

        if (trim($this->name) === '') {
            throw new InvalidArgumentException('Style requirement name must not be empty.');
        }

        if ($this->kind === self::KIND_REFERENCE && $this->propertyGroups !== []) {
            throw new InvalidArgumentException(
                'Reference style requirements must not contain property groups.'
            );
        }

        foreach ($this->propertyGroups as $groupName => $properties) {
            if (!is_string($groupName) || trim($groupName) === '') {
                throw new InvalidArgumentException(
                    'Style requirement property group names must not be empty.'
                );
            }

            if (!is_array($properties)) {
                throw new InvalidArgumentException(sprintf(
                    'Style requirement property group "%s" must be an array.',
                    $groupName
                ));
            }
        }
    }
}
