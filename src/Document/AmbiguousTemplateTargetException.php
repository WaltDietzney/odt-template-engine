<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Document;

use RuntimeException;

/**
 * Raised when one typed native target name resolves to more than one node.
 */
final class AmbiguousTemplateTargetException extends RuntimeException
{
}
