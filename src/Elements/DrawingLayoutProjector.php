<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Elements;

/**
 * Stateless projection from semantic drawing layout to native ODF carriers.
 */
final class DrawingLayoutProjector
{
    /**
     * @return array<string, string>
     */
    public function objectAttributes(DrawingLayout $layout): array
    {
        $attributes = [];

        if ($layout->anchor() !== null) {
            $attributes['text:anchor-type'] = $layout->anchor();
        }
        if ($layout->width() !== null) {
            $attributes['svg:width'] = $layout->width();
        }
        if ($layout->height() !== null) {
            $attributes['svg:height'] = $layout->height();
        }
        if ($layout->horizontalMode() === 'offset' && $layout->horizontalOffset() !== null) {
            $attributes['svg:x'] = $layout->horizontalOffset();
        }
        if ($layout->verticalMode() === 'offset' && $layout->verticalOffset() !== null) {
            $attributes['svg:y'] = $layout->verticalOffset();
        }

        return $attributes;
    }

    /**
     * @return array<string, string>
     */
    public function graphicLayoutProperties(DrawingLayout $layout): array
    {
        $properties = [];

        if ($layout->horizontalMode() === 'alignment') {
            $properties['style:horizontal-pos'] = (string) $layout->horizontalAlignment();
            $properties['style:horizontal-rel'] = (string) $layout->horizontalRelation();
        } elseif ($layout->horizontalMode() === 'offset') {
            $properties['style:horizontal-pos'] = 'from-left';
            $properties['style:horizontal-rel'] = (string) $layout->horizontalRelation();
        }

        if ($layout->verticalMode() === 'alignment') {
            $properties['style:vertical-pos'] = (string) $layout->verticalAlignment();
            $properties['style:vertical-rel'] = (string) $layout->verticalRelation();
        } elseif ($layout->verticalMode() === 'offset') {
            $properties['style:vertical-pos'] = 'from-top';
            $properties['style:vertical-rel'] = (string) $layout->verticalRelation();
        }

        if ($layout->wrap() !== null) {
            $properties['style:wrap'] = $layout->wrap();
        }

        return $properties;
    }

    public function requiresInlineTextFlow(DrawingLayout $layout): bool
    {
        return $layout->anchor() === 'as-char';
    }
}
