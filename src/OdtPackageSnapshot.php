<?php

declare(strict_types=1);

namespace OdtTemplateEngine;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/** @internal Temporary package and document snapshot used only by the E6 invocation. */
final readonly class OdtPackageSnapshot
{
    public function __construct(
        private string $workspacePath,
        private OdtDocumentContextSnapshot $contextState
    ) {
    }

    public function workspacePath(): string { return $this->workspacePath; }
    public function contextState(): OdtDocumentContextSnapshot { return $this->contextState; }

    public function cleanup(): void
    {
        if (!is_dir($this->workspacePath)) {
            return;
        }
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->workspacePath, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($iterator as $entry) {
            if ($entry->isDir()) {
                rmdir($entry->getPathname());
            } else {
                unlink($entry->getPathname());
            }
        }
        rmdir($this->workspacePath);
    }
}
