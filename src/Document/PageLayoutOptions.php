<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Document;

use DOMDocument;
use DOMElement;
use InvalidArgumentException;

/** @internal Validates and materializes the bounded H02 C1 page vocabulary. */
final class PageLayoutOptions
{
    private const STYLE_NS = 'urn:oasis:names:tc:opendocument:xmlns:style:1.0';
    private const FO_NS = 'urn:oasis:names:tc:opendocument:xmlns:xsl-fo-compatible:1.0';
    private const SVG_NS = 'urn:oasis:names:tc:opendocument:xmlns:svg-compatible:1.0';
    private const DRAW_NS = 'urn:oasis:names:tc:opendocument:xmlns:drawing:1.0';

    /** @var list<string> */
    private const C1_KEYS = [
        'background', 'background-color',
        'border', 'border-top', 'border-right', 'border-bottom', 'border-left',
        'padding', 'padding-top', 'padding-right', 'padding-bottom', 'padding-left',
        'shadow', 'border-line-width', 'border-line-width-top',
        'border-line-width-right', 'border-line-width-bottom', 'border-line-width-left',
        'columns', 'header', 'footer',
    ];

    public static function isExtended(array $options): bool
    {
        foreach (self::C1_KEYS as $key) {
            if (array_key_exists($key, $options)) {
                return true;
            }
        }
        return false;
    }

    public static function applyPage(DOMElement $properties, array $options): void
    {
        self::applyVisual($properties, $options, 'page');
        if (array_key_exists('columns', $options)) {
            self::applyColumns($properties, $options['columns']);
        }
    }

    public static function applyRegion(DOMElement $regionStyle, array $options, string $region): void
    {
        if (!is_array($options)) {
            throw new InvalidArgumentException(sprintf('%s options must be an array.', $region));
        }
        self::assertKeys($options, [
            'height', 'min-height', 'spacing', 'margin-left', 'margin-right', 'dynamic-spacing',
            'border', 'border-top', 'border-right', 'border-bottom', 'border-left',
            'padding', 'padding-top', 'padding-right', 'padding-bottom', 'padding-left',
            'shadow', 'border-line-width', 'border-line-width-top',
            'border-line-width-right', 'border-line-width-bottom', 'border-line-width-left',
        ], $region);
        if (array_key_exists('height', $options) && array_key_exists('min-height', $options)) {
            throw new InvalidArgumentException(sprintf('%s cannot specify both height and min-height.', $region));
        }
        if (array_key_exists('height', $options)) {
            self::setLength($regionStyle, self::SVG_NS, 'svg:height', $options['height'], $region . ' height', true);
            $regionStyle->removeAttributeNS(self::FO_NS, 'min-height');
        }
        if (array_key_exists('min-height', $options)) {
            self::setLength($regionStyle, self::FO_NS, 'fo:min-height', $options['min-height'], $region . ' min-height', false);
            $regionStyle->removeAttributeNS(self::SVG_NS, 'height');
        }
        foreach (['margin-left', 'margin-right'] as $key) {
            if (array_key_exists($key, $options)) {
                self::setLength($regionStyle, self::FO_NS, 'fo:' . $key, $options[$key], $region . ' ' . $key);
            }
        }
        if (array_key_exists('spacing', $options)) {
            $attribute = $region === 'header' ? 'fo:margin-bottom' : 'fo:margin-top';
            self::setLength($regionStyle, self::FO_NS, $attribute, $options['spacing'], $region . ' spacing');
        }
        if (array_key_exists('dynamic-spacing', $options)) {
            if (!is_bool($options['dynamic-spacing'])) {
                throw new InvalidArgumentException(sprintf('%s dynamic-spacing must be boolean.', $region));
            }
            $regionStyle->setAttributeNS(self::STYLE_NS, 'style:dynamic-spacing', $options['dynamic-spacing'] ? 'true' : 'false');
        }
        self::applyVisual($regionStyle, $options, $region);
    }

    private static function applyVisual(DOMElement $properties, array $options, string $label): void
    {
        self::assertVisualKeys($options, $label);
        self::applyBorder($properties, $options, $label);
        self::applyPadding($properties, $options, $label);
        self::applyLineWidths($properties, $options, $label);

        if (array_key_exists('shadow', $options)) {
            $shadow = $options['shadow'];
            if ($shadow === 'none') {
                // Keep an explicit local override; removing the attribute can
                // expose an inherited shadow from the parent style.
                $properties->setAttributeNS(self::STYLE_NS, 'style:shadow', 'none');
            } else {
                if (is_string($shadow)) {
                    if (!preg_match('/^(#[0-9a-fA-F]{6}|transparent) (-?(?:\d+(?:\.\d+)?|\.\d+)(?:cm|mm|in|pt|pc)) (-?(?:\d+(?:\.\d+)?|\.\d+)(?:cm|mm|in|pt|pc))$/', trim($shadow))) {
                        throw new InvalidArgumentException(sprintf('%s shadow must contain color and signed x/y lengths.', $label));
                    }
                    $properties->setAttributeNS(self::STYLE_NS, 'style:shadow', trim($shadow));
                    return;
                }
                if (!is_array($shadow) || !isset($shadow['color'], $shadow['offset-x'], $shadow['offset-y'])) {
                    throw new InvalidArgumentException(sprintf('%s shadow requires color, offset-x and offset-y.', $label));
                }
                self::assertExactKeys($shadow, ['color', 'offset-x', 'offset-y'], $label . ' shadow');
                $color = self::color($shadow['color'], 'shadow color');
                self::validateSignedLength($shadow['offset-x'], 'shadow offset-x');
                self::validateSignedLength($shadow['offset-y'], 'shadow offset-y');
                $properties->setAttributeNS(
                    self::STYLE_NS,
                    'style:shadow',
                    $color . ' ' . trim((string) $shadow['offset-x']) . ' ' . trim((string) $shadow['offset-y'])
                );
            }
        }
    }

    private static function assertVisualKeys(array $options, string $label): void
    {
        $allowed = [
            'border', 'border-top', 'border-right', 'border-bottom', 'border-left',
            'padding', 'padding-top', 'padding-right', 'padding-bottom', 'padding-left',
            'shadow', 'border-line-width', 'border-line-width-top',
            'border-line-width-right', 'border-line-width-bottom', 'border-line-width-left',
        ];
        foreach ($options as $key => $_value) {
            if (in_array($key, ['background', 'background-color'], true)) {
                throw new InvalidArgumentException(sprintf('%s fill options are deferred to H02 C2.', $label));
            }
            if (in_array($key, $allowed, true)) {
                continue;
            }
            // Page/region geometry is handled by its owner.
            if ($label === 'page' || in_array($key, [
                'height', 'min-height', 'spacing', 'margin-left', 'margin-right', 'dynamic-spacing',
            ], true)) {
                continue;
            }
            throw new InvalidArgumentException(sprintf('Unsupported %s option "%s".', $label, (string) $key));
        }
    }

    private static function assertExactKeys(array $options, array $allowed, string $label): void
    {
        foreach ($options as $key => $_value) {
            if (!in_array($key, $allowed, true)) {
                throw new InvalidArgumentException(sprintf('Unsupported %s option "%s".', $label, (string) $key));
            }
        }
    }

    private static function applyBorder(DOMElement $node, array $options, string $label): void
    {
        $sides = ['top', 'right', 'bottom', 'left'];
        $specific = array_values(array_filter($sides, static fn (string $side): bool => array_key_exists('border-' . $side, $options)));
        if (array_key_exists('border', $options) && $specific !== []) {
            throw new InvalidArgumentException(sprintf('%s border shorthand and side-specific values are ambiguous.', $label));
        }
        if (array_key_exists('border', $options)) {
            $value = self::border($options['border']);
            $node->setAttributeNS(self::FO_NS, 'fo:border', $value);
            foreach ($sides as $side) {
                $node->removeAttributeNS(self::FO_NS, 'border-' . $side);
            }
            self::cleanupLineWidths($node);
            return;
        }
        if ($specific === []) {
            return;
        }
        $effective = self::effectiveSides($node, 'border', $sides);
        foreach ($specific as $side) {
            $effective[$side] = self::border($options['border-' . $side]);
        }
        $node->removeAttributeNS(self::FO_NS, 'border');
        foreach ($sides as $side) {
            if ($effective[$side] !== null) {
                $node->setAttributeNS(self::FO_NS, 'fo:border-' . $side, $effective[$side]);
            }
        }
        self::cleanupLineWidths($node);
    }

    private static function applyPadding(DOMElement $node, array $options, string $label): void
    {
        $sides = ['top', 'right', 'bottom', 'left'];
        $specific = array_values(array_filter($sides, static fn (string $side): bool => array_key_exists('padding-' . $side, $options)));
        if (array_key_exists('padding', $options) && $specific !== []) {
            throw new InvalidArgumentException(sprintf('%s padding shorthand and side-specific values are ambiguous.', $label));
        }
        if (array_key_exists('padding', $options)) {
            self::validateLength($options['padding'], 'padding');
            $node->setAttributeNS(self::FO_NS, 'fo:padding', trim($options['padding']));
            foreach ($sides as $side) {
                $node->removeAttributeNS(self::FO_NS, 'padding-' . $side);
            }
            return;
        }
        if ($specific === []) {
            return;
        }
        $effective = self::effectiveSides($node, 'padding', $sides);
        foreach ($specific as $side) {
            self::validateLength($options['padding-' . $side], 'padding-' . $side);
            $effective[$side] = trim($options['padding-' . $side]);
        }
        $node->removeAttributeNS(self::FO_NS, 'padding');
        foreach ($sides as $side) {
            if ($effective[$side] !== null) {
                $node->setAttributeNS(self::FO_NS, 'fo:padding-' . $side, $effective[$side]);
            }
        }
    }

    private static function applyLineWidths(DOMElement $node, array $options, string $label): void
    {
        $sides = ['top', 'right', 'bottom', 'left'];
        $specific = array_values(array_filter($sides, static fn (string $side): bool => array_key_exists('border-line-width-' . $side, $options)));
        if (array_key_exists('border-line-width', $options) && $specific !== []) {
            throw new InvalidArgumentException(sprintf('%s border-line-width shorthand and side-specific values are ambiguous.', $label));
        }
        if (array_key_exists('border-line-width', $options)) {
            $parts = self::lineWidths($options['border-line-width']);
            foreach ($sides as $side) {
                self::assertDoubleBorder($node, $side);
            }
            $node->setAttributeNS(self::STYLE_NS, 'style:border-line-width', implode(' ', $parts));
            foreach ($sides as $side) {
                $node->removeAttributeNS(self::STYLE_NS, 'border-line-width-' . $side);
            }
            return;
        }
        if ($specific === []) {
            return;
        }
        $effective = self::effectiveSides($node, 'border-line-width', $sides);
        foreach ($specific as $side) {
            self::assertDoubleBorder($node, $side);
            $effective[$side] = implode(' ', self::lineWidths($options['border-line-width-' . $side]));
        }
        if ($node->hasAttributeNS(self::STYLE_NS, 'border-line-width')) {
            $shorthand = $node->getAttributeNS(self::STYLE_NS, 'border-line-width');
            foreach ($sides as $side) {
                if ($effective[$side] === null) {
                    $effective[$side] = $shorthand;
                }
            }
        }
        $node->removeAttributeNS(self::STYLE_NS, 'border-line-width');
        foreach ($sides as $side) {
            if ($effective[$side] !== null) {
                $node->setAttributeNS(self::STYLE_NS, 'style:border-line-width-' . $side, $effective[$side]);
            }
        }
        self::cleanupLineWidths($node);
    }

    /** @return array<string, ?string> */
    private static function effectiveSides(DOMElement $node, string $name, array $sides): array
    {
        $result = [];
        $namespace = $name === 'border' || $name === 'padding' ? self::FO_NS : self::STYLE_NS;
        $shorthand = $node->getAttributeNS($namespace, $name);
        foreach ($sides as $side) {
            $specific = $node->getAttributeNS($namespace, $name . '-' . $side);
            $result[$side] = $specific !== '' ? $specific : ($shorthand !== '' ? $shorthand : null);
        }
        return $result;
    }

    private static function cleanupLineWidths(DOMElement $node): void
    {
        $borders = self::effectiveSides($node, 'border', ['top', 'right', 'bottom', 'left']);
        $width = $node->getAttributeNS(self::STYLE_NS, 'border-line-width');
        if ($width !== '' && !self::allDouble($borders)) {
            $node->removeAttributeNS(self::STYLE_NS, 'border-line-width');
            foreach ($borders as $side => $border) {
                if (self::isDouble($border)) {
                    $node->setAttributeNS(self::STYLE_NS, 'style:border-line-width-' . $side, $width);
                }
            }
        }
        foreach ($borders as $side => $border) {
            if (!self::isDouble($border)) {
                $node->removeAttributeNS(self::STYLE_NS, 'border-line-width-' . $side);
            }
        }
    }

    private static function allDouble(array $borders): bool
    {
        foreach ($borders as $border) {
            if (!self::isDouble($border)) {
                return false;
            }
        }
        return true;
    }

    private static function isDouble(?string $border): bool
    {
        return $border !== null && preg_match('/\bdouble\b/', $border) === 1;
    }

    private static function applyColumns(DOMElement $properties, mixed $value): void
    {
        if (!is_array($value)) {
            throw new InvalidArgumentException('Page columns must be an array.');
        }
        if (array_key_exists('balance', $value)) {
            throw new InvalidArgumentException('Page columns do not support "balance".');
        }
        $groups = SectionStyleOptions::propertyGroups(['columns' => $value]);
        $definition = $groups['style:section-properties']['style:columns'];
        $dom = $properties->ownerDocument;
        $old = null;
        foreach ($properties->childNodes as $child) {
            if ($child instanceof DOMElement && $child->namespaceURI === self::STYLE_NS && $child->localName === 'columns') {
                $old = $child;
                break;
            }
        }
        if ($old instanceof DOMElement) {
            $properties->removeChild($old);
        }
        $columns = $dom->createElementNS(self::STYLE_NS, 'style:columns');
        foreach ($definition as $attribute => $attributeValue) {
            if ($attribute === 'style:column' || $attribute === 'style:column-sep') {
                continue;
            }
            [$prefix, $local] = explode(':', $attribute, 2);
            $ns = $prefix === 'fo' ? self::FO_NS : self::STYLE_NS;
            $columns->setAttributeNS($ns, $attribute, (string) $attributeValue);
        }
        if (isset($definition['style:column-sep'])) {
            $columns->appendChild(self::child($dom, 'style:column-sep', $definition['style:column-sep']));
        }
        foreach ($definition['style:column'] ?? [] as $column) {
            $columns->appendChild(self::child($dom, 'style:column', $column));
        }
        foreach ($properties->childNodes as $child) {
            if ($child instanceof DOMElement
                && $child->namespaceURI === self::STYLE_NS
                && $child->localName === 'footnote-sep') {
                $properties->insertBefore($columns, $child);
                return;
            }
        }
        $lastBackground = null;
        foreach ($properties->childNodes as $child) {
            if ($child instanceof DOMElement
                && $child->namespaceURI === self::STYLE_NS
                && $child->localName === 'background-image') {
                $lastBackground = $child;
            }
        }
        if ($lastBackground instanceof DOMElement && $lastBackground->nextSibling !== null) {
            $properties->insertBefore($columns, $lastBackground->nextSibling);
            return;
        }
        $properties->appendChild($columns);
    }

    private static function child(DOMDocument $dom, string $qualifiedName, array $attributes): DOMElement
    {
        $element = $dom->createElementNS(self::STYLE_NS, $qualifiedName);
        foreach ($attributes as $attribute => $value) {
            [$prefix, $local] = explode(':', $attribute, 2);
            $ns = $prefix === 'fo' ? self::FO_NS : self::STYLE_NS;
            $element->setAttributeNS($ns, $attribute, (string) $value);
        }
        return $element;
    }

    private static function assertKeys(array $options, array $allowed, string $label, bool $ignoreUnknown = false): void
    {
        foreach ($options as $key => $_value) {
            if (!$ignoreUnknown && !in_array($key, $allowed, true)) {
                throw new InvalidArgumentException(sprintf('Unsupported %s option "%s".', $label, (string) $key));
            }
            if ($ignoreUnknown && in_array($key, ['background', 'background-color'], true)) {
                throw new InvalidArgumentException(sprintf('%s fill options are deferred to H02 C2.', $label));
            }
        }
    }

    private static function setLength(DOMElement $node, string $namespace, string $qualifiedName, mixed $value, string $label, bool $positive = false): void
    {
        $match = self::validateLength($value, $label);
        if ($positive && (float) $match[0] <= 0.0) {
            throw new InvalidArgumentException(sprintf('%s must be positive.', $label));
        }
        $node->setAttributeNS($namespace, $qualifiedName, trim($value));
    }

    /** @return array{0:string,1:string} */
    private static function validateLength(mixed $value, string $label): array
    {
        if (!is_string($value) || !preg_match('/^(\d+(?:\.\d+)?|\.\d+)(cm|mm|in|pt|pc)$/', trim($value), $match)) {
            throw new InvalidArgumentException(sprintf('%s must be a non-negative absolute ODF length.', $label));
        }
        return [$match[1], $match[2]];
    }

    private static function validateSignedLength(mixed $value, string $label): void
    {
        if (!is_string($value) || !preg_match('/^-?(\d+(?:\.\d+)?|\.\d+)(cm|mm|in|pt|pc)$/', trim($value))) {
            throw new InvalidArgumentException(sprintf('%s must be a signed absolute ODF length.', $label));
        }
    }

    private static function color(mixed $value, string $label): string
    {
        if (!is_string($value) || (!preg_match('/^#[0-9a-fA-F]{6}$/', $value) && $value !== 'transparent')) {
            throw new InvalidArgumentException(sprintf('%s must be #RRGGBB or transparent.', $label));
        }
        return $value;
    }

    private static function border(mixed $value): string
    {
        if (is_array($value)) {
            self::assertExactKeys($value, ['width', 'style', 'color'], 'border');
            if (!isset($value['width'], $value['style'], $value['color'])) {
                throw new InvalidArgumentException('Border requires width, style and color.');
            }
            self::validateLength($value['width'], 'border width');
            $style = $value['style'];
            $color = self::color($value['color'], 'border color');
            if (!is_string($style) || !in_array($style, ['solid', 'dashed', 'dotted', 'double'], true)) {
                throw new InvalidArgumentException('Border style must be solid, dashed, dotted, or double.');
            }
            return trim((string) $value['width']) . ' ' . $style . ' ' . $color;
        }
        if ($value === 'none') {
            return 'none';
        }
        if (!is_string($value) || !preg_match('/^(\d+(?:\.\d+)?|\.\d+)(cm|mm|in|pt|pc) (solid|dashed|dotted|double) (#[0-9a-fA-F]{6}|transparent)$/', trim($value))) {
            throw new InvalidArgumentException('Border must be none or "width style #RRGGBB".');
        }
        return trim($value);
    }

    private static function assertDoubleBorder(DOMElement $node, string $side): void
    {
        $attribute = 'border' . ($side === '' ? '' : '-' . $side);
        $value = $node->getAttributeNS(self::FO_NS, $attribute);
        if ($value === '' && $side !== '') {
            $value = $node->getAttributeNS(self::FO_NS, 'border');
        }
        if (!preg_match('/\bdouble\b/', $value)) {
            throw new InvalidArgumentException('border-line-width requires an effective double border.');
        }
    }

    /** @return list<string> */
    private static function lineWidths(mixed $value): array
    {
        if (!is_array($value) || count($value) !== 3 || array_values($value) !== $value) {
            throw new InvalidArgumentException('Double-line width must contain three lengths.');
        }
        foreach ($value as $part) {
            if (!is_string($part)
                || !preg_match('/^(\d+(?:\.\d+)?|\.\d+)(cm|mm|in|pt|pc)$/', trim($part), $match)
                || (float) $match[1] <= 0.0) {
                throw new InvalidArgumentException('Double-line width parts must be positive absolute ODF lengths.');
            }
        }
        return array_map('trim', $value);
    }

}
