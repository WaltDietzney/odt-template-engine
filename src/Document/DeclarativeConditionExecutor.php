<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Document;

use OdtTemplateEngine\OdtDocumentContext;
use OdtTemplateEngine\Template\ConditionExpression;
use OdtTemplateEngine\Template\ControlDescriptor;
use OdtTemplateEngine\Template\DataScopeDescriptor;
use OdtTemplateEngine\Template\DependencyDescriptor;
use OdtTemplateEngine\Template\NativeObjectDescriptor;
use OdtTemplateEngine\Template\TemplateContract;

/**
 * Executes recognized native Section controls in bounded regions.
 *
 * @internal
 */
final class DeclarativeConditionExecutor
{
    public function __construct(
        private SectionWorkingTargetResolver $resolver = new SectionWorkingTargetResolver(),
        private SectionRemovalService $removal = new SectionRemovalService(),
        private SectionInstantiationService $instances = new SectionInstantiationService()
    ) {
    }

    /** @param array<string, mixed> $values */
    public function execute(
        OdtDocumentContext $context,
        TemplateContract $contract,
        array $values
    ): void {
        $nativeObjects = [];
        foreach ($contract->nativeObjects() as $nativeObject) {
            $nativeObjects[$nativeObject->id()] = $nativeObject;
        }

        $controls = array_values(array_filter(
            $contract->controls(),
            static fn ($control): bool => $control instanceof ControlDescriptor
                && $control->representation() === 'NATIVE_SECTION_DECLARATION'
                && $control->supportState() === 'RECOGNIZED'
                && in_array($control->kind(), ['IF', 'IFNOT', 'FOREACH'], true)
        ));
        $controlIds = [];
        foreach ($controls as $control) {
            $controlIds[$this->carrier($control, $nativeObjects)->id()] = true;
        }

        $children = [];
        $roots = [];
        foreach ($controls as $control) {
            $carrier = $this->carrier($control, $nativeObjects);
            $parentId = null;
            foreach ($carrier->ownerIds() as $ownerId) {
                if (isset($controlIds[$ownerId])) {
                    // Phase-B ownerIds are ordered outermost to innermost.
                    // The last matching control is the direct owner.
                    $parentId = $ownerId;
                }
            }

            if ($parentId === null) {
                $roots[] = $control;
            } else {
                $children[$parentId][] = $control;
            }
        }

        $rootScope = DataScopeDescriptor::root();
        foreach ($roots as $control) {
            $this->executeControl(
                $context,
                $control,
                $children,
                $nativeObjects,
                $values,
                $rootScope,
                null,
                null,
                $contract
            );
        }
    }

    /**
     * @param array<string, list<ControlDescriptor>> $children
     * @param array<string, NativeObjectDescriptor> $nativeObjects
     * @param array<string, mixed> $values
     */
    private function executeControl(
        OdtDocumentContext $context,
        ControlDescriptor $control,
        array $children,
        array $nativeObjects,
        array $values,
        DataScopeDescriptor $scope,
        ?SectionWorkingTarget $owner,
        ?NativeObjectDescriptor $ownerCarrier,
        TemplateContract $contract
    ): void {
        $carrier = $this->carrier($control, $nativeObjects);
        if ($control->scope()->id() !== $scope->id()) {
            throw new DeclarativeConditionExecutionException(
                sprintf(
                    'control %s has scope %s but execution is in scope %s',
                    $control->id(),
                    $control->scope()->pathPrefix(),
                    $scope->pathPrefix()
                )
            );
        }

        try {
            $target = $owner === null
                ? $this->resolver->resolve($context, $carrier)
                : $this->resolver->resolveWithin(
                    $owner,
                    $carrier,
                    $this->identitySuffix($ownerCarrier, $owner)
                );
        } catch (\Throwable $exception) {
            if ($exception instanceof DeclarativeConditionExecutionException) {
                throw $exception;
            }
            throw new DeclarativeConditionExecutionException(
                'could not resolve native Section ' . ($carrier->name() ?? '<unnamed>'),
                0,
                $exception
            );
        }

        if ($control->kind() === 'FOREACH') {
            $this->executeForeach(
                $context,
                $control,
                $carrier,
                $target,
                $children,
                $nativeObjects,
                $values,
                $contract
            );
            return;
        }

        $expression = $this->conditionExpression($carrier);
        try {
            $condition = ConditionExpression::parse($expression)->evaluate($values);
        } catch (\Throwable $exception) {
            throw new DeclarativeConditionExecutionException(
                'condition evaluation failed for ' . $carrier->name(),
                0,
                $exception
            );
        }

        $keep = $control->kind() === 'IFNOT' ? !$condition : (bool) $condition;
        if (!$keep) {
            $this->removal->remove($target->section());
            return;
        }

        $this->instances->bindWorkingTargetScalars($target, $values);
        foreach ($children[$carrier->id()] ?? [] as $child) {
            $this->executeControl(
                $context,
                $child,
                $children,
                $nativeObjects,
                $values,
                $scope,
                $target,
                $carrier,
                $contract
            );
        }
    }

    /**
     * @param array<string, list<ControlDescriptor>> $children
     * @param array<string, NativeObjectDescriptor> $nativeObjects
     * @param array<string, mixed> $values
     */
    private function executeForeach(
        OdtDocumentContext $context,
        ControlDescriptor $control,
        NativeObjectDescriptor $carrier,
        SectionWorkingTarget $target,
        array $children,
        array $nativeObjects,
        array $values,
        TemplateContract $contract
    ): void {
        $dependency = $this->dependency($control, $carrier, $contract);
        $name = $dependency->name();
        if (!array_key_exists($name, $values)) {
            throw new DeclarativeForeachExecutionException(
                $carrier->name() ?? '<unnamed>',
                $dependency->path(),
                'missing collection dependency'
            );
        }

        $collection = $values[$name];
        if (!is_array($collection)) {
            throw new DeclarativeForeachExecutionException(
                $carrier->name() ?? '<unnamed>',
                $dependency->path(),
                $collection === null ? 'collection is null' : 'collection must be an array'
            );
        }

        $createdScope = $control->createdScope();
        if ($createdScope === null) {
            throw new DeclarativeForeachExecutionException(
                $carrier->name() ?? '<unnamed>',
                $dependency->path(),
                'collection item scope is unavailable'
            );
        }

        foreach ($collection as $item) {
            if (!is_array($item)) {
                throw new DeclarativeForeachExecutionException(
                    $carrier->name() ?? '<unnamed>',
                    $dependency->path(),
                    'collection item must be an associative array'
                );
            }

            $clone = $this->instances->instantiateWorkingTarget($target, $item);
            $cloneTarget = new SectionWorkingTarget(
                $target->document(),
                $target->regionRoot(),
                $clone,
                $target->provenance(),
                $target->nativeObjectId()
            );

            foreach ($children[$carrier->id()] ?? [] as $child) {
                $this->executeControl(
                    $context,
                    $child,
                    $children,
                    $nativeObjects,
                    $item,
                    $createdScope,
                    $cloneTarget,
                    $carrier,
                    $contract
                );
            }
        }

        $this->removal->remove($target->section());
    }

    /** @param array<string, NativeObjectDescriptor> $nativeObjects */
    private function carrier(ControlDescriptor $control, array $nativeObjects): NativeObjectDescriptor
    {
        $id = $control->carrierNativeObjectId();
        if ($id === null || !isset($nativeObjects[$id])) {
            throw new DeclarativeConditionExecutionException(
                'recognized native control has no resolvable Section carrier'
            );
        }

        return $nativeObjects[$id];
    }

    private function conditionExpression(NativeObjectDescriptor $carrier): string
    {
        $name = $carrier->name();
        if ($name === null || preg_match('/^#(?:if|ifnot):(.+)$/', $name, $match) !== 1) {
            throw new DeclarativeConditionExecutionException('conditional Section has invalid declaration name');
        }

        $expression = trim($match[1]);
        if ($expression === '') {
            throw new DeclarativeConditionExecutionException('conditional Section has empty condition expression');
        }

        return $expression;
    }

    private function dependency(
        ControlDescriptor $control,
        NativeObjectDescriptor $carrier,
        TemplateContract $contract
    ): DependencyDescriptor {
        $dependencyId = $control->dependencyIds()[0] ?? null;
        foreach ($contract->dependencies() as $dependency) {
            if ($dependency->id() === $dependencyId && $dependency->kind() === 'COLLECTION') {
                return $dependency;
            }
        }

        throw new DeclarativeForeachExecutionException(
            $carrier->name() ?? '<unnamed>',
            (string) $dependencyId,
            'collection dependency is unavailable'
        );
    }

    private function identitySuffix(
        ?NativeObjectDescriptor $ownerCarrier,
        SectionWorkingTarget $owner
    ): string {
        if ($ownerCarrier === null || $ownerCarrier->name() === null) {
            return '';
        }

        $physicalName = $owner->name();
        $prototypeName = $ownerCarrier->name();
        if (!str_starts_with($physicalName, $prototypeName)) {
            throw new DeclarativeConditionExecutionException(
                'working Section identity does not match its contract carrier'
            );
        }

        return substr($physicalName, strlen($prototypeName));
    }
}
