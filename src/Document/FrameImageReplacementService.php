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
            $frame->setAttributeNS('urn:oasis:names:tc:opendocument:xmlns:svg-compatible:1.0', 'svg:width', $width);
        }
        if ($height !== null) {
            $frame->setAttributeNS('urn:oasis:names:tc:opendocument:xmlns:svg-compatible:1.0', 'svg:height', $height);
        }
        $mime = match (strtolower(pathinfo($href, PATHINFO_EXTENSION))) {
            'svg' => 'image/svg+xml', 'png' => 'image/png', 'jpg', 'jpeg' => 'image/jpeg',
            'gif' => 'image/gif', 'bmp' => 'image/bmp', 'webp' => 'image/webp', default => null,
        };
        foreach ($images as $image) {
            $image->setAttributeNS('http://www.w3.org/1999/xlink', 'xlink:href', $href);
            // Writer's SVG/PNG alternatives must not retain contradictory MIME hints.
            if ($mime !== null && $image->hasAttribute('draw:mime-type')) {
                $image->setAttributeNS('urn:oasis:names:tc:opendocument:xmlns:drawing:1.0', 'draw:mime-type', $mime);
            }
        }
    }
}
