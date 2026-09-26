<?php

namespace OdtTemplateEngine;

use OdtTemplateEngine\Document\PageLayoutManager;

/**
 * ODT template with programmatic page layout support.
 *
 * This class extends the regular template processor with helpers for changing
 * page margins and selected page layout properties in styles.xml.
 */
class PageLayoutOdtTemplate extends OdtTemplate
{
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

}
