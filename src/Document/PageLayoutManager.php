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
        $properties = $this->findPageLayoutProperties($masterPage);

        $this->applyLayoutProperties($properties, $options);
    }

    /**
     * Define a new native style:page-layout in styles.xml.
     *
     * @param array<string, string> $options
     */
    public function defineLayout(string $layoutName, array $options): void
    {
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
        } catch (\Throwable $exception) {
            $automaticStyles->removeChild($layout);
            throw $exception;
        }
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

        $properties = $xpath->query('./style:page-layout-properties', $layout)->item(0);
        if (!$properties instanceof DOMElement) {
            throw new RuntimeException(sprintf(
                'Page layout "%s" has no style:page-layout-properties element.',
                $layoutName
            ));
        }

        return $properties;
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
