<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Mapping;

/** Complete non-mutating Phase-E dry-run result. */
final readonly class ConcretePreflightResult
{
    public const READY = 'READY';
    public const ERROR = 'ERROR';

    /** @param list<ConcretePreflightOperation> $operations */
    public function __construct(
        private MappingResolution $mappingResolution,
        private array $operations
    ) {
        foreach ($operations as $operation) {
            if (!$operation instanceof ConcretePreflightOperation) {
                throw new \InvalidArgumentException('Preflight operations must be typed values.');
            }
        }
    }

    public function status(): string
    {
        foreach ($this->operations as $operation) {
            if ($operation->status() === ConcretePreflightOperation::ERROR) {
                return self::ERROR;
            }
        }
        return self::READY;
    }

    public function ready(): bool { return $this->status() === self::READY; }
    public function mappingResolution(): MappingResolution { return $this->mappingResolution; }
    /** @return list<ConcretePreflightOperation> */
    public function operations(): array { return $this->operations; }

    /** @return list<ConcretePreflightDiagnostic> */
    public function diagnostics(): array
    {
        $diagnostics = [];
        foreach ($this->operations as $operation) {
            array_push($diagnostics, ...$operation->diagnostics());
        }
        return $diagnostics;
    }
}
