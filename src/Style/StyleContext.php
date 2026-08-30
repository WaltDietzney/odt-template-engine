<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Style;

use LogicException;

/**
 * Holds pending style requirements for one logical ODT document.
 *
 * Existing definitions in styles.xml remain authoritative document data. This
 * context only owns style requirements registered while the document is being
 * edited. Additional style families can be added as their migration semantics
 * are characterized.
 */
final class StyleContext
{
    /** @var array<string, array<string, mixed>> */
    private array $paragraphStyles = [];

    /**
     * Register one pending paragraph style definition.
     *
     * Re-registering an equivalent definition is idempotent. Reusing the same
     * name for a different definition is an explicit conflict.
     *
     * @param array<string, mixed> $definition
     */
    public function registerParagraphStyle(string $name, array $definition): void
    {
        if (!isset($this->paragraphStyles[$name])) {
            $this->paragraphStyles[$name] = $definition;

            return;
        }

        if ($this->paragraphStyles[$name] === $definition) {
            return;
        }

        throw new LogicException(sprintf(
            'Paragraph style "%s" is already registered with a different definition.',
            $name
        ));
    }

    /** @return array<string, array<string, mixed>> */
    public function paragraphStyles(): array
    {
        return $this->paragraphStyles;
    }

    /**
     * Clear pending requirements after the logical document has been reset.
     */
    public function reset(): void
    {
        $this->paragraphStyles = [];
    }
}
