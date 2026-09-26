<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Document;

use OdtTemplateEngine\Mapping\ConcretePreflightResult;
use OdtTemplateEngine\OdtPackage;

/** @internal Provides the single outer rollback boundary for one common Phase-E invocation. */
final class PhaseEAutomationExecutor
{
    /** @param callable():void $executeFamilies */
    public function execute(
        OdtPackage $package,
        ConcretePreflightResult $preflight,
        callable $executeFamilies
    ): void {
        if (!$preflight->ready()) {
            throw new \InvalidArgumentException('Phase-E automation requires a READY concrete preflight.');
        }

        $snapshot = $package->snapshotWorkingState();
        try {
            $executeFamilies();
        } catch (\Throwable $executionFailure) {
            try {
                $package->restoreWorkingState($snapshot);
            } catch (\Throwable $rollbackFailure) {
                throw new PhaseEAutomationRollbackException($executionFailure, $rollbackFailure);
            }

            throw $executionFailure;
        } finally {
            try {
                $snapshot->cleanup();
            } catch (\Throwable) {
                // Snapshot cleanup must not replace the execution result.
            }
        }
    }
}
