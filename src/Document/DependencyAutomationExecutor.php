<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Document;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;
use OdtTemplateEngine\Mapping\ConcretePreflightResult;
use OdtTemplateEngine\Mapping\DependencyScopeProjection;
use OdtTemplateEngine\Mapping\DependencyScopeProjector;
use OdtTemplateEngine\Mapping\ProjectedDependencyValue;
use OdtTemplateEngine\OdtDocumentContext;
use OdtTemplateEngine\Template\BindingDescriptor;
use OdtTemplateEngine\Template\ControlDescriptor;
use OdtTemplateEngine\Template\DataScopeDescriptor;
use OdtTemplateEngine\Template\DependencyDescriptor;
use OdtTemplateEngine\Template\SourceProvenance;
use OdtTemplateEngine\Template\TemplateContract;
use OdtTemplateEngine\Template\TemplateProcessor;

/** Executes READY dependency consumers through their established mutation owners. */
final class DependencyAutomationExecutor
{
    private const CLASSIC_KINDS = ['SCALAR', 'FILTERED_SCALAR', 'SPECIAL'];
    private const USER_FIELD_KINDS = ['NATIVE_USER_FIELD_DECLARATION', 'NATIVE_USER_FIELD_REFERENCE'];
    private const OFFICE_NAMESPACE = 'urn:oasis:names:tc:opendocument:xmlns/office:1.0';
    private const STYLE_NAMESPACE = 'urn:oasis:names:tc:opendocument:xmlns:style:1.0';

    public function __construct(
        private readonly DependencyScopeProjector $projector = new DependencyScopeProjector(),
        private readonly DeclarativeConditionExecutor $structural = new DeclarativeConditionExecutor(),
        private readonly TemplateProcessor $processor = new TemplateProcessor()
    ) {
    }

    /**
     * @param callable(string,string):void $bindUserField
     * @param callable(string,string,?string):string $applyFilter
     * @param callable(string,array<string,mixed>):bool $evaluateCondition
     */
    public function execute(
        OdtDocumentContext $context,
        TemplateContract $contract,
        ConcretePreflightResult $preflight,
        callable $bindUserField,
        callable $applyFilter,
        callable $evaluateCondition
    ): void {
        $projection = $this->projector->project($contract, $preflight);
        $dependencies = $this->dependencyIndex($contract);
        $controlsByRegion = $this->recognizedControlNames($contract);
        $rootRegions = $this->rootClassicRegions($context, $contract, $dependencies, $controlsByRegion);

        // User Field ownership is evidence-based and precedes structural mutation.
        $boundFields = [];
        foreach ($contract->bindings() as $binding) {
            if (!$binding instanceof BindingDescriptor
                || !in_array($binding->kind(), self::USER_FIELD_KINDS, true)
                || $binding->supportState() !== 'SUPPORTED'
                || $binding->dependencyId() === null
            ) {
                continue;
            }
            $dependency = $dependencies[$binding->dependencyId()] ?? null;
            $value = $projection->dependency($binding->dependencyId());
            if (!$dependency instanceof DependencyDescriptor
                || $dependency->scope()->kind() !== DataScopeDescriptor::ROOT
                || !$value instanceof ProjectedDependencyValue
                || $value->status() !== 'PRESENT'
                || !is_string($value->value())
            ) {
                throw new \LogicException('READY User Field evidence has no projected ROOT string value.');
            }
            if (!isset($boundFields[$dependency->name()])) {
                $bindUserField($dependency->name(), $value->value());
                $boundFields[$dependency->name()] = true;
            }
        }

        $this->structural->executeProjected(
            $context,
            $contract,
            $projection,
            $evaluateCondition,
            function (DOMElement $node, DependencyScopeProjection $scope, array $indexes) use (
                $contract,
                $dependencies,
                $controlsByRegion,
                $context,
                $applyFilter
            ): void {
                $values = $this->classicValuesForScope($scope, $dependencies, $contract, $indexes);
                $specials = $this->specialNamesForScope($scope, $dependencies, $contract, $indexes);
                $controls = $controlsByRegion[$this->regionKeyForNode($node, $context) ?? ''] ?? [];
                $this->processOwnedTree($node, $values, $specials, $controls, $applyFilter);
            }
        );

        foreach ($rootRegions as $entry) {
            $values = $this->classicValuesForBindings($projection, $entry['bindings'], $dependencies);
            $specials = $this->specialNamesForBindings($entry['bindings'], $dependencies);
            $this->processOwnedTree($entry['root'], $values, $specials, $entry['controls'], $applyFilter);
        }
    }

    /** @return array<string,DependencyDescriptor> */
    private function dependencyIndex(TemplateContract $contract): array
    {
        $index = [];
        foreach ($contract->dependencies() as $dependency) {
            $index[$dependency->id()] = $dependency;
        }
        return $index;
    }

    /** @return array<string,list<string>> */
    private function recognizedControlNames(TemplateContract $contract): array
    {
        $native = [];
        foreach ($contract->nativeObjects() as $object) {
            $native[$object->id()] = $object;
        }
        $names = [];
        foreach ($contract->controls() as $control) {
            if (!$control instanceof ControlDescriptor
                || $control->representation() !== 'NATIVE_SECTION_DECLARATION'
                || !in_array($control->kind(), ['IF', 'IFNOT', 'FOREACH'], true)
            ) {
                continue;
            }
            if ($control->supportState() !== 'RECOGNIZED') {
                throw new \LogicException('TemplateContract contains an unsupported declarative control for a READY E3 invocation.');
            }
            $carrier = $native[$control->carrierNativeObjectId() ?? ''] ?? null;
            if ($carrier === null || $carrier->kind() !== 'section' || $carrier->name() === null) {
                throw new \LogicException('Recognized declarative control has no Section carrier evidence.');
            }
            $names[$this->regionKey($carrier->provenance())][] = $carrier->name();
        }
        foreach ($names as $key => $regionNames) {
            $names[$key] = array_values(array_unique($regionNames));
        }
        return $names;
    }

    /**
     * @param array<string,DependencyDescriptor> $dependencies
     * @return list<array{root:DOMElement,bindings:list<BindingDescriptor>,controls:list<string>}>
     */
    private function rootClassicRegions(
        OdtDocumentContext $context,
        TemplateContract $contract,
        array $dependencies,
        array $controlsByRegion
    ): array {
        $groups = [];
        foreach ($contract->bindings() as $binding) {
            if (!$binding instanceof BindingDescriptor
                || !in_array($binding->kind(), self::CLASSIC_KINDS, true)
                || $binding->supportState() !== 'SUPPORTED'
                || $binding->dependencyId() === null
            ) {
                continue;
            }
            $dependency = $dependencies[$binding->dependencyId()] ?? null;
            if (!$dependency instanceof DependencyDescriptor
                || $dependency->scope()->kind() !== DataScopeDescriptor::ROOT
            ) {
                continue;
            }
            $key = $this->regionKey($binding->provenance());
            $groups[$key]['provenance'] = $binding->provenance();
            $groups[$key]['bindings'][] = $binding;
        }

        $regions = [];
        foreach ($groups as $group) {
            $provenance = $group['provenance'];
            $root = $this->workingRegion($context, $provenance);
            $regions[] = [
                'root' => $root,
                'bindings' => $group['bindings'],
                'controls' => $controlsByRegion[$this->regionKey($provenance)] ?? [],
            ];
        }
        return $regions;
    }

    /**
     * @param array<string,DependencyDescriptor> $dependencies
     * @param list<int> $indexes
     * @return array<string,string>
     */
    private function classicValuesForScope(
        DependencyScopeProjection $scope,
        array $dependencies,
        TemplateContract $contract,
        array $indexes
    ): array {
        $ids = [];
        foreach ($contract->bindings() as $binding) {
            if (!$binding instanceof BindingDescriptor
                || !in_array($binding->kind(), self::CLASSIC_KINDS, true)
                || $binding->supportState() !== 'SUPPORTED'
                || $binding->dependencyId() === null
            ) {
                continue;
            }
            if (($dependencies[$binding->dependencyId()] ?? null)?->scope()->id() === $scope->scope()->id()) {
                $ids[$binding->dependencyId()] = true;
            }
        }
        $values = [];
        foreach (array_keys($ids) as $id) {
            $projected = $scope->dependency($id);
            $dependency = $dependencies[$id];
            if (!$projected instanceof ProjectedDependencyValue || $projected->status() !== 'PRESENT') {
                throw new \LogicException('READY classic consumer has no PRESENT value in its projected scope.');
            }
            $value = $projected->value();
            if (!is_string($value) && !is_int($value) && !is_float($value) && !is_bool($value)) {
                throw new \LogicException('READY classic consumer has an incompatible projected scalar value.');
            }
            $suffix = implode('', array_map(static fn (int $index): string => '_' . $index, $indexes));
            $values[$dependency->name() . $suffix] = (string) $value;
        }
        return $values;
    }

    /** @param list<BindingDescriptor> $bindings @param array<string,DependencyDescriptor> $dependencies @return array<string,string> */
    private function classicValuesForBindings(
        DependencyScopeProjection $projection,
        array $bindings,
        array $dependencies
    ): array {
        $values = [];
        foreach ($bindings as $binding) {
            $id = $binding->dependencyId();
            $dependency = $id === null ? null : ($dependencies[$id] ?? null);
            $projected = $id === null ? null : $projection->dependency($id);
            if (!$dependency instanceof DependencyDescriptor || !$projected instanceof ProjectedDependencyValue) {
                throw new \LogicException('Root classic binding is not represented in the READY dependency projection.');
            }
            $value = $projected->value();
            if (!is_string($value) && !is_int($value) && !is_float($value) && !is_bool($value)) {
                throw new \LogicException('Root classic binding has an incompatible projected scalar value.');
            }
            $values[$dependency->name()] = (string) $value;
        }
        return $values;
    }

    /** @param array<string,DependencyDescriptor> $dependencies @param list<int> $indexes @return array<string,array<string,bool>> */
    private function specialNamesForScope(
        DependencyScopeProjection $scope,
        array $dependencies,
        TemplateContract $contract,
        array $indexes
    ): array {
        $names = [];
        foreach ($contract->bindings() as $binding) {
            if (!$binding instanceof BindingDescriptor
                || $binding->kind() !== 'SPECIAL'
                || $binding->supportState() !== 'SUPPORTED'
                || $binding->dependencyId() === null
            ) {
                continue;
            }
            $dependency = $dependencies[$binding->dependencyId()] ?? null;
            if ($dependency instanceof DependencyDescriptor && $dependency->scope()->id() === $scope->scope()->id()) {
                $names[$binding->filterName() ?? ''][$dependency->name()] = true;
            }
        }
        $suffix = implode('', array_map(static fn (int $index): string => '_' . $index, $indexes));
        $result = [];
        foreach ($names as $filter => $variables) {
            foreach (array_keys($variables) as $variable) {
                $result[$filter][$variable . $suffix] = true;
            }
        }
        return $result;
    }

    /** @param list<BindingDescriptor> $bindings @param array<string,DependencyDescriptor> $dependencies @return array<string,array<string,bool>> */
    private function specialNamesForBindings(
        array $bindings,
        array $dependencies
    ): array {
        $names = [];
        foreach ($bindings as $binding) {
            if ($binding->kind() !== 'SPECIAL'
                || $binding->supportState() !== 'SUPPORTED'
                || $binding->dependencyId() === null
            ) {
                continue;
            }
            $dependency = $dependencies[$binding->dependencyId()] ?? null;
            if ($dependency instanceof DependencyDescriptor) {
                $names[$binding->filterName() ?? ''][$dependency->name()] = true;
            }
        }
        return $names;
    }

    /** @param array<string,string> $values @param array<string,array<string,bool>> $specials @param list<string> $controls */
    private function processOwnedTree(
        DOMNode $node,
        array $values,
        array $specials,
        array $controls,
        callable $applyFilter
    ): void {
        $this->processor->replaceScalarTextInSubtree(
            $node,
            $values,
            static function (string $filter, string $value, ?string $option) use ($applyFilter): string {
                if (in_array($filter, ['nl2br', 'ul', 'ol'], true)) {
                    return '{{' . $filter . ':' . $value . ($option === null ? '' : '|' . $option) . '}}';
                }
                return $applyFilter($filter, $value, $option);
            },
            true,
            true
        );
        if (isset($specials['nl2br'])) {
            $this->processor->replaceNl2brInNode($node, $values, true, $specials['nl2br']);
        }
        if (isset($specials['ul']) || isset($specials['ol'])) {
            $this->processor->replaceListsInNode(
                $node,
                $values,
                true,
                $specials['ul'] ?? [],
                $specials['ol'] ?? []
            );
        }

        foreach ($this->directSectionChildren($node) as $section) {
            if ($this->isControlName($section->getAttribute('text:name'), $controls)) {
                continue;
            }
            $this->processOwnedTree($section, $values, $specials, $controls, $applyFilter);
        }
    }

    /** @return list<DOMElement> */
    private function directSectionChildren(DOMNode $root): array
    {
        $sections = [];
        $walk = function (DOMNode $node) use (&$walk, &$sections): void {
            foreach ($node->childNodes as $child) {
                if ($child instanceof DOMElement && $child->nodeName === 'text:section') {
                    $sections[] = $child;
                    continue;
                }
                $walk($child);
            }
        };
        $walk($root);
        return $sections;
    }

    /** @param list<string> $controls */
    private function isControlName(string $name, array $controls): bool
    {
        foreach ($controls as $control) {
            if ($name === $control
                || preg_match('/^' . preg_quote($control, '/') . '(?:_\d+)+$/', $name) === 1
            ) {
                return true;
            }
        }
        return false;
    }

    private function regionKey(SourceProvenance $provenance): string
    {
        return implode("\0", [
            $provenance->sourcePart(),
            $provenance->regionKind(),
            $provenance->regionOwner() ?? '',
            $provenance->carrierKind(),
        ]);
    }

    private function regionKeyForNode(DOMNode $node, OdtDocumentContext $context): ?string
    {
        $document = $node instanceof DOMDocument ? $node : $node->ownerDocument;
        if ($document === $context->contentDom()) {
            return implode("\0", ['content.xml', 'BODY', '', 'office:text']);
        }
        for ($current = $node; $current !== null; $current = $current->parentNode) {
            if (!$current instanceof DOMElement
                || !in_array($current->nodeName, ['style:header', 'style:footer'], true)
            ) {
                continue;
            }
            $master = $current->parentNode;
            if (!$master instanceof DOMElement || $master->nodeName !== 'style:master-page') {
                continue;
            }
            return implode("\0", [
                'styles.xml',
                'MASTER_PAGE_CONTENT',
                $master->getAttribute('style:name'),
                $current->nodeName,
            ]);
        }
        return null;
    }

    private function workingRegion(OdtDocumentContext $context, SourceProvenance $provenance): DOMElement
    {
        if ($provenance->sourcePart() === 'content.xml'
            && $provenance->regionKind() === 'BODY'
            && $provenance->carrierKind() === 'office:text'
            && $provenance->regionOwner() === null
        ) {
            $xpath = new DOMXPath($context->contentDom());
            $xpath->registerNamespace('office', self::OFFICE_NAMESPACE);
            $root = $xpath->query('/office:document-content/office:body/office:text')->item(0);
            if ($root instanceof DOMElement) {
                return $root;
            }
        }
        if ($provenance->sourcePart() === 'styles.xml'
            && $provenance->regionKind() === 'MASTER_PAGE_CONTENT'
            && $provenance->regionOwner() !== null
            && in_array($provenance->carrierKind(), ['style:header', 'style:footer'], true)
        ) {
            foreach ($context->stylesDom()->getElementsByTagName('*') as $candidate) {
                if (!$candidate instanceof DOMElement || $candidate->nodeName !== $provenance->carrierKind()) {
                    continue;
                }
                for ($master = $candidate->parentNode; $master !== null; $master = $master->parentNode) {
                    if ($master instanceof DOMElement && $master->nodeName === 'style:master-page') {
                        if ($master->getAttribute('style:name') === $provenance->regionOwner()
                            && $candidate->namespaceURI === self::STYLE_NAMESPACE
                        ) {
                            return $candidate;
                        }
                        break;
                    }
                }
            }
        }
        throw new \LogicException('Template binding provenance cannot be localized to its current working region.');
    }
}
