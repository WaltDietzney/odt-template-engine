<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Elements;

use DOMDocument;
use DOMNode;
use InvalidArgumentException;
use OdtTemplateEngine\Document\StyleRequirement;
use OdtTemplateEngine\Utils\StyleMapper;

/**
 * Native editable ODF rounded rectangle, positioned relative to a drawing group.
 */
final class RoundedRectElement extends OdtElement
{
    public function __construct(
        private readonly string $name,
        private readonly string $x,
        private readonly string $y,
        private readonly string $width,
        private readonly string $height,
        private readonly string $radius,
        private readonly string $color
    ) {
        foreach ([$x, $y, $width, $height, $radius] as $length) {
            if (preg_match('/^-?\d+(?:\.\d+)?(?:cm|mm|pt|in)$/D', $length) !== 1) {
                throw new InvalidArgumentException('Invalid rounded rectangle length.');
            }
        }
        if (preg_match('/^#[0-9a-fA-F]{6}$/D', $color) !== 1) {
            throw new InvalidArgumentException('Invalid rounded rectangle color.');
        }
    }

    public function toDomNode(DOMDocument $dom): DOMNode
    {
        $node = $dom->createElement('draw:rect');
        foreach ([
            'draw:name' => $this->name,
            'draw:style-name' => $this->styleName(),
            'svg:x' => $this->x,
            'svg:y' => $this->y,
            'svg:width' => $this->width,
            'svg:height' => $this->height,
            'draw:corner-radius' => $this->radius,
        ] as $key => $value) {
            $node->setAttribute($key, $value);
        }
        $node->appendChild($dom->createElement('text:p'));

        return $node;
    }

    public function getOwnStyleRequirements(): iterable
    {
        return [new StyleRequirement(
            StyleRequirement::KIND_DEFINITION,
            StyleRequirement::SCOPE_AUTOMATIC,
            'graphic',
            StyleRequirement::PART_CONTENT,
            $this->styleName(),
            null,
            ['style:graphic-properties' => $this->graphicProperties()]
        )];
    }

    private function graphicProperties(): array
    {
        return [
            'draw:stroke' => 'none',
            'draw:fill' => 'solid',
            'draw:fill-color' => strtoupper($this->color),
            'style:run-through' => 'foreground',
        ];
    }

    private function styleName(): string
    {
        return StyleMapper::generateStyleName($this->graphicProperties());
    }
}
