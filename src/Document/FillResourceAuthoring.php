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
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $expected = match ($extension) {
            'png' => IMAGETYPE_PNG,
            'jpg', 'jpeg' => IMAGETYPE_JPEG,
            default => null,
        };
        if ($expected === null || !is_file($path) || !is_readable($path)
            || (@getimagesize($path)[2] ?? null) !== $expected) {
            throw new InvalidArgumentException('Fill image must be a readable local PNG or JPEG.');
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
