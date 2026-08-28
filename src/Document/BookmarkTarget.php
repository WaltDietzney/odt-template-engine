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

    /**
     * Replace a safely bounded textual bookmark range and preserve its markers.
     */
    public function replaceText(string $value): self
    {
        $this->descriptor();
        (new BookmarkMutationService())->replaceText($this->context, $this->targetName, $value);

        return $this;
    }
}
