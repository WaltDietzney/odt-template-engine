<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Document;

use DOMElement;
use InvalidArgumentException;
use LogicException;
use OdtTemplateEngine\OdtDocumentContext;
use OdtTemplateEngine\OdtPackage;

/** @internal Document-local named fill definitions. */
final class FillResourceAuthoring
{
    private const DRAW = 'urn:oasis:names:tc:opendocument:xmlns:drawing:1.0';
    private const OFFICE = 'urn:oasis:names:tc:opendocument:xmlns:office:1.0';
    private const XLINK = 'http://www.w3.org/1999/xlink';

    public static function defineGradient(OdtDocumentContext $context, string $name, array $options): void
    {
        self::validateName($name);
        $type = $options['type'] ?? null;
        if (!in_array($type, ['linear', 'axial', 'radial', 'elliptical', 'square', 'rectangular'], true)) {
            throw new InvalidArgumentException('Unsupported gradient type.');
        }
        $center = !in_array($type, ['linear', 'axial'], true);
        $allowed = ['type', 'start-color', 'end-color', 'angle', 'border', 'start-intensity', 'end-intensity'];
        if ($center) {
            $allowed = array_merge($allowed, ['center-x', 'center-y']);
        }
        if (array_diff(array_keys($options), $allowed) !== []) {
            throw new InvalidArgumentException('Unsupported gradient option.');
        }
        $attributes = ['name' => $name, 'style' => $type === 'elliptical' ? 'ellipsoid' : $type];
        foreach (['start-color', 'end-color'] as $key) {
            $value = $options[$key] ?? null;
            if (!is_string($value) || !preg_match('/^#[0-9a-fA-F]{6}$/D', $value)) {
                throw new InvalidArgumentException('Gradient colors must be #RRGGBB.');
            }
            $attributes[$key] = strtolower($value);
        }
        $angle = $options['angle'] ?? 0;
        if ((!is_int($angle) && !is_float($angle)) || !is_finite((float) $angle) || $angle < 0 || $angle >= 360) {
            throw new InvalidArgumentException('Gradient angle must be in [0, 360).');
        }
        $attributes['angle'] = (string) $angle . 'deg';
        foreach (['border' => 0, 'start-intensity' => 100, 'end-intensity' => 100] as $key => $default) {
            $attributes[$key] = self::percent($options[$key] ?? $default);
        }
        if ($center) {
            $attributes['cx'] = self::percent($options['center-x'] ?? 50);
            $attributes['cy'] = self::percent($options['center-y'] ?? 50);
        }
        $existing = self::find($context, 'gradient', $name);
        if ($existing !== null) {
            foreach ($attributes as $key => $value) {
                if ($existing->getAttributeNS(self::DRAW, $key) !== $value) {
                    throw new LogicException('Conflicting gradient definition: ' . $name);
                }
            }
            if ($existing->hasChildNodes()) {
                throw new LogicException('Cannot redefine authored gradient: ' . $name);
            }
            return;
        }
        $container = self::container($context);
        $node = $container->ownerDocument->createElementNS(self::DRAW, 'draw:gradient');
        foreach ($attributes as $key => $value) {
            $node->setAttributeNS(self::DRAW, 'draw:' . $key, $value);
        }
        $container->appendChild($node);
    }

    public static function defineImage(
        OdtDocumentContext $context,
        OdtPackage $package,
        string $name,
        string $path
    ): void {
        self::validateName($name);
        $info = \OdtTemplateEngine\Utils\ImageInfo::fromPath($path);
        if (!in_array($info->mime, ['image/png', 'image/jpeg', 'image/svg+xml'], true)) {
            throw new InvalidArgumentException('Fill image must be a readable local PNG, JPEG or supported static SVG.');
        }
        $href = 'Pictures/' . basename($path);
        $existing = self::find($context, 'fill-image', $name);
        if ($existing !== null && ($existing->getAttributeNS(self::XLINK, 'href') !== $href
            || !is_file($package->path($href)))) {
            throw new LogicException('Conflicting fill image definition: ' . $name);
        }
        $snapshot = $context->snapshotState();
        $created = [];
        try {
            $created = $package->copyImageResourcesAtomically([['path' => $path]]);
            $requirement = new FillImageRequirement(FillImageRequirement::PART_STYLES, $name, $href);
            $context->registerFillImageRequirement($requirement);
            (new FillImageRequirementMaterializer())->materialize($context, $requirement);
        } catch (\Throwable $exception) {
            $context->restoreState($snapshot);
            $package->removePreparedPackageFiles($created);
            throw $exception;
        }
    }

    /** @internal Copy-on-write replacement of one named object's existing bitmap fill. */
    public static function replaceBitmapByObjectName(
        OdtDocumentContext $context,
        OdtPackage $package,
        string $objectName,
        string $path
    ): void {
        $info = \OdtTemplateEngine\Utils\ImageInfo::fromPath($path);
        if (!in_array($info->mime, ['image/png', 'image/jpeg', 'image/svg+xml'], true)) {
            throw new InvalidArgumentException('Bitmap replacement supports PNG, JPEG and static SVG.');
        }
        $matches = [];
        foreach ([$context->contentDom(), $context->stylesDom()] as $dom) {
            foreach ($dom->getElementsByTagNameNS(self::DRAW, '*') as $node) {
                if ($node->getAttributeNS(self::DRAW, 'name') === $objectName
                    && in_array($node->localName, ['frame', 'custom-shape', 'rect', 'ellipse'], true)) {
                    $matches[] = $node;
                }
            }
        }
        if ($objectName === '' || count($matches) !== 1) {
            throw new InvalidArgumentException('Bitmap replacement requires one uniquely named shape or frame across both parts.');
        }
        $object = $matches[0];
        $dom = $object->ownerDocument;
        $style = self::graphicStyle($context, $dom, $object->getAttributeNS(self::DRAW, 'style-name'));
        $effective = [];
        $background = null;
        $visited = [];
        $current = $style;
        while ($current !== null) {
            $id = ($current->ownerDocument === $context->contentDom() ? 'content:' : 'styles:') . $current->getNodePath();
            if (isset($visited[$id]) || count($visited) >= 32) {
                throw new InvalidArgumentException('Cyclic or excessive graphic style inheritance.');
            }
            $visited[$id] = true;
            foreach ($current->childNodes as $group) {
                if (!$group instanceof DOMElement || $group->namespaceURI !== 'urn:oasis:names:tc:opendocument:xmlns:style:1.0'
                    || $group->localName !== 'graphic-properties') {
                    continue;
                }
                foreach (['fill', 'fill-image-name'] as $key) {
                    if (!array_key_exists($key, $effective) && $group->hasAttributeNS(self::DRAW, $key)) {
                        $effective[$key] = $group->getAttributeNS(self::DRAW, $key);
                    }
                }
                if ($background === null) {
                    $background = $group->getElementsByTagNameNS('urn:oasis:names:tc:opendocument:xmlns:style:1.0', 'background-image')->item(0);
                }
            }
            $parent = $current->getAttributeNS('urn:oasis:names:tc:opendocument:xmlns:style:1.0', 'parent-style-name');
            $current = $parent === '' ? null : self::graphicStyle(
                $context, $context->stylesDom(), $parent, true,
                isset($effective['fill'], $effective['fill-image-name'])
            );
        }
        if (($effective['fill'] ?? null) !== 'bitmap' || ($effective['fill-image-name'] ?? '') === '') {
            throw new InvalidArgumentException('Target does not have a supported existing bitmap fill.');
        }
        $source = self::find($context, 'fill-image', $effective['fill-image-name']);
        if ($source === null) {
            throw new InvalidArgumentException('Target bitmap fill declaration is missing.');
        }
        $href = $package->prepareImageResource($path);
        if ($source->getAttributeNS(self::XLINK, 'href') === $href) {
            return;
        }
        $identity = ($dom === $context->contentDom() ? 'content' : 'styles') . "\0" . $objectName . "\0" . $href;
        $fillName = 'bitmap_fill_' . substr(hash('sha256', $identity), 0, 24);
        $styleName = 'bitmap_object_' . substr(hash('sha256', $identity . $style->C14N()), 0, 24);
        $existingFill = self::find($context, 'fill-image', $fillName);
        if ($existingFill !== null && $existingFill->getAttributeNS(self::XLINK, 'href') !== $href) {
            throw new LogicException('Conflicting isolated bitmap fill declaration.');
        }
        $styleNamespace = 'urn:oasis:names:tc:opendocument:xmlns:style:1.0';
        $clone = $dom->importNode($style->cloneNode(true), true);
        $clone->setAttributeNS($styleNamespace, 'style:name', $styleName);
        $clone->removeAttributeNS($styleNamespace, 'display-name');
        $properties = $clone->getElementsByTagNameNS($styleNamespace, 'graphic-properties')->item(0);
        if (!$properties instanceof DOMElement) {
            $properties = $dom->createElementNS($styleNamespace, 'style:graphic-properties');
            $clone->appendChild($properties);
        }
        $properties->setAttributeNS(self::DRAW, 'draw:fill', 'bitmap');
        $properties->setAttributeNS(self::DRAW, 'draw:fill-image-name', $fillName);
        if ($background instanceof DOMElement) {
            $newBackground = $properties->getElementsByTagNameNS($styleNamespace, 'background-image')->item(0);
            if (!$newBackground instanceof DOMElement) {
                $newBackground = $dom->importNode($background->cloneNode(true), true);
                $properties->appendChild($newBackground);
            }
            $newBackground->setAttributeNS(self::XLINK, 'xlink:href', $href);
        }
        $automatic = $dom->getElementsByTagNameNS(self::OFFICE, 'automatic-styles')->item(0);
        if (!$automatic instanceof DOMElement) {
            throw new LogicException('Bitmap replacement requires office:automatic-styles in the target part.');
        }
        foreach ($automatic->childNodes as $existingStyle) {
            if ($existingStyle instanceof DOMElement && $existingStyle->getAttributeNS($styleNamespace, 'name') === $styleName) {
                if ($existingStyle->C14N() !== $clone->C14N()) {
                    throw new LogicException('Conflicting isolated bitmap graphic style.');
                }
                throw new LogicException('Target bitmap style identity is already occupied.');
            }
        }
        $requirement = new FillImageRequirement(FillImageRequirement::PART_STYLES, $fillName, $href);
        $context->registerFillImageRequirement($requirement);
        (new FillImageRequirementMaterializer())->materialize($context, $requirement);
        $automatic->appendChild($clone);
        $object->setAttributeNS(self::DRAW, 'draw:style-name', $styleName);
    }

    private static function graphicStyle(
        OdtDocumentContext $context,
        \DOMDocument $dom,
        string $name,
        bool $commonOnly = false,
        bool $allowMissing = false
    ): ?DOMElement {
        $namespace = 'urn:oasis:names:tc:opendocument:xmlns:style:1.0';
        foreach ($commonOnly ? ['styles'] : ['automatic-styles', 'styles'] as $scope) {
            $owner = $scope === 'styles' ? $context->stylesDom() : $dom;
            $matches = [];
            foreach ($owner->getElementsByTagNameNS(self::OFFICE, $scope) as $container) {
                foreach ($container->childNodes as $candidate) {
                    if ($candidate instanceof DOMElement && $candidate->namespaceURI === $namespace
                        && $candidate->localName === 'style' && $candidate->getAttributeNS($namespace, 'name') === $name
                        && $candidate->getAttributeNS($namespace, 'family') === 'graphic') {
                        $matches[] = $candidate;
                    }
                }
            }
            if (count($matches) > 1) {
                throw new InvalidArgumentException('Ambiguous target graphic style.');
            }
            if ($matches !== []) {
                return $matches[0];
            }
        }
        if ($allowMissing) {
            return null;
        }
        throw new InvalidArgumentException('Target graphic style is missing.');
    }

    public static function find(OdtDocumentContext $context, string $kind, string $name): ?DOMElement
    {
        $match = null;
        foreach (self::container($context)->childNodes as $node) {
            if ($node instanceof DOMElement && $node->namespaceURI === self::DRAW
                && $node->localName === $kind && $node->getAttributeNS(self::DRAW, 'name') === $name) {
                if ($match !== null) {
                    throw new LogicException('Ambiguous fill resource: ' . $name);
                }
                $match = $node;
            }
        }
        return $match;
    }

    private static function container(OdtDocumentContext $context): DOMElement
    {
        $node = $context->stylesDom()->getElementsByTagNameNS(self::OFFICE, 'styles')->item(0);
        if (!$node instanceof DOMElement) {
            throw new LogicException('Missing office:styles.');
        }
        return $node;
    }

    private static function validateName(string $name): void
    {
        // XML 1.0 Fifth Edition NameStartChar/NameChar, excluding colon (NCName).
        $start = 'A-Z_a-z\x{C0}-\x{D6}\x{D8}-\x{F6}\x{F8}-\x{2FF}\x{370}-\x{37D}\x{37F}-\x{1FFF}'
            . '\x{200C}-\x{200D}\x{2070}-\x{218F}\x{2C00}-\x{2FEF}\x{3001}-\x{D7FF}'
            . '\x{F900}-\x{FDCF}\x{FDF0}-\x{FFFD}\x{10000}-\x{EFFFF}';
        if (preg_match('/^[' . $start . '][' . $start . '0-9.\-\x{B7}\x{300}-\x{36F}\x{203F}-\x{2040}]*$/uD', $name) !== 1) {
            throw new InvalidArgumentException('Fill resource name must be a valid XML NCName.');
        }
    }

    private static function percent(mixed $value): string
    {
        if (!is_int($value) || $value < 0 || $value > 100) {
            throw new InvalidArgumentException('Gradient percentage must be an integer from 0 to 100.');
        }
        return $value . '%';
    }
}
