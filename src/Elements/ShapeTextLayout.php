<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Elements;

use InvalidArgumentException;

/**
 * Text placement inside a native drawing shape, independent of the shape position.
 */
final readonly class ShapeTextLayout
{
    /** @param array<string, string> $padding */
    public function __construct(
        private string $verticalAlignment = 'top',
        private array $padding = [],
        private ?string $horizontalAlignment = null
    ) {
        if (!in_array($verticalAlignment, ['top', 'middle', 'bottom'], true)) {
            throw new InvalidArgumentException('Unsupported shape text vertical alignment.');
        }
        if ($horizontalAlignment !== null && !in_array($horizontalAlignment, ['left', 'center', 'right'], true)) {
            throw new InvalidArgumentException('Unsupported shape text horizontal alignment.');
        }
        foreach ($padding as $side => $length) {
            if (!in_array($side, ['top', 'right', 'bottom', 'left'], true)
                || preg_match('/^\d+(?:\.\d+)?(?:cm|mm|pt|in)$/D', $length) !== 1
            ) {
                throw new InvalidArgumentException('Invalid shape text padding.');
            }
        }
    }

    /** @return array<string, string> */
    public function graphicProperties(): array
    {
        $properties = ['draw:textarea-vertical-align' => $this->verticalAlignment];
        if ($this->horizontalAlignment !== null) {
            $properties['draw:textarea-horizontal-align'] = $this->horizontalAlignment;
        }
        foreach ($this->padding as $side => $length) {
            $properties['fo:padding-' . $side] = $length;
        }

        return $properties;
    }
}
