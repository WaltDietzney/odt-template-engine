<?php

namespace OdtTemplateEngine;

use DOMElement;
use DOMXPath;
use RuntimeException;

/**
 * ODT template with programmatic page layout support.
 *
 * This class extends the regular template processor with helpers for changing
 * page margins and selected page layout properties in styles.xml.
 */
class PageLayoutOdtTemplate extends OdtTemplate
{
    private const STYLE_NS = 'urn:oasis:names:tc:opendocument:xmlns:style:1.0';
    private const FO_NS = 'urn:oasis:names:tc:opendocument:xmlns:xsl-fo-compatible:1.0';

    /**
     * Change page margins for the document's master page.
     *
     * @param string $top Top margin, for example "1cm".
     * @param string $right Right margin.
     * @param string $bottom Bottom margin.
     * @param string $left Left margin.
     * @param string $masterPage Master page name. Defaults to "Standard".
     */
    public function setPageMargins(
        string $top,
        string $right,
        string $bottom,
        string $left,
        string $masterPage = 'Standard'
    ): static {
        return $this->setPageLayout([
            'margin-top' => $top,
            'margin-right' => $right,
            'margin-bottom' => $bottom,
            'margin-left' => $left,
        ], $masterPage);
    }

    /**
     * Change selected page layout properties in styles.xml.
     *
     * Supported keys:
     * - margin-top
     * - margin-right
     * - margin-bottom
     * - margin-left
     * - page-width
     * - page-height
     * - orientation (portrait|landscape)
     *
     * @param array<string, string> $options Page layout options.
     * @param string $masterPage Master page name. Defaults to "Standard".
     */
    public function setPageLayout(array $options, string $masterPage = 'Standard'): static
    {
        $properties = $this->findPageLayoutProperties($masterPage);

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

        if (array_key_exists('orientation', $options)) {
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

        return $this;
    }

    /**
     * Resolve the page-layout-properties node referenced by a master page.
     */
    private function findPageLayoutProperties(string $masterPage): DOMElement
    {
        $xpath = new DOMXPath($this->domStyles);
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

    /**
     * Escape arbitrary text for use as an XPath string literal.
     */
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
