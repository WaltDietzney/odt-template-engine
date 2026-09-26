<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Document;

use DOMElement;

/** Applies the low-level image-reference and optional dimension updates to a frame. */
final class FrameImageReplacementService
{
    public function updateFrame(
        DOMElement $frame,
        string $href,
        ?string $width,
        ?string $height
    ): void {
        $images = [];
        foreach ($frame->childNodes as $child) {
            if ($child instanceof DOMElement && $child->nodeName === 'draw:image') {
                $images[] = $child;
            }
        }
        if ($width !== null) {
            $frame->setAttribute('svg:width', $width);
        }
        if ($height !== null) {
            $frame->setAttribute('svg:height', $height);
        }
        foreach ($images as $image) {
            $image->setAttribute('xlink:href', $href);
        }
    }
}
