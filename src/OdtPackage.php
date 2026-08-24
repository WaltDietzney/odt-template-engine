<?php

declare(strict_types=1);

namespace OdtTemplateEngine;

use DOMDocument;
use Exception;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use ZipArchive;

/**
 * Owns the physical lifecycle of one editable ODT package.
 *
 * The package owns archive extraction, the temporary workspace, package files,
 * manifest synchronization, persistence, ZIP rebuild, and cleanup. Mutable XML
 * state is grouped in an OdtDocumentContext so later document-scoped concerns
 * have a clear lifetime without being mixed into archive mechanics.
 */
final class OdtPackage
{
    private string $workspacePath;

    private OdtDocumentContext $context;

    private bool $cleanedUp = false;

    /**
     * @throws Exception If the template cannot be opened or the workspace cannot be created.
     */
    public function __construct(private readonly string $templatePath)
    {
        if (!is_file($templatePath)) {
            throw new Exception(sprintf('Template file not found: %s', $templatePath));
        }

        $workspacePath = sys_get_temp_dir() . '/odt_' . uniqid('', true);
        if (!mkdir($workspacePath) && !is_dir($workspacePath)) {
            throw new Exception('Failed to create temporary directory.');
        }

        $this->workspacePath = $workspacePath;
        $this->extractTemplate();
        $this->context = new OdtDocumentContext(
            $this->loadXmlFile('content.xml'),
            $this->loadXmlFile('styles.xml'),
            $this->loadXmlFile('meta.xml')
        );
    }

    public function templatePath(): string
    {
        return $this->templatePath;
    }

    public function workspacePath(): string
    {
        return $this->workspacePath;
    }

    public function context(): OdtDocumentContext
    {
        return $this->context;
    }

    public function contentDom(): DOMDocument
    {
        return $this->context->contentDom();
    }

    public function stylesDom(): DOMDocument
    {
        return $this->context->stylesDom();
    }

    public function metaDom(): DOMDocument
    {
        return $this->context->metaDom();
    }

    /**
     * Return a path inside the document-scoped package workspace.
     */
    public function path(string $relativePath): string
    {
        return $this->workspacePath . '/' . ltrim($relativePath, '/');
    }

    /**
     * Reload the core XML documents from the current workspace contents.
     *
     * This is intentionally different from extracting the original template
     * again. It supports compatibility flows that serialize document changes
     * and then continue with freshly parsed DOMDocument instances.
     */
    public function reloadCoreDocuments(): void
    {
        $this->context->replaceCoreDocuments(
            $this->loadXmlFile('content.xml'),
            $this->loadXmlFile('styles.xml'),
            $this->loadXmlFile('meta.xml')
        );
    }

    /**
     * Persist the current core DOM documents into the workspace.
     */
    public function persistCoreDocuments(): void
    {
        $this->saveMinifiedXml($this->contentDom(), $this->path('content.xml'));
        $this->saveMinifiedXml($this->stylesDom(), $this->path('styles.xml'));
        $this->saveMinifiedXml($this->metaDom(), $this->path('meta.xml'));
    }

    /**
     * Ensure that files below Pictures/ are represented in the ODT manifest.
     */
    public function synchronizeImageManifest(): void
    {
        $manifestPath = $this->path('META-INF/manifest.xml');
        $picturesDir = $this->path('Pictures');

        if (!is_file($manifestPath) || !is_dir($picturesDir)) {
            return;
        }

        $manifest = file_get_contents($manifestPath);
        if ($manifest === false) {
            throw new RuntimeException('Unable to read ODT manifest.');
        }

        $existingEntries = [];
        preg_match_all('/manifest:full-path="([^"]+)"/', $manifest, $matches);
        foreach ($matches[1] ?? [] as $path) {
            $existingEntries[$path] = true;
        }

        $imageMimeTypes = [
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'svg' => 'image/svg+xml',
            'bmp' => 'image/bmp',
            'webp' => 'image/webp',
        ];

        $changed = false;
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($picturesDir, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isDir()) {
                continue;
            }

            $relativePicturePath = substr($file->getPathname(), strlen($picturesDir) + 1);
            $packagePath = 'Pictures/' . str_replace(DIRECTORY_SEPARATOR, '/', $relativePicturePath);

            if (isset($existingEntries[$packagePath])) {
                continue;
            }

            $extension = strtolower(pathinfo($file->getFilename(), PATHINFO_EXTENSION));
            $mimeType = $imageMimeTypes[$extension] ?? 'application/octet-stream';
            $entry = sprintf(
                "\n <manifest:file-entry manifest:full-path=\"%s\" manifest:media-type=\"%s\"/>",
                htmlspecialchars($packagePath, ENT_QUOTES | ENT_XML1),
                htmlspecialchars($mimeType, ENT_QUOTES | ENT_XML1)
            );

            $manifest = str_replace(
                '</manifest:manifest>',
                $entry . "\n</manifest:manifest>",
                $manifest
            );
            $existingEntries[$packagePath] = true;
            $changed = true;
        }

        if ($changed && file_put_contents($manifestPath, $manifest) === false) {
            throw new RuntimeException('Unable to update ODT manifest.');
        }
    }

    /**
     * Serialize the current document state and create a valid ODT archive.
     *
     * The caller remains responsible for domain-specific finalization such as
     * style collection before this method is invoked.
     *
     * @throws Exception If the ODT archive cannot be created.
     */
    public function saveAs(string $outputPath): void
    {
        $this->synchronizeImageManifest();
        $this->persistCoreDocuments();

        $mimetypePath = $this->path('mimetype');
        if (!is_file($mimetypePath)) {
            throw new Exception('Missing mimetype file in template.');
        }

        $mimetype = file_get_contents($mimetypePath);
        if ($mimetype === false) {
            throw new Exception('Unable to read mimetype file from template.');
        }

        $zip = new ZipArchive();
        if ($zip->open($outputPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new Exception(sprintf('Could not create output file: %s', $outputPath));
        }

        try {
            // ODF requires the mimetype entry first and without compression.
            $zip->addFromString('mimetype', $mimetype);
            $zip->setCompressionName('mimetype', ZipArchive::CM_STORE);

            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator(
                    $this->workspacePath,
                    RecursiveDirectoryIterator::SKIP_DOTS
                )
            );

            foreach ($iterator as $file) {
                if ($file->isDir()) {
                    continue;
                }

                $filePath = $file->getPathname();
                $localPath = substr($filePath, strlen($this->workspacePath) + 1);
                $localPath = str_replace(DIRECTORY_SEPARATOR, '/', $localPath);

                if (in_array($localPath, ['mimetype', 'template.odt'], true)) {
                    continue;
                }

                $zip->addFile($filePath, $localPath);
            }
        } finally {
            $zip->close();
        }
    }

    /**
     * Remove this package's temporary workspace.
     *
     * Cleanup is idempotent so explicit cleanup and shutdown cleanup can safely
     * coexist during the compatibility migration.
     */
    public function cleanup(): void
    {
        if ($this->cleanedUp || !is_dir($this->workspacePath)) {
            $this->cleanedUp = true;

            return;
        }

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(
                $this->workspacePath,
                RecursiveDirectoryIterator::SKIP_DOTS
            ),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $file) {
            if ($file->isDir()) {
                rmdir($file->getPathname());
            } else {
                unlink($file->getPathname());
            }
        }

        rmdir($this->workspacePath);
        $this->cleanedUp = true;
    }

    private function extractTemplate(): void
    {
        $zip = new ZipArchive();
        if ($zip->open($this->templatePath) !== true) {
            throw new Exception('Could not open ODT file.');
        }

        try {
            if (!$zip->extractTo($this->workspacePath)) {
                throw new Exception('Could not extract ODT file.');
            }
        } finally {
            $zip->close();
        }
    }

    private function loadXmlFile(string $filename): DOMDocument
    {
        $path = $this->path($filename);
        if (!is_file($path)) {
            throw new Exception(sprintf('Missing %s in template.', $filename));
        }

        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = false;

        if (!$dom->load($path, LIBXML_NOENT | LIBXML_NOCDATA)) {
            throw new Exception(sprintf('Could not load %s from template.', $filename));
        }

        return $dom;
    }

    private function saveMinifiedXml(DOMDocument $dom, string $path): void
    {
        $xml = $dom->saveXML();
        if ($xml === false) {
            throw new RuntimeException(sprintf('Unable to serialize XML for %s.', $path));
        }

        $xml = preg_replace('/>\s+</', '><', $xml) ?? $xml;
        $xml = preg_replace('/[\r\n\t]+/', '', $xml) ?? $xml;
        $xml = preg_replace('/ {2,}/', ' ', $xml) ?? $xml;

        if (file_put_contents($path, $xml) === false) {
            throw new RuntimeException(sprintf('Unable to write XML file %s.', $path));
        }
    }
}
