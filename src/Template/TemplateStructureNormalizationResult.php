<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Template;

/** Immutable summary of one canonical template normalization pass. */
final readonly class TemplateStructureNormalizationResult
{
    /** @param list<array<string, mixed>> $repairs @param list<array<string, mixed>> $skipped @param list<TemplateStructureDiagnostic> $diagnostics */
    public function __construct(private array $repairs, private array $skipped, private array $diagnostics) {}

    public function changed(): bool { return $this->repairs !== []; }
    /** @return list<array<string, mixed>> */ public function repairs(): array { return $this->repairs; }
    /** @return list<array<string, mixed>> */ public function skipped(): array { return $this->skipped; }
    /** @return list<TemplateStructureDiagnostic> */ public function diagnostics(): array { return $this->diagnostics; }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return ['changed' => $this->changed(), 'repairs' => $this->repairs, 'skipped' => $this->skipped, 'diagnostics' => array_map(static fn (TemplateStructureDiagnostic $item): array => $item->toArray(), $this->diagnostics)];
    }
}
