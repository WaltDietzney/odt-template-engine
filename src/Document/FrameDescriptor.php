<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Document;

/**
 * Immutable snapshot of a named native ODF frame.
 */
final readonly class FrameDescriptor
{
    /** @param list<InspectionDiagnostic> $diagnostics */
    public function __construct(
        private string $name,
        private string $documentPart,
        private string $payloadType,
        private ?string $width,
        private ?string $height,
        private ?string $containingSection,
        private array $diagnostics = []
    ) {
    }

    public function name(): string
    {
        return $this->name;
    }

    public function documentPart(): string
    {
        return $this->documentPart;
    }

    public function payloadType(): string
    {
        return $this->payloadType;
    }

    public function width(): ?string
    {
        return $this->width;
    }

    public function height(): ?string
    {
        return $this->height;
    }

    public function containingSection(): ?string
    {
        return $this->containingSection;
    }

    /** @return list<InspectionDiagnostic> */
    public function diagnostics(): array
    {
        return $this->diagnostics;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'type' => 'frame',
            'name' => $this->name,
            'document_part' => $this->documentPart,
            'payload_type' => $this->payloadType,
            'width' => $this->width,
            'height' => $this->height,
            'containing_section' => $this->containingSection,
            'diagnostics' => array_map(
                static fn (InspectionDiagnostic $diagnostic): array => $diagnostic->toArray(),
                $this->diagnostics
            ),
        ];
    }
}
