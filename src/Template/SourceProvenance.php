<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Template;

/** Immutable source location for one authored template-contract item. */
final readonly class SourceProvenance
{
    /**
     * @param list<string> $nativeOwnerChain
     */
    public function __construct(
        private string $evidenceId,
        private string $sourcePart,
        private string $regionKind,
        private ?string $regionOwner,
        private string $carrierKind,
        private string $representationKind,
        private int $sourceOrder,
        private ?string $physicalScope = null,
        private array $nativeOwnerChain = []
    ) {
    }

    public function evidenceId(): string
    {
        return $this->evidenceId;
    }

    public function sourcePart(): string
    {
        return $this->sourcePart;
    }

    public function regionKind(): string
    {
        return $this->regionKind;
    }

    public function regionOwner(): ?string
    {
        return $this->regionOwner;
    }

    public function carrierKind(): string
    {
        return $this->carrierKind;
    }

    public function representationKind(): string
    {
        return $this->representationKind;
    }

    public function sourceOrder(): int
    {
        return $this->sourceOrder;
    }

    public function physicalScope(): ?string
    {
        return $this->physicalScope;
    }

    /** @return list<string> */
    public function nativeOwnerChain(): array
    {
        return $this->nativeOwnerChain;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'evidence_id' => $this->evidenceId,
            'source_part' => $this->sourcePart,
            'region_kind' => $this->regionKind,
            'region_owner' => $this->regionOwner,
            'carrier_kind' => $this->carrierKind,
            'representation_kind' => $this->representationKind,
            'source_order' => $this->sourceOrder,
            'physical_scope' => $this->physicalScope,
            'native_owner_chain' => $this->nativeOwnerChain,
        ];
    }
}
