<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Elements;

use InvalidArgumentException;

/**
 * Validated native enhanced geometry independent of physical drawing size.
 */
final readonly class ShapeGeometry
{
    private const PRESETS = [
        'rectangle' => 'M 0 0 L 21600 0 21600 21600 0 21600 0 0 Z N',
        'ellipse' => 'U 10800 10800 10800 10800 0 360 Z N',
        'diamond' => 'M 10800 0 L 21600 10800 10800 21600 0 10800 10800 0 Z N',
        'arrow-contour' => 'M 3600 21600 X 0 10800 3600 0 L 21600 0 X 18000 10800 21600 21600 Z N',
    ];

    private function __construct(private string $type, private string $path)
    {
    }

    public static function preset(string $type): self
    {
        if (!isset(self::PRESETS[$type])) {
            throw new InvalidArgumentException(sprintf('Unsupported shape geometry "%s".', $type));
        }

        return new self($type, self::PRESETS[$type]);
    }

    public function type(): string
    {
        return $this->type;
    }

    public function path(): string
    {
        return $this->path;
    }

    public function viewBox(): string
    {
        return '0 0 21600 21600';
    }
}
