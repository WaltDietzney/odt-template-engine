<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Mapping;

use OdtTemplateEngine\Template\BindingDescriptor;
use OdtTemplateEngine\Template\ControlDescriptor;
use OdtTemplateEngine\Template\DependencyDescriptor;
use OdtTemplateEngine\Template\TemplateContract;

/** @internal Applies concrete requirements derived from existing contract consumers. */
final class DependencyConcretePreflightValidator
{
    public function validate(
        DependencyMappingResolution $resolution,
        TemplateContract $contract
    ): ConcretePreflightOperation {
        $target = $resolution->target();
        $consumers = $this->dependencyConsumers($contract)[$target->id()] ?? [];
        $diagnostics = $this->diagnostics($resolution, $consumers);

        return new ConcretePreflightOperation(
            'dependency',
            $target->path(),
            $resolution,
            EngineCapabilityCatalog::phaseE1()->dependencyAutomation()->id(),
            $this->payloadKind($target, $consumers),
            null,
            $diagnostics === [] ? ConcretePreflightOperation::READY : ConcretePreflightOperation::ERROR,
            $diagnostics
        );
    }

    /** @return array<string, list<BindingDescriptor|ControlDescriptor>> */
    private function dependencyConsumers(TemplateContract $contract): array
    {
        $consumers = [];
        foreach ($contract->bindings() as $binding) {
            if ($binding->dependencyId() !== null) {
                $consumers[$binding->dependencyId()][] = $binding;
            }
        }
        foreach ($contract->controls() as $control) {
            if (!$control instanceof ControlDescriptor) {
                continue;
            }
            foreach ($control->dependencyIds() as $dependencyId) {
                $consumers[$dependencyId][] = $control;
            }
        }
        return $consumers;
    }

    /** @param list<BindingDescriptor|ControlDescriptor> $consumers
     *  @return list<ConcretePreflightDiagnostic>
     */
    private function diagnostics(DependencyMappingResolution $resolution, array $consumers): array
    {
        $dependency = $resolution->target();
        $identity = $dependency->path();
        if ($resolution->status() === DependencyMappingResolution::UNRESOLVED) {
            return [$this->diagnostic(
                'UNRESOLVED_APPLICATION_SOURCE',
                'No explicit or permitted scoped same-name application source resolves this dependency.',
                $identity
            )];
        }

        $source = $resolution->source()?->canonical();
        $data = $resolution->dataResolution();
        if ($data === null) {
            return [$this->diagnostic(
                'MISSING_APPLICATION_RESOLUTION',
                'Resolved dependency has no data result.',
                $identity,
                $source
            )];
        }

        $pureCondition = $consumers !== [];
        foreach ($consumers as $consumer) {
            if (!$consumer instanceof ControlDescriptor || !in_array($consumer->kind(), ['IF', 'IFNOT'], true)) {
                $pureCondition = false;
            }
        }

        $diagnostics = [];
        if ($dependency->kind() === 'COLLECTION' && $resolution->source() !== null) {
            $this->validateCollectionRecords(
                $data,
                $this->collectionDepth($resolution->source()),
                $identity,
                $source,
                [],
                $diagnostics
            );
        }
        $this->walk($data, $dependency, $consumers, $pureCondition, $identity, $source, [], $diagnostics);
        return $diagnostics;
    }

    /** @param list<BindingDescriptor|ControlDescriptor> $consumers
     *  @param list<int> $itemPath
     *  @param list<ConcretePreflightDiagnostic> $diagnostics
     */
    private function walk(
        ApplicationDataResolution $node,
        DependencyDescriptor $dependency,
        array $consumers,
        bool $pureCondition,
        string $identity,
        ?string $source,
        array $itemPath,
        array &$diagnostics
    ): void {
        $status = $node->status();
        if ($status === ApplicationDataResolution::MISSING) {
            $diagnostics[] = $this->diagnostic('MISSING_SOURCE_VALUE', 'The resolved application source is missing.', $identity, $source, $itemPath);
            return;
        }
        if ($status === ApplicationDataResolution::NULL) {
            if ($dependency->kind() === 'COLLECTION' || !$pureCondition) {
                $diagnostics[] = $this->diagnostic('NULL_SOURCE_VALUE', 'Null is not accepted by this dependency consumer.', $identity, $source, $itemPath);
            }
            return;
        }
        if ($status === ApplicationDataResolution::WRONG_SHAPE) {
            if ($node->items() !== []) {
                foreach ($node->items() as $item) {
                    $this->walk(
                        $item,
                        $dependency,
                        $consumers,
                        $pureCondition,
                        $identity,
                        $source,
                        [...$itemPath, $item->itemIndex() ?? 0],
                        $diagnostics
                    );
                }
            } else {
                $diagnostics[] = $this->diagnostic('WRONG_APPLICATION_SHAPE', 'The resolved application value has the wrong shape.', $identity, $source, $itemPath);
            }
            return;
        }
        if ($status === ApplicationDataResolution::EMPTY_COLLECTION) {
            if ($dependency->kind() !== 'COLLECTION' || !$this->hasConsumerKind($consumers, ['FOREACH'])) {
                $diagnostics[] = $this->diagnostic('WRONG_APPLICATION_SHAPE', 'An empty collection is valid only for a collection dependency.', $identity, $source, $itemPath);
            }
            return;
        }

        if ($dependency->kind() === 'COLLECTION') {
            foreach ($node->items() as $item) {
                $this->walk(
                    $item,
                    $dependency,
                    $consumers,
                    $pureCondition,
                    $identity,
                    $source,
                    [...$itemPath, $item->itemIndex() ?? 0],
                    $diagnostics
                );
            }
            return;
        }

        if ($node->items() !== []) {
            foreach ($node->items() as $item) {
                $this->walk(
                    $item,
                    $dependency,
                    $consumers,
                    $pureCondition,
                    $identity,
                    $source,
                    [...$itemPath, $item->itemIndex() ?? 0],
                    $diagnostics
                );
            }
            return;
        }

        foreach ($consumers as $consumer) {
            if (!$consumer instanceof BindingDescriptor) {
                continue;
            }
            $kind = $consumer->kind();
            $value = $node->value();
            $accepted = match ($kind) {
                'SCALAR', 'FILTERED_SCALAR' => is_string($value) || is_int($value) || is_float($value) || is_bool($value),
                'SPECIAL', 'NATIVE_USER_FIELD_DECLARATION', 'NATIVE_USER_FIELD_REFERENCE' => is_string($value),
                default => false,
            };
            if (!$accepted) {
                $diagnostics[] = $this->diagnostic(
                    'INCOMPATIBLE_DEPENDENCY_PAYLOAD',
                    sprintf('Value is incompatible with the %s consumer.', $kind),
                    $identity,
                    $source,
                    $itemPath,
                    ['consumer_kind' => $kind, 'filter' => $consumer->filterName()]
                );
            }
        }
    }

    /** @param list<int> $itemPath @param list<ConcretePreflightDiagnostic> $diagnostics */
    private function validateCollectionRecords(
        ApplicationDataResolution $node,
        int $remainingCollectionDepth,
        string $identity,
        ?string $source,
        array $itemPath,
        array &$diagnostics
    ): void {
        if ($remainingCollectionDepth <= 0 || $node->items() === []) {
            return;
        }
        foreach ($node->items() as $item) {
            $path = [...$itemPath, $item->itemIndex() ?? 0];
            if ($remainingCollectionDepth === 1) {
                $value = $item->value();
                if ($item->status() === ApplicationDataResolution::PRESENT
                    && (!is_array($value) || !$this->isNamedRecord($value))
                ) {
                    $diagnostics[] = $this->diagnostic(
                        'INVALID_COLLECTION_ITEM_RECORD',
                        'Foreach items must be arrays with string keys only (empty records are valid).',
                        $identity,
                        $source,
                        $path
                    );
                }
                continue;
            }
            $this->validateCollectionRecords(
                $item,
                $remainingCollectionDepth - 1,
                $identity,
                $source,
                $path,
                $diagnostics
            );
        }
    }

    private function collectionDepth(ApplicationPath $path): int
    {
        return count(array_filter(
            $path->segments(),
            static fn (ApplicationPathSegment $segment): bool => $segment->isCollection()
        ));
    }

    /** @param list<BindingDescriptor|ControlDescriptor> $consumers @param list<string> $kinds */
    private function hasConsumerKind(array $consumers, array $kinds): bool
    {
        foreach ($consumers as $consumer) {
            if ($consumer instanceof ControlDescriptor && in_array($consumer->kind(), $kinds, true)) {
                return true;
            }
        }
        return false;
    }

    /** @param list<BindingDescriptor|ControlDescriptor> $consumers */
    private function payloadKind(DependencyDescriptor $dependency, array $consumers): string
    {
        if ($dependency->kind() === 'COLLECTION') {
            return 'NAMED_RECORD_COLLECTION';
        }
        foreach ($consumers as $consumer) {
            if ($consumer instanceof BindingDescriptor
                && in_array($consumer->kind(), ['SPECIAL', 'NATIVE_USER_FIELD_DECLARATION', 'NATIVE_USER_FIELD_REFERENCE'], true)
            ) {
                return 'STRING';
            }
        }
        foreach ($consumers as $consumer) {
            if ($consumer instanceof BindingDescriptor && in_array($consumer->kind(), ['SCALAR', 'FILTERED_SCALAR'], true)) {
                return 'SCALAR';
            }
        }
        foreach ($consumers as $consumer) {
            if ($consumer instanceof ControlDescriptor && in_array($consumer->kind(), ['IF', 'IFNOT'], true)) {
                return 'CONDITION_VALUE';
            }
        }
        return 'DEPENDENCY_VALUE';
    }

    /** @param list<int> $itemPath @param array<string, scalar|null> $extra */
    private function diagnostic(
        string $code,
        string $message,
        string $identity,
        ?string $source = null,
        array $itemPath = [],
        array $extra = []
    ): ConcretePreflightDiagnostic {
        if ($itemPath !== []) {
            $extra['item_path'] = implode('.', $itemPath);
        }
        return new ConcretePreflightDiagnostic($code, $message, 'dependency', $identity, $source, $extra);
    }

    private function isNamedRecord(array $value): bool
    {
        foreach (array_keys($value) as $key) {
            if (!is_string($key)) {
                return false;
            }
        }
        return true;
    }
}
