<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Mapping;

use OdtTemplateEngine\Document\DocumentInspection;
use OdtTemplateEngine\Elements\OdtElement;
use OdtTemplateEngine\Template\NativeObjectDescriptor;
use OdtTemplateEngine\Template\TemplateContract;

/** Performs complete concrete Phase-E validation without mutating the document. */
final class ConcreteMappingPreflight
{
    public function __construct(
        private ?MappingResolutionResolver $resolver = null,
        private ?DependencyConcretePreflightValidator $dependencyValidator = null,
        private ?FrameImageReplacementPreflightValidator $frameValidator = null
    ) {
        $this->resolver ??= new MappingResolutionResolver();
        $this->dependencyValidator ??= new DependencyConcretePreflightValidator();
        $this->frameValidator ??= new FrameImageReplacementPreflightValidator();
    }

    /**
     * @param array<mixed> $data Canonical application data.
     * $workingDocument is an existing read-only inspection of the current
     * working document, used only for concrete named-frame applicability.
     */
    public function preflight(
        MappingDefinition $definition,
        TemplateContract $contract,
        array $data,
        DocumentInspection $workingDocument
    ): ConcretePreflightResult {
        $resolution = $this->resolver->resolve($definition, $contract, $data);
        $operations = [];

        foreach ($resolution->dependencies() as $dependencyResolution) {
            $operations[] = $this->dependencyValidator->validate($dependencyResolution, $contract);
        }

        $nativeDescriptors = $this->nativeDescriptors($contract);
        $catalog = EngineCapabilityCatalog::phaseE1();
        foreach ($resolution->nativeObjectActions() as $nativeResolution) {
            $mapping = $nativeResolution->mapping();
            $identity = $mapping->targetKind() . ':' . $mapping->targetName();
            $capability = $catalog->nativeAction($mapping->targetKind(), $mapping->actionId());
            $payloadKind = $capability?->payloadKind();
            $sourcePath = $mapping->source()->canonical();
            $diagnostics = $this->dataStateDiagnostics(
                $nativeResolution->dataResolution(),
                'native_action',
                $identity,
                $sourcePath
            );
            $applicability = null;

            if ($mapping->actionId() === 'replace-content'
                && $nativeResolution->dataResolution()->status() === ApplicationDataResolution::PRESENT
                && $nativeResolution->dataResolution()->items() === []
                && !$nativeResolution->dataResolution()->value() instanceof OdtElement
            ) {
                $diagnostics[] = $this->diagnostic(
                    'INCOMPATIBLE_NATIVE_ACTION_PAYLOAD',
                    'Section replace-content requires an OdtElement payload.',
                    'native_action',
                    $identity,
                    $sourcePath
                );
            }
            if ($mapping->actionId() === 'replace-text'
                && $nativeResolution->dataResolution()->status() === ApplicationDataResolution::PRESENT
                && $nativeResolution->dataResolution()->items() === []
                && !is_string($nativeResolution->dataResolution()->value())
            ) {
                $diagnostics[] = $this->diagnostic(
                    'INCOMPATIBLE_NATIVE_ACTION_PAYLOAD',
                    'Bookmark replace-text requires a string payload.',
                    'native_action',
                    $identity,
                    $sourcePath
                );
            }

            if ($mapping->actionId() === 'replace-image') {
                $frameResult = $this->frameValidator->validate(
                    $nativeResolution->dataResolution(),
                    $nativeDescriptors[$mapping->targetKind() . "\0" . $mapping->targetName()] ?? null,
                    $workingDocument,
                    $identity,
                    $sourcePath
                );
                $applicability = $frameResult['applicability'];
                array_push($diagnostics, ...$frameResult['diagnostics']);
            } elseif (in_array($mapping->actionId(), ['replace-content', 'replace-text'], true)) {
                $applicability = FrameImageReplacementPreflightValidator::APPLICABLE;
            }

            $operations[] = $this->operation(
                'native_action',
                $identity,
                $nativeResolution,
                $mapping->actionId(),
                $payloadKind,
                $applicability,
                $diagnostics
            );
        }

        foreach ($resolution->documentCapabilities() as $documentResolution) {
            $mapping = $documentResolution->mapping();
            $identity = $mapping->group() . '.' . $mapping->target();
            $sourcePath = $mapping->source()->canonical();
            $diagnostics = $this->dataStateDiagnostics(
                $documentResolution->dataResolution(),
                'document_capability',
                $identity,
                $sourcePath
            );
            $value = $documentResolution->dataResolution()->value();
            if ($documentResolution->dataResolution()->status() === ApplicationDataResolution::PRESENT
                && $documentResolution->dataResolution()->items() === []
                && !is_string($value) && !is_int($value) && !is_float($value) && !is_bool($value)
            ) {
                $diagnostics[] = $this->diagnostic(
                    'INCOMPATIBLE_DOCUMENT_CAPABILITY_PAYLOAD',
                    'Metadata Phase-E values must be string, int, float, or bool.',
                    'document_capability',
                    $identity,
                    $sourcePath
                );
            }
            $operations[] = $this->operation(
                'document_capability',
                $identity,
                $documentResolution,
                $identity,
                'SCALAR',
                FrameImageReplacementPreflightValidator::APPLICABLE,
                $diagnostics
            );
        }

        return new ConcretePreflightResult($resolution, $operations);
    }

    /** @return list<ConcretePreflightDiagnostic> */
    private function dataStateDiagnostics(
        ApplicationDataResolution $data,
        string $family,
        string $identity,
        string $source
    ): array {
        $code = match ($data->status()) {
            ApplicationDataResolution::MISSING => 'MISSING_SOURCE_VALUE',
            ApplicationDataResolution::NULL => 'NULL_SOURCE_VALUE',
            ApplicationDataResolution::WRONG_SHAPE,
            ApplicationDataResolution::EMPTY_COLLECTION => 'WRONG_APPLICATION_SHAPE',
            default => null,
        };
        if ($code === null && $data->items() !== []) {
            $code = $family === 'native_action'
                ? 'INCOMPATIBLE_NATIVE_ACTION_PAYLOAD'
                : 'INCOMPATIBLE_DOCUMENT_CAPABILITY_PAYLOAD';
        }
        return $code === null ? [] : [$this->diagnostic(
            $code,
            sprintf('Application source resolved with status %s.', $data->status()),
            $family,
            $identity,
            $source
        )];
    }

    /** @return array<string, NativeObjectDescriptor> */
    private function nativeDescriptors(TemplateContract $contract): array
    {
        $descriptors = [];
        foreach ($contract->nativeObjects() as $descriptor) {
            if ($descriptor->name() !== null) {
                $descriptors[$descriptor->kind() . "\0" . $descriptor->name()] = $descriptor;
            }
        }
        return $descriptors;
    }

    /** @param list<ConcretePreflightDiagnostic> $diagnostics */
    private function operation(
        string $family,
        string $identity,
        DependencyMappingResolution|NativeObjectActionResolution|DocumentCapabilityResolution $resolution,
        ?string $capability,
        ?string $payload,
        ?string $applicability,
        array $diagnostics
    ): ConcretePreflightOperation {
        return new ConcretePreflightOperation(
            $family,
            $identity,
            $resolution,
            $capability,
            $payload,
            $applicability,
            $diagnostics === [] ? ConcretePreflightOperation::READY : ConcretePreflightOperation::ERROR,
            $diagnostics
        );
    }

    /** @param array<string, scalar|null> $context */
    private function diagnostic(
        string $code,
        string $message,
        string $family,
        string $identity,
        ?string $source = null,
        array $context = []
    ): ConcretePreflightDiagnostic {
        return new ConcretePreflightDiagnostic($code, $message, $family, $identity, $source, $context);
    }
}
