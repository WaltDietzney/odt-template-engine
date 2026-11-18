<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Document;

use DOMElement;
use InvalidArgumentException;
use OdtTemplateEngine\OdtDocumentContext;

/** @internal Projects validated fill options into native ODF properties. */
final class FillProjection
{
    private const DRAW = 'urn:oasis:names:tc:opendocument:xmlns:drawing:1.0';
    private const STYLE = 'urn:oasis:names:tc:opendocument:xmlns:style:1.0';
    private const FO = 'urn:oasis:names:tc:opendocument:xmlns:xsl-fo-compatible:1.0';
    private const XLINK = 'http://www.w3.org/1999/xlink';

    /** Validate the shared semantic fill vocabulary without a document. */
    public static function validate(mixed $fill): array
    {
        $type = is_array($fill) ? ($fill['type'] ?? null) : null;
        $allowed = match ($type) {
            'none' => ['type'],
            'solid' => ['type', 'color'],
            'gradient' => ['type', 'name'],
            'bitmap' => ['type', 'name', 'repeat'],
            default => throw new InvalidArgumentException('Unsupported background type.'),
        };
        if (array_diff(array_keys($fill), $allowed) !== []) {
            throw new InvalidArgumentException('Unsupported background option.');
        }
        if ($type === 'solid' && (!is_string($fill['color'] ?? null)
            || !preg_match('/^#[0-9a-fA-F]{6}$/D', $fill['color']))) {
            throw new InvalidArgumentException('Background color must be #RRGGBB.');
        }
        if (in_array($type, ['gradient', 'bitmap'], true)
            && (!is_string($fill['name'] ?? null) || trim($fill['name']) === '')) {
            throw new InvalidArgumentException('Background resource name required.');
        }
        if ($type === 'bitmap' && array_key_exists('repeat', $fill)
            && !in_array($fill['repeat'], ['repeat', 'stretch'], true)) {
            throw new InvalidArgumentException('Unsupported bitmap repeat.');
        }
        return $fill;
    }

    /** Validate all named textbox resources before any style registration. */
    public static function validateElementTree(
        OdtDocumentContext $context,
        \OdtTemplateEngine\Elements\OdtElement $element
    ): void {
        if ($element instanceof \OdtTemplateEngine\Elements\DrawTextBox
            && ($fill = $element->semanticBackgroundOptions()) !== null) {
            $probe = $context->stylesDom()->createElementNS(self::STYLE, 'style:graphic-properties');
            self::apply($context, $probe, ['background' => $fill], false);
        }
        foreach ($element->ownedElements() as $child) {
            self::validateElementTree($context, $child);
        }
    }

    public static function apply(OdtDocumentContext $context, DOMElement $node, array $options, bool $backgroundImage = true): void
    {
        $group = array_key_exists('background', $options);
        $shorthand = array_key_exists('background-color', $options);
        if (!$group && !$shorthand) {
            return;
        }
        if ($group && $shorthand) {
            throw new InvalidArgumentException('Ambiguous background options.');
        }
        $fill = $group ? $options['background'] : (
            $options['background-color'] === 'transparent'
                ? ['type' => 'none']
                : ['type' => 'solid', 'color' => $options['background-color']]
        );
        $fill = self::validate($fill);
        $type = $fill['type'];
        $resource = null;
        $repeat = 'stretch';
        if (in_array($type, ['gradient', 'bitmap'], true)) {
            if (!is_string($fill['name'] ?? null) || trim($fill['name']) === '') {
                throw new InvalidArgumentException('Background resource name required.');
            }
            $resource = FillResourceAuthoring::find($context, $type === 'gradient' ? 'gradient' : 'fill-image', $fill['name']);
            if ($resource === null) {
                throw new InvalidArgumentException('Unknown background resource: ' . $fill['name']);
            }
        }
        if ($type === 'bitmap') {
            $repeat = array_key_exists('repeat', $fill) ? $fill['repeat'] : 'stretch';
            if (!in_array($repeat, ['repeat', 'stretch'], true)) {
                throw new InvalidArgumentException('Unsupported bitmap repeat.');
            }
            if ($resource->getAttributeNS(self::XLINK, 'href') === '') {
                throw new InvalidArgumentException('Bitmap resource has no href.');
            }
        }
        foreach (['fill', 'fill-color', 'fill-gradient-name', 'fill-image-name', 'fill-hatch-name',
            'fill-hatch-solid', 'fill-image-width', 'fill-image-height', 'fill-image-ref-point',
            'fill-image-ref-point-x', 'fill-image-ref-point-y', 'tile-repeat-offset', 'opacity',
            'opacity-name', 'gradient-step-count'] as $attribute) {
            $node->removeAttributeNS(self::DRAW, $attribute);
        }
        $node->removeAttributeNS(self::STYLE, 'repeat');
        $node->removeAttributeNS(self::FO, 'background-color');
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child instanceof DOMElement && $child->namespaceURI === self::STYLE
                && $child->localName === 'background-image') {
                $node->removeChild($child);
            }
        }
        $node->setAttributeNS(self::DRAW, 'draw:fill', $type);
        if ($type === 'none' || $type === 'solid') {
            $color = $type === 'none' ? 'transparent' : strtolower($fill['color']);
            if ($backgroundImage) {
                $node->setAttributeNS(self::FO, 'fo:background-color', $color);
            }
            if ($type === 'solid') {
                $node->setAttributeNS(self::DRAW, 'draw:fill-color', $color);
            }
        } elseif ($type === 'gradient') {
            $node->setAttributeNS(self::DRAW, 'draw:fill-gradient-name', $fill['name']);
        } else {
            $node->setAttributeNS(self::DRAW, 'draw:fill-image-name', $fill['name']);
            $node->setAttributeNS(self::STYLE, 'style:repeat', $repeat);
            if ($backgroundImage) {
                $image = $node->ownerDocument->createElementNS(self::STYLE, 'style:background-image');
                $image->setAttributeNS(self::XLINK, 'xlink:href', $resource->getAttributeNS(self::XLINK, 'href'));
                $image->setAttributeNS(self::XLINK, 'xlink:type', 'simple');
                $image->setAttributeNS(self::XLINK, 'xlink:actuate', 'onLoad');
                $image->setAttributeNS(self::STYLE, 'style:repeat', $repeat);
                $node->insertBefore($image, $node->firstChild);
            }
        }
    }
}
