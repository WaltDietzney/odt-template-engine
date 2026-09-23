<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Document;

use DOMDocument;
use DOMElement;
use OdtTemplateEngine\OdtDocumentContext;
use OdtTemplateEngine\Mapping\DependencyScopeProjection;
use OdtTemplateEngine\Mapping\ProjectedDependencyValue;
use OdtTemplateEngine\Mapping\ApplicationDataResolution;
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
    private const TEXT_NAMESPACE = 'urn:oasis:names:tc:opendocument:xmlns:text:1.0';

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
        $contentSnapshot = $this->snapshot($context->contentDom());
        $stylesSnapshot = $this->snapshot($context->stylesDom());

        try {
            $this->executeWithoutRollback($context, $contract, $values);
        } catch (\Throwable $exception) {
            try {
                $this->restore($context->contentDom(), $contentSnapshot);
                $this->restore($context->stylesDom(), $stylesSnapshot);
            } catch (\Throwable $rollbackException) {
                throw new DeclarativeConditionExecutionException(
                    'declarative execution rollback failed: ' . $rollbackException->getMessage(),
                    0,
                    $exception
                );
            }

            throw $exception;
        }
    }

    /**
     * Execute the recognized structural controls using already projected template scopes.
     *
     * @param callable(string, array<string, mixed>): bool $evaluateCondition
     * @param callable(DOMElement, DependencyScopeProjection, list<int>): void $completeScope
     * @internal
     */
    public function executeProjected(
        OdtDocumentContext $context,
        TemplateContract $contract,
        DependencyScopeProjection $rootScope,
        callable $evaluateCondition,
        callable $completeScope
    ): void {
        $contentSnapshot = $this->snapshot($context->contentDom());
        $stylesSnapshot = $this->snapshot($context->stylesDom());
        try {
            $this->executeProjectedWithoutRollback(
                $context,
                $contract,
                $rootScope,
                $evaluateCondition,
                $completeScope
            );
        } catch (\Throwable $exception) {
            try {
                $this->restore($context->contentDom(), $contentSnapshot);
                $this->restore($context->stylesDom(), $stylesSnapshot);
            } catch (\Throwable $rollbackException) {
                throw new DeclarativeConditionExecutionException(
                    'declarative execution rollback failed: ' . $rollbackException->getMessage(),
                    0,
                    $exception
                );
            }
            throw $exception;
        }
    }

    private function executeProjectedWithoutRollback(
        OdtDocumentContext $context,
        TemplateContract $contract,
        DependencyScopeProjection $rootScope,
        callable $evaluateCondition,
        callable $completeScope
    ): void {
        [$roots, $children, $nativeObjects] = $this->controlTree($contract);
        foreach ($roots as $control) {
            $this->executeProjectedControl(
                $context,
                $control,
                $children,
                $nativeObjects,
                $rootScope,
                null,
                null,
                [],
                $evaluateCondition,
                $completeScope
            );
        }
    }

    /**
     * @param array<string, list<ControlDescriptor>> $children
     * @param array<string, NativeObjectDescriptor> $nativeObjects
     */
    private function executeProjectedControl(
        OdtDocumentContext $context,
        ControlDescriptor $control,
        array $children,
        array $nativeObjects,
        DependencyScopeProjection $scope,
        ?SectionWorkingTarget $owner,
        ?NativeObjectDescriptor $ownerCarrier,
        array $identityIndexes,
        callable $evaluateCondition,
        callable $completeScope
    ): void {
        $carrier = $this->carrier($control, $nativeObjects);
        if ($control->scope()->id() !== $scope->scope()->id()) {
            throw new DeclarativeConditionExecutionException('control and projected template scopes do not match');
        }
        $target = $owner === null
            ? $this->resolver->resolve($context, $carrier)
            : $this->resolver->resolveWithin($owner, $carrier, $this->identitySuffix($ownerCarrier, $owner));

        if ($control->kind() === 'FOREACH') {
            $dependencyId = $control->dependencyIds()[0] ?? null;
            $collectionValue = $dependencyId === null ? null : $scope->dependency($dependencyId);
            if (!$collectionValue instanceof ProjectedDependencyValue
                || $collectionValue->target()->kind() !== 'COLLECTION'
                || !in_array($collectionValue->status(), [
                    ApplicationDataResolution::PRESENT,
                    ApplicationDataResolution::EMPTY_COLLECTION,
                ], true)
            ) {
                throw new DeclarativeForeachExecutionException(
                    $carrier->name() ?? '<unnamed>',
                    $collectionValue instanceof ProjectedDependencyValue ? $collectionValue->target()->path() : (string) $dependencyId,
                    'collection is not represented by a valid projected collection dependency'
                );
            }
            $items = $scope->collectionItems((string) $dependencyId);
            $anchor = null;
            foreach ($items as $itemScope) {
                $clone = $this->instances->cloneWorkingTarget($target, $anchor);
                $anchor = $clone;
                $cloneTarget = new SectionWorkingTarget(
                    $target->document(),
                    $target->regionRoot(),
                    $clone,
                    $target->provenance(),
                    $target->nativeObjectId()
                );
                $localIndex = $this->localCloneIndex($target, $clone);
                $itemIdentityIndexes = [...$identityIndexes, $localIndex];
                foreach ($children[$carrier->id()] ?? [] as $child) {
                    $this->executeProjectedControl(
                        $context,
                        $child,
                        $children,
                        $nativeObjects,
                        $itemScope,
                        $cloneTarget,
                        $carrier,
                        $itemIdentityIndexes,
                        $evaluateCondition,
                        $completeScope
                    );
                }
                $completeScope($clone, $itemScope, $itemIdentityIndexes);
            }
            $this->removal->remove($target->section());
            return;
        }

        $expression = $this->conditionExpression($carrier);
        $values = $this->scopeValues($scope);
        $condition = $evaluateCondition($expression, $values);
        $keep = $control->kind() === 'IFNOT' ? !$condition : $condition;
        if (!$keep) {
            $this->removal->remove($target->section());
            return;
        }
        foreach ($children[$carrier->id()] ?? [] as $child) {
            $this->executeProjectedControl(
                $context,
                $child,
                $children,
                $nativeObjects,
                $scope,
                $target,
                $carrier,
                $identityIndexes,
                $evaluateCondition,
                $completeScope
            );
        }
        $completeScope($target->section(), $scope, $identityIndexes);
    }

    /** @return array<string, mixed> */
    private function scopeValues(DependencyScopeProjection $scope): array
    {
        $values = [];
        foreach ($scope->dependencies() as $projected) {
            if ($projected->target()->kind() === 'VALUE') {
                $values[$projected->target()->name()] = $projected->value();
            }
        }
        return $values;
    }

    private function localCloneIndex(SectionWorkingTarget $prototype, DOMElement $clone): int
    {
        $suffix = substr($clone->getAttribute('text:name'), strlen($prototype->name()));
        if (preg_match('/^_(\d+)$/', $suffix, $match) !== 1) {
            throw new DeclarativeConditionExecutionException('cloned Section has an invalid local identity suffix');
        }
        return (int) $match[1];
    }

    /** @return array{0:list<ControlDescriptor>,1:array<string,list<ControlDescriptor>>,2:array<string,NativeObjectDescriptor>} */
    private function controlTree(TemplateContract $contract): array
    {
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
                    $parentId = $ownerId;
                }
            }
            if ($parentId === null) {
                $roots[] = $control;
            } else {
                $children[$parentId][] = $control;
            }
        }
        return [$roots, $children, $nativeObjects];
    }

    /** @param array<string, mixed> $values */
    private function executeWithoutRollback(
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

    private function snapshot(DOMDocument $document): DOMDocument
    {
        $snapshot = $document->cloneNode(true);
        if (!$snapshot instanceof DOMDocument) {
            throw new DeclarativeConditionExecutionException('working document could not be snapshotted');
        }

        return $snapshot;
    }

    private function restore(DOMDocument $document, DOMDocument $snapshot): void
    {
        $targetRoot = $document->documentElement;
        $snapshotRoot = $snapshot->documentElement;
        if (!$targetRoot instanceof DOMElement || !$snapshotRoot instanceof DOMElement) {
            throw new DeclarativeConditionExecutionException('working document could not be restored');
        }

        while ($targetRoot->hasAttributes()) {
            $attribute = $targetRoot->attributes?->item(0);
            if ($attribute === null) {
                break;
            }
            if ($attribute->namespaceURI !== null) {
                $targetRoot->removeAttributeNS($attribute->namespaceURI, $attribute->localName);
            } else {
                $targetRoot->removeAttribute($attribute->name);
            }
        }
        if ($snapshotRoot->hasAttributes()) {
            foreach ($snapshotRoot->attributes as $attribute) {
                if ($attribute->namespaceURI !== null) {
                    $targetRoot->setAttributeNS(
                        $attribute->namespaceURI,
                        $attribute->nodeName,
                        $attribute->nodeValue ?? ''
                    );
                } else {
                    $targetRoot->setAttribute($attribute->name, $attribute->nodeValue ?? '');
                }
            }
        }

        while ($targetRoot->firstChild !== null) {
            $targetRoot->removeChild($targetRoot->firstChild);
        }
        foreach ($snapshotRoot->childNodes as $child) {
            $targetRoot->appendChild($document->importNode($child, true));
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

        $this->normalizeTrueConditionalVisibility($target->section());
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
     * A recognized conditional Section owns result visibility after evaluation.
     * Preserve unrelated native conditional attributes and ordinary Sections.
     */
    private function normalizeTrueConditionalVisibility(DOMElement $section): void
    {
        $display = strtolower(trim($section->getAttributeNS(self::TEXT_NAMESPACE, 'display')));
        if ($display === 'none') {
            $section->removeAttributeNS(self::TEXT_NAMESPACE, 'display');
        }

        $hidden = strtolower(trim($section->getAttributeNS(self::TEXT_NAMESPACE, 'is-hidden')));
        if ($hidden === 'true') {
            $section->removeAttributeNS(self::TEXT_NAMESPACE, 'is-hidden');
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

        $insertionAnchor = null;
        foreach ($collection as $item) {
            if (!is_array($item) || !$this->hasOnlyStringKeys($item)) {
                throw new DeclarativeForeachExecutionException(
                    $carrier->name() ?? '<unnamed>',
                    $dependency->path(),
                    'collection item must be a named record with string keys'
                );
            }

            $clone = $this->instances->instantiateWorkingTarget($target, $item, $insertionAnchor);
            $insertionAnchor = $clone;
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

    /** @param array<mixed, mixed> $item */
    private function hasOnlyStringKeys(array $item): bool
    {
        foreach (array_keys($item) as $key) {
            if (!is_string($key)) {
                return false;
            }
        }

        return true;
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
