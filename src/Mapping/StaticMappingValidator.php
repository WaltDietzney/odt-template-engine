<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Mapping;

use OdtTemplateEngine\Template\DependencyDescriptor;
use OdtTemplateEngine\Template\NativeObjectDescriptor;
use OdtTemplateEngine\Template\TemplateContract;

/** Non-mutating validation of explicit mapping rules against source semantics. */
final class StaticMappingValidator
{
    public function validate(
        MappingDefinition $definition,
        TemplateContract $contract,
        TemplateCapabilityProjection $projection,
        EngineCapabilityCatalog $catalog
    ): MappingValidationResult {
        $diagnostics = [];
        $deferred = [];
        $dependencyRules = [];
        $targetCollectionMappings = [];

        foreach ($definition->dependencies() as $mapping) {
            $source = $mapping->source();
            $matches = array_values(array_filter(
                $contract->dependencies(),
                static fn (DependencyDescriptor $dependency): bool =>
                    $dependency->path() === $mapping->dependencyPath()
            ));
            if ($matches === []) {
                $diagnostics[] = new MappingDiagnostic('unknown_dependency', 'Dependency target does not exist in TemplateContract.', [
                    'source' => $source->canonical(),
                    'target' => $mapping->dependencyPath(),
                ]);
                continue;
            }
            if (count($matches) > 1) {
                $diagnostics[] = new MappingDiagnostic('ambiguous_dependency', 'Dependency target path is not unique in TemplateContract.', [
                    'source' => $source->canonical(),
                    'target' => $mapping->dependencyPath(),
                ]);
                continue;
            }

            $dependency = $matches[0];
            $dependencyRules[$dependency->id()][] = $source->canonical();
            $targetIsCollection = $dependency->kind() === 'COLLECTION';
            $sourceIsCollection = $source->terminalKind() === ApplicationPathSegment::COLLECTION;
            if ($targetIsCollection !== $sourceIsCollection) {
                $diagnostics[] = new MappingDiagnostic(
                    'dependency_value_collection_mismatch',
                    'Application and TemplateContract dependency value/collection shapes differ.',
                    ['source' => $source->canonical(), 'target' => $dependency->path()]
                );
                continue;
            }

            $projected = $projection->dependency($dependency->path());
            if ($projected?->applicability() === Applicability::NOT_APPLICABLE) {
                $diagnostics[] = new MappingDiagnostic(
                    'dependency_mapping_not_ready',
                    $projected->reason() ?? 'Dependency mapping is not applicable to this contract.',
                    ['target' => $dependency->path()]
                );
            } elseif ($projected?->applicability() === Applicability::UNKNOWN) {
                $deferred[] = new DeferredMappingCheck(
                    'dependency_mapping_readiness_unknown',
                    $projected->reason() ?? 'Dependency mapping readiness requires further inspection.',
                    ['target' => $dependency->path()]
                );
            }

            if ($targetIsCollection) {
                $targetCollectionMappings[$dependency->path()][] = $source->canonical();
            }
        }

        foreach ($dependencyRules as $target => $sources) {
            $this->appendConflict($diagnostics, 'duplicate_dependency_target', $target, $sources);
        }

        foreach ($definition->dependencies() as $mapping) {
            $target = $this->findDependency($contract, $mapping->dependencyPath());
            if ($target === null) {
                continue;
            }

            $sourceScopes = $mapping->source()->collectionPrefixes();
            $targetScopes = $this->templateCollectionScopes($target);
            if (count($sourceScopes) !== count($targetScopes)) {
                $diagnostics[] = new MappingDiagnostic(
                    'invalid_collection_scope_relationship',
                    'Application and template paths do not have matching collection-scope depth.',
                    ['source' => $mapping->source()->canonical(), 'target' => $target->path()]
                );
                continue;
            }
            foreach ($targetScopes as $index => $targetScope) {
                $scopeSources = $targetCollectionMappings[$targetScope] ?? [];
                if (!in_array($sourceScopes[$index], $scopeSources, true)) {
                    $diagnostics[] = new MappingDiagnostic(
                        'invalid_collection_scope_relationship',
                        'Nested collection mapping is not connected through the corresponding explicit parent scope.',
                        [
                            'source' => $mapping->source()->canonical(),
                            'target' => $target->path(),
                            'required_template_scope' => $targetScope,
                            'required_application_scope' => $sourceScopes[$index],
                        ]
                    );
                    break;
                }
            }
        }

        $this->validateNativeMappings($definition, $contract, $projection, $catalog, $diagnostics, $deferred);
        $this->validateDocumentMappings($definition, $projection, $catalog, $diagnostics);

        return new MappingValidationResult($diagnostics, $deferred);
    }

    /** @param list<MappingDiagnostic> $diagnostics */
    private function validateNativeMappings(
        MappingDefinition $definition,
        TemplateContract $contract,
        TemplateCapabilityProjection $projection,
        EngineCapabilityCatalog $catalog,
        array &$diagnostics,
        array &$deferred
    ): void {
        $targetSources = [];
        foreach ($definition->nativeObjectActions() as $mapping) {
            $key = $mapping->targetKind() . "\0" . $mapping->targetName() . "\0" . $mapping->actionId();
            $targetSources[$key]['target'] = $mapping->targetKind() . ':' . $mapping->targetName()
                . '/' . $mapping->actionId();
            $targetSources[$key]['sources'][] = $mapping->source()->canonical();

            $sameName = array_values(array_filter(
                $contract->nativeObjects(),
                static fn (NativeObjectDescriptor $object): bool => $object->name() === $mapping->targetName()
            ));
            if ($sameName === []) {
                $diagnostics[] = new MappingDiagnostic('unknown_native_object', 'Named native target does not exist in TemplateContract.', [
                    'kind' => $mapping->targetKind(), 'name' => $mapping->targetName(),
                ]);
                continue;
            }
            $sameKind = array_values(array_filter(
                $sameName,
                static fn (NativeObjectDescriptor $object): bool => $object->kind() === $mapping->targetKind()
            ));
            if ($sameKind === []) {
                $diagnostics[] = new MappingDiagnostic('native_object_kind_mismatch', 'Named native target exists with a different kind.', [
                    'kind' => $mapping->targetKind(), 'name' => $mapping->targetName(),
                    'actual_kinds' => implode(',', array_unique(array_map(
                        static fn (NativeObjectDescriptor $object): string => $object->kind(),
                        $sameName
                    ))),
                ]);
                continue;
            }
            if (count($sameKind) > 1) {
                $diagnostics[] = new MappingDiagnostic('ambiguous_native_object', 'Named native target is not unique in TemplateContract.', [
                    'kind' => $mapping->targetKind(), 'name' => $mapping->targetName(),
                ]);
                continue;
            }

            $action = $catalog->action($mapping->actionId());
            if ($action === null) {
                $diagnostics[] = new MappingDiagnostic('unsupported_native_action', 'Native action is not supported by this engine capability catalog.', [
                    'kind' => $mapping->targetKind(), 'name' => $mapping->targetName(), 'action' => $mapping->actionId(),
                ]);
                continue;
            }
            if ($action->targetKind() !== $mapping->targetKind()) {
                $diagnostics[] = new MappingDiagnostic('unsupported_native_action_for_kind', 'Supported action is not available for the requested native object kind.', [
                    'kind' => $mapping->targetKind(), 'name' => $mapping->targetName(), 'action' => $mapping->actionId(),
                    'required_kind' => $action->targetKind(),
                ]);
                continue;
            }

            $projected = $projection->nativeObject($mapping->targetKind(), $mapping->targetName());
            $projectedAction = $projected?->action($mapping->actionId());
            if ($projectedAction?->applicability() === Applicability::NOT_APPLICABLE) {
                $diagnostics[] = new MappingDiagnostic('native_action_not_applicable', $projectedAction->reason() ?? 'Action is not applicable to this source target.', [
                    'kind' => $mapping->targetKind(), 'name' => $mapping->targetName(), 'action' => $mapping->actionId(),
                ]);
            } elseif ($projectedAction?->applicability() === Applicability::UNKNOWN) {
                $deferred[] = new DeferredMappingCheck('native_action_applicability_unknown',
                    $projectedAction->reason() ?? 'Action applicability requires concrete preflight.', [
                        'kind' => $mapping->targetKind(), 'name' => $mapping->targetName(), 'action' => $mapping->actionId(),
                    ]);
            }
        }

        foreach ($targetSources as $entry) {
            $this->appendConflict($diagnostics, 'duplicate_native_action_target', $entry['target'], $entry['sources']);
        }
    }

    /** @param list<MappingDiagnostic> $diagnostics */
    private function validateDocumentMappings(
        MappingDefinition $definition,
        TemplateCapabilityProjection $projection,
        EngineCapabilityCatalog $catalog,
        array &$diagnostics
    ): void {
        $targets = [];
        foreach ($definition->documentCapabilities() as $mapping) {
            $key = $mapping->group() . "\0" . $mapping->target();
            $targets[$key]['target'] = $mapping->group() . '.' . $mapping->target();
            $targets[$key]['sources'][] = $mapping->source()->canonical();
            if (!$catalog->supportsDocumentTarget($mapping->group(), $mapping->target())
                || $projection->documentCapability($mapping->group(), $mapping->target()) === null
            ) {
                $diagnostics[] = new MappingDiagnostic('unsupported_document_capability', 'Document capability target is not supported.', [
                    'group' => $mapping->group(), 'target' => $mapping->target(),
                ]);
            }
        }

        foreach ($targets as $entry) {
            $this->appendConflict($diagnostics, 'duplicate_document_capability_target', $entry['target'], $entry['sources']);
        }
    }

    /** @param list<MappingDiagnostic> $diagnostics
     *  @param list<string> $sources
     */
    private function appendConflict(array &$diagnostics, string $code, string $target, array $sources): void
    {
        $uniqueSources = array_values(array_unique($sources));
        if (count($uniqueSources) > 1) {
            $diagnostics[] = new MappingDiagnostic($code, 'Multiple explicit sources target the same mutation target.', [
                'target' => $target,
                'sources' => implode(',', $uniqueSources),
            ]);
        }
    }

    private function findDependency(TemplateContract $contract, string $path): ?DependencyDescriptor
    {
        foreach ($contract->dependencies() as $dependency) {
            if ($dependency->path() === $path) {
                return $dependency;
            }
        }

        return null;
    }

    /** @return list<string> */
    private function templateCollectionScopes(DependencyDescriptor $dependency): array
    {
        $prefix = $dependency->scope()->pathPrefix();
        $scopes = [];
        if ($prefix !== '') {
            $parts = [];
            foreach (explode('.', $prefix) as $part) {
                $parts[] = $part;
                $scopes[] = implode('.', $parts);
            }
        }

        if ($dependency->kind() === 'COLLECTION') {
            $scopes[] = $dependency->path();
        }

        return $scopes;
    }
}
