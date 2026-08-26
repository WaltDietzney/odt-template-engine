<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Document;

/**
 * Read-only handle for one named bookmark or bookmark range.
 */
final class BookmarkTarget extends AbstractAddressableTarget
{
    public function type(): string
    {
        return 'bookmark';
    }

    public function descriptor(): BookmarkDescriptor
    {
        return (new TypedTargetResolver())->resolveBookmarkDescriptor($this->context, $this->targetName);
    }
}
