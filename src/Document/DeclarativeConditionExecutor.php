<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Document;

use OdtTemplateEngine\OdtDocumentContext;
use OdtTemplateEngine\Template\ConditionExpression;
use OdtTemplateEngine\Template\ControlDescriptor;
use OdtTemplateEngine\Template\DataScopeDescriptor;
use OdtTemplateEngine\Template\NativeObjectDescriptor;
use OdtTemplateEngine\Template\TemplateContract;

/**
 * Executes recognized native #if/#ifnot Section controls in bounded regions.
 *
 * @internal
 */
final class DeclarativeConditionExecutor
{
    public function __construct(
        private SectionWorkingTargetResolver $resolver = new SectionWorkingTargetResolver(),
        private SectionRemovalService $removal = new SectionRemovalService()
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
                && in_array($control->kind(), ['IF', 'IFNOT'], true)
        ));
        foreach ($controls as $control) {
            if ($control->scope()->kind() !== DataScopeDescriptor::ROOT) {
                throw new DeclarativeConditionExecutionException(
                    'conditional Section requires an unsupported non-root data scope'
                );
            }
        }
        $controlIds = array_fill_keys(array_map(
            static fn (ControlDescriptor $control): string => $control->carrierNativeObjectId() ?? '',
            $controls
        ), true);
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

        foreach ($roots as $control) {
            $this->executeControl($context, $control, $children, $nativeObjects, $values, null);
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
        ?SectionWorkingTarget $owner
    ): void {
        $carrier = $this->carrier($control, $nativeObjects);
        try {
            $target = $owner === null
                ? $this->resolver->resolve($context, $carrier)
                : $this->resolver->resolveWithin($owner, $carrier);
        } catch (\Throwable $exception) {
            if ($exception instanceof DeclarativeConditionExecutionException) {
                throw $exception;
            }
            throw new DeclarativeConditionExecutionException(
                'could not resolve conditional Section ' . ($carrier->name() ?? '<unnamed>'),
                0,
                $exception
            );
        }

        $expression = $this->expression($carrier);
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

        foreach ($children[$carrier->id()] ?? [] as $child) {
            $this->executeControl($context, $child, $children, $nativeObjects, $values, $target);
        }
    }

    /** @param array<string, NativeObjectDescriptor> $nativeObjects */
    private function carrier(ControlDescriptor $control, array $nativeObjects): NativeObjectDescriptor
    {
        $id = $control->carrierNativeObjectId();
        if ($id === null || !isset($nativeObjects[$id])) {
            throw new DeclarativeConditionExecutionException(
                'recognized conditional control has no resolvable native Section carrier'
            );
        }

        return $nativeObjects[$id];
    }

    private function expression(NativeObjectDescriptor $carrier): string
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
}
