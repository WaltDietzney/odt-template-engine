<?php

namespace OdtTemplateEngine;

use DOMElement;
use DOMXPath;
use OdtTemplateEngine\Document\PageLayoutManager;

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
        (new PageLayoutManager($this->documentContext()))->setLayout($options, $masterPage);

        return $this;
    }

    /**
     * Adjust list indentation without touching unrelated fo:margin-left attributes.
     *
     * The base implementation historically used a global regular expression on
     * styles.xml. That also removed page and other style margins. Page layout
     * support must preserve those unrelated attributes.
     */
    protected function adjustBulletIndentation(): void
    {
        $xpath = new DOMXPath($this->domStyles);
        $xpath->registerNamespace('style', self::STYLE_NS);

        $nodes = $xpath->query('//style:list-level-label-alignment');
        if ($nodes === false) {
            return;
        }

        foreach ($nodes as $node) {
            if (!$node instanceof DOMElement) {
                continue;
            }

            $node->setAttributeNS(self::FO_NS, 'fo:margin-left', '0.35cm');
            $node->setAttributeNS(self::FO_NS, 'fo:text-indent', '-0.25cm');
        }
    }
}
