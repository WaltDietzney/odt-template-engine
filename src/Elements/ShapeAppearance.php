<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Elements;

use InvalidArgumentException;

/**
 * Immutable graphic appearance for native editable shapes.
 */
final readonly class ShapeAppearance
{
    private function __construct(
        private string $fill,
        private ?string $color,
        private ?string $imagePath,
        private string $stroke
    ) {
    }

    public static function solid(string $color, string $stroke = 'none'): self
    {
        if (preg_match('/^#[0-9a-fA-F]{6}$/D', $color) !== 1) {
            throw new InvalidArgumentException('Shape fill color must be a six-digit hex color.');
        }

        self::validateStroke($stroke);

        return new self('solid', strtoupper($color), null, $stroke);
    }

    public static function bitmap(string $path, string $stroke = 'none'): self
    {
        if (!is_file($path) || !is_readable($path)) {
            throw new InvalidArgumentException('Shape bitmap must be an existing readable file.');
        }

        self::validateStroke($stroke);

        return new self('bitmap', null, $path, $stroke);
    }

    public static function none(string $stroke = 'solid'): self
    {
        self::validateStroke($stroke);

        return new self('none', null, null, $stroke);
    }

    public function graphicProperties(): array
    {
        $properties = ['draw:fill' => $this->fill, 'draw:stroke' => $this->stroke];
        if ($this->color !== null) {
            $properties['draw:fill-color'] = $this->color;
        }
        if ($this->imagePath !== null) {
            $properties['draw:fill-image-name'] = $this->fillImageName();
            $properties['draw:fill-image-width'] = '100%';
            $properties['draw:fill-image-height'] = '100%';
            $properties['style:repeat'] = 'stretch';
        }

        return $properties;
    }

    public function imagePath(): ?string
    {
        return $this->imagePath;
    }

    public function fillImageName(): ?string
    {
        return $this->imagePath === null ? null : 'shape_fill_' . substr(hash_file('sha256', $this->imagePath), 0, 16);
    }

    private static function validateStroke(string $stroke): void
    {
        if (!in_array($stroke, ['none', 'solid'], true)) {
            throw new InvalidArgumentException('Unsupported shape stroke.');
        }
    }
}
