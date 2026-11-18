<?php

namespace OdtTemplateEngine;

use OdtTemplateEngine\Document\PageLayoutManager;
use OdtTemplateEngine\Document\PageStyleManager;
use OdtTemplateEngine\Elements\OdtElement;

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

    /**
     * Define a native style:page-layout for a new master page.
     *
     * @param array<string, string> $options
     */
    public function definePageLayout(string $name, array $options): static
    {
        (new PageStyleManager($this->documentContext(), $this->documentPackage()))
            ->definePageLayout($name, $options);

        return $this;
    }

    /** Explicitly mutate a native page-layout identity (shared references remain shared). */
    public function updatePageLayout(string $name, array $options): static
    {
        (new PageLayoutManager($this->documentContext()))->updateLayout($name, $options);

        return $this;
    }

    /** @return array<string, mixed> */
    public function inspectPageLayout(string $name): array
    {
        return (new PageLayoutManager($this->documentContext()))->inspectLayout($name);
    }

    /** @return array<string, mixed> */
    public function inspectPageStyle(string $name): array
    {
        return (new PageStyleManager($this->documentContext(), $this->documentPackage()))
            ->inspectPageStyle($name);
    }

    /**
     * Define a native style:master-page and associate it with a page layout.
     *
     * Supported options are display-name, next-style-name, header and footer.
     * Header/footer values are native OdtElement instances.
     *
     * @param array{display-name?: string, next-style-name?: string, header?: OdtElement, footer?: OdtElement} $options
     */
    public function definePageStyle(string $name, string $pageLayoutName, array $options = []): static
    {
        (new PageStyleManager($this->documentContext(), $this->documentPackage()))
            ->definePageStyle($name, $pageLayoutName, $options);

        return $this;
    }

    /** Assign a master page to a paragraph style used by a document area. */
    public function assignPageStyle(string $paragraphStyleName, string $masterPageName): static
    {
        (new PageStyleManager($this->documentContext(), $this->documentPackage()))
            ->assignPageStyle($paragraphStyleName, $masterPageName);

        return $this;
    }

    /** Change or remove the native automatic successor of a master page. */
    public function setNextPageStyle(string $masterPageName, ?string $nextMasterPageName): static
    {
        (new PageStyleManager($this->documentContext(), $this->documentPackage()))
            ->setNextPageStyle($masterPageName, $nextMasterPageName);

        return $this;
    }

}
