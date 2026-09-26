<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Document;

/**
 * Read-only handle for one named native drawing frame.
 */
final class FrameTarget extends AbstractAddressableTarget
{
    public function type(): string
    {
        return 'frame';
    }

    public function descriptor(): FrameDescriptor
    {
        return (new TypedTargetResolver())->resolveFrameDescriptor($this->context, $this->targetName);
    }
}
