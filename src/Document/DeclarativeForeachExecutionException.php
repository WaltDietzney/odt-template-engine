<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Document;

use RuntimeException;

/** @internal Failure while executing a recognized native foreach Section. */
final class DeclarativeForeachExecutionException extends RuntimeException
{
    public function __construct(string $control, string $path, string $reason)
    {
        parent::__construct(sprintf('foreach %s (%s): %s', $control, $path, $reason));
    }
}
