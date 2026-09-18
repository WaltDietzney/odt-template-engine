<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Mapping;

use OdtTemplateEngine\Template\NativeObjectDescriptor;
use OdtTemplateEngine\Template\TemplateContract;
use OdtTemplateEngine\Template\TemplateContractCapabilities;

/** Projects source-derived template targets onto static engine capabilities. */
final class TemplateCapabilityProjector
{
    public function project(
        TemplateContract $contract,
        EngineCapabilityCatalog $catalog
    ): TemplateCapabilityProjection {
        $readiness = $contract->capabilities()->readiness('dependency_mapping');
        $dependencyApplicability = match ($readiness) {
            TemplateContractCapabilities::READY => Applicability::APPLICABLE,
            TemplateContractCapabilities::LIMITED, null => Applicability::UNKNOWN,
            default => Applicability::NOT_APPLICABLE,
        };
        $dependencyReason = match ($dependencyApplicability) {
            Applicability::APPLICABLE => null,
            Applicability::UNKNOWN => 'Template dependency-mapping readiness is incomplete.',
            Applicability::NOT_APPLICABLE => 'TemplateContract marks dependency mapping as blocked.',
        };

        $dependencies = array_map(
            static fn ($dependency): ProjectedDependencyTarget => new ProjectedDependencyTarget(
                $dependency,
                $dependencyApplicability,
                $dependencyReason
            ),
            $contract->dependencies()
        );

        $nameCounts = [];
        foreach ($contract->nativeObjects() as $object) {
            if ($object->name() !== null) {
                $key = $object->kind() . "\0" . $object->name();
                $nameCounts[$key] = ($nameCounts[$key] ?? 0) + 1;
            }
        }

        $nativeTargets = [];
        foreach ($contract->nativeObjects() as $object) {
            $nativeTargets[] = new ProjectedNativeObjectTarget(
                $object,
                $this->projectNativeActions($object, $catalog, $nameCounts)
            );
        }

        $documentCapabilities = array_map(
            fn (string $target): ProjectedDocumentCapability =>
                new ProjectedDocumentCapability(
                    'metadata',
                    $target,
                    $catalog->metadataPayloadKind($target) ?? 'UNKNOWN'
                ),
            $catalog->metadataTargets()
        );

        return new TemplateCapabilityProjection($dependencies, $nativeTargets, $documentCapabilities);
    }

    /** @param array<string, int> $nameCounts
     *  @return list<ProjectedNativeAction>
     */
    private function projectNativeActions(
        NativeObjectDescriptor $object,
        EngineCapabilityCatalog $catalog,
        array $nameCounts
    ): array {
        $projected = [];
        foreach ($catalog->nativeActions() as $capability) {
            if ($capability->targetKind() !== $object->kind()) {
                continue;
            }

            if ($object->name() === null) {
                $applicability = Applicability::NOT_APPLICABLE;
                $reason = 'The source-derived native object has no addressable name.';
            } elseif (($nameCounts[$object->kind() . "\0" . $object->name()] ?? 0) > 1) {
                $applicability = Applicability::NOT_APPLICABLE;
                $reason = 'The source-derived target identity is not unique in this TemplateContract.';
            } elseif ($object->kind() === 'frame') {
                $applicability = Applicability::UNKNOWN;
                $reason = 'NativeObjectDescriptor does not establish whether this frame contains an image.';
            } else {
                $applicability = Applicability::APPLICABLE;
                $reason = null;
            }

            $projected[] = new ProjectedNativeAction($capability, $applicability, $reason);
        }

        return $projected;
    }
}
