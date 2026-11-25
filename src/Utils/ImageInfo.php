<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Utils;

use DOMDocument;
use DOMElement;
use InvalidArgumentException;

/** @internal Shared local image metadata and bounded static SVG input policy. */
final readonly class ImageInfo
{
    private const SVG = 'http://www.w3.org/2000/svg';
    private const ELEMENTS = ['svg', 'g', 'path', 'rect', 'circle', 'ellipse', 'line', 'polyline', 'polygon', 'title', 'desc'];
    private const ATTRIBUTES = [
        'id', 'class', 'version', 'width', 'height', 'viewBox', 'preserveAspectRatio',
        'x', 'y', 'x1', 'y1', 'x2', 'y2', 'cx', 'cy', 'r', 'rx', 'ry', 'd', 'points',
        'fill', 'fill-rule', 'fill-opacity', 'stroke', 'stroke-width', 'stroke-linecap',
        'stroke-linejoin', 'stroke-miterlimit', 'stroke-dasharray', 'stroke-dashoffset',
        'stroke-opacity', 'opacity', 'transform', 'color', 'vector-effect', 'role', 'focusable',
    ];

    private function __construct(public ?float $width, public ?float $height, public string $mime)
    {
    }

    public static function fromPath(string $path): self
    {
        if (str_contains($path, '://') || !is_file($path) || !is_readable($path)) {
            throw new InvalidArgumentException('Image must be a readable local file.');
        }
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if ($extension === 'svg') {
            return self::svg($path);
        }
        $expected = match ($extension) {
            'png' => 'image/png', 'jpg', 'jpeg' => 'image/jpeg', 'gif' => 'image/gif',
            'bmp' => 'image/bmp', 'webp' => 'image/webp', default => null,
        };
        $size = @getimagesize($path);
        if ($expected === null || !is_array($size) || ($size['mime'] ?? null) !== $expected
            || $size[0] <= 0 || $size[1] <= 0) {
            throw new InvalidArgumentException('Image extension and supported image content must agree.');
        }
        return new self((float) $size[0], (float) $size[1], $expected);
    }

    /** Derive one dimension in the supplied dimension's unit; never change an explicit pair. */
    public function completeDimensions(?string $width, ?string $height, int $precision = 8): array
    {
        if (($width === null) === ($height === null)) {
            return [$width, $height];
        }
        if ($this->width === null || $this->height === null) {
            throw new InvalidArgumentException('One image dimension requires a determinable intrinsic ratio.');
        }
        $raw = $width ?? $height;
        if (preg_match('/^(\d+(?:\.\d+)?|\.\d+)(cm|mm|in|pt|pc|px)$/D', $raw, $match) !== 1
            || (float) $match[1] <= 0) {
            throw new InvalidArgumentException('Image dimension must be a positive absolute length.');
        }
        $derived = (float) $match[1] * ($width !== null ? $this->height / $this->width : $this->width / $this->height);
        $value = rtrim(rtrim(number_format($derived, $precision, '.', ''), '0'), '.');
        if (!is_finite($derived) || (float) $value <= 0) {
            throw new InvalidArgumentException('Derived image dimension is not representable.');
        }
        return $width !== null ? [$width, $value . $match[2]] : [$value . $match[2], $height];
    }

    private static function svg(string $path): self
    {
        $size = filesize($path);
        if ($size === false || $size > 1048576) {
            throw new InvalidArgumentException('Static SVG is limited to 1 MiB.');
        }
        $xml = file_get_contents($path);
        if ($xml === false || str_contains($xml, "\0") || preg_match('//u', $xml) !== 1
            || preg_match('/<!\s*(?:DOCTYPE|ENTITY)/i', $xml)) {
            throw new InvalidArgumentException('SVG must be UTF-8 XML without DTDs or entity declarations.');
        }
        $dom = new DOMDocument();
        $dom->resolveExternals = false;
        $dom->substituteEntities = false;
        $previous = libxml_use_internal_errors(true);
        try {
            $loaded = $dom->loadXML($xml, LIBXML_NONET | LIBXML_COMPACT);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
        if (!$loaded || $dom->doctype !== null || $dom->documentElement?->localName !== 'svg'
            || $dom->documentElement->namespaceURI !== self::SVG || $dom->documentElement->prefix !== '') {
            throw new InvalidArgumentException('SVG requires an unprefixed SVG root in the default SVG namespace.');
        }
        foreach ($dom->childNodes as $node) {
            if ($node->nodeType === XML_PI_NODE) {
                throw new InvalidArgumentException('SVG processing instructions are not supported.');
            }
        }
        $count = 0;
        self::validateSvgElement($dom->documentElement, 0, $count);
        $root = $dom->documentElement;
        $width = self::svgLength($root->getAttribute('width'));
        $height = self::svgLength($root->getAttribute('height'));
        $viewBox = trim($root->getAttribute('viewBox'));
        $bounds = null;
        if ($viewBox !== '') {
            $numbers = preg_split('/[\s,]+/', $viewBox);
            if (count($numbers) !== 4 || count(array_filter($numbers, 'is_numeric')) !== 4
                || !is_finite((float) $numbers[0]) || !is_finite((float) $numbers[1])
                || !is_finite((float) $numbers[2]) || !is_finite((float) $numbers[3])
                || (float) $numbers[2] <= 0 || (float) $numbers[3] <= 0) {
                throw new InvalidArgumentException('SVG viewBox requires four finite numbers and positive dimensions.');
            }
            $bounds = [(float) $numbers[2], (float) $numbers[3]];
        }
        if ($width !== null && $height !== null) {
            return new self($width, $height, 'image/svg+xml');
        }
        if ($bounds !== null) {
            return new self($bounds[0], $bounds[1], 'image/svg+xml');
        }
        return new self(null, null, 'image/svg+xml');
    }

    private static function validateSvgElement(DOMElement $element, int $depth, int &$count): void
    {
        if (++$count > 4096 || $depth > 32 || $element->namespaceURI !== self::SVG || $element->prefix !== ''
            || !in_array($element->localName, self::ELEMENTS, true)
            || ($element->localName === 'svg' && $depth !== 0)) {
            throw new InvalidArgumentException('Unsupported or excessively complex static SVG element.');
        }
        foreach ($element->attributes as $attribute) {
            $name = $attribute->nodeName;
            if ($name === 'xmlns' && $depth === 0 && $attribute->value === self::SVG) {
                continue;
            }
            if (($attribute->namespaceURI !== null && $attribute->namespaceURI !== '')
                || (!in_array($name, self::ATTRIBUTES, true) && !str_starts_with($name, 'aria-'))
                || strlen($attribute->value) > 131072
                || preg_match('/url\s*\(|[\\\\]|[\x00-\x08\x0B\x0C\x0E-\x1F]/i', $attribute->value)) {
                throw new InvalidArgumentException('Unsupported SVG attribute, reference or excessive attribute value.');
            }
        }
        foreach ($element->childNodes as $child) {
            if ($child instanceof DOMElement) {
                self::validateSvgElement($child, $depth + 1, $count);
            } elseif (!in_array($child->nodeType, [XML_TEXT_NODE, XML_COMMENT_NODE, XML_CDATA_SECTION_NODE], true)) {
                throw new InvalidArgumentException('Unsupported SVG node.');
            } elseif ($child->nodeType !== XML_COMMENT_NODE && trim($child->textContent) !== ''
                && !in_array($element->localName, ['title', 'desc'], true)) {
                throw new InvalidArgumentException('Visible SVG text is outside the static icon subset.');
            }
        }
    }

    private static function svgLength(string $value): ?float
    {
        if ($value === '' || preg_match('/^(?:\d+(?:\.\d+)?|\.\d+)%$/D', $value)) {
            return null;
        }
        if (preg_match('/^(\d+(?:\.\d+)?|\.\d+)(px|cm|mm|in|pt|pc)?$/D', $value, $match) !== 1) {
            throw new InvalidArgumentException('SVG viewport dimensions require positive absolute lengths or percentages.');
        }
        $factor = match ($match[2] ?? 'px') {
            'cm' => 96 / 2.54, 'mm' => 96 / 25.4, 'in' => 96.0,
            'pt' => 96 / 72, 'pc' => 16.0, default => 1.0,
        };
        $number = (float) $match[1] * $factor;
        if (!is_finite($number) || $number <= 0) {
            throw new InvalidArgumentException('SVG viewport dimensions must be finite and positive.');
        }
        return $number;
    }
}
