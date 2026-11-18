<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Document;

use DOMElement;
use DOMXPath;
use OdtTemplateEngine\OdtDocumentContext;
use RuntimeException;

/**
 * Updates page-layout properties in styles.xml for one document context.
 */
final class PageLayoutManager
{
    private const STYLE_NS = 'urn:oasis:names:tc:opendocument:xmlns:style:1.0';
    private const FO_NS = 'urn:oasis:names:tc:opendocument:xmlns:xsl-fo-compatible:1.0';
    private const DRAW_NS = 'urn:oasis:names:tc:opendocument:xmlns:drawing:1.0';

    public function __construct(private readonly OdtDocumentContext $context)
    {
    }

    public function setMargins(
        string $top,
        string $right,
        string $bottom,
        string $left,
        string $masterPage = 'Standard'
    ): void {
        $this->setLayout([
            'margin-top' => $top,
            'margin-right' => $right,
            'margin-bottom' => $bottom,
            'margin-left' => $left,
        ], $masterPage);
    }

    /**
     * @param array<string, string> $options
     */
    public function setLayout(array $options, string $masterPage = 'Standard'): void
    {
        if (!PageLayoutOptions::isExtended($options)) {
            $properties = $this->findPageLayoutProperties($masterPage);
            $this->applyLayoutProperties($properties, $options);
            return;
        }

        $snapshot = $this->context->snapshotState();
        try {
            $layout = $this->findLayoutForMaster($masterPage);
            $properties = $this->layoutProperties($layout, $masterPage);
            $this->applyLayoutProperties($properties, $options);
            PageLayoutOptions::applyPage($properties, $options);
            FillProjection::apply($this->context, $properties, $options);
            $this->synchronizeDrawingPageFill($layout, $options);
            foreach (['header', 'footer'] as $region) {
                if (array_key_exists($region, $options)) {
                    $regionProperties = $this->regionProperties($layout, $region, $masterPage);
                    PageLayoutOptions::applyRegion($regionProperties, $options[$region], $region);
                    FillProjection::apply($this->context, $regionProperties, $options[$region]);
                }
            }
        } catch (\Throwable $exception) {
            $this->context->restoreState($snapshot);
            throw $exception;
        }
    }

    /**
     * Define a new native style:page-layout in styles.xml.
     *
     * @param array<string, string> $options
     */
    public function defineLayout(string $layoutName, array $options): void
    {
        $snapshot = $this->context->snapshotState();
        $layoutName = trim($layoutName);
        if ($layoutName === '') {
            throw new RuntimeException('Page layout name must not be empty.');
        }

        $dom = $this->context->stylesDom();
        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('style', self::STYLE_NS);
        if ($xpath->query(sprintf('//style:page-layout[@style:name=%s]', $this->xpathLiteral($layoutName)))->length > 0) {
            throw new RuntimeException(sprintf('Page layout "%s" already exists.', $layoutName));
        }

        $automaticStyles = $this->findOrCreateContainer($dom, 'automatic-styles');
        $layout = $dom->createElementNS(self::STYLE_NS, 'style:page-layout');
        $layout->setAttributeNS(self::STYLE_NS, 'style:name', $layoutName);
        $properties = $dom->createElementNS(self::STYLE_NS, 'style:page-layout-properties');
        $layout->appendChild($properties);
        $layout->appendChild($dom->createElementNS(self::STYLE_NS, 'style:header-style'));
        $layout->appendChild($dom->createElementNS(self::STYLE_NS, 'style:footer-style'));
        $automaticStyles->appendChild($layout);

        try {
            $this->applyLayoutProperties($properties, $options);
            PageLayoutOptions::applyPage($properties, $options);
            FillProjection::apply($this->context, $properties, $options);
            $this->synchronizeDrawingPageFill($layout, $options);
            foreach (['header', 'footer'] as $region) {
                if (array_key_exists($region, $options)) {
                    $regionProperties = $this->regionProperties($layout, $region, $layoutName);
                    PageLayoutOptions::applyRegion($regionProperties, $options[$region], $region);
                    FillProjection::apply($this->context, $regionProperties, $options[$region]);
                }
            }
        } catch (\Throwable $exception) {
            $this->context->restoreState($snapshot);
            throw $exception;
        }
    }

    public function updateLayout(string $layoutName, array $options): void
    {
        $snapshot = $this->context->snapshotState();
        try {
            $layout = $this->findLayout($layoutName);
            $properties = $this->layoutProperties($layout, 'Page layout ' . $layoutName);
            $this->applyLayoutProperties($properties, $options);
            PageLayoutOptions::applyPage($properties, $options);
            FillProjection::apply($this->context, $properties, $options);
            $this->synchronizeDrawingPageFill($layout, $options);
            foreach (['header', 'footer'] as $region) {
                if (array_key_exists($region, $options)) {
                    $regionProperties = $this->regionProperties($layout, $region, $layoutName);
                    PageLayoutOptions::applyRegion($regionProperties, $options[$region], $region);
                    FillProjection::apply($this->context, $regionProperties, $options[$region]);
                }
            }
        } catch (\Throwable $exception) {
            $this->context->restoreState($snapshot);
            throw $exception;
        }
    }

    /** @return array<string, mixed> */
    public function inspectLayout(string $layoutName): array
    {
        $layout = $this->findLayout($layoutName);
        $properties = $this->layoutProperties($layout, 'Page layout ' . $layoutName);
        $result = [
            'name' => $layoutName,
            'master-pages' => [],
            'properties' => [],
            'regions' => ['header' => null, 'footer' => null],
            'unsupported' => [],
        ];
        $xpath = new DOMXPath($this->context->stylesDom());
        $xpath->registerNamespace('style', self::STYLE_NS);
        $xpath->registerNamespace('draw', self::DRAW_NS);
        foreach ($xpath->query('//style:master-page[@style:page-layout-name=' . $this->xpathLiteral($layoutName) . ']') as $master) {
            if ($master instanceof DOMElement) {
                $result['master-pages'][] = [
                    'name' => $master->getAttributeNS(self::STYLE_NS, 'name'),
                    'drawing-style-name' => $master->getAttributeNS(self::DRAW_NS, 'style-name') ?: null,
                ];
            }
        }
        foreach ($properties->attributes as $attribute) {
            if ($attribute instanceof \DOMAttr) {
                $result['properties'][$this->qualifiedAttribute($attribute)] = $attribute->value;
            }
        }
        foreach (['header', 'footer'] as $region) {
            $regionNode = $this->regionPropertiesOrNull($layout, $region);
            if (!$regionNode instanceof DOMElement) {
                continue;
            }
            $values = [];
            foreach ($regionNode->attributes as $attribute) {
                if ($attribute instanceof \DOMAttr) {
                    $values[$this->qualifiedAttribute($attribute)] = $attribute->value;
                }
            }
            $result['regions'][$region] = $values;
        }
        $result['fill'] = $this->resolvedFill($properties);
        foreach ($properties->childNodes as $child) {
            if (!$child instanceof DOMElement
                || $child->namespaceURI !== self::STYLE_NS
                || $child->localName !== 'columns') {
                continue;
            }
            $columns = ['attributes' => [], 'children' => []];
            foreach ($child->attributes as $attribute) {
                if ($attribute instanceof \DOMAttr) {
                    $columns['attributes'][$this->qualifiedAttribute($attribute)] = $attribute->value;
                }
            }
            foreach ($child->childNodes as $column) {
                if (!$column instanceof DOMElement) {
                    continue;
                }
                $entry = ['name' => $column->localName, 'attributes' => []];
                foreach ($column->attributes as $attribute) {
                    if ($attribute instanceof \DOMAttr) {
                        $entry['attributes'][$this->qualifiedAttribute($attribute)] = $attribute->value;
                    }
                }
                $columns['children'][] = $entry;
            }
            $result['properties']['columns'] = $columns;
        }
        return $result;
    }

    /** Synchronize an existing layout fill when a new master starts using it. */
    public function synchronizeLayoutFill(string $layoutName): void
    {
        $layout = $this->findLayout($layoutName);
        $properties = $this->layoutProperties($layout, 'Page layout ' . $layoutName);
        $options = $this->fillOptionsFromProperties($properties);
        if ($options !== []) $this->synchronizeDrawingPageFill($layout, $options);
    }

    /** @return array<string, mixed> */
    private function fillOptionsFromProperties(DOMElement $properties): array
    {
        $type = $properties->getAttributeNS(self::DRAW_NS, 'fill');
        if ($type === 'solid') return ['background' => ['type' => 'solid', 'color' => $properties->getAttributeNS(self::DRAW_NS, 'fill-color')]];
        if ($type === 'gradient') return ['background' => ['type' => 'gradient', 'name' => $properties->getAttributeNS(self::DRAW_NS, 'fill-gradient-name')]];
        if ($type === 'bitmap') return ['background' => ['type' => 'bitmap', 'name' => $properties->getAttributeNS(self::DRAW_NS, 'fill-image-name'), 'repeat' => $properties->getAttributeNS(self::STYLE_NS, 'repeat') ?: 'stretch']];
        if ($type === 'none') return ['background' => ['type' => 'none']];
        return [];
    }

    /** Keep Writer's drawing-page carrier aligned with a page-layout fill. */
    private function synchronizeDrawingPageFill(DOMElement $layout, array $options): void
    {
        if (!array_key_exists('background', $options) && !array_key_exists('background-color', $options)) {
            return;
        }
        $layoutName = $layout->getAttributeNS(self::STYLE_NS, 'name');
        $xpath = new DOMXPath($this->context->stylesDom());
        $xpath->registerNamespace('style', self::STYLE_NS);
        $xpath->registerNamespace('draw', self::DRAW_NS);
        $masters = [];
        foreach ($xpath->query('//style:master-page[@style:page-layout-name=' . $this->xpathLiteral($layoutName) . ']') ?: [] as $master) {
            if ($master instanceof DOMElement) {
                $masters[] = $master;
            }
        }
        foreach ($masters as $master) {
            $styleName = $master->getAttributeNS(self::DRAW_NS, 'style-name');
            $style = $this->drawingPageStyle($styleName);
            if ($styleName === '' || !$style instanceof DOMElement || $this->drawingStyleUsedByOtherLayout($styleName, $layoutName)) {
                $styleName = $this->privateDrawingStyleName($master->getAttributeNS(self::STYLE_NS, 'name'));
                $style = $this->cloneDrawingPageStyle($style, $styleName);
                $master->setAttributeNS(self::DRAW_NS, 'draw:style-name', $styleName);
            }
            $properties = $this->drawingPageProperties($style);
            FillProjection::apply($this->context, $properties, $options, false);
        }
    }

    private function drawingStyleUsedByOtherLayout(string $styleName, string $layoutName): bool
    {
        $xpath = new DOMXPath($this->context->stylesDom());
        $xpath->registerNamespace('style', self::STYLE_NS);
        $xpath->registerNamespace('draw', self::DRAW_NS);
        foreach ($xpath->query('//style:master-page[@draw:style-name=' . $this->xpathLiteral($styleName) . ']', null) ?: [] as $master) {
            if ($master instanceof DOMElement && $master->getAttributeNS(self::STYLE_NS, 'page-layout-name') !== $layoutName) {
                return true;
            }
        }
        return false;
    }

    private function drawingPageStyle(string $name): ?DOMElement
    {
        if ($name === '') return null;
        $xpath = new DOMXPath($this->context->stylesDom());
        $xpath->registerNamespace('style', self::STYLE_NS);
        $node = $xpath->query('//style:style[@style:name=' . $this->xpathLiteral($name) . '][@style:family="drawing-page"]')->item(0);
        return $node instanceof DOMElement ? $node : null;
    }

    private function cloneDrawingPageStyle(?DOMElement $source, string $name): DOMElement
    {
        $dom = $this->context->stylesDom();
        $container = $this->findOrCreateContainer($dom, 'automatic-styles');
        if ($source instanceof DOMElement) {
            $clone = $source->cloneNode(true);
            if (!$clone instanceof DOMElement) throw new RuntimeException('Unable to clone drawing-page style.');
            $clone->setAttributeNS(self::STYLE_NS, 'style:name', $name);
        } else {
            $clone = $dom->createElementNS(self::STYLE_NS, 'style:style');
            $clone->setAttributeNS(self::STYLE_NS, 'style:name', $name);
            $clone->setAttributeNS(self::STYLE_NS, 'style:family', 'drawing-page');
            $clone->appendChild($dom->createElementNS(self::STYLE_NS, 'style:drawing-page-properties'));
        }
        $container->appendChild($clone);
        return $clone;
    }

    private function privateDrawingStyleName(string $masterName): string
    {
        $base = 'H02_' . preg_replace('/[^A-Za-z0-9_]+/', '_', $masterName) . '_DrawingPage';
        $name = $base; $i = 2;
        while ($this->drawingPageStyle($name) !== null) $name = $base . '_' . $i++;
        return $name;
    }

    private function drawingPageProperties(DOMElement $style): DOMElement
    {
        foreach ($style->childNodes as $child) {
            if ($child instanceof DOMElement && $child->namespaceURI === self::STYLE_NS && $child->localName === 'drawing-page-properties') return $child;
        }
        $properties = $this->context->stylesDom()->createElementNS(self::STYLE_NS, 'style:drawing-page-properties');
        $style->appendChild($properties);
        return $properties;
    }

    /** @return array<string, mixed> */
    private function resolvedFill(DOMElement $properties): array
    {
        $fill = $properties->getAttributeNS(self::DRAW_NS, 'fill');
        if ($fill === '') return ['type' => 'unsupported'];
        $result = ['type' => $fill];
        foreach (['fill-color', 'fill-gradient-name', 'fill-image-name'] as $key) {
            if ($properties->hasAttributeNS(self::DRAW_NS, $key)) $result[$key] = $properties->getAttributeNS(self::DRAW_NS, $key);
        }
        if ($properties->hasAttributeNS(self::FO_NS, 'background-color')) $result['background-color'] = $properties->getAttributeNS(self::FO_NS, 'background-color');
        return $result;
    }

    private function qualifiedAttribute(\DOMAttr $attribute): string
    {
        return $attribute->prefix === '' ? $attribute->localName : $attribute->prefix . ':' . $attribute->localName;
    }

    private function applyLayoutProperties(DOMElement $properties, array $options): void
    {

        $foProperties = [
            'margin-top' => 'margin-top',
            'margin-right' => 'margin-right',
            'margin-bottom' => 'margin-bottom',
            'margin-left' => 'margin-left',
            'page-width' => 'page-width',
            'page-height' => 'page-height',
        ];

        foreach ($foProperties as $option => $attribute) {
            if (!array_key_exists($option, $options)) {
                continue;
            }

            $value = trim((string) $options[$option]);
            if ($value === '') {
                throw new RuntimeException(sprintf('Page layout option "%s" must not be empty.', $option));
            }

            $properties->setAttributeNS(self::FO_NS, 'fo:' . $attribute, $value);
        }

        if (!array_key_exists('orientation', $options)) {
            return;
        }

        $orientation = strtolower(trim((string) $options['orientation']));
        if (!in_array($orientation, ['portrait', 'landscape'], true)) {
            throw new RuntimeException('Page orientation must be "portrait" or "landscape".');
        }

        $properties->setAttributeNS(
            self::STYLE_NS,
            'style:print-orientation',
            $orientation
        );
    }

    private function findOrCreateContainer(\DOMDocument $dom, string $localName): DOMElement
    {
        foreach ($dom->getElementsByTagNameNS('urn:oasis:names:tc:opendocument:xmlns:office:1.0', $localName) as $node) {
            if ($node instanceof DOMElement) {
                return $node;
            }
        }

        $root = $dom->documentElement;
        if (!$root instanceof DOMElement) {
            throw new RuntimeException('ODF styles.xml has no document element.');
        }
        $container = $dom->createElementNS(
            'urn:oasis:names:tc:opendocument:xmlns:office:1.0',
            'office:' . $localName
        );
        $root->appendChild($container);

        return $container;
    }

    private function findPageLayoutProperties(string $masterPage): DOMElement
    {
        return $this->layoutProperties($this->findLayoutForMaster($masterPage), $masterPage);
    }

    private function findLayoutForMaster(string $masterPage): DOMElement
    {
        $xpath = new DOMXPath($this->context->stylesDom());
        $xpath->registerNamespace('style', self::STYLE_NS);

        $master = $xpath->query(
            sprintf('//style:master-page[@style:name=%s]', $this->xpathLiteral($masterPage))
        )->item(0);

        if (!$master instanceof DOMElement) {
            throw new RuntimeException(sprintf('Master page "%s" was not found in styles.xml.', $masterPage));
        }

        $layoutName = $master->getAttributeNS(self::STYLE_NS, 'page-layout-name');
        if ($layoutName === '') {
            throw new RuntimeException(sprintf(
                'Master page "%s" does not reference a page layout.',
                $masterPage
            ));
        }

        $layout = $xpath->query(
            sprintf('//style:page-layout[@style:name=%s]', $this->xpathLiteral($layoutName))
        )->item(0);

        if (!$layout instanceof DOMElement) {
            throw new RuntimeException(sprintf('Page layout "%s" was not found in styles.xml.', $layoutName));
        }

        return $layout;
    }

    private function findLayout(string $layoutName): DOMElement
    {
        $xpath = new DOMXPath($this->context->stylesDom());
        $xpath->registerNamespace('style', self::STYLE_NS);
        $layout = $xpath->query(sprintf('//style:page-layout[@style:name=%s]', $this->xpathLiteral($layoutName)))->item(0);
        if (!$layout instanceof DOMElement) {
            throw new RuntimeException(sprintf('Page layout "%s" was not found.', $layoutName));
        }
        return $layout;
    }

    private function layoutProperties(DOMElement $layout, string $owner): DOMElement
    {
        $xpath = new DOMXPath($this->context->stylesDom());
        $xpath->registerNamespace('style', self::STYLE_NS);
        $properties = $xpath->query('./style:page-layout-properties', $layout)->item(0);
        if (!$properties instanceof DOMElement) {
            throw new RuntimeException(sprintf(
                '%s has no style:page-layout-properties element.',
                $owner
            ));
        }
        return $properties;
    }

    private function regionProperties(DOMElement $layout, string $region, string $owner): DOMElement
    {
        $existing = $this->regionPropertiesOrNull($layout, $region);
        if ($existing instanceof DOMElement) {
            return $existing;
        }
        $container = null;
        foreach ($layout->childNodes as $child) {
            if ($child instanceof DOMElement
                && $child->namespaceURI === self::STYLE_NS
                && $child->localName === $region . '-style') {
                $container = $child;
                break;
            }
        }
        if (!$container instanceof DOMElement) {
            $container = $this->context->stylesDom()->createElementNS(self::STYLE_NS, 'style:' . $region . '-style');
        }
        $properties = $this->context->stylesDom()->createElementNS(self::STYLE_NS, 'style:header-footer-properties');
        $container->appendChild($properties);
        if ($container->parentNode === null) {
            $reference = null;
            if ($region === 'header') {
                foreach ($layout->childNodes as $child) {
                    if ($child instanceof DOMElement
                        && $child->namespaceURI === self::STYLE_NS
                        && $child->localName === 'footer-style') {
                        $reference = $child;
                        break;
                    }
                }
            }
            if ($reference instanceof DOMElement) {
                $layout->insertBefore($container, $reference);
            } else {
                $layout->appendChild($container);
            }
        } elseif ($container->parentNode !== $layout) {
            $layout->appendChild($container);
        }
        return $properties;
    }

    private function regionPropertiesOrNull(DOMElement $layout, string $region): ?DOMElement
    {
        foreach ($layout->childNodes as $child) {
            if (!$child instanceof DOMElement
                || $child->namespaceURI !== self::STYLE_NS
                || $child->localName !== $region . '-style') {
                continue;
            }
            foreach ($child->childNodes as $nested) {
                if ($nested instanceof DOMElement
                    && $nested->namespaceURI === self::STYLE_NS
                    && $nested->localName === 'header-footer-properties') {
                    return $nested;
                }
            }
        }
        return null;
    }

    private function xpathLiteral(string $value): string
    {
        if (!str_contains($value, "'")) {
            return "'" . $value . "'";
        }

        if (!str_contains($value, '"')) {
            return '"' . $value . '"';
        }

        $parts = explode("'", $value);

        return 'concat(' . implode(', "\'", ', array_map(
            static fn (string $part): string => "'" . $part . "'",
            $parts
        )) . ')';
    }
}
