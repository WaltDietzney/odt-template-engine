<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Document;

use RuntimeException;
use Throwable;

/** @internal Preserves both failures when restoring a failed Phase-E invocation also fails. */
final class PhaseEAutomationRollbackException extends RuntimeException
{
    public function __construct(
        private readonly Throwable $executionFailure,
        private readonly Throwable $rollbackFailure
    ) {
        parent::__construct(
            sprintf(
                'Phase-E execution failed (%s); rollback also failed (%s).',
                $executionFailure->getMessage(),
                $rollbackFailure->getMessage()
            ),
            0,
            $executionFailure
        );
    }

    public function executionFailure(): Throwable { return $this->executionFailure; }
    public function rollbackFailure(): Throwable { return $this->rollbackFailure; }
}
