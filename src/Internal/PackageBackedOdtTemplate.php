<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Internal;

use OdtTemplateEngine\OdtPackage;
use OdtTemplateEngine\OdtTemplate;
use OdtTemplateEngine\Utils\StyleWriter;

/**
 * ARCH-02 migration adapter used to characterize the extracted package lifecycle.
 *
 * This class is intentionally internal. It proves that the existing template
 * behavior can run against OdtPackage/OdtDocumentContext before the public
 * OdtTemplate facade is switched to composition. It must not become a second
 * public template API.
 *
 * @internal
 */
final class PackageBackedOdtTemplate extends OdtTemplate
{
    private OdtPackage $package;

    public function __construct(string $templatePath)
    {
        $this->package = new OdtPackage($templatePath);
        $this->synchronizeLegacyState();
        $this->prepareLoadedTemplate();

        register_shutdown_function([$this, 'cleanup']);
    }

    /**
     * Reload the XML documents from the package workspace.
     *
     * This migration adapter deliberately does not re-extract the source ODT.
     * ARCH-02 characterization tests exercise the normal constructor/save path;
     * compatibility semantics of the legacy public load()/refresh() pair remain
     * a separate migration decision before OdtTemplate itself is changed.
     */
    public function load(): void
    {
        $this->package->reloadCoreDocuments();
        $this->synchronizeLegacyState();
        $this->prepareLoadedTemplate();
    }

    public function save(string $outputPath): void
    {
        $this->injectImageStyles();
        StyleWriter::writeAllStyles($this->domStyles);
        $this->adjustBulletIndentation();

        $this->package->saveAs($outputPath);
    }

    public function cleanup(): void
    {
        $this->package->cleanup();
    }

    public function package(): OdtPackage
    {
        return $this->package;
    }

    private function synchronizeLegacyState(): void
    {
        $this->templatePath = $this->package->templatePath();
        $this->tempDir = $this->package->workspacePath();
        $this->domContent = $this->package->contentDom();
        $this->domStyles = $this->package->stylesDom();
        $this->domMeta = $this->package->metaDom();
    }

    private function prepareLoadedTemplate(): void
    {
        $this->normalizeTemplateDom($this->domContent);
        $this->normalizeTemplateDom($this->domStyles);
        $this->ensureDefaultParagraphStyles();
        $this->ensureDefaultListStyles();
        $this->ensureDefaultListStylesForContentXml($this->domContent);
    }
}
